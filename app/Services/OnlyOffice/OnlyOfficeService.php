<?php

namespace App\Services\OnlyOffice;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\EditSession;
use App\Models\User;
use App\Services\Documents\DocumentService;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Tích hợp ONLYOFFICE Docs: tạo cấu hình editor, quản lý phiên sửa
 * và xử lý callback lưu file.
 *
 * @see https://api.onlyoffice.com/docs/docs-api/usage-api/callback-handler/
 */
class OnlyOfficeService
{
    /** Trạng thái callback của Document Server. */
    public const STATUS_EDITING = 1;

    public const STATUS_MUST_SAVE = 2;

    public const STATUS_SAVE_ERROR = 3;

    public const STATUS_CLOSED_NO_CHANGES = 4;

    public const STATUS_FORCE_SAVE = 6;

    public const STATUS_FORCE_SAVE_ERROR = 7;

    public function __construct(private DocumentService $documents) {}

    /**
     * Dùng lại phiên sửa đang mở (để nhiều người cùng sửa chung một phiên),
     * hoặc tạo phiên mới nếu chưa có.
     */
    public function openSession(Document $document, User $user): EditSession
    {
        return DB::transaction(function () use ($document, $user) {
            Document::query()->whereKey($document->id)->lockForUpdate()->first();

            $session = $document->editSessions()
                ->where('status', EditSession::STATUS_OPEN)
                ->latest('id')
                ->first();

            if ($session) {
                return $session;
            }

            return $document->editSessions()->create([
                'doc_key' => $document->id.'-'.Str::random(32),
                'base_version_id' => $document->current_version_id,
                'status' => EditSession::STATUS_OPEN,
                'opened_by' => $user->id,
            ]);
        });
    }

    /**
     * Cấu hình truyền cho DocsAPI.DocEditor, đã ký JWT.
     *
     * @return array<string, mixed>
     */
    public function editorConfig(Document $document, User $user, bool $canEdit, bool $isMobile): array
    {
        $editable = $canEdit && ! $isMobile && $document->isEditable();
        $version = $document->currentVersion()->firstOrFail();

        if ($editable) {
            $key = $this->openSession($document, $user)->doc_key;
        } else {
            // Chế độ chỉ xem: key theo phiên bản, không cần phiên sửa.
            $key = $document->id.'-v'.$version->id.'-'.substr($version->sha256, 0, 16);
        }

        $config = [
            'documentType' => $document->document_type,
            'type' => $isMobile ? 'mobile' : 'desktop',
            'width' => '100%',
            'height' => '100%',
            'document' => [
                'fileType' => $document->extension,
                'key' => $key,
                'title' => $document->name,
                'url' => $this->downloadUrl($version),
                'permissions' => [
                    'edit' => $editable,
                    'download' => true,
                    'print' => true,
                    'comment' => $editable,
                    'review' => $editable,
                ],
            ],
            'editorConfig' => [
                'mode' => $editable ? 'edit' : 'view',
                'lang' => 'vi',
                'region' => 'vi-VN',
                'user' => [
                    'id' => (string) $user->id,
                    'name' => $user->name,
                ],
                'customization' => [
                    'autosave' => true,
                    'forcesave' => true,
                    'compactHeader' => true,
                    'uiTheme' => 'theme-light',
                    'hideRightMenu' => false,
                    // Không hiện popup giới thiệu tính năng mới (dễ gây rối cho người dùng).
                    'features' => ['featuresTips' => false],
                ],
            ],
        ];

        if ($editable) {
            $config['editorConfig']['callbackUrl'] = $this->callbackUrl($key);
        }

        $config['token'] = $this->encode($config);

        return $config;
    }

    /**
     * Link tải file (có chữ ký, hết hạn sau vài phút) cho Document Server.
     */
    public function downloadUrl(DocumentVersion $version): string
    {
        $relative = URL::temporarySignedRoute(
            'onlyoffice.download',
            now()->addMinutes(config('onlyoffice.download_ttl')),
            ['version' => $version->id],
            absolute: false,
        );

        return config('onlyoffice.app_internal_url').$relative;
    }

    public function callbackUrl(string $key): string
    {
        return config('onlyoffice.app_internal_url').route('onlyoffice.callback', ['key' => $key], absolute: false);
    }

    /**
     * Lấy nội dung callback đã xác minh JWT. Trả về null nếu token không hợp lệ.
     *
     * @return array<string, mixed>|null
     */
    public function verifiedPayload(Request $request): ?array
    {
        try {
            if ($request->filled('token')) {
                return $this->decode((string) $request->input('token'));
            }

            $header = $request->headers->get(config('onlyoffice.jwt_header'), '') ?? '';
            $token = Str::after($header, 'Bearer ');

            if ($token === '') {
                return null;
            }

            $decoded = $this->decode($token);

            return is_array($decoded['payload'] ?? null) ? $decoded['payload'] : $decoded;
        } catch (Throwable $e) {
            Log::warning('ONLYOFFICE callback: JWT không hợp lệ', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Xử lý callback. Trả về mã lỗi theo quy ước ONLYOFFICE (0 = đã nhận).
     *
     * @param  array<string, mixed>  $payload
     */
    public function handleCallback(EditSession $session, array $payload): int
    {
        $status = (int) ($payload['status'] ?? 0);
        $session->update(['last_status_code' => $status]);

        switch ($status) {
            case self::STATUS_MUST_SAVE:
            case self::STATUS_FORCE_SAVE:
                if ($session->status !== EditSession::STATUS_OPEN && $status === self::STATUS_MUST_SAVE) {
                    // Callback gửi lại cho phiên đã đóng: đã xử lý rồi.
                    return 0;
                }

                $this->saveFromCallback($session, $payload);

                if ($status === self::STATUS_MUST_SAVE) {
                    $session->update(['status' => EditSession::STATUS_CLOSED]);
                }

                return 0;

            case self::STATUS_CLOSED_NO_CHANGES:
                $session->update(['status' => EditSession::STATUS_CLOSED]);

                return 0;

            case self::STATUS_SAVE_ERROR:
            case self::STATUS_FORCE_SAVE_ERROR:
                Log::error('ONLYOFFICE báo lỗi lưu file', [
                    'document_id' => $session->document_id,
                    'doc_key' => $session->doc_key,
                    'status' => $status,
                ]);

                if ($status === self::STATUS_SAVE_ERROR) {
                    $session->update(['status' => EditSession::STATUS_ERROR]);
                }

                return 0;

            default:
                return 0;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function saveFromCallback(EditSession $session, array $payload): void
    {
        // Đóng editor sau khi đã force save mà không sửa thêm: không tạo bản trùng.
        if ((int) ($payload['status'] ?? 0) === self::STATUS_MUST_SAVE && ($payload['notmodified'] ?? false) === true) {
            return;
        }

        $url = (string) ($payload['url'] ?? '');

        if ($url === '') {
            throw new RuntimeException('Callback không có URL file đã sửa.');
        }

        $response = Http::timeout(60)->get($this->internalizeUrl($url));

        if (! $response->successful()) {
            throw new RuntimeException("Không tải được file đã sửa (HTTP {$response->status()}).");
        }

        $contents = $response->body();
        $document = $session->document;

        // Cùng nội dung với bản hiện tại thì không tạo bản mới (callback gửi lặp).
        if ($document->currentVersion?->sha256 === hash('sha256', $contents)) {
            return;
        }

        $userId = $payload['users'][0] ?? null;
        $user = $userId !== null ? User::find((int) $userId) : null;

        $this->documents->addVersion($document, $contents, DocumentVersion::SOURCE_EDITOR, $user);
    }

    /**
     * Đổi địa chỉ public của Document Server sang địa chỉ nội bộ (nếu có cấu hình).
     */
    private function internalizeUrl(string $url): string
    {
        $internal = config('onlyoffice.server_internal_url');

        if ($internal === '' || $internal === null) {
            return $url;
        }

        return Str::replaceStart(config('onlyoffice.public_url'), $internal, $url);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function encode(array $payload): string
    {
        return JWT::encode($payload, $this->secret(), 'HS256');
    }

    /**
     * @return array<string, mixed>
     */
    public function decode(string $token): array
    {
        $claims = JWT::decode($token, new Key($this->secret(), 'HS256'));

        return json_decode(json_encode($claims, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
    }

    private function secret(): string
    {
        $secret = (string) config('onlyoffice.jwt_secret');

        if ($secret === '') {
            throw new RuntimeException('Chưa cấu hình ONLYOFFICE_JWT_SECRET.');
        }

        return $secret;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\DocumentVersion;
use App\Models\EditSession;
use App\Services\Documents\DocumentService;
use App\Services\OnlyOffice\OnlyOfficeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Các endpoint chỉ Document Server gọi tới (không qua đăng nhập):
 * tải file bằng link có chữ ký, và callback lưu file có JWT.
 */
class OnlyOfficeController extends Controller
{
    public function __construct(
        private OnlyOfficeService $onlyOffice,
        private DocumentService $documents,
    ) {}

    public function download(DocumentVersion $version): StreamedResponse
    {
        return $this->documents->disk()->download($version->storage_key, basename($version->storage_key));
    }

    public function callback(Request $request, string $key): JsonResponse
    {
        $payload = $this->onlyOffice->verifiedPayload($request);

        if ($payload === null) {
            return response()->json(['error' => 1, 'message' => 'Token không hợp lệ.'], 403);
        }

        if (($payload['key'] ?? null) !== $key) {
            return response()->json(['error' => 1, 'message' => 'Sai key phiên sửa.'], 422);
        }

        $session = EditSession::query()->where('doc_key', $key)->first();

        if (! $session) {
            return response()->json(['error' => 1, 'message' => 'Không tìm thấy phiên sửa.'], 404);
        }

        try {
            return response()->json(['error' => $this->onlyOffice->handleCallback($session, $payload)]);
        } catch (Throwable $e) {
            Log::error('ONLYOFFICE callback: không lưu được file', [
                'doc_key' => $key,
                'error' => $e->getMessage(),
            ]);

            // Trả lỗi để Document Server gửi lại callback.
            return response()->json(['error' => 1, 'message' => 'Không lưu được file.'], 500);
        }
    }
}

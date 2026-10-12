<?php

namespace Tests\Feature\OnlyOffice;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\EditSession;
use App\Models\User;
use App\Services\Documents\DocumentService;
use App\Services\OnlyOffice\OnlyOfficeService;
use App\Services\Tree\FolderTreeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class OnlyOfficeCallbackTest extends TestCase
{
    use RefreshDatabase;

    private Document $document;

    private User $user;

    private OnlyOfficeService $onlyOffice;

    protected function setUp(): void
    {
        parent::setUp();

        config(['onlyoffice.jwt_secret' => str_repeat('k', 48)]);
        Storage::fake('local');

        $this->user = User::factory()->create();
        $this->onlyOffice = app(OnlyOfficeService::class);
        $folder = app(FolderTreeService::class)->truongVinh()->children()->first();
        $this->document = app(DocumentService::class)->createWithContents($folder, 'Báo giá.xlsx', 'ban goc', DocumentVersion::SOURCE_UPLOAD, $this->user);
    }

    public function test_editor_config_is_signed_and_reuses_the_open_session(): void
    {
        $config = $this->onlyOffice->editorConfig($this->document, $this->user, canEdit: true, isMobile: false);

        $this->assertSame('edit', $config['editorConfig']['mode']);
        $this->assertSame('cell', $config['documentType']);
        $this->assertArrayHasKey('callbackUrl', $config['editorConfig']);
        $this->assertSame($config['document']['key'], $this->onlyOffice->decode($config['token'])['document']['key']);

        $again = $this->onlyOffice->editorConfig($this->document, User::factory()->create(), canEdit: true, isMobile: false);
        $this->assertSame($config['document']['key'], $again['document']['key'], 'Người thứ hai phải vào cùng phiên để cùng sửa.');
    }

    public function test_mobile_gets_view_mode_without_a_session(): void
    {
        $config = $this->onlyOffice->editorConfig($this->document, $this->user, canEdit: true, isMobile: true);

        $this->assertSame('view', $config['editorConfig']['mode']);
        $this->assertSame('mobile', $config['type']);
        $this->assertArrayNotHasKey('callbackUrl', $config['editorConfig']);
        $this->assertSame(0, EditSession::query()->count());
    }

    public function test_signed_download_url_serves_the_file_only_with_a_valid_signature(): void
    {
        $url = $this->onlyOffice->downloadUrl($this->document->currentVersion);
        $path = parse_url($url, PHP_URL_PATH).'?'.parse_url($url, PHP_URL_QUERY);

        $this->get($path)->assertOk();
        $this->get(parse_url($url, PHP_URL_PATH))->assertForbidden();
    }

    public function test_callback_with_invalid_token_is_rejected(): void
    {
        $session = $this->onlyOffice->openSession($this->document, $this->user);

        $this->postJson(route('onlyoffice.callback', $session->doc_key), ['key' => $session->doc_key, 'status' => 2, 'token' => 'sai'])
            ->assertForbidden();
    }

    public function test_status_2_saves_a_new_version_and_closes_the_session(): void
    {
        Http::fake(['*' => Http::response('ban da sua')]);
        $session = $this->onlyOffice->openSession($this->document, $this->user);

        $this->sendCallback($session, ['status' => 2, 'url' => 'http://onlyoffice/cache/file.xlsx', 'users' => [(string) $this->user->id]])
            ->assertOk()
            ->assertJson(['error' => 0]);

        $this->document->refresh();
        $this->assertSame(2, $this->document->versions()->count());
        $this->assertSame(DocumentVersion::SOURCE_EDITOR, $this->document->currentVersion->source);
        $this->assertSame($this->user->id, $this->document->currentVersion->created_by);
        $this->assertSame(EditSession::STATUS_CLOSED, $session->fresh()->status);
    }

    public function test_force_save_keeps_the_session_open_and_close_without_changes_adds_nothing(): void
    {
        Http::fake(['*' => Http::response('ban force save')]);
        $session = $this->onlyOffice->openSession($this->document, $this->user);

        $this->sendCallback($session, ['status' => 6, 'url' => 'http://onlyoffice/a.xlsx', 'users' => [(string) $this->user->id]])->assertOk();
        $this->assertSame(EditSession::STATUS_OPEN, $session->fresh()->status);
        $this->assertSame(2, $this->document->versions()->count());

        $this->sendCallback($session, ['status' => 2, 'url' => 'http://onlyoffice/b.xlsx', 'notmodified' => true])->assertOk();
        $this->assertSame(2, $this->document->versions()->count(), 'Không tạo bản trùng sau force save.');
        $this->assertSame(EditSession::STATUS_CLOSED, $session->fresh()->status);
    }

    public function test_identical_content_does_not_create_a_duplicate_version(): void
    {
        Http::fake(['*' => Http::response('ban goc')]);
        $session = $this->onlyOffice->openSession($this->document, $this->user);

        $this->sendCallback($session, ['status' => 2, 'url' => 'http://onlyoffice/a.xlsx'])->assertOk();

        $this->assertSame(1, $this->document->versions()->count());
    }

    public function test_status_4_closes_the_session(): void
    {
        $session = $this->onlyOffice->openSession($this->document, $this->user);

        $this->sendCallback($session, ['status' => 4])->assertOk();

        $this->assertSame(EditSession::STATUS_CLOSED, $session->fresh()->status);
        $this->assertNotSame($session->doc_key, $this->onlyOffice->openSession($this->document, $this->user)->doc_key);
    }

    public function test_download_failure_returns_error_so_document_server_retries(): void
    {
        Http::fake(['*' => Http::response('', 500)]);
        $session = $this->onlyOffice->openSession($this->document, $this->user);

        $this->sendCallback($session, ['status' => 2, 'url' => 'http://onlyoffice/a.xlsx'])
            ->assertStatus(500)
            ->assertJson(['error' => 1]);

        $this->assertSame(EditSession::STATUS_OPEN, $session->fresh()->status);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function sendCallback(EditSession $session, array $payload): TestResponse
    {
        $payload = ['key' => $session->doc_key, ...$payload];

        return $this->postJson(route('onlyoffice.callback', $session->doc_key), [
            ...$payload,
            'token' => $this->onlyOffice->encode($payload),
        ]);
    }
}

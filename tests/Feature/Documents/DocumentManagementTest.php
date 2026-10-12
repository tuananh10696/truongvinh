<?php

namespace Tests\Feature\Documents;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Folder;
use App\Models\User;
use App\Services\Documents\DocumentService;
use App\Services\Tree\FolderTreeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentManagementTest extends TestCase
{
    use RefreshDatabase;

    private Folder $folder;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->folder = app(FolderTreeService::class)->truongVinh()->children()->where('name', 'Hợp đồng')->sole();
        $this->user = User::factory()->create();
    }

    public function test_uploading_files_creates_documents_with_a_first_version(): void
    {
        $this->actingAs($this->user)->post(route('documents.store', $this->folder), [
            'files' => [
                UploadedFile::fake()->createWithContent('Hợp đồng mẫu.docx', 'noi dung 1'),
                UploadedFile::fake()->createWithContent('Báo giá.xlsx', 'noi dung 2'),
            ],
        ])->assertRedirect();

        $document = Document::query()->where('name', 'Hợp đồng mẫu.docx')->sole();
        $this->assertSame('docx', $document->extension);
        $this->assertSame('hop dong mau.docx', $document->search_name);
        $this->assertSame(1, $document->versions()->count());
        Storage::disk('local')->assertExists($document->currentVersion->storage_key);
    }

    public function test_same_name_in_same_folder_gets_a_suffix(): void
    {
        $service = app(DocumentService::class);

        $service->createWithContents($this->folder, 'Báo giá.xlsx', 'a', DocumentVersion::SOURCE_UPLOAD, $this->user);
        $second = $service->createWithContents($this->folder, $service->uniqueName($this->folder, 'Báo giá.xlsx'), 'b', DocumentVersion::SOURCE_UPLOAD, $this->user);

        $this->assertSame('Báo giá (2).xlsx', $second->name);
    }

    public function test_cannot_upload_directly_into_company_root(): void
    {
        $this->actingAs($this->user)->post(route('documents.store', app(FolderTreeService::class)->companyRoot()), [
            'files' => [UploadedFile::fake()->create('a.pdf', 10)],
        ])->assertStatus(422);
    }

    public function test_rename_keeps_the_extension(): void
    {
        $document = app(DocumentService::class)->createWithContents($this->folder, 'Cũ.docx', 'x', DocumentVersion::SOURCE_UPLOAD, $this->user);

        $this->actingAs($this->user)->patch(route('documents.update', $document), ['name' => 'Mới'])->assertRedirect();

        $this->assertSame('Mới.docx', $document->fresh()->name);
    }

    public function test_delete_moves_to_trash_and_can_be_restored(): void
    {
        $document = app(DocumentService::class)->createWithContents($this->folder, 'Xoá thử.docx', 'x', DocumentVersion::SOURCE_UPLOAD, $this->user);

        $this->actingAs($this->user)->delete(route('documents.destroy', $document))->assertRedirect();
        $this->assertSoftDeleted($document);

        $this->actingAs($this->user)->post(route('trash.restore', $document->id))->assertRedirect();
        $this->assertNotSoftDeleted($document);
    }

    public function test_restoring_an_old_version_creates_a_new_version_and_keeps_history(): void
    {
        $service = app(DocumentService::class);
        $document = $service->createWithContents($this->folder, 'Báo giá.xlsx', 'ban 1', DocumentVersion::SOURCE_UPLOAD, $this->user);
        $first = $document->currentVersion;
        $service->addVersion($document, 'ban 2', DocumentVersion::SOURCE_EDITOR, $this->user);

        $this->actingAs($this->user)
            ->post(route('documents.versions.restore', [$document, $first]))
            ->assertRedirect();

        $document->refresh();
        $this->assertSame(3, $document->versions()->count());
        $this->assertSame(DocumentVersion::SOURCE_RESTORE, $document->currentVersion->source);
        $this->assertSame('ban 1', $service->contents($document->currentVersion));
    }

    public function test_version_list_and_download(): void
    {
        $service = app(DocumentService::class);
        $document = $service->createWithContents($this->folder, 'Báo giá.xlsx', 'ban 1', DocumentVersion::SOURCE_UPLOAD, $this->user);
        $service->addVersion($document, 'ban 2', DocumentVersion::SOURCE_EDITOR, $this->user);

        $this->actingAs($this->user)
            ->getJson(route('documents.versions.index', $document))
            ->assertOk()
            ->assertJsonCount(2, 'versions')
            ->assertJsonPath('versions.0.isCurrent', true);

        $first = $document->versions()->where('version_no', 1)->sole();
        $response = $this->actingAs($this->user)->get(route('documents.versions.download', [$document, $first]));
        $response->assertOk();
        $this->assertSame('ban 1', $response->streamedContent());
    }

    public function test_search_ignores_vietnamese_diacritics(): void
    {
        app(DocumentService::class)->createWithContents($this->folder, 'Hợp đồng thuê nhà xưởng.docx', 'x', DocumentVersion::SOURCE_UPLOAD, $this->user);

        $this->actingAs($this->user)
            ->get(route('search', ['q' => 'thue nha xuong']))
            ->assertInertia(fn ($page) => $page
                ->component('Search')
                ->has('documents', 1)
                ->where('documents.0.path', 'Trường Vinh › Hợp đồng'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Folder;
use App\Models\User;
use App\Services\Documents\DocumentService;
use App\Services\OnlyOffice\OnlyOfficeService;
use App\Services\Tree\FolderTreeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(
        private DocumentService $documents,
        private OnlyOfficeService $onlyOffice,
        private FolderTreeService $tree,
    ) {}

    /**
     * Mở file: Word/Excel mở bằng ONLYOFFICE (máy tính sửa được, điện thoại chỉ xem).
     * Loại file khác thì tải về.
     */
    public function show(Request $request, Document $document): Response|StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $document->isViewable()) {
            return $this->download($document);
        }

        DB::table('document_opens')->upsert(
            [['user_id' => $user->id, 'document_id' => $document->id, 'opened_at' => now()]],
            ['user_id', 'document_id'],
            ['opened_at'],
        );

        $isMobile = (bool) preg_match('/Mobi|Android|iPhone|iPad/i', (string) $request->userAgent());
        $document->load(['folder', 'updater:id,name']);

        return Inertia::render('Documents/Show', [
            'document' => [
                'id' => $document->id,
                'name' => $document->name,
                'extension' => $document->extension,
                'editable' => $document->isEditable() && ! $isMobile,
                'folderId' => $document->folder_id,
                'updatedAt' => $document->updated_at?->format('H:i d/m/Y'),
                'updatedBy' => $document->updater?->name,
            ],
            'breadcrumbs' => $this->tree->breadcrumbs($document->folder),
            'editor' => [
                'scriptUrl' => config('onlyoffice.public_url').'/web-apps/apps/api/documents/api.js',
                'config' => $this->onlyOffice->editorConfig($document, $user, canEdit: true, isMobile: $isMobile),
            ],
            'isMobile' => $isMobile,
        ]);
    }

    public function store(Request $request, Folder $folder): RedirectResponse
    {
        $this->ensureCanHoldDocuments($folder);

        $request->validate([
            'files' => ['required', 'array', 'max:20'],
            'files.*' => ['file', 'max:'.config('documents.max_upload_kb')],
        ], attributes: ['files' => 'file', 'files.*' => 'file']);

        $count = 0;

        foreach ($request->file('files') as $file) {
            $this->documents->storeUpload($folder, $file, $request->user());
            $count++;
        }

        $this->toast("Đã tải lên {$count} file.");

        return back();
    }

    public function storeBlank(Request $request, Folder $folder): RedirectResponse
    {
        $this->ensureCanHoldDocuments($folder);

        $data = $request->validate([
            'type' => ['required', Rule::in(DocumentService::BLANK_TYPES)],
            'name' => ['required', 'string', 'max:150'],
        ], attributes: ['type' => 'loại file', 'name' => 'tên file']);

        $document = $this->documents->createBlank($folder, $data['type'], $data['name'], $request->user());

        return to_route('documents.show', $document);
    }

    public function update(Request $request, Document $document): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
        ], attributes: ['name' => 'tên file']);

        $this->documents->rename($document, $data['name']);
        $this->toast('Đã đổi tên file.');

        return back();
    }

    public function destroy(Document $document): RedirectResponse
    {
        $document->update(['updated_by' => request()->user()->id]);
        $document->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Đã chuyển 1 file vào Thùng rác.',
            'undo' => route('trash.restore', $document->id, false),
        ]);

        return back();
    }

    public function download(Document $document): StreamedResponse
    {
        return $this->downloadVersion($document, $document->currentVersion()->firstOrFail());
    }

    public function downloadVersion(Document $document, DocumentVersion $version): StreamedResponse
    {
        abort_unless($version->document_id === $document->id, 404);

        $name = $version->id === $document->current_version_id
            ? $document->name
            : pathinfo($document->name, PATHINFO_FILENAME)." (bản {$version->version_no}).{$document->extension}";

        return $this->documents->disk()->download($version->storage_key, $name);
    }

    private function ensureCanHoldDocuments(Folder $folder): void
    {
        abort_if(
            in_array($folder->kind, [Folder::KIND_ROOT, Folder::KIND_TRUONG_VINH, Folder::KIND_COMPANY_ROOT, Folder::KIND_COMPANY, Folder::KIND_PROJECT], true),
            422,
            'Hãy chọn một thư mục cụ thể để lưu file.',
        );
    }
}

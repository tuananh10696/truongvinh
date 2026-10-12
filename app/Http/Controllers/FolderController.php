<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use App\Models\User;
use App\Services\Documents\DocumentService;
use App\Services\Tree\FolderTreeService;
use App\Support\DocumentPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FolderController extends Controller
{
    public function __construct(private FolderTreeService $tree) {}

    public function show(Request $request, Folder $folder): Response|RedirectResponse
    {
        if ($folder->kind === Folder::KIND_ROOT) {
            return to_route('dashboard');
        }

        /** @var User $user */
        $user = $request->user();

        // Thư mục con của dự án hiển thị dạng tab trên trang dự án.
        if ($folder->isProjectTab()) {
            return $this->projectView($folder->parent, $folder, $user);
        }

        if ($folder->isProject()) {
            return $this->projectView($folder, null, $user);
        }

        $documents = $folder->documents()->with('updater:id,name')->latest('updated_at')->get();

        return Inertia::render('Folders/Show', [
            'folder' => $this->folderData($folder, $user),
            'breadcrumbs' => $this->tree->breadcrumbs($folder),
            'children' => $folder->children()
                ->withCount(['children', 'documents'])
                ->get()
                ->map(fn (Folder $child) => [
                    'id' => $child->id,
                    'name' => $child->name,
                    'kind' => $child->kind,
                    'childrenCount' => $child->children_count,
                    'documentsCount' => $child->documents_count,
                ]),
            'documents' => DocumentPresenter::list($documents),
            'blankTypes' => DocumentService::BLANK_TYPES,
        ]);
    }

    public function store(Request $request, Folder $folder): RedirectResponse
    {
        abort_if(in_array($folder->kind, [Folder::KIND_ROOT, Folder::KIND_COMPANY_ROOT, Folder::KIND_COMPANY], true), 422, 'Dùng chức năng thêm công ty hoặc thêm dự án.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
        ], attributes: ['name' => 'tên thư mục']);

        $this->tree->createChild($folder, $data['name'], Folder::KIND_FOLDER, [], $request->user());

        $this->toast('Đã tạo thư mục.');

        return back();
    }

    public function update(Request $request, Folder $folder): RedirectResponse
    {
        abort_if($folder->is_system, 403, 'Không đổi tên được thư mục hệ thống.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
        ], attributes: ['name' => 'tên thư mục']);

        $folder->update(['name' => trim($data['name'])]);

        $this->toast('Đã đổi tên thư mục.');

        return back();
    }

    private function projectView(Folder $project, ?Folder $activeTab, User $user): Response
    {
        $tabs = $project->children()->withCount('documents')->get();
        $activeTab ??= $tabs->first();

        $documents = $activeTab
            ? $activeTab->documents()->with('updater:id,name')->latest('updated_at')->get()
            : collect();

        return Inertia::render('Folders/Project', [
            'project' => $this->folderData($project, $user),
            'breadcrumbs' => $this->tree->breadcrumbs($project),
            'tabs' => $tabs->map(fn (Folder $tab) => [
                'id' => $tab->id,
                'name' => $tab->name,
                'documentsCount' => $tab->documents_count,
            ]),
            'activeTab' => $activeTab ? ['id' => $activeTab->id, 'name' => $activeTab->name] : null,
            'totalDocuments' => $tabs->sum('documents_count'),
            'documents' => DocumentPresenter::list($documents),
            'blankTypes' => DocumentService::BLANK_TYPES,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function folderData(Folder $folder, User $user): array
    {
        return [
            'id' => $folder->id,
            'name' => $folder->name,
            'kind' => $folder->kind,
            'isSystem' => $folder->is_system,
            'companyId' => $folder->company_id,
            'isPinned' => $user->pinnedFolders()->whereKey($folder->id)->exists(),
            'parentName' => $folder->parent?->kind === Folder::KIND_COMPANY ? $folder->parent->name : null,
        ];
    }
}

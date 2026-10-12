<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Folder;
use App\Support\DocumentPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tìm theo tên file, tên thư mục, công ty, dự án. Gõ không dấu cũng được.
 */
class SearchController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $needle = Document::normalizeForSearch($q);
        $documents = [];
        $folders = [];

        if (mb_strlen($needle) >= 2) {
            $pattern = '%'.str_replace(['%', '_'], ['\%', '\_'], $needle).'%';

            $documents = DocumentPresenter::list(
                Document::query()
                    ->where('search_name', 'like', $pattern)
                    ->with(['folder', 'updater:id,name'])
                    ->latest('updated_at')
                    ->limit(50)
                    ->get(),
                withPath: true,
            );

            $folderModels = Folder::query()
                ->where('search_name', 'like', $pattern)
                ->whereNotIn('kind', [Folder::KIND_ROOT])
                ->orderBy('depth')
                ->limit(20)
                ->get();
            $paths = DocumentPresenter::folderPaths($folderModels->map(fn (Folder $folder) => $folder->parent ?? $folder));

            $folders = $folderModels->map(fn (Folder $folder) => [
                'id' => $folder->id,
                'name' => $folder->name,
                'kind' => $folder->kind,
                'path' => $folder->parent_id ? ($paths[$folder->parent_id] ?? null) : null,
            ])->all();
        }

        return Inertia::render('Search', [
            'q' => $q,
            'documents' => $documents,
            'folders' => $folders,
        ]);
    }
}

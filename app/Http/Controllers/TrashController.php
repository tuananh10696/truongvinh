<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Support\DocumentPresenter;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Thùng rác: file đã xoá vẫn còn, khôi phục được.
 */
class TrashController extends Controller
{
    public function index(): Response
    {
        $documents = Document::onlyTrashed()
            ->with(['folder', 'updater:id,name'])
            ->latest('deleted_at')
            ->limit(100)
            ->get();

        return Inertia::render('Trash', [
            'documents' => DocumentPresenter::list($documents, withPath: true),
        ]);
    }

    public function restore(int $document): RedirectResponse
    {
        $model = Document::onlyTrashed()->findOrFail($document);
        $model->restore();

        $this->toast("Đã khôi phục {$model->name}.");

        return back();
    }
}

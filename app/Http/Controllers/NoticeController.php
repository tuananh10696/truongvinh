<?php

namespace App\Http\Controllers;

use App\Models\Notice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Thông báo chung: nhân viên nào cũng đăng được, chỉ người đăng được xoá.
 */
class NoticeController extends Controller
{
    public function index(Request $request): Response
    {
        $notices = Notice::query()
            ->with('author:id,name,department')
            ->latest()
            ->paginate(20)
            ->through(fn (Notice $notice) => [
                'id' => $notice->id,
                'title' => $notice->title,
                'body' => $notice->body,
                'author' => $notice->author->department ?: $notice->author->name,
                'date' => $notice->created_at?->format('d/m/Y H:i'),
                'canDelete' => $notice->user_id === $request->user()->id,
            ]);

        return Inertia::render('Notices/Index', ['notices' => $notices]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'body' => ['nullable', 'string', 'max:2000'],
        ], attributes: ['title' => 'tiêu đề', 'body' => 'nội dung']);

        $request->user()->notices()->create($data);
        $this->toast('Đã đăng thông báo.');

        return back();
    }

    public function destroy(Request $request, Notice $notice): RedirectResponse
    {
        abort_unless($notice->user_id === $request->user()->id, 403);

        $notice->delete();
        $this->toast('Đã xoá thông báo.');

        return back();
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Todo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Việc cần làm cá nhân: mỗi người chỉ thấy và sửa việc của mình.
 */
class TodoController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'due_on' => ['nullable', 'date'],
        ], attributes: ['title' => 'việc cần làm', 'due_on' => 'hạn']);

        $request->user()->todos()->create($data);

        return back();
    }

    public function update(Request $request, Todo $todo): RedirectResponse
    {
        abort_unless($todo->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'done' => ['required', 'boolean'],
        ]);

        $todo->update(['done_at' => $data['done'] ? now() : null]);

        return back();
    }

    public function destroy(Request $request, Todo $todo): RedirectResponse
    {
        abort_unless($todo->user_id === $request->user()->id, 403);

        $todo->delete();

        return back();
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\RecurringTask;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Việc định kỳ cá nhân, ví dụ "Gửi phiếu thu, ngày 5 hằng tháng".
 */
class RecurringTaskController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'frequency' => ['required', Rule::in([RecurringTask::DAILY, RecurringTask::WEEKLY, RecurringTask::MONTHLY])],
            'weekday' => ['nullable', 'required_if:frequency,weekly', 'integer', 'between:1,7'],
            'day_of_month' => ['nullable', 'required_if:frequency,monthly', 'integer', 'between:1,31'],
            'time_of_day' => ['nullable', 'date_format:H:i'],
            'starts_on' => ['nullable', 'date'],
        ], attributes: [
            'title' => 'tên việc',
            'frequency' => 'kiểu lặp',
            'weekday' => 'thứ trong tuần',
            'day_of_month' => 'ngày trong tháng',
            'time_of_day' => 'giờ',
        ]);

        $request->user()->recurringTasks()->create([
            ...$data,
            'weekday' => $data['frequency'] === RecurringTask::WEEKLY ? $data['weekday'] : null,
            'day_of_month' => $data['frequency'] === RecurringTask::MONTHLY ? $data['day_of_month'] : null,
            'starts_on' => $data['starts_on'] ?? today(),
        ]);

        $this->toast('Đã tạo việc định kỳ.');

        return back();
    }

    public function destroy(Request $request, RecurringTask $recurringTask): RedirectResponse
    {
        abort_unless($recurringTask->user_id === $request->user()->id, 403);

        $recurringTask->delete();
        $this->toast('Đã xoá việc định kỳ.');

        return back();
    }

    /**
     * Đánh dấu đã làm xong lần đến hạn của một ngày.
     */
    public function complete(Request $request, RecurringTask $recurringTask): RedirectResponse
    {
        abort_unless($recurringTask->user_id === $request->user()->id, 403);

        $data = $request->validate(['due_on' => ['required', 'date']]);

        $recurringTask->completions()->firstOrCreate(
            ['due_on' => $data['due_on']],
            ['completed_at' => now()],
        );

        return back();
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\RecurringTask;
use App\Models\User;
use App\Services\Tasks\RecurrenceService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Lịch tháng: việc định kỳ và việc cần làm có hạn của người dùng.
 */
class CalendarController extends Controller
{
    public function __construct(private RecurrenceService $recurrence) {}

    public function __invoke(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $month = rescue(
            fn () => CarbonImmutable::createFromFormat('!Y-m', (string) $request->query('month')),
            fn () => CarbonImmutable::today()->startOfMonth(),
            report: false,
        )->startOfMonth();

        $gridStart = $month->startOfWeek(CarbonImmutable::MONDAY);
        $gridEnd = $month->endOfMonth()->endOfWeek(CarbonImmutable::SUNDAY);
        $events = [];

        $tasks = $user->recurringTasks()->with('completions')->where('active', true)->get();

        foreach ($tasks as $task) {
            // Việc hằng ngày hiện riêng ở cột bên, không lặp lại trong từng ô lịch.
            if ($task->frequency === RecurringTask::DAILY) {
                continue;
            }

            $done = $task->completions->map(fn ($c) => $c->due_on->toDateString())->all();

            foreach ($this->recurrence->occurrencesBetween($task, $gridStart, $gridEnd) as $date) {
                $events[$date->toDateString()][] = [
                    'type' => 'recurring',
                    'id' => $task->id,
                    'title' => $task->title,
                    'rule' => $this->recurrence->describe($task),
                    'done' => in_array($date->toDateString(), $done, true),
                ];
            }
        }

        $todos = $user->todos()->whereBetween('due_on', [$gridStart, $gridEnd])->get();

        foreach ($todos as $todo) {
            $events[$todo->due_on->toDateString()][] = [
                'type' => 'todo',
                'id' => $todo->id,
                'title' => $todo->title,
                'rule' => 'Việc cần làm',
                'done' => $todo->done_at !== null,
            ];
        }

        $days = [];

        for ($date = $gridStart; $date->lte($gridEnd); $date = $date->addDay()) {
            $days[] = [
                'date' => $date->toDateString(),
                'day' => $date->day,
                'inMonth' => $date->month === $month->month,
                'isToday' => $date->isToday(),
                'events' => $events[$date->toDateString()] ?? [],
            ];
        }

        return Inertia::render('Calendar', [
            'month' => $month->format('Y-m'),
            'monthLabel' => 'Tháng '.$month->month.', '.$month->year,
            'prevMonth' => $month->subMonth()->format('Y-m'),
            'nextMonth' => $month->addMonth()->format('Y-m'),
            'days' => $days,
            'tasks' => $tasks->map(fn ($task) => [
                'id' => $task->id,
                'title' => $task->title,
                'rule' => $this->recurrence->describe($task),
                'daily' => $task->frequency === RecurringTask::DAILY,
            ])->values(),
        ]);
    }
}

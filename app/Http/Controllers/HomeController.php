<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Folder;
use App\Models\Notice;
use App\Models\RecurringTask;
use App\Models\User;
use App\Services\Tasks\RecurrenceService;
use App\Services\Tree\FolderTreeService;
use App\Support\DocumentPresenter;
use App\Support\VietnameseDate;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(
        private FolderTreeService $tree,
        private RecurrenceService $recurrence,
    ) {}

    public function __invoke(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $today = CarbonImmutable::today();

        return Inertia::render('Home', [
            'today' => VietnameseDate::long($today),
            'notices' => $this->notices(),
            'truongVinh' => $this->truongVinh(),
            'companies' => $this->companies(),
            'recent' => $this->recentDocuments($user),
            'todos' => $this->todos($user, $today),
            'recurring' => $this->recurring($user, $today),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function notices(): array
    {
        return Notice::query()
            ->with('author:id,name,department')
            ->latest()
            ->limit(2)
            ->get()
            ->map(fn (Notice $notice) => [
                'id' => $notice->id,
                'title' => $notice->title,
                'body' => $notice->body,
                'author' => $notice->author->department ?: $notice->author->name,
                'date' => $notice->created_at?->format('d/m/Y'),
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function truongVinh(): array
    {
        $folder = $this->tree->truongVinh();

        return [
            'id' => $folder->id,
            'name' => $folder->name,
            'children' => $folder->children()->get()->map(fn (Folder $child) => [
                'id' => $child->id,
                'name' => $child->name,
                'count' => $this->countInSubtree($child),
            ])->all(),
        ];
    }

    /**
     * @return array{rootId: int, items: array<int, array<string, mixed>>, total: int}
     */
    private function companies(): array
    {
        $root = $this->tree->companyRoot();
        $query = $root->children()->withCount('children');

        return [
            'rootId' => $root->id,
            'total' => (clone $query)->count(),
            'items' => $query->limit(5)->get()->map(fn (Folder $company) => [
                'id' => $company->id,
                'name' => $company->name,
                'projects' => $company->children_count,
            ])->all(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentDocuments(User $user): array
    {
        $documents = Document::query()
            ->join('document_opens', 'document_opens.document_id', '=', 'documents.id')
            ->where('document_opens.user_id', $user->id)
            ->orderByDesc('document_opens.opened_at')
            ->select('documents.*')
            ->with(['folder', 'updater:id,name'])
            ->limit(5)
            ->get();

        return DocumentPresenter::list($documents, withPath: true);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function todos(User $user, CarbonImmutable $today): array
    {
        return $user->todos()
            ->where(fn ($query) => $query->whereNull('done_at')->orWhere('done_at', '>=', $today))
            ->orderByRaw('done_at is not null')
            ->orderByRaw('due_on is null')
            ->orderBy('due_on')
            ->orderBy('id')
            ->get()
            ->map(fn ($todo) => [
                'id' => $todo->id,
                'title' => $todo->title,
                'dueOn' => $todo->due_on?->toDateString(),
                'done' => $todo->done_at !== null,
            ])
            ->all();
    }

    /**
     * @return array{week: array<int, array<string, mixed>>, upcoming: array<int, array<string, mixed>>}
     */
    private function recurring(User $user, CarbonImmutable $today): array
    {
        $tasks = $user->recurringTasks()->where('active', true)->get();
        $completed = DB::table('recurring_task_completions')
            ->whereIn('recurring_task_id', $tasks->pluck('id'))
            ->get(['recurring_task_id', 'due_on'])
            ->groupBy('recurring_task_id')
            ->map(fn ($rows) => $rows->pluck('due_on')->map(fn ($date) => substr((string) $date, 0, 10))->all());

        $weekStart = $today->startOfWeek(CarbonImmutable::MONDAY);
        $weekEnd = $weekStart->addDays(6);
        $busyDays = [];

        foreach ($tasks as $task) {
            foreach ($this->recurrence->occurrencesBetween($task, $weekStart, $weekEnd) as $date) {
                if (! in_array($date->toDateString(), $completed[$task->id] ?? [], true)) {
                    $busyDays[$date->toDateString()] = true;
                }
            }
        }

        $week = collect(range(0, 6))->map(function (int $offset) use ($weekStart, $today, $busyDays) {
            $date = $weekStart->addDays($offset);

            return [
                'date' => $date->toDateString(),
                'day' => $date->day,
                'weekday' => VietnameseDate::weekdayShort($date),
                'isToday' => $date->equalTo($today),
                'busy' => isset($busyDays[$date->toDateString()]),
            ];
        })->all();

        $upcoming = $tasks
            ->map(function (RecurringTask $task) use ($today, $completed) {
                $next = $this->recurrence->nextDue($task, $today, $completed[$task->id] ?? []);

                return $next ? [
                    'id' => $task->id,
                    'title' => $task->title,
                    'rule' => $this->recurrence->describe($task),
                    'dueOn' => $next->toDateString(),
                    'day' => $next->format('d'),
                    'month' => 'Th'.$next->month,
                    'isToday' => $next->equalTo($today),
                ] : null;
            })
            ->filter()
            ->sortBy('dueOn')
            ->take(4)
            ->values()
            ->all();

        return ['week' => $week, 'upcoming' => $upcoming];
    }

    private function countInSubtree(Folder $folder): int
    {
        return Document::query()
            ->join('folders', 'folders.id', '=', 'documents.folder_id')
            ->where('folders.path', 'like', $folder->path.'%')
            ->whereNull('folders.deleted_at')
            ->count();
    }
}

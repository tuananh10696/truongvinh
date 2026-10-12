<?php

namespace Tests\Unit;

use App\Models\RecurringTask;
use App\Services\Tasks\RecurrenceService;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class RecurrenceServiceTest extends TestCase
{
    protected bool $seed = false;

    private function task(array $attributes): RecurringTask
    {
        return (new RecurringTask)->forceFill([
            'title' => 'Việc',
            'active' => true,
            'starts_on' => '2026-01-01',
            ...$attributes,
        ]);
    }

    private function dates(RecurringTask $task, string $from, string $to): array
    {
        return (new RecurrenceService)
            ->occurrencesBetween($task, CarbonImmutable::parse($from), CarbonImmutable::parse($to))
            ->map(fn ($date) => $date->toDateString())
            ->all();
    }

    public function test_monthly_on_day_5(): void
    {
        $task = $this->task(['frequency' => RecurringTask::MONTHLY, 'day_of_month' => 5]);

        $this->assertSame(['2026-10-05', '2026-11-05'], $this->dates($task, '2026-10-01', '2026-11-30'));
    }

    public function test_monthly_day_31_falls_back_to_month_end(): void
    {
        $task = $this->task(['frequency' => RecurringTask::MONTHLY, 'day_of_month' => 31]);

        $this->assertSame(['2026-02-28', '2026-03-31', '2026-04-30'], $this->dates($task, '2026-02-01', '2026-04-30'));
    }

    public function test_weekly_on_monday(): void
    {
        $task = $this->task(['frequency' => RecurringTask::WEEKLY, 'weekday' => 1]);

        $this->assertSame(['2026-10-12', '2026-10-19'], $this->dates($task, '2026-10-12', '2026-10-25'));
    }

    public function test_daily_respects_start_date(): void
    {
        $task = $this->task(['frequency' => RecurringTask::DAILY, 'starts_on' => '2026-10-14']);

        $this->assertSame(['2026-10-14', '2026-10-15'], $this->dates($task, '2026-10-12', '2026-10-15'));
    }

    public function test_next_due_skips_completed_dates(): void
    {
        $task = $this->task(['frequency' => RecurringTask::MONTHLY, 'day_of_month' => 12]);

        $next = (new RecurrenceService)->nextDue($task, CarbonImmutable::parse('2026-10-12'), ['2026-10-12']);

        $this->assertSame('2026-11-12', $next?->toDateString());
    }

    public function test_describe_in_vietnamese(): void
    {
        $service = new RecurrenceService;

        $this->assertSame('Hằng tháng, ngày 5', $service->describe($this->task(['frequency' => RecurringTask::MONTHLY, 'day_of_month' => 5])));
        $this->assertSame('Hằng ngày, 09:00', $service->describe($this->task(['frequency' => RecurringTask::DAILY, 'time_of_day' => '09:00:00'])));
    }
}

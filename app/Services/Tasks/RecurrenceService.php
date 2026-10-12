<?php

namespace App\Services\Tasks;

use App\Models\RecurringTask;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Tính các lần đến hạn của việc định kỳ từ quy tắc lặp.
 */
class RecurrenceService
{
    /**
     * Các ngày đến hạn trong khoảng [from, to].
     *
     * @return Collection<int, CarbonImmutable>
     */
    public function occurrencesBetween(RecurringTask $task, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $start = CarbonImmutable::parse($task->starts_on)->startOfDay();
        $cursor = $from->startOfDay()->max($start);
        $end = $to->startOfDay();
        $dates = collect();

        if (! $task->active || $cursor->gt($end)) {
            return $dates;
        }

        if ($task->frequency === RecurringTask::MONTHLY) {
            $month = $cursor->startOfMonth();

            while ($month->lte($end)) {
                $day = min((int) $task->day_of_month, $month->daysInMonth);
                $date = $month->setDay($day);

                if ($date->betweenIncluded($cursor, $end)) {
                    $dates->push($date);
                }

                $month = $month->addMonthNoOverflow();
            }

            return $dates;
        }

        while ($cursor->lte($end)) {
            if ($task->frequency === RecurringTask::DAILY
                || ($task->frequency === RecurringTask::WEEKLY && $cursor->dayOfWeekIso === (int) $task->weekday)) {
                $dates->push($cursor);
            }

            $cursor = $cursor->addDay();
        }

        return $dates;
    }

    /**
     * Lần đến hạn gần nhất từ ngày $from trở đi mà chưa làm xong.
     *
     * @param  array<int, string>  $completedDates  dạng Y-m-d
     */
    public function nextDue(RecurringTask $task, CarbonImmutable $from, array $completedDates = []): ?CarbonImmutable
    {
        return $this->occurrencesBetween($task, $from, $from->addDays(400))
            ->first(fn (CarbonImmutable $date) => ! in_array($date->toDateString(), $completedDates, true));
    }

    /**
     * Mô tả quy tắc lặp bằng tiếng Việt, ví dụ "Hằng tháng, ngày 5".
     */
    public function describe(RecurringTask $task): string
    {
        $weekdays = [1 => 'thứ Hai', 2 => 'thứ Ba', 3 => 'thứ Tư', 4 => 'thứ Năm', 5 => 'thứ Sáu', 6 => 'thứ Bảy', 7 => 'Chủ nhật'];

        $rule = match ($task->frequency) {
            RecurringTask::DAILY => 'Hằng ngày',
            RecurringTask::WEEKLY => 'Hằng tuần, '.($weekdays[(int) $task->weekday] ?? ''),
            RecurringTask::MONTHLY => 'Hằng tháng, ngày '.$task->day_of_month,
            default => '',
        };

        if ($task->time_of_day) {
            $rule .= ', '.substr($task->time_of_day, 0, 5);
        }

        return $rule;
    }
}

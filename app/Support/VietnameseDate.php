<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Định dạng ngày giờ kiểu thân thiện cho giao diện tiếng Việt.
 */
class VietnameseDate
{
    private const WEEKDAYS = [1 => 'Thứ Hai', 2 => 'Thứ Ba', 3 => 'Thứ Tư', 4 => 'Thứ Năm', 5 => 'Thứ Sáu', 6 => 'Thứ Bảy', 7 => 'Chủ nhật'];

    private const WEEKDAYS_SHORT = [1 => 'T2', 2 => 'T3', 3 => 'T4', 4 => 'T5', 5 => 'T6', 6 => 'T7', 7 => 'CN'];

    /**
     * "Hôm nay, 10:24", "Hôm qua, 16:05" hoặc "05/10/2026".
     */
    public static function friendly(?CarbonInterface $date): ?string
    {
        if (! $date) {
            return null;
        }

        $date = $date->copy()->timezone(config('app.timezone'));

        return match (true) {
            $date->isToday() => 'Hôm nay, '.$date->format('H:i'),
            $date->isYesterday() => 'Hôm qua, '.$date->format('H:i'),
            default => $date->format('d/m/Y'),
        };
    }

    /**
     * "Thứ Hai, 12/10/2026".
     */
    public static function long(CarbonInterface $date): string
    {
        return self::WEEKDAYS[$date->dayOfWeekIso].', '.$date->format('d/m/Y');
    }

    public static function weekdayShort(CarbonInterface $date): string
    {
        return self::WEEKDAYS_SHORT[$date->dayOfWeekIso];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Việc lặp lại theo lịch của từng người (hằng ngày, hằng tuần, hằng tháng).
 * Không lưu từng lần lặp, chỉ lưu quy tắc; lần đã làm xong lưu ở completions.
 *
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $frequency
 * @property int|null $weekday ISO: 1 = thứ Hai ... 7 = Chủ nhật
 * @property int|null $day_of_month
 * @property string|null $time_of_day
 * @property Carbon $starts_on
 * @property bool $active
 */
#[Fillable(['user_id', 'title', 'frequency', 'weekday', 'day_of_month', 'time_of_day', 'starts_on', 'active'])]
class RecurringTask extends Model
{
    public const DAILY = 'daily';

    public const WEEKLY = 'weekly';

    public const MONTHLY = 'monthly';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<RecurringTaskCompletion, $this>
     */
    public function completions(): HasMany
    {
        return $this->hasMany(RecurringTaskCompletion::class);
    }
}

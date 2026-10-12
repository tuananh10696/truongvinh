<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Việc cần làm cá nhân, chỉ người tạo thấy.
 *
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property Carbon|null $due_on
 * @property Carbon|null $done_at
 */
#[Fillable(['user_id', 'title', 'due_on', 'done_at'])]
class Todo extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_on' => 'date',
            'done_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

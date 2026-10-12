<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Bộ thư mục mặc định, ví dụ 7 thư mục của một dự án.
 * Admin sửa template trong DB, không cần release code.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 */
#[Fillable(['code', 'name'])]
class FolderTemplate extends Model
{
    public const PROJECT = 'project';

    public const TRUONG_VINH = 'truong_vinh';

    /**
     * @return HasMany<FolderTemplateItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(FolderTemplateItem::class)->orderBy('sort_order');
    }
}

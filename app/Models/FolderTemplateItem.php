<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $folder_template_id
 * @property string $name
 * @property int $sort_order
 */
#[Fillable(['folder_template_id', 'name', 'sort_order'])]
class FolderTemplateItem extends Model
{
    /**
     * @return BelongsTo<FolderTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(FolderTemplate::class, 'folder_template_id');
    }
}

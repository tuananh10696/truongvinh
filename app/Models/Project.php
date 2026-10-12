<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string|null $status
 * @property-read Company $company
 * @property-read Folder|null $folder
 */
#[Fillable(['company_id', 'name', 'status'])]
class Project extends Model
{
    use SoftDeletes;

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return HasOne<Folder, $this>
     */
    public function folder(): HasOne
    {
        return $this->hasOne(Folder::class)->where('kind', Folder::KIND_PROJECT);
    }
}

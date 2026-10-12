<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Công ty khách hàng (dữ liệu nghiệp vụ). Vị trí hiển thị nằm ở bảng folders.
 *
 * @property int $id
 * @property string $name
 * @property string|null $tax_code
 * @property string|null $country
 * @property-read Folder|null $folder
 */
#[Fillable(['name', 'tax_code', 'country'])]
class Company extends Model
{
    use SoftDeletes;

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * @return HasOne<Folder, $this>
     */
    public function folder(): HasOne
    {
        return $this->hasOne(Folder::class)->where('kind', Folder::KIND_COMPANY);
    }
}

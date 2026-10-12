<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Một node trong cây thư mục. Cấu trúc cây là dữ liệu, không code cứng,
 * để khách đổi cách sắp xếp mà không phải sửa code.
 *
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property string $search_name
 * @property string $kind
 * @property string $path materialized path dạng "/1/5/23/"
 * @property int $depth
 * @property int $sort_order
 * @property int|null $company_id
 * @property int|null $project_id
 * @property bool $is_system
 * @property bool $inherit_permissions
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Folder|null $parent
 * @property-read Collection<int, Folder> $children
 * @property-read Collection<int, Document> $documents
 */
#[Fillable(['parent_id', 'name', 'kind', 'path', 'depth', 'sort_order', 'company_id', 'project_id', 'is_system', 'inherit_permissions', 'created_by'])]
class Folder extends Model
{
    use SoftDeletes;

    public const KIND_ROOT = 'root';

    public const KIND_TRUONG_VINH = 'truong_vinh';

    public const KIND_COMPANY_ROOT = 'company_root';

    public const KIND_COMPANY = 'company';

    public const KIND_PROJECT = 'project';

    public const KIND_FOLDER = 'folder';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'inherit_permissions' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Folder $folder) {
            $folder->search_name = Document::normalizeForSearch($folder->name);
        });
    }

    /**
     * @return BelongsTo<Folder, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    /**
     * @return HasMany<Folder, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Folder::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    /**
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Các node cha, từ gốc xuống node cha trực tiếp.
     *
     * @return Collection<int, Folder>
     */
    public function ancestors(): Collection
    {
        $ids = array_values(array_filter(
            array_map('intval', explode('/', trim($this->path, '/'))),
            fn (int $id) => $id !== $this->id,
        ));

        if ($ids === []) {
            return new Collection;
        }

        return Folder::query()
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn (Folder $folder) => array_search($folder->id, $ids, true))
            ->values();
    }

    public function isProject(): bool
    {
        return $this->kind === self::KIND_PROJECT;
    }

    /**
     * Thư mục con trực tiếp của một dự án được hiển thị dạng tab.
     */
    public function isProjectTab(): bool
    {
        return $this->parent?->kind === self::KIND_PROJECT;
    }
}

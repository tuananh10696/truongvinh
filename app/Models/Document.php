<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $folder_id
 * @property string $name
 * @property string $extension
 * @property string $search_name
 * @property int|null $current_version_id
 * @property int $size
 * @property Carbon|null $expires_on
 * @property int|null $locked_by
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Folder $folder
 * @property-read DocumentVersion|null $currentVersion
 * @property-read User|null $updater
 */
#[Fillable(['folder_id', 'name', 'extension', 'search_name', 'current_version_id', 'size', 'expires_on', 'locked_by', 'created_by', 'updated_by'])]
class Document extends Model
{
    use SoftDeletes;

    /** Định dạng sửa được trực tiếp bằng ONLYOFFICE. */
    public const EDITABLE_EXTENSIONS = ['docx', 'xlsx', 'pptx', 'odt', 'ods', 'odp', 'txt', 'csv'];

    /** Định dạng chỉ xem trong ONLYOFFICE (file cũ được chuyển định dạng khi sửa). */
    public const VIEWABLE_EXTENSIONS = ['doc', 'xls', 'ppt', 'pdf', 'rtf'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_on' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Document $document) {
            $document->extension = Str::lower(pathinfo($document->name, PATHINFO_EXTENSION));
            $document->search_name = self::normalizeForSearch($document->name);
        });
    }

    /**
     * Bỏ dấu tiếng Việt và chuyển về chữ thường để tìm kiếm không dấu.
     */
    public static function normalizeForSearch(string $value): string
    {
        return Str::of($value)->ascii('vi')->lower()->squish()->toString();
    }

    /**
     * @return BelongsTo<Folder, $this>
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    /**
     * @return BelongsTo<DocumentVersion, $this>
     */
    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'current_version_id');
    }

    /**
     * @return HasMany<DocumentVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class)->orderByDesc('version_no');
    }

    /**
     * @return HasMany<EditSession, $this>
     */
    public function editSessions(): HasMany
    {
        return $this->hasMany(EditSession::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isEditable(): bool
    {
        return in_array($this->extension, self::EDITABLE_EXTENSIONS, true);
    }

    public function isViewable(): bool
    {
        return $this->isEditable() || in_array($this->extension, self::VIEWABLE_EXTENSIONS, true);
    }

    /**
     * Loại tài liệu theo cách ONLYOFFICE phân loại: word, cell, slide, pdf.
     *
     * @return Attribute<string, never>
     */
    protected function documentType(): Attribute
    {
        return Attribute::get(fn () => match ($this->extension) {
            'xlsx', 'xls', 'ods', 'csv' => 'cell',
            'pptx', 'ppt', 'odp' => 'slide',
            'pdf' => 'pdf',
            default => 'word',
        });
    }
}

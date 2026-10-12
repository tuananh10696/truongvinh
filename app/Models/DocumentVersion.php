<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Mỗi lần tải lên, sửa trên ONLYOFFICE hoặc khôi phục đều tạo một phiên bản mới.
 * File của phiên bản cũ không bao giờ bị ghi đè.
 *
 * @property int $id
 * @property int $document_id
 * @property int $version_no
 * @property string $storage_key
 * @property int $size
 * @property string $sha256
 * @property string $source
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property-read Document $document
 * @property-read User|null $creator
 */
#[Fillable(['document_id', 'version_no', 'storage_key', 'size', 'sha256', 'source', 'created_by'])]
class DocumentVersion extends Model
{
    public const SOURCE_UPLOAD = 'upload';

    public const SOURCE_EDITOR = 'editor';

    public const SOURCE_BLANK = 'blank';

    public const SOURCE_RESTORE = 'restore';

    public const SOURCE_IMPORT = 'import';

    /**
     * @return BelongsTo<Document, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

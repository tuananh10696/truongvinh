<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Một phiên sửa trên ONLYOFFICE. doc_key gắn với phiên (không đổi trong lúc
 * nhiều người cùng sửa) và chỉ đổi khi phiên đóng.
 *
 * @property int $id
 * @property string $doc_key
 * @property int $document_id
 * @property int $base_version_id
 * @property string $status
 * @property int|null $last_status_code
 * @property int|null $opened_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Document $document
 */
#[Fillable(['doc_key', 'document_id', 'base_version_id', 'status', 'last_status_code', 'opened_by'])]
class EditSession extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_ERROR = 'error';

    /**
     * @return BelongsTo<Document, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class)->withTrashed();
    }
}

<?php

namespace App\Support;

use App\Models\Document;
use App\Models\Folder;
use Illuminate\Support\Collection;

/**
 * Chuyển Document sang dữ liệu gửi cho giao diện. Đường dẫn thư mục
 * được tra một lần cho cả danh sách để tránh truy vấn lặp.
 */
class DocumentPresenter
{
    /**
     * @param  iterable<Document>  $documents
     * @return array<int, array<string, mixed>>
     */
    public static function list(iterable $documents, bool $withPath = false): array
    {
        $documents = collect($documents);
        $paths = $withPath ? self::folderPaths($documents->pluck('folder')->filter()) : collect();

        return $documents->map(fn (Document $document) => [
            'id' => $document->id,
            'name' => $document->name,
            'extension' => $document->extension,
            'size' => $document->size,
            'updatedAt' => VietnameseDate::friendly($document->updated_at),
            'updatedBy' => $document->updater?->name,
            'editable' => $document->isEditable(),
            'viewable' => $document->isViewable(),
            'folderId' => $document->folder_id,
            'path' => $withPath ? ($paths[$document->folder_id] ?? null) : null,
            'deletedAt' => VietnameseDate::friendly($document->deleted_at),
        ])->values()->all();
    }

    /**
     * Đường dẫn dạng "Công ty A › Dự án 1 › PCCC" (bỏ node gốc "Trang chủ").
     *
     * @param  Collection<int, Folder>  $folders
     * @return Collection<int, string>
     */
    public static function folderPaths(Collection $folders): Collection
    {
        $ids = $folders
            ->flatMap(fn (Folder $folder) => explode('/', trim($folder->path, '/')))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $nodes = Folder::query()
            ->whereIn('id', $ids)
            ->get(['id', 'name', 'kind'])
            ->keyBy('id');

        return $folders->mapWithKeys(function (Folder $folder) use ($nodes) {
            $names = collect(explode('/', trim($folder->path, '/')))
                ->map(fn ($id) => $nodes[(int) $id] ?? null)
                ->filter(fn (?Folder $node) => $node && ! in_array($node->kind, [Folder::KIND_ROOT, Folder::KIND_COMPANY_ROOT], true))
                ->pluck('name');

            return [$folder->id => $names->implode(' › ')];
        });
    }
}

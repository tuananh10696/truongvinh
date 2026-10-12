<?php

namespace App\Services\Documents;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\EditSession;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Quản lý file và phiên bản. File đã lưu không bao giờ bị ghi đè:
 * mỗi thay đổi là một phiên bản mới.
 */
class DocumentService
{
    /** Loại file trống có thể tạo mới ngay trên hệ thống. */
    public const BLANK_TYPES = ['docx', 'xlsx'];

    public function disk(): Filesystem
    {
        return Storage::disk(config('documents.disk'));
    }

    public function storeUpload(Folder $folder, UploadedFile $file, User $user): Document
    {
        $name = $this->uniqueName($folder, $this->cleanName($file->getClientOriginalName()));

        return $this->createWithContents($folder, $name, (string) $file->get(), DocumentVersion::SOURCE_UPLOAD, $user);
    }

    public function createBlank(Folder $folder, string $type, string $baseName, User $user): Document
    {
        if (! in_array($type, self::BLANK_TYPES, true)) {
            throw new InvalidArgumentException("Không hỗ trợ tạo file loại [{$type}].");
        }

        $contents = (string) file_get_contents(resource_path("templates/blank.{$type}"));
        $name = $this->uniqueName($folder, $this->cleanName($baseName).'.'.$type);

        return $this->createWithContents($folder, $name, $contents, DocumentVersion::SOURCE_BLANK, $user);
    }

    public function createWithContents(Folder $folder, string $name, string $contents, string $source, ?User $user): Document
    {
        return DB::transaction(function () use ($folder, $name, $contents, $source, $user) {
            $document = Document::create([
                'folder_id' => $folder->id,
                'name' => $name,
                'created_by' => $user?->id,
                'updated_by' => $user?->id,
            ]);

            $this->addVersion($document, $contents, $source, $user);

            return $document->refresh();
        });
    }

    public function addVersion(Document $document, string $contents, string $source, ?User $user): DocumentVersion
    {
        return DB::transaction(function () use ($document, $contents, $source, $user) {
            Document::query()->whereKey($document->id)->lockForUpdate()->first();

            $versionNo = (int) $document->versions()->max('version_no') + 1;
            $key = "documents/{$document->id}/v{$versionNo}.{$document->extension}";

            $this->disk()->put($key, $contents);

            $version = $document->versions()->create([
                'version_no' => $versionNo,
                'storage_key' => $key,
                'size' => strlen($contents),
                'sha256' => hash('sha256', $contents),
                'source' => $source,
                'created_by' => $user?->id,
            ]);

            $document->forceFill([
                'current_version_id' => $version->id,
                'size' => $version->size,
                'updated_by' => $user?->id,
            ])->save();

            return $version;
        });
    }

    /**
     * Khôi phục bản cũ bằng cách tạo bản mới có nội dung của bản cũ.
     * Bản hiện tại vẫn còn trong lịch sử.
     */
    public function restore(Document $document, DocumentVersion $version, User $user): DocumentVersion
    {
        if ($version->document_id !== $document->id) {
            throw new InvalidArgumentException('Phiên bản không thuộc file này.');
        }

        $restored = $this->addVersion($document, $this->contents($version), DocumentVersion::SOURCE_RESTORE, $user);

        // Phiên sửa đang mở dựa trên bản cũ: đóng lại để lần mở sau dùng bản vừa khôi phục.
        $document->editSessions()
            ->where('status', EditSession::STATUS_OPEN)
            ->update(['status' => EditSession::STATUS_CLOSED]);

        return $restored;
    }

    public function rename(Document $document, string $baseName): Document
    {
        $name = $this->cleanName($baseName);
        $extension = '.'.$document->extension;

        if (! Str::endsWith(Str::lower($name), $extension)) {
            $name .= $extension;
        }

        if ($name !== $document->name) {
            $document->update(['name' => $this->uniqueName($document->folder, $name, $document->id)]);
        }

        return $document;
    }

    public function contents(DocumentVersion $version): string
    {
        return (string) $this->disk()->get($version->storage_key);
    }

    /**
     * Thêm hậu tố " (2)", " (3)" nếu trong thư mục đã có file cùng tên.
     */
    public function uniqueName(Folder $folder, string $name, ?int $ignoreId = null): string
    {
        $base = pathinfo($name, PATHINFO_FILENAME);
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $candidate = $name;
        $counter = 2;

        while (Document::query()
            ->where('folder_id', $folder->id)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->whereRaw('lower(name) = ?', [Str::lower($candidate)])
            ->exists()) {
            $candidate = "{$base} ({$counter})".($extension !== '' ? ".{$extension}" : '');
            $counter++;
        }

        return $candidate;
    }

    private function cleanName(string $name): string
    {
        $name = trim(str_replace(['/', '\\', "\0"], '-', $name));

        return $name === '' ? 'Tài liệu' : Str::limit($name, 200, '');
    }
}

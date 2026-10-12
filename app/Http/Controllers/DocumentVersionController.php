<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Services\Documents\DocumentService;
use App\Support\VietnameseDate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DocumentVersionController extends Controller
{
    public function __construct(private DocumentService $documents) {}

    public function index(Document $document): JsonResponse
    {
        $sources = [
            DocumentVersion::SOURCE_UPLOAD => 'tải file lên',
            DocumentVersion::SOURCE_EDITOR => 'sửa trên hệ thống',
            DocumentVersion::SOURCE_BLANK => 'tạo file mới',
            DocumentVersion::SOURCE_RESTORE => 'khôi phục bản cũ',
            DocumentVersion::SOURCE_IMPORT => 'nhập dữ liệu',
        ];

        $versions = $document->versions()->with('creator:id,name')->get()->map(fn (DocumentVersion $version) => [
            'id' => $version->id,
            'versionNo' => $version->version_no,
            'when' => VietnameseDate::friendly($version->created_at),
            'who' => $version->creator->name ?? 'Hệ thống',
            'what' => $sources[$version->source] ?? $version->source,
            'size' => $version->size,
            'isCurrent' => $version->id === $document->current_version_id,
            'downloadUrl' => route('documents.versions.download', [$document, $version], false),
        ]);

        return response()->json(['versions' => $versions]);
    }

    public function restore(Request $request, Document $document, DocumentVersion $version): RedirectResponse
    {
        $this->documents->restore($document, $version, $request->user());
        $this->toast("Đã khôi phục bản {$version->version_no}.");

        return back();
    }
}

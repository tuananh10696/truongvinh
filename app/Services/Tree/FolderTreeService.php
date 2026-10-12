<?php

namespace App\Services\Tree;

use App\Models\Folder;
use App\Models\FolderTemplate;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FolderTreeService
{
    /**
     * Tạo thư mục con, tính sẵn path và thứ tự hiển thị.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createChild(Folder $parent, string $name, string $kind = Folder::KIND_FOLDER, array $attributes = [], ?User $user = null): Folder
    {
        return DB::transaction(function () use ($parent, $name, $kind, $attributes, $user) {
            $sortOrder = (int) Folder::query()->where('parent_id', $parent->id)->max('sort_order') + 1;

            $folder = Folder::create([
                'parent_id' => $parent->id,
                'name' => trim($name),
                'kind' => $kind,
                'path' => $parent->path,
                'depth' => $parent->depth + 1,
                'sort_order' => $sortOrder,
                'company_id' => $attributes['company_id'] ?? $parent->company_id,
                'project_id' => $attributes['project_id'] ?? $parent->project_id,
                'is_system' => $attributes['is_system'] ?? false,
                'created_by' => $user?->id,
            ]);

            $folder->update(['path' => $parent->path.$folder->id.'/']);

            return $folder;
        });
    }

    /**
     * Tạo các thư mục con theo template (ví dụ 7 thư mục của dự án).
     */
    public function applyTemplate(Folder $folder, string $templateCode, ?User $user = null): void
    {
        $template = FolderTemplate::query()->where('code', $templateCode)->with('items')->first();

        if (! $template) {
            throw new RuntimeException("Chưa có template thư mục [{$templateCode}].");
        }

        foreach ($template->items as $item) {
            $this->createChild($folder, $item->name, Folder::KIND_FOLDER, ['is_system' => true], $user);
        }
    }

    /**
     * Tạo cây gốc nếu chưa có: Trang chủ → Trường Vinh, Công ty.
     */
    public function ensureRoot(): Folder
    {
        $root = Folder::query()->where('kind', Folder::KIND_ROOT)->first();

        if ($root) {
            return $root;
        }

        return DB::transaction(function () {
            $root = Folder::create([
                'name' => 'Trang chủ',
                'kind' => Folder::KIND_ROOT,
                'path' => '/',
                'depth' => 0,
                'is_system' => true,
            ]);
            $root->update(['path' => '/'.$root->id.'/']);

            $truongVinh = $this->createChild($root, 'Trường Vinh', Folder::KIND_TRUONG_VINH, ['is_system' => true]);
            $this->applyTemplate($truongVinh, FolderTemplate::TRUONG_VINH);

            $this->createChild($root, 'Công ty', Folder::KIND_COMPANY_ROOT, ['is_system' => true]);

            return $root;
        });
    }

    public function truongVinh(): Folder
    {
        return Folder::query()->where('kind', Folder::KIND_TRUONG_VINH)->firstOrFail();
    }

    public function companyRoot(): Folder
    {
        return Folder::query()->where('kind', Folder::KIND_COMPANY_ROOT)->firstOrFail();
    }

    /**
     * Đường dẫn để hiển thị breadcrumb, gồm cả node hiện tại.
     *
     * @return Collection<int, array{id: int, name: string, kind: string}>
     */
    public function breadcrumbs(Folder $folder): Collection
    {
        return $folder->ancestors()
            ->push($folder)
            ->map(fn (Folder $node) => [
                'id' => $node->id,
                'name' => $node->name,
                'kind' => $node->kind,
            ])
            ->values()
            ->toBase();
    }
}

<?php

namespace Database\Seeders;

use App\Services\Tree\FolderTreeService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Dữ liệu bắt buộc (template + cây gốc). Dữ liệu demo chỉ chạy ở môi trường local.
     */
    public function run(FolderTreeService $tree): void
    {
        $this->call(FolderTemplateSeeder::class);
        $tree->ensureRoot();

        if (app()->environment('local')) {
            $this->call(DemoSeeder::class);
        }
    }
}

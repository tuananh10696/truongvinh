<?php

namespace Database\Seeders;

use App\Models\FolderTemplate;
use Illuminate\Database\Seeder;

/**
 * Bộ thư mục mặc định, lấy nguyên văn từ sơ đồ khách cung cấp.
 */
class FolderTemplateSeeder extends Seeder
{
    /** @var array<string, array{name: string, items: array<int, string>}> */
    public const TEMPLATES = [
        FolderTemplate::TRUONG_VINH => [
            'name' => 'Thư mục của Trường Vinh',
            'items' => ['Hợp đồng', 'Kế toán', 'Thương mại', 'Du học', 'Tổng hợp'],
        ],
        FolderTemplate::PROJECT => [
            'name' => 'Thư mục của một dự án',
            'items' => [
                'Đầu tư',
                'HĐDV (TV)',
                'Các HĐ tổng hợp của DA',
                'PCCC',
                'Môi trường',
                'Tài chính (Thuế, BH, Ngân hàng)',
                'Tổng hợp',
            ],
        ],
    ];

    public function run(): void
    {
        foreach (self::TEMPLATES as $code => $definition) {
            $template = FolderTemplate::updateOrCreate(['code' => $code], ['name' => $definition['name']]);

            if ($template->items()->exists()) {
                continue;
            }

            foreach ($definition['items'] as $index => $name) {
                $template->items()->create(['name' => $name, 'sort_order' => $index + 1]);
            }
        }
    }
}

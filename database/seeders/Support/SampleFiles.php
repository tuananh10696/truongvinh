<?php

namespace Database\Seeders\Support;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

/**
 * Tạo file Word/Excel mẫu tiếng Việt cho môi trường demo.
 * Chỉ dùng khi dev (phpoffice nằm trong require-dev).
 */
class SampleFiles
{
    /**
     * @param  array<int, string>  $paragraphs
     */
    public static function word(string $title, array $paragraphs = []): string
    {
        $word = new PhpWord;
        $word->setDefaultFontName('Times New Roman');
        $word->setDefaultFontSize(13);

        $section = $word->addSection();

        if ($title !== '') {
            $section->addText('CỘNG HOÀ XÃ HỘI CHỦ NGHĨA VIỆT NAM', ['bold' => true], ['alignment' => 'center']);
            $section->addText('Độc lập - Tự do - Hạnh phúc', ['bold' => true], ['alignment' => 'center']);
            $section->addTextBreak();
            $section->addText($title, ['bold' => true, 'size' => 15], ['alignment' => 'center']);
            $section->addTextBreak();
        }

        foreach ($paragraphs as $paragraph) {
            $section->addText($paragraph, [], ['spaceAfter' => 120]);
        }

        return self::capture(fn (string $path) => IOFactory::createWriter($word, 'Word2007')->save($path));
    }

    /**
     * @param  array<int, array<int, string|int|float|null>>  $rows
     */
    public static function excel(string $title, array $rows = []): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sheet1');

        if ($title !== '') {
            $sheet->setCellValue('A1', $title);
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        }

        $sheet->fromArray($rows, null, 'A3');

        if ($rows !== []) {
            $sheet->getStyle('A3:'.$sheet->getHighestColumn().'3')->getFont()->setBold(true);
        }

        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return self::capture(fn (string $path) => (new Xlsx($spreadsheet))->save($path));
    }

    private static function capture(callable $write): string
    {
        $path = tempnam(sys_get_temp_dir(), 'tv-sample-');
        $write($path);
        $contents = (string) file_get_contents($path);
        @unlink($path);

        return $contents;
    }
}

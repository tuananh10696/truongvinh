<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Folder;
use App\Models\RecurringTask;
use App\Models\User;
use App\Services\Documents\DocumentService;
use App\Services\Tree\DirectoryService;
use App\Services\Tree\FolderTreeService;
use Database\Seeders\Support\SampleFiles;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Dữ liệu demo để cho khách xem thử. Tên người, công ty, số liệu đều là mẫu.
 */
class DemoSeeder extends Seeder
{
    public function __construct(
        private FolderTreeService $tree,
        private DirectoryService $directory,
        private DocumentService $documents,
    ) {}

    public function run(): void
    {
        if (User::query()->where('email', 'lan@truongvinh.local')->exists()) {
            return;
        }

        $users = $this->users();
        $lan = $users['lan'];
        $minh = $users['minh'];

        $companies = [
            'Công ty A' => ['Dự án 1', 'Dự án 2', 'Dự án 3'],
            'Công ty B' => ['Dự án 1', 'Dự án 2'],
            'Công ty C' => ['Dự án 1', 'Dự án 2'],
        ];

        foreach ($companies as $companyName => $projects) {
            $company = $this->directory->createCompany(['name' => $companyName], $users['admin']);

            foreach ($projects as $projectName) {
                $this->directory->createProject($company, $projectName, $users['admin']);
            }
        }

        $this->documentsFor($users);
        $this->workspace($lan, $minh, $users['thu']);

        $lan->pinnedFolders()->attach([
            $this->folder('Công ty A', 'Dự án 1')->id,
            $this->tvFolder('Hợp đồng')->id,
        ]);
    }

    /**
     * @return array<string, User>
     */
    private function users(): array
    {
        $make = function (string $name, string $email, ?string $department): User {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'department' => $department,
                'password' => 'password',
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();

            return $user;
        };

        return [
            'admin' => $make('Quản trị hệ thống', 'admin@truongvinh.local', null),
            'lan' => $make('Nguyễn Lan', 'lan@truongvinh.local', 'Phòng hồ sơ'),
            'minh' => $make('Trần Minh', 'minh@truongvinh.local', 'Phòng dự án'),
            'ha' => $make('Lê Hà', 'ha@truongvinh.local', 'Phòng kế toán'),
            'thu' => $make('Phạm Thu', 'thu@truongvinh.local', 'Phòng hành chính'),
        ];
    }

    /**
     * @param  array<string, User>  $users
     */
    private function documentsFor(array $users): void
    {
        $pccc = $this->folder('Công ty A', 'Dự án 1', 'PCCC');

        $quote = $this->documents->createWithContents($pccc, 'Báo giá thiết bị PCCC.xlsx', SampleFiles::excel('BÁO GIÁ THIẾT BỊ PCCC', [
            ['STT', 'Hạng mục', 'ĐVT', 'Số lượng', 'Đơn giá (VNĐ)', 'Thành tiền (VNĐ)'],
            [1, 'Bình chữa cháy CO2 5kg', 'bình', 10, 650000, 6500000],
            [2, 'Đầu báo khói quang', 'cái', 24, 420000, 10080000],
            [3, 'Tủ trung tâm báo cháy 10 kênh', 'tủ', 1, 18500000, 18500000],
            [4, 'Đèn chỉ dẫn thoát hiểm', 'bộ', 12, 380000, 4560000],
            [null, 'Tổng cộng', null, null, null, 39640000],
        ]), DocumentVersion::SOURCE_UPLOAD, $users['minh']);
        $this->documents->addVersion($quote, SampleFiles::excel('BÁO GIÁ THIẾT BỊ PCCC (cập nhật)', [
            ['STT', 'Hạng mục', 'ĐVT', 'Số lượng', 'Đơn giá (VNĐ)', 'Thành tiền (VNĐ)'],
            [1, 'Bình chữa cháy CO2 5kg', 'bình', 12, 650000, 7800000],
            [2, 'Đầu báo khói quang', 'cái', 24, 420000, 10080000],
            [3, 'Tủ trung tâm báo cháy 10 kênh', 'tủ', 1, 18500000, 18500000],
            [4, 'Đèn chỉ dẫn thoát hiểm', 'bộ', 12, 380000, 4560000],
            [null, 'Tổng cộng', null, null, null, 40940000],
        ]), DocumentVersion::SOURCE_EDITOR, $users['lan']);

        $this->documents->createWithContents($pccc, 'Hợp đồng thi công hệ thống PCCC.docx', SampleFiles::word('HỢP ĐỒNG THI CÔNG HỆ THỐNG PCCC', [
            'Số: [số hợp đồng]',
            'Hôm nay, tại trụ sở Công ty A, chúng tôi gồm:',
            'Bên A (Chủ đầu tư): Công ty A',
            'Bên B (Nhà thầu): [tên nhà thầu]',
            'Điều 1. Nội dung công việc: thi công lắp đặt hệ thống phòng cháy chữa cháy cho Dự án 1.',
            'Điều 2. Giá trị hợp đồng: theo báo giá đính kèm.',
            'Điều 3. Thời gian thực hiện: [số ngày] ngày kể từ ngày ký.',
        ]), DocumentVersion::SOURCE_UPLOAD, $users['lan']);

        $this->documents->createWithContents($this->folder('Công ty A', 'Dự án 1', 'HĐDV (TV)'), 'Hợp đồng dịch vụ tư vấn đầu tư.docx', SampleFiles::word('HỢP ĐỒNG DỊCH VỤ TƯ VẤN ĐẦU TƯ', [
            'Bên A: Công ty A',
            'Bên B: Công ty Trường Vinh',
            'Điều 1. Bên B tư vấn thủ tục đầu tư, xin cấp phép và hỗ trợ triển khai Dự án 1 tại Việt Nam.',
            'Điều 2. Phí dịch vụ và phương thức thanh toán: [điền theo thoả thuận].',
        ]), DocumentVersion::SOURCE_UPLOAD, $users['lan']);

        $this->documents->createWithContents($this->folder('Công ty B', 'Dự án 1', 'Các HĐ tổng hợp của DA'), 'Hợp đồng thuê nhà xưởng.docx', SampleFiles::word('HỢP ĐỒNG THUÊ NHÀ XƯỞNG', [
            'Bên cho thuê: [tên bên cho thuê]',
            'Bên thuê: Công ty B',
            'Diện tích thuê: [diện tích] m2 tại [địa chỉ khu công nghiệp].',
        ]), DocumentVersion::SOURCE_UPLOAD, $users['ha']);

        $this->documents->createWithContents($this->tvFolder('Hợp đồng'), 'Mẫu hợp đồng dịch vụ.docx', SampleFiles::word('MẪU HỢP ĐỒNG DỊCH VỤ', [
            'Mẫu dùng cho khách hàng mới từ tháng 10/2026.',
        ]), DocumentVersion::SOURCE_UPLOAD, $users['thu']);

        $this->documents->createWithContents($this->tvFolder('Kế toán'), 'Theo dõi thanh toán quý 3.xlsx', SampleFiles::excel('THEO DÕI THANH TOÁN QUÝ 3', [
            ['Khách hàng', 'Dự án', 'Số phiếu thu', 'Số tiền (VNĐ)', 'Ngày thu', 'Ghi chú'],
            ['Công ty A', 'Dự án 1', 'PT-0701', 50000000, '05/07/2026', ''],
            ['Công ty B', 'Dự án 1', 'PT-0802', 35000000, '05/08/2026', ''],
            ['Công ty C', 'Dự án 2', 'PT-0903', 42000000, '05/09/2026', 'Chưa đối chiếu'],
        ]), DocumentVersion::SOURCE_UPLOAD, $users['ha']);

        // Lan đã mở gần đây vài file để trang chủ có dữ liệu.
        $opened = Document::query()->orderBy('id')->limit(5)->pluck('id');

        foreach ($opened as $index => $id) {
            DB::table('document_opens')->insert([
                'user_id' => $users['lan']->id,
                'document_id' => $id,
                'opened_at' => now()->subHours($index * 20),
            ]);
        }
    }

    private function workspace(User $lan, User $minh, User $thu): void
    {
        $thu->notices()->create([
            'title' => 'Đã cập nhật mẫu hợp đồng dịch vụ mới',
            'body' => 'Mẫu mới nằm trong Trường Vinh › Hợp đồng. Từ hôm nay dùng mẫu này cho khách mới.',
        ]);
        $minh->notices()->create([
            'title' => 'Họp giao ban sáng thứ Hai, 8:30',
            'body' => 'Phòng họp tầng 2. Mỗi bộ phận chuẩn bị tình hình hồ sơ trong tuần.',
        ]);

        $lan->todos()->createMany([
            ['title' => 'Gửi báo giá PCCC cho Công ty A', 'due_on' => today()],
            ['title' => 'Nhắc Công ty B ký phụ lục hợp đồng', 'due_on' => today()->addDays(3)],
            ['title' => 'Scan giấy chứng nhận đầu tư Công ty C', 'done_at' => now()],
        ]);

        $lan->recurringTasks()->createMany([
            ['title' => 'Kiểm tra hồ sơ mới nhận', 'frequency' => RecurringTask::DAILY, 'time_of_day' => '09:00', 'starts_on' => today()->subMonth()],
            ['title' => 'Đối chiếu công nợ khách hàng', 'frequency' => RecurringTask::MONTHLY, 'day_of_month' => 20, 'starts_on' => today()->subMonth()],
            ['title' => 'Gửi phiếu thu cho khách hàng', 'frequency' => RecurringTask::MONTHLY, 'day_of_month' => 5, 'starts_on' => today()->subMonth()],
        ]);
    }

    private function folder(string $company, string $project, ?string $tab = null): Folder
    {
        $companyFolder = $this->tree->companyRoot()->children()->where('name', $company)->firstOrFail();
        $projectFolder = $companyFolder->children()->where('name', $project)->firstOrFail();

        return $tab ? $projectFolder->children()->where('name', $tab)->firstOrFail() : $projectFolder;
    }

    private function tvFolder(string $name): Folder
    {
        return $this->tree->truongVinh()->children()->where('name', $name)->firstOrFail();
    }
}

# Kho giấy tờ Trường Vinh

Hệ thống lưu trữ tập trung giấy tờ, hợp đồng cho Trường Vinh. Xem và sửa file Word/Excel ngay trên trình duyệt (ONLYOFFICE Docs), mỗi lần lưu là một phiên bản mới, khôi phục được bản cũ.

Tài liệu phân tích và kế hoạch: `../quan-ly-giay-to-so-do.md`, `../ke-hoach-trien-khai.md`.

## Công nghệ

| Thành phần    | Phiên bản                                                |
| ------------- | -------------------------------------------------------- |
| PHP / Laravel | 8.3+ / 13 (Fortify, Wayfinder)                           |
| Frontend      | Vue 3 + Inertia 3 + TypeScript + Tailwind 4 + shadcn-vue |
| Database      | PostgreSQL 17 (Docker)                                   |
| Editor        | ONLYOFFICE Docs Community 9.4.0.1 (Docker)               |

## Chạy ở máy dev

```bash
cp .env.example .env            # lần đầu
php artisan key:generate        # lần đầu
# Đặt ONLYOFFICE_JWT_SECRET trong .env (ít nhất 32 ký tự): openssl rand -hex 24

docker compose up -d            # PostgreSQL (cổng 5434) + ONLYOFFICE (cổng 8080)
composer install && npm install
php artisan migrate --seed      # tạo bảng, template thư mục, cây gốc và dữ liệu demo (APP_ENV=local)

php artisan serve --port=8010   # app: http://localhost:8010
npm run dev                     # hoặc npm run build
```

Cổng 8010 và 5434 được chọn để không đụng các project khác trên máy. Nếu đổi cổng app, sửa cả `APP_URL` và `ONLYOFFICE_APP_INTERNAL_URL` trong `.env`.

Tài khoản demo (mật khẩu đều là `password`):

| Email                  | Vai trò mẫu                                                               |
| ---------------------- | ------------------------------------------------------------------------- |
| lan@truongvinh.local   | Nguyễn Lan, Phòng hồ sơ (có sẵn việc cần làm, việc định kỳ, thư mục ghim) |
| minh@truongvinh.local  | Trần Minh, Phòng dự án                                                    |
| ha@truongvinh.local    | Lê Hà, Phòng kế toán                                                      |
| thu@truongvinh.local   | Phạm Thu, Phòng hành chính                                                |
| admin@truongvinh.local | Quản trị hệ thống                                                         |

Không có trang tự đăng ký: tài khoản do quản trị tạo (hiện tạo bằng seeder hoặc `php artisan tinker`).

## Cấu trúc code chính

```
app/
├── Http/Controllers/        Home, Folder, Directory (công ty/dự án), Document, DocumentVersion,
│                            OnlyOffice (download + callback), Search, Trash, Notice, Todo,
│                            RecurringTask, Calendar, Pin
├── Models/                  Folder (cây thư mục), Document, DocumentVersion, EditSession,
│                            Company, Project, FolderTemplate(+Item), Notice, Todo,
│                            RecurringTask(+Completion)
├── Services/
│   ├── Tree/                FolderTreeService (cây, template), DirectoryService (công ty, dự án)
│   ├── Documents/           DocumentService (upload, phiên bản, khôi phục, đổi tên)
│   ├── OnlyOffice/          OnlyOfficeService (config editor + JWT, phiên sửa, callback)
│   └── Tasks/               RecurrenceService (tính lịch việc định kỳ)
└── Support/                 DocumentPresenter, VietnameseDate
config/documents.php         disk lưu file, dung lượng upload tối đa
config/onlyoffice.php        URL, JWT secret
database/seeders/            FolderTemplateSeeder (7 thư mục dự án, 5 thư mục Trường Vinh), DemoSeeder
resources/js/pages/          Home, Folders/Show, Folders/Project (tab bìa hồ sơ), Documents/Show (editor),
                             Search, Trash, Calendar, Notices/Index
resources/js/components/app/ DocumentList, UploadDropzone, VersionHistorySheet, TodoPanel, RecurringPanel...
resources/templates/         blank.docx, blank.xlsx cho chức năng "Tạo file mới"
```

Nguyên tắc:

- **Cây thư mục là dữ liệu**, không code cứng. Bộ thư mục mặc định nằm ở bảng `folder_templates`.
- **File không bao giờ bị ghi đè**: mỗi lần tải lên, sửa trên ONLYOFFICE hay khôi phục đều tạo `document_versions` mới.
- **Phiên sửa ONLYOFFICE**: `doc_key` gắn với phiên (nhiều người cùng sửa chung một phiên), chỉ đổi khi phiên đóng. Callback xác thực bằng JWT, bỏ qua bản trùng (`notmodified`, cùng sha256).
- Điện thoại luôn mở ở chế độ chỉ xem.

## Kiểm tra chất lượng

```bash
php artisan test                    # 70 test (PHPUnit, SQLite in-memory)
./vendor/bin/phpstan analyse        # Larastan
./vendor/bin/pint                   # format PHP
npx vp check --fix                  # lint + format frontend
npx vue-tsc --noEmit                # kiểm tra TypeScript
```

## Chưa làm trong đợt này

- Phân quyền chi tiết theo thư mục (hiện mọi tài khoản đăng nhập đều xem, sửa được tất cả).
- Audit log, import hàng loạt dữ liệu cũ, script backup/restore, Docker image cho production.
- Màn hình quản lý người dùng (hiện tạo tài khoản bằng seeder/tinker).
- Font chữ Hán cho ONLYOFFICE (cần build image riêng có font).

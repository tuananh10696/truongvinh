# CLAUDE.md – Kho giấy tờ Trường Vinh

Hệ thống web lưu trữ tập trung giấy tờ, hợp đồng cho Trường Vinh (công ty môi giới đầu tư cho doanh nghiệp Đài Loan, Trung Quốc vào Việt Nam; có thêm mảng du học, thương mại). Người dùng là khoảng 9 nhân viên văn phòng **không rành máy tính**: ưu tiên dễ dùng, chữ to, ít thao tác.

Tài liệu nghiệp vụ ở thư mục cha (không nằm trong repo): `../quan-ly-giay-to-so-do.md` (sơ đồ, câu hỏi mở), `../ke-hoach-trien-khai.md` (kế hoạch, stack, rủi ro).

## Chạy ở máy dev

```bash
docker compose up -d                  # PostgreSQL :5434, ONLYOFFICE Docs :8080
php artisan migrate:fresh --seed      # template + cây gốc + dữ liệu demo (APP_ENV=local)
php artisan serve --port=8010         # app: http://localhost:8010
npm run dev                           # hoặc npm run build
```

- **Cổng 8010 / 5434 là cố ý.** Cổng 8000 và 5433 trên máy này thuộc project khác (`tao_xanh`). Không dừng, không đụng tiến trình của project khác.
- Đổi cổng app thì sửa cả `APP_URL` và `ONLYOFFICE_APP_INTERNAL_URL` (Document Server gọi về Laravel qua `host.docker.internal`).
- Tài khoản demo: `lan@truongvinh.local` / `password` (xem README cho đủ danh sách).
- `ONLYOFFICE_JWT_SECRET` phải dài ít nhất 32 ký tự (firebase/php-jwt 7 từ chối key ngắn).

## Kiểm tra trước khi báo xong

```bash
php artisan test                      # PHPUnit, SQLite in-memory, đang có 70 test
./vendor/bin/phpstan analyse          # Larastan, phải 0 lỗi, không thêm @phpstan-ignore
./vendor/bin/pint                     # format PHP
npx vp check --fix                    # lint + format frontend (vite-plus)
npx vue-tsc --noEmit                  # TypeScript
npm run build
```

Sau khi thêm/sửa route: chạy `php artisan wayfinder:generate --with-form` (Vite plugin cũng tự chạy khi dev/build).

CI (`.github/workflows/tests.yml`) chạy PHP 8.4 + SQLite in-memory. `composer.lock` dùng Symfony 8 nên **cần PHP ≥ 8.4.1**.

## Stack

Laravel 13 (Fortify, Wayfinder) · Vue 3 + Inertia 3 + TypeScript · Tailwind 4 + shadcn-vue (component copy trong `resources/js/components/ui`) · icon `@lucide/vue` · font Lexend · PostgreSQL 17 · ONLYOFFICE Docs Community 9.4.0.1 (pin trong `compose.yaml`).

Không thêm thư viện mới nếu không thật cần. Thư viện phải miễn phí, license mở, ổn định (yêu cầu của khách).

## Kiến trúc

```
app/Models/          Folder (cây), Document, DocumentVersion, EditSession, Company, Project,
                     FolderTemplate(+Item), Notice, Todo, RecurringTask(+Completion)
app/Services/Tree/   FolderTreeService (tạo node, path, template), DirectoryService (công ty, dự án)
app/Services/Documents/DocumentService   upload, phiên bản, khôi phục, đổi tên, tên trùng
app/Services/OnlyOffice/OnlyOfficeService config editor + JWT, phiên sửa, callback
app/Services/Tasks/RecurrenceService     tính lịch việc định kỳ
app/Support/         DocumentPresenter (dữ liệu cho UI, đường dẫn thư mục), VietnameseDate
resources/js/pages/  Home, Folders/Show, Folders/Project (tab bìa hồ sơ), Documents/Show (editor),
                     Search, Trash, Calendar, Notices/Index, auth/*, settings/*
```

Controller mỏng, logic nằm ở Service. Thông báo toast dùng `$this->toast()` (Inertia flash).

## Quy tắc nghiệp vụ bắt buộc

1. **Cây thư mục là dữ liệu, không code cứng.** Cấu trúc: Trang chủ → Trường Vinh (5 thư mục) / Công ty → công ty → dự án → 7 thư mục. Bộ thư mục mặc định nằm ở bảng `folder_templates` (seed từ `FolderTemplateSeeder`, tên lấy **nguyên văn** từ sơ đồ khách). Khách đã đổi cấu trúc nhiều lần, đừng rải tên thư mục cụ thể vào code.
2. **File không bao giờ bị ghi đè.** Mọi thay đổi (tải lên, sửa trên ONLYOFFICE, khôi phục) đều tạo `document_versions` mới qua `DocumentService::addVersion`. Khôi phục = tạo bản mới có nội dung bản cũ.
3. Không cho lưu file ở cấp gốc `truong_vinh`, `company_root`, `company`, `project` (kiểm tra cả backend lẫn frontend).
4. **Điện thoại chỉ xem**, không sửa, không tải lên.
5. Xoá file = soft delete vào Thùng rác, có nút Hoàn tác.
6. Việc cần làm và việc định kỳ là **của riêng từng người** (kiểm tra `user_id` ở mọi thao tác). Thông báo chung: ai cũng đăng được, chỉ người đăng được xoá.
7. **Câu chữ hiển thị cho người dùng phải được khách duyệt.** Không tự ý đổi chữ đã có; chữ mới thì ghi rõ là bản nháp khi báo cáo.

## ONLYOFFICE: những điểm dễ sai

- `doc_key` gắn với **phiên sửa** (`edit_sessions`), không gắn với từng lần lưu. Nhiều người mở cùng file dùng chung một phiên; key chỉ đổi khi phiên đóng (callback status 2 hoặc 4).
- Callback xác thực JWT (`token` trong body hoặc header `Authorization: Bearer`), route nằm ngoài CSRF (`bootstrap/app.php`).
- Status 2/6 mới lưu. Bỏ qua bản trùng khi `notmodified=true` (đóng sau force save) hoặc cùng sha256. Tải file lỗi thì trả `error: 1` (HTTP 500) để Document Server gửi lại.
- Link tải file cho Document Server dùng signed route **tương đối** (`signed:relative`) cộng với `ONLYOFFICE_APP_INTERNAL_URL`.
- Container ONLYOFFICE cần `ALLOW_PRIVATE_IP_ADDRESS=true` để gọi về máy host.

## Lưu ý khi test giao diện

- Test editor ONLYOFFICE bằng trình duyệt headless phải dùng **Chrome arm64 gốc**: `arch -arm64 ".../Google Chrome" --headless=new --remote-debugging-port=...` rồi `puppeteer.connect`. Node trên máy là bản x86 nên Chrome do puppeteer tự mở chạy qua Rosetta, editor bị "Mất kết nối" (không phải lỗi app).
- Mở Dialog/Sheet từ `DropdownMenu`: dùng `:modal="false"` và mở sau khi menu đóng (`setTimeout`), nếu không dialog đóng ngay.
- Giao diện chỉ có chế độ sáng; mục "Giao diện" và passkey đã ẩn khỏi UI nhưng vẫn còn trong code của starter kit (gỡ hẳn sẽ làm hỏng component có sẵn).

## Quy ước làm việc

- Đây là project cá nhân của user (ngoài `/Users/lt00838/DATA/BAP/`): được sửa code trực tiếp. **Commit/push chỉ khi user yêu cầu.**
- Trả lời bằng tiếng Việt, giữ nguyên thuật ngữ kỹ thuật.
- Sau khi sửa UI: build lại và chụp màn hình kiểm tra cả desktop (1440px) lẫn điện thoại (390px).

## Chưa làm (việc tiếp theo)

- Phân quyền theo thư mục (hiện mọi tài khoản đăng nhập đều xem/sửa được tất cả). Phải có trước go-live.
- Audit log, import hàng loạt dữ liệu cũ, script backup/restore, Docker image cho production, màn hình quản lý người dùng, font chữ Hán cho ONLYOFFICE.
- Câu hỏi còn chờ khách: xem `../quan-ly-giay-to-so-do.md` mục 7 và `../ke-hoach-trien-khai.md` mục 11.

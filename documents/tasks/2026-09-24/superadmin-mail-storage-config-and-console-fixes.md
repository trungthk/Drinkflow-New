# Task: Cấu hình Email/Lưu trữ hệ thống, dọn file env & sửa lỗi trang Superadmin

**Ngày thực hiện:** 24/09/2026
**Trạng thái:** ✅ Hoàn thành (đã chạy `php artisan test` toàn bộ: 489 passed, 2 skipped; `npm run build` OK)

## Tổng quan

- Dọn các file `.env*`, gỡ `.env` và `realtime/.env` khỏi git.
- Superadmin › Cài đặt hệ thống: thêm cấu hình **Email** và **Lưu trữ**. Mỗi trường có giá trị trong cấu hình thì dùng giá trị đó, để trống thì dùng `.env`. Hai form đặt trong 2 tab.
- Nội dung các trang superadmin trải hết chiều ngang.
- Rà code toàn bộ trang superadmin theo vai trò tester, rồi sửa 5 lỗi nghiêm trọng theo quyết định của người dùng.

---

## 1. Dọn file env (commit `16eaabc`, `c1ee5a5`)

- **`src/.env.example`:**
  - Chuyển sang MySQL (`127.0.0.1:3306`, `root`).
  - Thêm `SUPERVISOR_*`, `RETENTION_*`, `APP_PREVIOUS_KEYS`.
  - Bỏ `VITE_APP_NAME` và `CORS_ORIGIN`, vì không dùng.
- **`realtime/.env.example`:** bỏ `APP_KEY`/`SOCKET_TOKEN_SECRET` bị lộ trong file mẫu. Gateway lấy secret dùng chung từ `src/.env`.
- **`.env.example`:** chỉ giữ biến `docker-compose.yml` thực sự dùng.
- **Gỡ khỏi git:** `.env` và `realtime/.env` (đã có trong `.gitignore`).
- ⚠️ **Việc còn lại:**
  - APP_KEY cũ từng nằm trong `realtime/.env.example` đã commit, nên cần đổi key: đưa key cũ vào `APP_PREVIOUS_KEYS`, rồi chạy `php artisan key:generate`.
  - Trên server, sao lưu `.env` và `realtime/.env` trước khi `git pull`, vì pull sẽ xóa hai file này.

## 2. Cấu hình Email & Lưu trữ hệ thống

- **`SystemConfigService` (mới, singleton):**
  - Ghi nhớ giá trị `.env` ban đầu, rồi áp các giá trị đã lưu trong `system_settings` lên `config()` mỗi lần app khởi động (gọi trong `AppServiceProvider::boot`).
  - Nếu chưa có bảng hoặc DB lỗi, app tự dùng `.env`.
  - Sau khi lưu, gọi `queue:restart` để worker nạp cấu hình mới.
- **Email** (`mail.*`): mailer (smtp/sendmail/log), host, port, scheme (smtp/smtps), username, password, from address, from name.
  - Mật khẩu được mã hóa và không bao giờ trả về trình duyệt.
  - Để trống ô mật khẩu thì giữ mật khẩu đã lưu. Chọn `clear_password` để quay về mật khẩu trong `.env`.
- **Lưu trữ** (`storage.*`):
  - Chọn ổ mặc định: chỉ các disk local (không có gói S3).
  - Hạn mức dung lượng MB. Biến `.env` mới: `STORAGE_QUOTA_MB`, đọc qua `config('filesystems.quota_mb')`.
- **`StorageHealthService`:**
  - Khi có hạn mức: tính dung lượng thực của thư mục (cache 5 phút) so với hạn mức, trả `limit = quota`.
  - Khi không có: so với dung lượng cả ổ đĩa (`limit = volume`).
  - Dashboard hiện "(hạn mức)" khi đang so với hạn mức.
- **API:** `PUT /superadmin/system/mail` và `PUT /superadmin/system/storage`.
  - Form Request mới: `UpdateMailSettingsRequest`, `UpdateStorageSettingsRequest`.
  - Audit chỉ ghi tên trường đã đổi, không ghi giá trị.
- **Endpoint cũ `PUT /system/settings`:** không còn ghi được key `mail.*`/`storage.*`, tránh lưu mật khẩu dạng văn bản thường.
- **`SystemSettingsService`:** thêm `many()` (đọc nhiều key trong 1 query) và `forget()`.
- **Giao diện:**
  - Khung "Cấu hình dịch vụ" có 2 tab Email / Lưu trữ. Tab được giữ trên địa chỉ trang (`#mail` / `#storage`) và chuyển được bằng phím mũi tên.
  - Dashboard: dòng Email mở `#mail`, dòng Lưu trữ file mở `#storage`.
  - Mỗi trường có nhãn nguồn (`.sa-config-source`): "Cấu hình hệ thống" hoặc "File .env", kèm giá trị `.env`.
- **Bố cục:** bỏ `max-width: 1500px` của `.superadmin-content`, nên nội dung trải hết chiều ngang ở mọi trang superadmin.

## 3. Rà soát trang Superadmin & các lỗi đã sửa

### #1 Xóa người dùng lỗi 500 → xóa mềm

- **Nguyên nhân:** `orders`/`debts` tham chiếu `room_users` với `restrictOnDelete`, nên xóa cứng người đã có đơn thì lỗi 500.
- **Enum:** `GlobalUserStatus` thêm case `Deleted = 'deleted'`.
- **`DeleteGlobalUserAction` (mới):**
  - Chặn xóa khi còn nợ hoặc khi tài khoản đã bị xóa.
  - Chuyển các membership sang `removed`, thu hồi thiết bị, đặt `status = deleted`.
  - Ghi audit một lần, phát `RoomMembershipUpdated` và `ForceReloadRequested`.
  - Giữ nguyên lịch sử đơn và công nợ.
- **Đăng nhập:** tài khoản không active đã bị chặn sẵn ở middleware và Google OAuth.
- **`SetGlobalUserStatusAction`:** không cho đặt trạng thái `deleted`, và không cho đổi trạng thái của tài khoản đã xóa.
- **`AdminAddRoomUserAction`:** admin phòng không thêm lại được tài khoản đã xóa qua email (`admin.user_account_deleted`).
- **Danh sách người dùng** (trang và API):
  - Mặc định ẩn tài khoản `deleted`, có bộ lọc trạng thái.
  - Trang chi tiết của tài khoản đã xóa ẩn các nút thao tác.
  - Dashboard không đếm tài khoản đã xóa.
- **Lưu ý:** email của tài khoản đã xóa vẫn giữ nguyên, nên không đăng ký lại được bằng email cũ.

### #2 Hủy cưỡng chế chiến dịch → chuyển sang `archived`

- **Lỗi cũ:** `forceCancel` đặt `cancelled` trực tiếp, không hủy đơn, và hủy được cả chiến dịch đã lưu trữ.
- **`ForceArchiveCampaignAction` (mới):**
  - Chỉ nhận chiến dịch draft/scheduled/active/closing (`ARCHIVABLE_STATUSES`).
  - Chuyển sang `archived`, không gửi thông báo, không đụng tới đơn hay công nợ.
- Nút trên giao diện dùng cùng hằng số `ARCHIVABLE_STATUSES`. Cập nhật nội dung xác nhận và thông báo (vi/en/ja).

### #3 Audit log ghi 2 lần

- Bỏ phần ghi audit trong controller, chỉ giữ ở Action:
  - `AdminController`: tạo, sửa, trạng thái, vai trò, phòng, mật khẩu, xóa.
  - `RoomController`: tạo, sửa, trạng thái.
  - `GlobalUserController`: trạng thái, gộp.
  - `SystemController`: reset.
- `RoomController::status` dùng `RoomStatus::tryFrom` thay cho mảng chuỗi viết cứng.

### #4 Text chưa dịch

- **`room-detail`:** nút Bật/Tắt phòng, thông báo cập nhật trạng thái và nhãn vai trò admin (`superadmin.admins.role_*`) đã dùng bản dịch.
- **Tiêu đề tab của 3 trang chi tiết:** trước dùng `@section('title')` nhưng layout đọc `$title`, nên luôn hiện "Superadmin". Nay truyền `title` qua `@extends`.

### #5 Dashboard

- Bỏ chữ "System" viết cứng trên nút Cài đặt hệ thống.
- Dòng phụ KPI "Quản trị viên" → "Quản trị viên phòng" (`superadmin.dashboard.room_admins`), đếm bằng `AdminRole::Admin`.

## 4. Bản dịch (vi/en/ja)

- **Thêm:**
  - `superadmin.system.*`: các key cấu hình email, lưu trữ, dịch vụ và nguồn cấu hình.
  - `superadmin.dashboard.storage_usage_quota`, `superadmin.dashboard.room_admins` (thay `system_admins`).
  - `superadmin.common.deleted`, `superadmin.common.removed`.
  - `superadmin.users.already_deleted`, `superadmin.users.deleted_status_locked`.
  - `superadmin.actions.campaign_cannot_force_cancel` (thay `campaign_already_closed`).
  - `admin.user_account_deleted`.
  - `admin.audit_event_system_mail_config_updated`, `admin.audit_event_system_storage_config_updated`.
- **Sửa nội dung:** `superadmin.users.confirm_delete`, `superadmin.campaigns.force_cancel_description`, `superadmin.campaigns.force_cancelled`.

## 5. Test

- **`SystemConfigSettingsTest` (7 test):** ghi đè và fallback `.env`, mã hóa và giữ/xóa mật khẩu, validate, áp cấu hình khi khởi động, hạn mức lưu trữ, chặn endpoint cũ, phân quyền, hiển thị form.
- **`SuperadminConsoleFixesTest` (8 test):** xóa mềm, chặn đổi trạng thái sang/từ `deleted`, ẩn khỏi danh sách, chặn đăng nhập, chặn admin phòng thêm lại, lưu trữ cưỡng chế, audit chỉ ghi một lần, text/tiêu đề không còn viết cứng.
- **`SystemHealthTest`:** thêm test dung lượng; cập nhật test liên kết dashboard (`#mail`, `#storage`).
- **`RealtimeForceReloadTest`:** nghiệp vụ đổi sang xóa mềm, nên assertion đổi từ "dòng bị xóa" sang `status = deleted`.

## Việc còn lại (đề xuất từ lần rà soát, chưa làm)

- Tìm kiếm người dùng không bỏ dấu đúng: `strtoupper` thay vì `HasNormalizedName::normalizeString`.
- `RoomController::show` nạp toàn bộ thành viên và chiến dịch chỉ để đếm.
- Dashboard chờ kiểm tra socket (timeout 3s) ngay trong request, nên tách ra hoặc cache.
- Bộ lọc `status`/`role`/`severity`/`actor_type` chưa validate theo enum. API audit/security trả 500 với tham số dạng mảng hoặc ngày sai định dạng.
- Thêm `throttle` cho reset hệ thống và đặt lại mật khẩu admin.
- Chức năng có API nhưng chưa có giao diện: sửa và gửi thử kênh thông báo, gộp tài khoản, thu hồi từng thiết bị.
- Cờ "Buộc làm mới" của phiên bản chưa có client nào đọc.
- Trang hàng đợi: chưa có số job đang chờ và chưa xem được stack trace đầy đủ.
- `src/.env.example`: người dùng đã thêm `STORAGE_QUOTA_MB` nhưng chưa commit, cần tự kiểm tra rồi commit.
- Môi trường local: lỗi 500 `Access denied for user 'root'` là do thông tin DB trong `src/.env`, không phải lỗi code.

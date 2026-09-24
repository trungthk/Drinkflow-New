# Task: Cập nhật layout Super Admin, chế độ bảo trì & kênh thông báo hệ thống

**Ngày thực hiện:** 23/09/2026
**Commit:** `b58e513` – Update super admin layout
**Trạng thái:** ✅ Đã commit (commit không ghi lại kết quả `php artisan test`; cần chạy lại để xác nhận)

## Tổng quan

Làm lại giao diện và luồng thao tác của khu vực Super Admin (`/superadmin/*`): chuyển các thao tác sang modal xác nhận thống nhất, thêm empty state cho mọi danh sách, dịch toàn bộ chuỗi còn hardcode sang vi/en/ja. Đồng thời gỡ bỏ trang "Công nợ toàn hệ thống", viết lại middleware chế độ bảo trì (có trang bảo trì riêng + banner cho superadmin), bổ sung gán admin cho phòng, chặn/mở khoá tài khoản admin, lọc nhật ký audit chỉ cho admin, và tách phần gửi kênh thông báo dùng chung cho kênh cấp phòng và cấp hệ thống.

---

## 1. Gỡ bỏ trang "Công nợ toàn hệ thống" (Global Debt Overview)

- Xoá `Superadmin\DebtController`, `App\Exports\DebtOverviewExport`, view `superadmin/debts.blade.php` và 3 route `superadmin.debts.page`, `superadmin.debts.index`, `superadmin.debts.export`.
- Xoá mục menu "Công nợ" trong `superadmin/layout.blade.php`.
- Dashboard Super Admin bỏ KPI `outstanding_debt` (API `DashboardController::index()` và thẻ KPI trên view), lưới KPI chuyển sang 4 cột (`kpis-4`).
- Danh sách chiến dịch (`CampaignController::index()`, `PageController::campaigns()`) bỏ `debts_count`, chỉ còn `orders_count`.

## 2. Chế độ bảo trì (Maintenance mode)

- `SystemSettingsService::maintenanceState()` mới: gom logic tính trạng thái bảo trì, trả về `enabled`, `active`, `scheduled`, `starts_at`, `ends_at` và `starts`/`ends` (Carbon, parse an toàn, lỗi định dạng → `null`). `SystemController::maintenanceState()` dùng lại service này thay vì tự tính.
- Viết lại `CheckMaintenanceMode` (thêm `strict_types`, inject service qua constructor):
  - Chuyển từ global middleware (`append`) sang nhóm `web` trong `bootstrap/app.php` để có session, guard `admin` và route đã match.
  - Khi đang bảo trì: request JSON nhận 503 với `errors.maintenance.json_message`; request HTML nhận view `errors/maintenance.blade.php`. Có header `Retry-After` nếu biết thời điểm kết thúc.
  - Superadmin chỉ được bỏ qua bảo trì trong khu vực `admin/*` và `superadmin/*` (trang public/user vẫn hiện trang bảo trì vì cookie admin dùng chung).
  - Luôn cho phép các route đăng nhập admin (login, 2FA, quên mật khẩu, OTP, đặt lại mật khẩu, logout) và `locale.switch`; route Google OAuth chỉ được phép khi đang ở bước Google 2FA của admin (`admin_google_2fa_admin_id` trong session).
- Component mới `x-superadmin.maintenance-banner`, hiển thị trong layout Super Admin và `x-admin.layout` để nhắc superadmin khi bảo trì đang bật/đã lên lịch (không render gì với admin phòng).
- Trang System: bật/tắt bảo trì qua modal xác nhận, form thời gian bắt đầu/kết thúc; reset hệ thống qua modal yêu cầu nhập cụm xác nhận (`ResetSystemAction::CONFIRMATION_PHRASE`) và mật khẩu.

## 3. Quản lý phòng: gán admin & chuẩn hoá trạng thái

- `StoreRoomRequest` / `UpdateRoomRequest` nhận thêm `admin_ids` (`array`, mỗi phần tử `integer` + `exists:admin_accounts,id`).
- `ManageRoomAction::syncAdmins()` mới: đồng bộ danh sách admin của phòng trong transaction, ghi audit `room.admins_updated` (before/after).
- `RoomController::store()`/`update()` tách `admin_ids` khỏi dữ liệu phòng, gọi `syncAdmins()` khi có, trả về phòng kèm `admins`.
- Trạng thái phòng đổi `disabled` → `inactive` (validation request và `RoomController::status()`).
- UI: trang danh sách phòng có chọn admin khi tạo phòng; trang chi tiết phòng có modal "Sửa phòng" (tên, slug, trạng thái, admin phụ trách) và xác nhận xoá bằng modal.

## 4. Quản lý tài khoản admin

- `AdminStatus` thêm case `Blocked`, `Disabled`. `ManageAdminAction::setStatus()` validate theo `AdminStatus` (trước đây dùng nhầm `GlobalUserStatus`).
- `EnsureActiveAdmin`: khi admin không còn active thì logout, đồng thời `invalidate()` session và `regenerateToken()`; áp dụng cho cả route admin và superadmin.
- UI trang `admins`: modal tạo admin (họ tên, email, mật khẩu + nhập lại có nút hiện/ẩn, chọn phòng), lọc theo vai trò/trạng thái, hành động chỉ còn "Chặn/Mở khoá" (qua modal xác nhận).
- UI trang `admin-detail`: thông tin tài khoản, lần đăng nhập cuối, gán quyền truy cập phòng, modal đặt lại mật khẩu, chặn/mở khoá tài khoản.
- Component mới `x-superadmin.password-input` (input mật khẩu có nút toggle, tự ẩn lại khi đóng modal – xử lý trong `superadmin/modal.js`).

## 5. Người dùng toàn hệ thống (Global users)

- `GlobalUser::orders()` (HasManyThrough qua `RoomUser`) mới; `GlobalUserController::show()` trả thêm `room_users_count`, `orders_count`.
- UI `users` / `user-detail`: nút chặn/mở khoá tách biệt, xoá user và gỡ thành viên khỏi phòng qua modal xác nhận, hiển thị số phòng đã tham gia, tổng đơn, danh tính OAuth, thiết bị, empty state.

## 6. Chiến dịch, Feedback, Queue, Socket, Security

- Campaigns: thêm bộ lọc theo phòng (`room_id` chỉ nhận số nguyên dương), trạng thái lọc qua `CampaignStatus::tryFrom()` (giá trị lạ bị bỏ qua); nút "Buộc đóng" / "Buộc huỷ" hiển thị theo trạng thái chiến dịch, xác nhận qua modal.
- Feedbacks: hàng chờ duyệt, lọc theo trạng thái/số sao, duyệt/ẩn feedback, empty state.
- Queue: thử lại/xoá failed job qua modal xác nhận, tìm kiếm, empty state.
- Socket, Security: dịch lại toàn bộ nhãn, thêm empty state.

## 7. Nhật ký audit

- `AuditLog` thêm hằng `ACTOR_SUPERADMIN|ADMIN|USER|SYSTEM`, `ADMIN_ACTOR_TYPES`, quan hệ `actorAdmin()`, các hàm `labelForEvent()`, `eventLabel()`, `targetLabel()` (dịch qua `admin.audit_event_*` / `admin.audit_target_*`, fallback về key gốc).
- `PageController::audit()` chỉ liệt kê log của admin/superadmin; lọc event theo giá trị chính xác từ danh sách event thực có (dropdown đã dịch), `actor_type` chỉ nhận admin/superadmin; sắp xếp thêm theo `id`.

## 8. Kênh thông báo hệ thống (System notification channels)

- Tách logic gửi/định dạng tin (Slack, Telegram, Chatwork, Webhook – kèm SSRF guard, kiểm tra path segment) từ `RoomNotificationChannelDispatcher` ra trait dùng chung `Services/Notification/Concerns/SendsNotificationChannelPayloads`.
- `SystemNotificationChannelService` mới: `save()` (mã hoá config; khi sửa, credential để trống = giữ giá trị cũ), `mask()` (không trả secret), `editable()` (trả config nhưng xoá trắng các secret, kèm `secrets_configured`), `configured()`.
- `SystemNotificationChannelDispatcher` mới: gửi tin test qua kênh hệ thống.
- `NotificationController`: dùng service thay cho xử lý config trực tiếp; thêm `show()` và `test()` (giới hạn 5 lần/phút theo kênh + admin, trả 429 kèm `retry_after`; URL không an toàn → 422).
- Route mới: `GET /notifications/{channel}` (`superadmin.notifications.show`), `POST /notifications/{channel}/test` (`superadmin.notifications.test`).
- `SystemNotificationChannelRequest`: `config` là mảng, validate `webhook_url` qua `OutboundUrlGuard` (Slack chỉ cho `hooks.slack.com`), `bot_token` và `room_id` theo regex.

## 9. Thành phần UI dùng chung & giao diện

- Modal alert cho khu vực admin: component `x-admin.alert-modal` + `resources/js/admin/alert-modal.js`, ghi đè `window.alert` và cung cấp `window.showAdminAlert(message, type)`.
- `openSuperadminConfirm()` hỗ trợ thêm `description`, `confirmIcon`, reset nhãn/icon mặc định, ẩn lỗi cũ và hiện trạng thái loading khi submit (`renderSubmitLoading`, cũng được expose ra `window` trong `app.js`).
- Component mới `x-superadmin.empty-state`; cập nhật `x-superadmin.confirm-modal`, `x-superadmin.modal`.
- Font Super Admin đổi từ Plus Jakarta Sans sang Inter; cập nhật `resources/css/superadmin.css` (lưới KPI, modal, empty state, banner…).
- Bổ sung khoá dịch vi/en/ja trong `superadmin.php`, `admin.php`, `errors.php` (trang bảo trì), `validation.php` (`admin_ids`).

## 10. Dọn dẹp tài liệu

- Xoá `documents/dat-mon-giup.html`.
- Chuyển các file task ngày 23/09 vào thư mục `documents/tasks/2026-09-23/`.

---

## File thay đổi chính

```
src/app/Actions/Superadmin/ManageAdminAction.php
src/app/Actions/Superadmin/ManageRoomAction.php
src/app/Enums/AdminStatus.php
src/app/Exports/DebtOverviewExport.php                         (xoá)
src/app/Http/Controllers/Superadmin/CampaignController.php
src/app/Http/Controllers/Superadmin/DashboardController.php
src/app/Http/Controllers/Superadmin/DebtController.php         (xoá)
src/app/Http/Controllers/Superadmin/GlobalUserController.php
src/app/Http/Controllers/Superadmin/NotificationController.php
src/app/Http/Controllers/Superadmin/PageController.php
src/app/Http/Controllers/Superadmin/RoomController.php
src/app/Http/Controllers/Superadmin/SystemController.php
src/app/Http/Middleware/CheckMaintenanceMode.php
src/app/Http/Middleware/EnsureActiveAdmin.php
src/app/Http/Requests/StoreRoomRequest.php
src/app/Http/Requests/UpdateRoomRequest.php
src/app/Http/Requests/SystemNotificationChannelRequest.php
src/app/Models/AuditLog.php
src/app/Models/GlobalUser.php
src/app/Services/Notification/Concerns/SendsNotificationChannelPayloads.php   (mới)
src/app/Services/Notification/RoomNotificationChannelDispatcher.php
src/app/Services/Notification/SystemNotificationChannelDispatcher.php         (mới)
src/app/Services/Notification/SystemNotificationChannelService.php            (mới)
src/app/Services/System/SystemSettingsService.php
src/bootstrap/app.php
src/routes/superadmin.php
src/lang/{vi,en,ja}/{admin,errors,superadmin,validation}.php
src/resources/css/superadmin.css
src/resources/js/admin.js
src/resources/js/admin/alert-modal.js                          (mới)
src/resources/js/app.js
src/resources/js/superadmin/modal.js
src/resources/views/components/admin/{alert-modal,layout}.blade.php
src/resources/views/components/superadmin/{confirm-modal,empty-state,maintenance-banner,modal,password-input}.blade.php
src/resources/views/errors/maintenance.blade.php               (mới)
src/resources/views/superadmin/*.blade.php                     (debts.blade.php bị xoá)
```

## Kiểm thử

Test mới/bổ sung:

- `MaintenanceModeTest` (mới): trang bảo trì hiện cho khách/user/admin phòng; route đăng nhập vẫn truy cập được và superadmin thấy banner; bảo trì đã lên lịch hoặc đã hết hạn không chặn request.
- `AdminAccessRedirectTest`: phiên admin bị chặn bị đăng xuất trên cả route admin và superadmin.
- `AdminFeatureTest`: layout admin render modal alert dùng chung.
- `FeedbackModerationTest`: trang feedback Super Admin render nút thao tác và empty state.
- `SuperadminFeatureTest`: tạo/sửa phòng kèm gán admin, đặt trạng thái `inactive`, chặn/mở khoá admin, trang admin dùng modal, trang user có empty state, lọc chiến dịch theo phòng, audit chỉ hiện log admin (event đã dịch), trang System dùng modal reset.

Commit không ghi kết quả chạy test. Lệnh xác nhận:

```bash
cd src
php artisan test
npm run build
```

## Ghi chú / việc còn lại

- Route `superadmin.notifications.show` và `superadmin.notifications.test` đã có backend nhưng view `superadmin/notifications.blade.php` chưa được cập nhật trong commit này, nên UI chưa gọi 2 endpoint đó (sửa kênh / gửi tin test).

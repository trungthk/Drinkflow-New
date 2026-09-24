# Task: Hộp thư Superadmin, Modal gửi mail thử, Chẩn đoán Socket & sửa lỗi Realtime

**Ngày thực hiện:** 24/09/2026
**Trạng thái:** ✅ Hoàn thành (đã chạy `php artisan test` toàn bộ: 462 passed; `npm run build` OK)

## Tổng quan

Gộp các yêu cầu trong ngày:

- Menu user global và trang `/me`: thêm icon cho menu "Đánh giá"; mục "Room gần đây" chỉ hiện tổng số room.
- Superadmin: báo rõ nguyên nhân khi Socket "unreachable", thêm modal "Gửi mail thử nghiệm", hộp thư thông báo của tài khoản đang đăng nhập và chuông thông báo chưa đọc trên header.
- Test thực tế với vai trò user (room `mens-est`): phát hiện và sửa **lỗi rò rỉ thông báo realtime** giữa các thành viên, và sửa khung tài trợ bị hardcode trên dashboard phòng.

---

## 1. User global: menu Đánh giá và trang `/me`

- Thêm icon `rate_review` cho menu **Đánh giá** (`components/global/header.blade.php`), cùng kích thước với các icon menu khác.
- Mục **Room gần đây** (`user/global/dashboard.blade.php`): bỏ chữ "Xem tất cả (N)", thay bằng badge tròn chỉ hiện tổng số room (vẫn link sang `/me/rooms`).
- Xóa key dịch không còn dùng `global.dashboard.view_all_rooms` (vi/en/ja).

## 2. Chẩn đoán Socket.IO ("Không thể kết nối" trên prod)

- Trước đây `SystemHealthService::socket()` nuốt mọi lỗi và chỉ trả về `unreachable`.
- Nay hàm phân loại nguyên nhân qua enum mới `App\Enums\SocketHealthReason`:
  - `not_configured`: chưa có `REALTIME_URL`.
  - `secret_missing`: chưa có `REALTIME_INTERNAL_SECRET`.
  - `secret_mismatch`: gateway trả 401/403, secret giữa Laravel và gateway không khớp.
  - `connection_failed`: không kết nối được tới gateway.
  - `http_error`: gateway trả HTTP lỗi khác (vd 404/502).
  - `invalid_response`: gateway trả dữ liệu không hợp lệ.
- Kết quả trả thêm `reason`, `reason_message` (đã dịch, kèm cách xử lý) và `http_status`. Mỗi lần lỗi được ghi `Log::warning` (chỉ ghi host, **không** ghi secret).
- UI:
  - Trang Socket hiện pill trạng thái đã dịch (trước đây in chữ thô `unreachable`) kèm dòng nguyên nhân.
  - Trang System hiện nguyên nhân dưới dòng Socket.IO.
  - Dashboard hiện nguyên nhân trong tooltip.
- `realtime/server.js`: route `/health` và `/internal/emit` so khớp theo pathname, nên dấu `/` cuối hoặc query string không còn gây 404.
- Việc cần kiểm tra trên prod:
  - `REALTIME_URL` phải là địa chỉ nội bộ (vd `http://127.0.0.1:3001`).
  - `REALTIME_INTERNAL_SECRET` phải giống nhau ở Laravel và gateway.
  - Sau khi sửa `.env`, chạy `php artisan config:clear` và restart gateway.
- Lưu ý: gateway đọc `realtime/.env` **trước** `src/.env`. Nếu `realtime/.env` còn `APP_KEY`/`SOCKET_TOKEN_SECRET` cũ, token do Laravel ký sẽ bị từ chối (`Invalid or expired socket token`). Lỗi này đã gặp ở local trước khi gateway được restart.

## 3. Modal "Gửi mail thử nghiệm" (Superadmin > Cài đặt hệ thống)

- Bấm nút sẽ mở modal nhập **email người nhận** (mặc định là email admin đang đăng nhập) và **nội dung** (để trống thì dùng nội dung mặc định). Modal hiển thị mailer đang dùng.
- `SendTestMailRequest` (mới):
  - Validate `email` (bắt buộc, `email:rfc`) và `message` (tối đa 2000 ký tự).
  - Chỉ superadmin đang active được dùng.
- `MailHealthService::sendTest(string $recipient, ?string $message)`: nội dung được escape, chỉ giữ xuống dòng, nên không chèn được HTML.
- Mỗi lần gửi ghi audit log `system.mail_test` kèm người nhận. Route vẫn giữ `throttle:5,1`.
- Test cũ `SystemHealthTest` được cập nhật để gửi kèm `email`, vì yêu cầu nghiệp vụ mới bắt buộc nhập người nhận.

## 4. Hộp thư thông báo của Superadmin và chuông trên header

- Trang **Thông báo** (`/superadmin/notifications/page`) có thêm mục **"Thông báo của tôi"** (`#inbox`):
  - Chỉ liệt kê `admin_notifications` gửi tới **tài khoản đang đăng nhập**.
  - Có lọc Tất cả / Chưa đọc / Đã đọc, tìm theo tiêu đề/nội dung, phân trang riêng (`inbox_page`).
  - Có nút đánh dấu đã đọc từng thông báo hoặc tất cả.
- Phần quản lý kênh hệ thống vẫn giữ nguyên bên dưới.
- Cả hai nút **Lọc** có icon `filter_alt`. Khi danh sách rỗng hoặc không có kết quả, trang dùng component `x-superadmin.empty-state`.
- Sửa lỗi thiếu key dịch `superadmin.notifications.no_channels` / `prompt_credential` làm `TranslationCoverageTest` fail. Bổ sung các key còn thiếu cho `ja`.
- Header superadmin có component `x-superadmin.notification-bell`:
  - Badge số chưa đọc, dropdown 5 thông báo chưa đọc mới nhất.
  - Bấm vào một thông báo là đánh dấu đã đọc; có nút "Đánh dấu tất cả" và link "Xem tất cả".
- Backend:
  - `AdminNotificationService` có thêm `unreadForAdmin`, `unreadCountForAdmin`, `paginateForAdmin`, `markAllReadForAdmin`, `markRead`.
  - `Superadmin\AdminNotificationController` (mới). Thông báo của tài khoản khác trả 404.
  - `SuperadminLayoutComposer` (mới) cung cấp dữ liệu cho chuông.
  - Route: `POST /superadmin/admin-notifications/read-all`, `PATCH /superadmin/admin-notifications/{notification}/read`.
- Frontend: `resources/js/superadmin/notifications.js` (mới), CSS `.sa-bell*` / `.sa-inbox*` trong `superadmin.css`.

## 5. Sửa lỗi bảo mật: thông báo realtime bị gửi cho cả phòng

- **Phát hiện khi test với vai trò user:**
  - Khi admin đóng campaign, user 1 nhận qua socket cả thông báo của user 2 và user 3, kể cả tin nhắc thanh toán "66.000đ" của user 2. Đã đối chiếu `global_user_id` trong DB để xác nhận.
  - Chính chủ nhận mỗi thông báo 2 lần.
- **Nguyên nhân:**
  - `PublishRealtimeEvent` gửi `notification.created` kèm `room_id` của phòng.
  - Gateway lại phát mọi sự kiện tới cả `room:{id}` lẫn channel riêng của user.
  - `room.membership.updated` cũng đi đường này. Client thấy `status === 'removed'` là tự chuyển về `/me`, nên việc xóa một thành viên có thể đẩy cả phòng ra ngoài.
- **Sửa:**
  - `PublishRealtimeEvent`: `notification.created` luôn gửi với `room_id = 0`.
  - `realtime/server.js`: `notification.created` và `room.membership.updated` **chỉ** phát vào `user_channel` (thiếu `user_channel` thì trả 422). Các sự kiện còn lại phát một lần tới hợp các channel, nên không còn nhận trùng.
- Test: `RealtimeFlowTest::test_room_scoped_user_notification_is_not_sent_to_the_room_channel`.
- ⚠️ Cần **restart gateway realtime** để bản sửa `server.js` có hiệu lực.

## 6. Khung tài trợ trên dashboard phòng (`rooms/{slug}/dashboard`)

- Trước đây các con số sau đều hardcode, không lấy từ campaign:
  - "Sponsor phòng ban: 200.000đ"
  - Thanh tiến độ và "Còn lại"
  - "Hỗ trợ tối đa 20.000đ / người"
- Hệ quả là dashboard mâu thuẫn với trang menu (trang menu ghi "Không tài trợ").
- Nay khung hiển thị theo dữ liệu thật của campaign:
  - Chính sách tài trợ (`sponsor_type`, dùng chung bản dịch với trang menu).
  - Trần ngân sách mỗi sản phẩm (`max_budget`), chỉ hiện khi có.
  - Số tiền đã tài trợ (tổng `sponsor_amount`, không tính đơn đã hủy), chỉ hiện khi campaign có tài trợ.
- Bỏ thanh tiến độ giả. Thay 4 key dịch cũ bằng `room.dashboard.sponsor_used_total` và `sponsor_item_cap` (vi/en/ja).
- Test: `UserRoomDashboardTopItemsTest::test_dashboard_sponsor_box_uses_campaign_policy`.

## 7. Kết quả test thực tế (vai trò user, room `mens-est`)

- Đăng nhập qua `/dev/login`, vào dashboard phòng: không có lỗi JS hay 404.
- Socket kết nối thành công sau khi gateway được restart.
- Khi admin đóng campaign hoặc phát `campaign.updated`, trang user **tự reload** sau khoảng 1–2 giây, không cần F5.
- **Chưa test đặt món:** campaign hiện có đã hết giờ, và trong thời gian test chưa có campaign mới nào được mở.

---

## File thay đổi chính

- Enum/Request/Controller/Composer mới:
  - `app/Enums/SocketHealthReason.php`
  - `app/Http/Requests/SendTestMailRequest.php`
  - `app/Http/Controllers/Superadmin/AdminNotificationController.php`
  - `app/View/Composers/SuperadminLayoutComposer.php`
- Service:
  - `app/Services/System/SystemHealthService.php`
  - `app/Services/System/MailHealthService.php`
  - `app/Services/Notification/AdminNotificationService.php`
  - `app/Services/Dashboard/UserRoomDashboardService.php`
- Controller/Listener/Provider:
  - `Superadmin/SystemController.php`
  - `Superadmin/PageController.php`
  - `app/Listeners/PublishRealtimeEvent.php`
  - `app/Providers/AppServiceProvider.php`
- Route: `routes/superadmin.php`
- View:
  - `superadmin/{system,socket,dashboard,notifications,layout}.blade.php`
  - `components/superadmin/notification-bell.blade.php`
  - `user/dashboard.blade.php`
  - `user/global/dashboard.blade.php`
  - `components/global/header.blade.php`
- JS/CSS:
  - `resources/js/superadmin/notifications.js`
  - `resources/js/app.js`
  - `resources/css/superadmin.css`
- Gateway: `realtime/server.js`
- Lang (vi/en/ja): `superadmin.php`, `admin.php`, `room.php`, `global.php`
- Test:
  - `SuperadminInboxAndDiagnosticsTest.php` (mới)
  - `RealtimeFlowTest.php`
  - `SystemHealthTest.php`
  - `UserRoomDashboardTopItemsTest.php`

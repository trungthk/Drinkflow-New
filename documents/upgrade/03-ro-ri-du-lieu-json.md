# UPG-03 – Rò rỉ dữ liệu qua JSON / API

**Mục tiêu:** Endpoint trả JSON cho thành viên chỉ trả đúng các trường cần thiết, không serialize nguyên model Eloquent kèm quan hệ, không lộ thông tin cá nhân của thành viên khác.

> Mỗi task con dưới đây phải được quản trị xác nhận bằng lời (nêu rõ mã task) trước khi thực hiện. Xem [README](README.md#1-quy-trình-bắt-buộc-xác-nhận-trước-khi-thực-hiện).

---

## UPG-03.1 – `GET /rooms/{room}/orders` (JSON) lộ đơn của mọi thành viên

- **Mức độ:** 🔴 Cao
- **Hiện trạng:**
  - `OrderController::index` eager-load `campaign.orders` (`src/app/Http/Controllers/User/OrderController.php:51`).
  - Khi request có `Accept: application/json`, response chứa **toàn bộ đơn của mọi thành viên** trong từng campaign: `room_user_id`, số tiền, ghi chú...
  - Đồng thời gây tải nặng: mỗi trang 20 đơn kéo theo toàn bộ đơn của các campaign liên quan.
- **Phương án đề xuất:**
  1. Kiểm tra view `user/orders.blade.php` dùng `campaign->orders` để làm gì. Thay bằng `withCount` / truy vấn tổng hợp riêng nếu chỉ cần số liệu.
  2. Bỏ `campaign.orders` khỏi eager-load.
  3. JSON dùng API Resource (`OrderResource`) với danh sách trường cho phép.
- **Tiêu chí hoàn thành:** Test: thành viên A gọi JSON không thấy đơn/ID/số tiền của thành viên B. Giao diện trang đơn hàng hiển thị như cũ (kiểm tra bằng trình duyệt). `php artisan test` PASS.
- **Xác nhận của quản trị:** ⏳ Chưa xác nhận
- **Trạng thái thực hiện:** ⬜ Chưa thực hiện

---

## UPG-03.2 – Tra cứu thành viên (`members/lookup`) lộ email & số điện thoại

- **Mức độ:** 🟠 Trung bình
- **Hiện trạng:**
  - `CampaignController::lookupMember` (`src/app/Http/Controllers/User/CampaignController.php:314`) cho bất kỳ thành viên nào tra user khác theo user_code, email hoặc phone, và trả về `email`, `phone`.
  - Route không có throttle, nên có thể dò hàng loạt.
  - Hàm tải toàn bộ thành viên của room vào PHP rồi mới lọc (xem thêm UPG-06.3).
- **Phương án đề xuất:**
  1. Chỉ trả `display_name`, `user_code`, `avatar_url`. Email/phone được che, ví dụ `n***@company.com`, `09****123`. Mức che do quản trị chọn.
  2. Thêm rate limiter `room-member-lookup` (ví dụ 20 lần/phút theo user).
  3. Cập nhật JS đặt hộ nếu đang hiển thị email/phone.
- **Tiêu chí hoàn thành:** Test response không chứa email/phone đầy đủ; vượt giới hạn → 429. `npm run build`, `php artisan test` PASS.
- **Cần quản trị quyết định:** Có cần hiển thị email/phone (đã che) để người đặt hộ xác nhận đúng người hay không.
- **Xác nhận của quản trị:** ⏳ Chưa xác nhận
- **Trạng thái thực hiện:** ⬜ Chưa thực hiện

---

## UPG-03.3 – Serialize nguyên model Room/Order/Campaign ra JSON

- **Mức độ:** 🟠 Trung bình
- **Hiện trạng:** Các response sau trả nguyên model, kèm `rooms.settings` (JSON cấu hình), `owner_admin_id` và các cột nội bộ khác:
  - `CampaignController::show` → `$campaign->load([..., 'room'])` (`CampaignController.php:405`)
  - `OrderController::show` → `$order->load(['items.toppings', 'campaign', 'room'])` (`OrderController.php:171`)
  - `RoomController::show` → `$room->loadCount(['campaigns'])`, `room_user` (`RoomController.php:101`)
  - `RoomController::join` → `$roomUser->load('room')` (`RoomController.php:136`)
  - `RoomsController::index` (JSON), `PaymentsController::index` (JSON `orders` kèm `room.paymentAccounts`)
- **Phương án đề xuất:**
  1. Tạo API Resource cho Room (public fields: `id, name, slug, description, avatar_url`), Order, Campaign, RoomUser.
  2. Thay các response trên bằng Resource.
  3. Rà JS đang đọc các trường này để không làm vỡ giao diện.
- **Tiêu chí hoàn thành:** Test mỗi endpoint không chứa khoá `settings`, `owner_admin_id`. `npm run build`, `php artisan test` PASS; kiểm tra các trang liên quan bằng trình duyệt.
- **Xác nhận của quản trị:** ⏳ Chưa xác nhận
- **Trạng thái thực hiện:** ⬜ Chưa thực hiện

---

## UPG-03.4 – Email thành viên trong chi tiết campaign và trang tra cứu công khai

- **Mức độ:** 🟡 Thấp
- **Hiện trạng:**
  - `CampaignController::details` (`CampaignController.php:63`): với campaign tài trợ toàn phần, trả `orderer_email` của **mọi** người đặt.
  - `PublicOrderCheckService::summaries`: trang public `/check-order/*` (ai có link đều truy cập được) trả `member_email`, và email của người đặt hộ / người được đặt hộ.
- **Phương án đề xuất:** Bỏ hoặc che email trong hai response. Chỉ giữ tên hiển thị và mã thành viên.
- **Tiêu chí hoàn thành:** Test response không có email đầy đủ; giao diện modal chi tiết và trang tra cứu vẫn hiển thị đúng. `php artisan test` PASS.
- **Cần quản trị quyết định:** Có giữ email (đã che) trên trang tra cứu công khai hay không.
- **Xác nhận của quản trị:** ⏳ Chưa xác nhận
- **Trạng thái thực hiện:** ⬜ Chưa thực hiện

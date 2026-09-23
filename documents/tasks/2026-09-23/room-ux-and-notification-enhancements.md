# Task: Cải thiện UX Room, Thông báo & Dashboard

**Ngày thực hiện:** 23/09/2026
**Trạng thái:** ✅ Hoàn thành (đã chạy `php artisan test` toàn bộ, PASS)

## Tổng quan

Gộp một loạt cải tiến nhỏ trên trang cá nhân (`/me`), trang phòng (Room) và trang thông báo, gồm: sửa lỗi giao diện, bổ sung tính năng xem đơn hàng theo phòng, bổ sung thống kê biểu đồ cho dashboard phòng, và tinh gọn layout trang `/me`.

---

## 1. Trang hồ sơ Room (`rooms/{slug}/profile`)

- Thêm nút **Chỉnh sửa hồ sơ** nằm ngang phần thông tin user, redirect sang `/me/profile`.
- File: `src/resources/views/user/profile.blade.php` (dùng lại key dịch có sẵn `room.profile.edit_profile`).
- Test: `RoomProfileCompletedOrdersTest::test_room_profile_page_has_edit_button_linking_to_global_profile`.

## 2. Trang Thông báo (`/me/notifications`)

- Thêm 2 danh mục filter mới: **Hồ sơ cá nhân** (`device.new`, `security.alert`) và **Khác** (`admin.broadcast`, `notification.test`), thay cho tab "Bảo mật" cũ.
- Thêm cột `link` cho bảng `user_notifications` (migration `2026_09_23_000000_add_link_to_user_notifications_table`) — hiển thị nút "Xem chi tiết" khi thông báo có link.
- Nội dung thông báo (`body`) hiển thị dạng HTML an toàn qua accessor `UserNotification::getBodyHtmlAttribute()`: escape toàn bộ nội dung rồi tự động biến URL thành link + xuống dòng thành `<br>`, **không** render `{!! $notif->body !!}` thô để tránh XSS lưu trữ (tên hiển thị người dùng, tên chiến dịch... vốn là free-text được nội suy trực tiếp vào `body`).
- Files: `UserNotificationService.php`, `UserNotification.php`, `NotificationController` (không đổi), view `user/global/notifications.blade.php`, lang `global.php` (vi/en/ja).
- Test: `GlobalNotificationsTest` (thêm 3 test case: filter tab "Khác", body render an toàn, hiển thị link).

## 3. Sửa lỗi giật trang do icon font (toàn hệ thống)

- Nguyên nhân: Google Fonts "Material Symbols Outlined" load với `display=swap` (hoặc mặc định) → khi font chưa tải xong, trình duyệt hiện tạm chữ ligature thô (vd "close", "menu") rồi mới thay bằng icon, gây giật/lỗi font.
- Đã đổi `display=swap` → `display=block` cho riêng font icon (giữ nguyên `swap` cho font chữ Inter) tại toàn bộ 8 layout: `components/global`, `components/room`, `components/public`, `components/admin`, `components/admin-auth`, `superadmin/layout.blade.php`, `admin/rooms.blade.php`, `user/global/blocked.blade.php`.

## 4. Ảnh món trong modal chi tiết đơn (`rooms/{slug}/debts`)

- `CampaignController::details()` eager-load thêm `items.campaignItem`, trả về `image_url` trong JSON.
- Modal danh sách đơn hiển thị thumbnail món (tái sử dụng pattern icon-fallback đã có), có `loading="lazy"` + `onerror` fallback theo quy ước ảnh của dự án.
- Test: `CampaignDetailsEndpointTest::test_campaign_details_exposes_item_image_url`.

## 5. Biểu đồ Dashboard phòng (`rooms/{slug}/dashboard`)

- **Top nhà tài trợ**: biểu đồ cột xếp hạng theo tổng `sponsor_amount`, cùng logic với "Sponsor Leaderboard" hiện có ở trang Admin Reports.
- **Số món & giá trị 7 ngày gần nhất**: biểu đồ cột (số món) kết hợp đường (giá trị đơn), theo đúng pattern SVG dual-scale đã có sẵn ở Admin Dashboard (chấp nhận đánh đổi dual-axis để nhất quán UI với phần đã có).
- Backend: `UserRoomDashboardService::getTopSponsors()`, `getWeeklyItemTrend()`.
- Frontend: `resources/js/room/dashboard-charts.js` (mới), gắn vào `room.js`; view `user/dashboard.blade.php`; lang `room.php` (vi/en/ja, thêm khoá `top_sponsors_*`, `weekly_trend_*`, `day_*`).
- Test: 2 case mới trong `UserRoomDashboardTopItemsTest` (xếp hạng sponsor, tổng hợp theo ngày).

## 6. Xem đơn cả phòng trên trang Chiến dịch (`rooms/{slug}/campaigns`)

- Trích xuất "Campaign Details Modal" (trước chỉ có ở trang Công nợ) thành component dùng chung: `components/room/campaign-orders-modal.blade.php`, xoá ~260 dòng trùng lặp ở `debts.blade.php`.
- Gắn modal này vào `campaign.blade.php` kèm nút **"Xem đơn cả phòng"**, tái sử dụng endpoint JSON `user.campaigns.details` sẵn có (đã có toàn bộ đơn của mọi thành viên trong chiến dịch).
- Test: `CampaignOrderingAvailabilityTest::test_campaign_page_shows_room_orders_button_and_modal`.

## 7. Tinh gọn layout trang `/me`

- Bỏ khung "card" (nền trắng/viền/bo góc/shadow) của 2 mục **"Lối tắt nhanh"** và **"Room gần đây"**, đưa nội dung ra ngoài (nằm trực tiếp trên nền trang thay vì trong khung card).
- Đổi màu đường viền phân cách tiêu đề từ `border-slate-100` → `border-slate-200` để vẫn nhìn rõ trên nền trang (không còn nền trắng của card).
- File: `src/resources/views/user/global/dashboard.blade.php`.

---

## File thay đổi chính

```
src/app/Http/Controllers/User/CampaignController.php
src/app/Models/UserNotification.php
src/app/Services/Dashboard/UserRoomDashboardService.php
src/app/Services/Notification/UserNotificationService.php
src/database/migrations/2026_09_23_000000_add_link_to_user_notifications_table.php   (mới)
src/resources/js/room.js
src/resources/js/room/dashboard-charts.js                                            (mới)
src/resources/views/admin/rooms.blade.php
src/resources/views/components/admin-auth/layout.blade.php
src/resources/views/components/admin/layout.blade.php
src/resources/views/components/global/layout.blade.php
src/resources/views/components/public/layout.blade.php
src/resources/views/components/room/layout.blade.php
src/resources/views/components/room/campaign-orders-modal.blade.php                  (mới)
src/resources/views/superadmin/layout.blade.php
src/resources/views/user/campaign.blade.php
src/resources/views/user/dashboard.blade.php
src/resources/views/user/debts.blade.php
src/resources/views/user/global/blocked.blade.php
src/resources/views/user/global/dashboard.blade.php
src/resources/views/user/global/notifications.blade.php
src/resources/views/user/profile.blade.php
src/lang/{vi,en,ja}/global.php
src/lang/{vi,en,ja}/room.php
src/tests/Feature/CampaignDetailsEndpointTest.php                                    (mới)
src/tests/Feature/CampaignOrderingAvailabilityTest.php
src/tests/Feature/GlobalNotificationsTest.php
src/tests/Feature/RoomProfileCompletedOrdersTest.php
src/tests/Feature/UserRoomDashboardTopItemsTest.php
```

## Kiểm thử

- `cd src && php artisan test` → 425 passed, 2 skipped (không liên quan).
- `cd src && npm run build` → build thành công (Tailwind quét class mới, JS bundle `room.js` cập nhật).
- Chưa kiểm tra bằng Playwright/trình duyệt thật (người dùng bỏ qua bước này theo yêu cầu trong phiên làm việc).

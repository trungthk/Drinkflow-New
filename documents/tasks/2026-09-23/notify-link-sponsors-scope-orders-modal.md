# Task: Link thông báo, phạm vi 7 ngày cho Top nhà tài trợ & Modal đơn cả phòng gọn hơn

**Ngày thực hiện:** 23/09/2026
**Trạng thái:** ✅ Hoàn thành (đã chạy `php artisan test` toàn bộ, PASS 429/429, 2 skip không liên quan)

## Tổng quan

4 cải tiến nhỏ tiếp theo trên trang `/me`, Room Dashboard và tính năng xem đơn cả phòng: hiện link thông báo (nếu có) ở dropdown header, giới hạn biểu đồ Top nhà tài trợ trong 7 ngày gần nhất, bỏ header của mục "Lối tắt nhanh" trên `/me`, và thu gọn modal "Xem đơn cả phòng" thành dạng bảng ngang + bổ sung vào trang "Đơn hàng của tôi".

---

## 1. Link thông báo (nếu có) trên dropdown header

- `NotificationPresentationService::present()` trả thêm field `link` (lấy từ `UserNotification->link`, `null` với `AdminNotification` vì model này chưa có cột đó).
- Thêm nút "Xem chi tiết" vào dropdown thông báo ở cả 2 header: `components/global/header.blade.php` và `components/room/header.blade.php`, chỉ hiện khi `link` không rỗng.
- Cập nhật PHPDoc `@return` ở `AdminLayoutComposer::presentNotifications()` và `UserRoomLayoutComposer::presentNotifications()` cho khớp field mới.

## 2. Biểu đồ "Top nhà tài trợ" ở Room Dashboard: giới hạn 7 ngày gần nhất

- `UserRoomDashboardService::getTopSponsors()` trước đây truyền `null, null` (toàn bộ thời gian) vào `SponsorLeaderboardService::build()`. Nay tính `$from = today - 6 ngày`, `$to = today` (khớp khung thời gian của biểu đồ "Số món & giá trị 7 ngày gần nhất" cạnh bên).
- Cập nhật subtitle "Xếp hạng theo tổng giá trị tài trợ..." → bổ sung "...trong 7 ngày gần nhất" (vi/en/ja).

## 3. Bỏ header của mục "Lối tắt nhanh" trên `/me`

- File: `src/resources/views/user/global/dashboard.blade.php`. Xoá khối tiêu đề (icon + "Lối tắt nhanh" + mô tả), chỉ giữ lại lưới nút shortcut.
- Dọn 2 khoá dịch không còn dùng: `global.dashboard.quick_shortcuts`, `global.dashboard.shortcuts_desc` (vi/en/ja).

## 4. Modal "Xem đơn cả phòng" gọn hơn + bổ sung vào trang "Đơn hàng của tôi"

- Thiết kế lại `components/room/campaign-orders-modal.blade.php` từ dạng thẻ-mỗi-đơn (avatar, badge trạng thái, breakdown giá, size/đá/đường/ghi chú) sang **bảng hàng ngang gọn**: mỗi hàng = 1 đơn, 3 cột **Họ tên | Email | Món & Topping** — phù hợp khi phòng có tới ~100 thành viên.
- Bổ sung field `orderer_email` vào JSON trả về của `CampaignController::details()`.
- Thêm nút "Xem đơn cả phòng" + modal dùng chung vào trang "Đơn hàng của tôi" (`resources/views/user/orders.blade.php`), đặt cạnh tiêu đề "Món đã chọn", dùng lại đúng logic `openCampaignDetail()` đã có ở trang Chiến dịch/Công nợ.
- Thêm 2 khoá dịch cột bảng mới: `room.debts.column_orderer_name`, `column_orderer_email`, `column_items_toppings` (vi/en/ja).

---

## File thay đổi chính

```
src/app/Http/Controllers/User/CampaignController.php
src/app/Services/Dashboard/UserRoomDashboardService.php
src/app/Services/Notification/NotificationPresentationService.php
src/app/View/Composers/AdminLayoutComposer.php
src/app/View/Composers/UserRoomLayoutComposer.php
src/lang/{vi,en,ja}/global.php
src/lang/{vi,en,ja}/room.php
src/resources/views/components/global/header.blade.php
src/resources/views/components/room/header.blade.php
src/resources/views/components/room/campaign-orders-modal.blade.php
src/resources/views/user/global/dashboard.blade.php
src/resources/views/user/orders.blade.php
src/tests/Feature/CampaignDetailsEndpointTest.php
src/tests/Feature/UserRoomDashboardTopItemsTest.php
```

## Kiểm thử

- `cd src && php artisan test` → 429 passed, 2 skipped (ZipArchive extension thiếu ở môi trường local, không liên quan).
- `cd src && npm run build` → build thành công (không có JS thay đổi thực chất vòng này, rebuild để chắc chắn Tailwind quét đúng class Blade).

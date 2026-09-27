# Task: Thông báo có mã/link, Đặt dùm & Khóa chiến dịch (Admin), Trần ngân sách theo số lượng và các cải tiến UI/UX

**Ngày thực hiện:** 27/09/2026  
**Trạng thái:** ✅ Hoàn thành (đã viết Feature tests; `php artisan test`: 541 pass, 1 fail — `SeoTest::test_private_routes_send_noindex_header` thuộc phần SEO đang làm dở, không liên quan tới các thay đổi dưới đây)

---

## 1. Tổng quan mục tiêu

1. **Thông báo (user):** hiển thị mã đơn thay cho `#id`, có link tới đơn/chiến dịch, giữ nguyên nội dung admin gửi, click vào item trên header thì đánh dấu đã đọc.
2. **Thanh toán & công nợ:** thêm người gửi vào nội dung chuyển khoản VietQR; sửa lỗi công nợ tài trợ không hiển thị với người tài trợ; tạm ẩn nút "Thanh toán toàn bộ".
3. **Admin – Đặt dùm thành viên** trên trang Quản lý đơn hàng.
4. **Admin – Khóa chiến dịch:** thành viên không đặt được nữa (admin vẫn đặt dùm được); có modal xác nhận kèm tùy chọn gửi thông báo, chỉ thao tác khi chiến dịch còn hạn.
5. **Đặt món:** chọn số lượng trong modal "Chọn món"; trần ngân sách mỗi sản phẩm áp cho **đơn giá × số lượng**.
6. **Chiến dịch (admin):** tự chia đều tỷ lệ sponsor; danh sách "Thực Đơn & Danh Sách Món" hiển thị 4 chiến dịch + nút "Xem thêm"; modal chốt chiến dịch luôn ghi công nợ.
7. **Sửa lỗi:** link check-order trong gateway bị `https: //`; 2 biểu đồ dashboard room không hiển thị dữ liệu; trang `/guides` 404.
8. **Các chỉnh sửa giao diện nhỏ** (dashboard, profile, header, hướng dẫn, báo cáo/audit…).
9. **Đồng bộ đa ngôn ngữ vi / en / ja** cho toàn bộ key mới.

---

## 2. Chi tiết thay đổi

### 2.1. Thông báo người dùng

- `NotificationPresentationService`:
  - Đặt món thành công / đổi trạng thái đơn: dùng **mã đơn** (`order_code`) thay cho `#id`; link tới trang đơn (`user.orders.page`).
  - Thông báo từ quản trị phòng (`admin.broadcast` hoặc `data.broadcast = true`): hiển thị **đúng tiêu đề & nội dung admin đã gửi** (trước đây bị ghi đè bằng tiêu đề dịch sẵn).
  - Chiến dịch mới: link tới trang đặt món **chỉ khi chiến dịch còn nhận đơn**.
  - Nhắc thanh toán: tiêu đề có mã (`messages.payment_reminder_with_code`), ưu tiên mã đơn rồi tới mã công nợ.
- `CreateOrderNotification`, `CreateOrderStatusNotification`, `NotifyCampaignClosed`: lưu `order_code` / `debt_code`, link và nội dung đã dịch.
- Header (global & room): click vào item chưa đọc gọi `PATCH /notifications/{id}/read` (keepalive), cập nhật chấm, badge chuông — `resources/js/shared/header-notification-read.js`.

### 2.2. Thanh toán & công nợ

- `VietQrService::transferContent()`: nội dung chuyển khoản `"{mã} {TÊNNGƯỜIGỬI}"` (bỏ dấu, viết hoa, tối đa 25 ký tự — chỉ cắt phần tên). Áp dụng cho QR trang Đơn hàng, từng khoản công nợ và API thanh toán đơn. `RoomUser::payerName()`.
- `UserRoomDebtService::queryVisibleDebts()`: vẫn hiển thị công nợ khi thành viên **không có đơn** trong chiến dịch (công nợ của người tài trợ, kể cả khi được người khác order dùm).
- Trang `/rooms/{room}/debts`: tạm ẩn nút "Thanh toán toàn bộ" (`$showPayAllButton = false`).

### 2.3. Admin – Đặt dùm thành viên

- Migration `2026_09_27_000000_add_placed_by_admin_id_to_orders_table` (cột `orders.placed_by_admin_id`, FK `admin_accounts`). Trên SQLite, migration dựng lại partial index `orders_one_active_per_user_campaign` (Laravel rebuild bảng làm mất mệnh đề `WHERE`).
- `PlaceOrderOnBehalfAction` + `PlaceOrderOnBehalfRequest` + route `POST /admin/{room}/orders/on-behalf` (`throttle:30,1`): tạo đơn qua `CreateOrderAction` (giá, tài trợ, hạn mức nợ), từ chối thành viên đã có đơn hoạt động, ghi audit `order.placed_on_behalf`.
- `AdminOrderOnBehalfService`: menu + danh sách thành viên chưa có đơn cho modal.
- UI: nút "Đặt dùm thành viên" + modal (`admin/partials/order-on-behalf-modal.blade.php`, `resources/js/admin/order-on-behalf.js`); bảng đơn hiển thị "Đặt dùm bởi {admin}".

### 2.4. Admin – Khóa chiến dịch

- Migration `2026_09_27_010000_add_ordering_locked_at_to_campaigns_table` (`campaigns.ordering_locked_at`).
- `Campaign`: `isOrderable()` = `isOpenForOrders()` && !`isOrderingLocked()`; `orderingClosedMessage()`.
- `SetCampaignOrderingLockAction` + `CampaignOrderingLockRequest`; routes `POST …/lock-ordering`, `…/unlock-ordering`. Chỉ cho phép khi chiến dịch `active` và **còn hạn**; ghi audit; phát realtime `campaign.updated`; tùy chọn gửi thông báo in-app cho thành viên.
- `CreateOrderAction` có tham số `allowLocked` — admin đặt dùm vẫn được khi đang khóa.
- UI: nút Khóa/Mở khóa (trang chi tiết chiến dịch & Quản lý đơn), modal xác nhận có checkbox "Gửi thông báo cho thành viên" (`components/admin/campaign-ordering-lock-modal.blade.php`, `resources/js/admin/campaign-ordering-lock.js`), chip "Đã khóa đặt món" trong badge trạng thái.
- User: banner "Chiến dịch đã bị khóa"; trang đặt món redirect về trang chiến dịch; trang tự reload khi nhận realtime.

### 2.5. Đặt món: số lượng & trần ngân sách

- Modal "Chọn món": ô **Số lượng** (nút +/−, nhập số nguyên) phía trên "Ghi chú đặc biệt".
- Trần ngân sách mỗi sản phẩm áp cho **đơn giá × số lượng**: giới hạn số lượng tối đa ở client (kèm gợi ý "Tối đa N phần…"), kiểm tra ở `CreateOrderAction` và `UpdateOrderAction` (admin chỉnh giá).

### 2.6. Chiến dịch (admin)

- Thêm/xóa sponsor tự chia đều tỷ lệ (dư cộng vào sponsor đầu: 100 / 50-50 / 34-33-33).
- "Thực Đơn & Danh Sách Món": 4 chiến dịch đã chốt gần nhất + nút "Xem thêm" (`PreviousCampaignMenuService`, `components/admin/previous-campaign-picker.blade.php`; API `previous-menus` hỗ trợ `offset`, `limit`, `exclude`, trả `meta.has_more`).
- Modal "Xác nhận kết thúc đơn & Chốt chiến dịch": bỏ checkbox "Tự động tạo bản ghi công nợ" — server luôn tạo công nợ (bỏ qua `allow_debt`).

### 2.7. Sửa lỗi

- `SendsNotificationChannelPayloads::splitLabelValue()`: không tách nhãn tại dấu `:` của URL → hết lỗi link `https: //…` trong Telegram/Slack/Chatwork.
- Dashboard room: dữ liệu biểu đồ truyền bằng JSON chuẩn (trước đây `Js::from` sinh `JSON.parse(...)` nên JS không parse được); "Giá trị đơn hàng" 7 ngày tính theo `subtotal`.
- Trang `/guides` 404: thư mục tài liệu `public/guides` trùng route → đổi thành `public/guide-content` (`UserGuideService::GUIDES_DIR`).
- Blade: thay `@php(...)` inline bằng khối `@php … @endphp` trong `admin/orders.blade.php` (lỗi biên dịch khi trộn hai kiểu).

### 2.8. Chỉnh sửa giao diện khác

- Hướng dẫn: ô tìm kiếm & chế độ lưới/danh sách cùng một hàng; lightbox xem ảnh toàn màn hình (đóng X/Esc, trước/sau, vuốt) trên cả 3 trang chi tiết.
- Dashboard room: bỏ khung "Khoản nợ cần thanh toán", bỏ nút X của khung "Thông báo".
- Profile room: "Đã uống N lần" → "xN"; Tổng nợ màu đỏ, Tổng chi tiêu màu xanh.
- Header room: hiển thị email thay cho mã thành viên.
- Dashboard admin: bỏ bảng "Đơn hàng mới nhận".
- Modal điều chỉnh giá: dấu `*` đỏ ở "Lý do điều chỉnh giá".
- Modal chi tiết công nợ (Sổ công nợ): 2 tab **Cơ bản** / **Lịch sử** (thanh toán + điều chỉnh).
- Báo cáo & Nhật ký hoạt động: bộ lọc thời gian mặc định **7 ngày qua** (`DateRangeHelper::lastDays()`); "Tất cả thời gian" ở trang báo cáo gửi `period=all`.

---

## 3. Migration

```bash
cd src
php artisan migrate
```

- `2026_09_27_000000_add_placed_by_admin_id_to_orders_table`
- `2026_09_27_010000_add_ordering_locked_at_to_campaigns_table`

Sau khi pull cần build lại asset: `npm run build`.

---

## 4. Kiểm thử

Test mới: `NotificationPresentationTest`, `UserRoomDebtVisibilityTest`, `GuideImageLightboxTest`, `AdminOrderOnBehalfTest`, `CampaignOrderingLockTest`, `CampaignCartQuantityTest`, `PreviousCampaignMenusTest`, `AdminDefaultDateRangeTest`; bổ sung vào `GuestCampaignLinkTest`, `UserRoomDashboardTopItemsTest`, `VietQrPayloadTest`, `AdminFeatureTest`, `AdminCampaignSubViewsTest`, `AdminDashboardTrendTest`, `AdminFilterControlsTest`.

Kết quả: `php artisan test` → 541 pass, 1 fail (`SeoTest`, phần SEO chưa hoàn tất).

---

## 5. Lưu ý

- Nút "Thanh toán toàn bộ" chỉ đang ẩn tạm; nội dung chuyển khoản của luồng này vẫn là `user_code` (UUID, không đủ chỗ cho tên người gửi).
- Trần ngân sách áp cho từng dòng món trong giỏ (cùng món thêm thành nhiều dòng được xét riêng).
- "Hôm nay" của bộ lọc 7 ngày tính theo `config('app.timezone')` (hiện là UTC).

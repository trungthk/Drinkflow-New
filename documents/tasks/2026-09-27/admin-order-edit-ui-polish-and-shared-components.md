# Task: Sửa đơn trong Admin, trang bảo trì dùng chung, lightbox & loading dùng chung và hàng loạt cải tiến giao diện Admin/Room

**Ngày thực hiện:** 27/09/2026
**Trạng thái:** ✅ Hoàn thành. `php artisan test`: 567 pass, 1 fail. Test fail là `SeoTest::test_private_routes_send_noindex_header` (header `X-Robots-Tag` của `/rooms/missing`). Lỗi này đã có trước đợt này, thuộc phần SEO đang làm dở. `npm run build` OK.

---

## 1. Tổng quan

1. **Trang bảo trì / 503 dùng chung một view.** Nút "Kiểm tra lại" có trạng thái loading.
2. **Admin – Sửa đơn:** đổi món, số lượng, size, topping, trả riêng. Không cho order dùm người khác. Sửa được cả sau hạn đặt khi chiến dịch còn active.
3. **Admin – Đặt dùm / Sửa đơn:** chọn thành viên và món bằng dropdown có tìm kiếm, không phân biệt hoa thường và có dấu hay không.
4. **Room:** hiển thị "Đã có N người đặt món" ở dashboard và trang chiến dịch. Lightbox cho ảnh món ở menu và giỏ hàng. Thẻ màu riêng cho "Tổng chi tiêu" và "Sponsor đã nhận" ở trang analytics.
5. **Component dùng chung:**
   - `<x-loading-overlay />`: dùng ở modal đóng chiến dịch, biểu đồ dashboard admin và room.
   - `<x-image-lightbox />`: dùng ở trang hướng dẫn và trang chiến dịch.
6. **Admin – cải tiến giao diện:**
   - Dashboard: hyperlink ở thẻ số liệu, thanh tiến độ màu theo tỷ lệ, modal "Gửi thông báo" mới.
   - Danh sách chiến dịch: cột Số món, Tiền quán (gross), thời gian bắt đầu, dời cột trạng thái, search chỉ theo mã/tên/quán, dùng modal đóng chiến dịch dùng chung.
   - Quản lý đơn: email, mỗi món 1 dòng, lọc theo loại đơn, copy mã đơn.
   - Sổ công nợ: icon trạng thái, "Xác nhận", modal Thu tiền/Điều chỉnh mới, tooltip.
   - Thành viên: Ngày tham gia, Tổng đơn hàng, icon trạng thái.
   - Báo cáo: huy chương Top 5, màu tỷ lệ tham gia, tab chỉ tiếng Việt, copy email.
7. **Đồng bộ đa ngôn ngữ vi / en / ja** cho mọi key mới. Xoá các key không còn dùng.

---

## 2. Chi tiết thay đổi

### 2.1. Trang bảo trì / 503

- `CheckMaintenanceMode` trả view `errors.503`. Xoá `errors/maintenance.blade.php`. `errors/503.blade.php` là view duy nhất, hiển thị được cả khi không có `$maintenance` (ví dụ `php artisan down`).
- Nút "Kiểm tra lại" và lần tự tải lại sau 60 giây đều hiện spinner + "Đang kiểm tra..." (`errors.maintenance.checking`). Trang tự tải lại khi được khôi phục từ back/forward cache.
- Xoá khối lang `errors.503.*` không còn dùng.

### 2.2. Admin – Sửa đơn & Đặt dùm

- **Tách logic tính giá:**
  - `App\Services\Order\OrderPricingService` gồm `priceLines()`, `sponsorAmount()` (tham số `excludeOrderId`, không tính tài trợ của chính đơn đang sửa khi dùng quỹ `budget`), `enforceDebtPolicy()`, `createItems()`.
  - `CreateOrderAction` dùng service này, hành vi không đổi.
- **Sửa món trong đơn:**
  - `UpdateOrderItemsAction` + `UpdateOrderItemsRequest`.
  - Route `PUT /admin/{room}/orders/{order}/items` (`admin.orders.items.update`, `throttle:30,1`).
  - Thay toàn bộ món và tính lại giá theo menu. Các lần "Điều chỉnh giá" trước đó bị thay thế, modal có ghi rõ.
  - Ghi audit `order.items_updated`, gửi thông báo `order.updated` cho thành viên, phát `OrderUpdated`.
- **Điều kiện được sửa:**
  - Đơn còn active, chưa thanh toán và không ở trạng thái chờ xác nhận thanh toán.
  - Chiến dịch `active`; được sửa cả sau hạn đặt.
  - Không nhận `proxy_user_code`, nên không tạo đơn con.
- **`AdminOrderOnBehalfService::formData()`:** trả dữ liệu khi chiến dịch `active`, kèm cờ `can_place`. Nút "Đặt dùm" chỉ hiện khi còn trong hạn đặt.
- **UI:**
  - Modal đặt dùm có 2 chế độ (tạo / sửa) và nút −/+ số lượng trên từng món.
  - Món đã hết bán được đánh dấu đỏ và phải xoá trước khi lưu.
  - Trong menu thao tác của đơn có mục "Sửa đơn".
  - Dropdown thành viên và món dùng `select[data-searchable]` có sẵn: không phân biệt hoa thường, bỏ dấu, `đ` → `d`, tìm theo cả email và danh mục.

### 2.3. Room (người dùng)

- **Số người đã đặt:**
  - `Campaign::orderedMembersCount()` đếm số thành viên khác nhau có đơn chưa huỷ.
  - Hiển thị "Đã có N người đặt món" (`room.campaign.ordered_members_count`, `trans_choice`) ở dashboard và trang chiến dịch.
  - Sửa lỗi đếm cả đơn đã huỷ ở trang chiến dịch.
- **Lightbox dùng chung:**
  - `resources/js/shared/image-lightbox.js` + `<x-image-lightbox />` thay cho `guide-lightbox` (đã xoá).
  - `<img data-lightbox="group">` mở ảnh theo nhóm, chỉ gồm ảnh đang hiện và tải được. Dùng `data-lightbox-src` cho ảnh lazy.
  - Áp dụng cho ảnh món ở menu và giỏ hàng.
  - Esc chỉ đóng lightbox, giỏ hàng bên dưới vẫn mở.
  - Text dùng chung chuyển sang `global.lightbox.*`.
- **Trang analytics:** thẻ "Tổng chi tiêu" màu xanh dương, "Sponsor đã nhận từ Room" màu tím (`$metricTones`).
- **CSS:** thêm `@source '../views/components/*.blade.php'` vào `room.css`, `global.css`, `public.css`. Trước đây class của component ở thư mục gốc `components/` chỉ được build nhờ view đã compile trong `storage`.

### 2.4. Component loading dùng chung

- `<x-loading-overlay />` (`label`, `visible`) + `resources/js/shared/loading-overlay.js` (`setLoadingOverlay()`).
- Áp dụng:
  - Modal "Đóng chiến dịch": thay đoạn code cũ.
  - Biểu đồ "Xu hướng Chiến dịch" ở dashboard admin: hiện từ lúc vào trang và mỗi lần tải lại dữ liệu.
  - "Top nhà tài trợ" và "Số món & giá trị 7 ngày gần nhất" ở dashboard room.

### 2.5. Admin – Dashboard

- Thẻ "Chiến dịch đang mở", "Đơn hàng hôm nay", "Công nợ chưa thu" là link tới danh sách chiến dịch (lọc `active`), quản lý đơn và sổ công nợ (`admin.dashboard_metric_view`).
- Thanh tiến độ "Đã đặt món" đổi màu theo tỷ lệ: dưới 50% đỏ, dưới 80% cam, còn lại xanh.
- **Modal "Gửi thông báo" mới** (nằm trong layout admin):
  - Chọn loại thông báo bằng thẻ có icon.
  - Bộ đếm ký tự, xem trước thông báo.
  - Nút gửi có loading, đóng bằng Esc hoặc click nền.
  - Validation và endpoint không đổi.

### 2.6. Admin – Danh sách chiến dịch

- **Cột:** Chiến dịch (kèm thời gian bắt đầu cạnh tên quán) · **Số món** · Số đơn · **Tiền quán** · **Trạng thái** · Thao tác. Bỏ cột "Khung giờ đặt".
- **Số món:** tổng số lượng món chính, không tính topping, bỏ đơn huỷ.
- **Tiền quán = Gross total:** tiền món + phí giao − giảm giá, trước tài trợ. Trước đây cột này luôn 0đ vì đọc `subtotal_amount` không tồn tại.
- **Code dùng chung:** `Campaign::orderItems()` (HasManyThrough) và `Campaign::grossTotal()`. Modal đóng chiến dịch cũng dùng `grossTotal()`.
- **Search:** chỉ theo mã, tên chiến dịch, tên quán; không còn tìm theo mô tả.
- **Đóng chiến dịch:** "Đóng chiến dịch" mở modal tổng kết dùng chung `<x-admin.close-campaign-modal>`. Xoá modal xác nhận cũ, phần JS của nó và 3 key lang.
- **Nhãn modal tổng kết:** "Tự order", "Được order dùm", "Tính riêng", "Tổng số món". Bỏ dòng giải thích dưới "Tổng số tiền tính riêng".

### 2.7. Admin – Quản lý đơn hàng

- **Cột người đặt:** hiện email thay cho user code; cột rộng hơn (`w-72`).
- **Cột chi tiết món:** mỗi món 1 dòng. Sửa lỗi size/topping không bao giờ hiện, do đọc sai trường `size`/`name` thay vì `size_name`/`topping_name`. Lỗi tương tự trong modal chi tiết đơn cũng đã sửa.
- **Lọc theo loại đơn:** Tự đặt / Admin order dùm / Người khác order dùm. Enum `App\Enums\OrderPlacementType`, scope `Order::ofPlacementType()`.
- Có icon copy sau mã đơn.

### 2.8. Admin – Sổ công nợ

- Link chiến dịch không gạch chân khi hover.
- "Duyệt thanh toán" đổi thành **"Xác nhận"**.
- Badge trạng thái có icon.
- Nút "Điều chỉnh" có tooltip.
- **Modal Thu tiền / Điều chỉnh mới:**
  - Header có icon, dòng phụ "thành viên · chiến dịch".
  - Thẻ "Còn lại phải thu" và "Còn lại sau thu/điều chỉnh", tính theo đúng công thức `AdjustDebtAction`.
  - Chọn phương thức thanh toán và loại điều chỉnh bằng thẻ có icon.
  - Nút "Thu toàn bộ".
  - Loại "Miễn nợ" ẩn ô số tiền.
  - Lỗi hiển thị trong modal thay cho `alert`.

### 2.9. Admin – Thành viên

- Thêm cột **Ngày tham gia**: chỉ hiện ngày, lấy `joined_at`, nếu không có thì dùng `created_at`.
- "Số đơn đã đặt" đổi thành **"Tổng đơn hàng"** (key mới `th_total_orders`; key cũ vẫn dùng cho báo cáo/export).
- Cột hành động rộng hơn; badge trạng thái có icon.
- **Sửa lỗi 500:** `@php(...)` một dòng đứng trước khối `@php … @endphp`. Đã gom biến vào khối `@php` ở đầu mỗi dòng.

### 2.10. Admin – Báo cáo

- Top 5 món / quán: huy chương vàng, bạc, đồng cho hạng 1–3 (`rankBadge()`); hạng 4–5 giữ số.
- Tỷ lệ tham gia từng chiến dịch: màu theo mức dưới 50%, dưới 80%, còn lại; có chú thích.
- Tên tab chỉ còn tiếng Việt (bỏ phần tiếng Anh trong ngoặc).
- Tab Công nợ, Tài trợ, Thành viên: icon copy sau email (`memberCellHtml()`).

### 2.11. Thay đổi SEO/landing có sẵn trong working tree

Các thay đổi này không thuộc đợt việc trên nhưng được commit cùng:
- Ảnh `home-intro.jpg` được thay bằng `home-intro.webp` (og:image, hero, video modal).
- `SeoTest` cập nhật theo ảnh mới.

---

## 3. Kiểm thử

- **Test mới:**
  - `AdminOrderEditTest`
  - `AdminCampaignListColumnsTest`
  - `AdminReportsVisualsTest`
  - `LoadingOverlayTest`
- **Test bổ sung vào file có sẵn:**
  - `AdminDashboardTrendTest`
  - `AdminDebtLedgerPageTest`
  - `AdminRoomUsersModalTest`
  - `AdminBroadcastNotificationTest`
  - `CampaignCartQuantityTest`
  - `RoomAnalyticsPeriodTest`
  - `UserRoomDashboardTopItemsTest`
- **Test sửa assertion do hành vi được yêu cầu thay đổi:**
  - `ErrorPagesTest`: 503 giờ là trang bảo trì.
  - `AdminFeatureTest`: bỏ cột khung giờ đặt.
  - `GuideImageLightboxTest`: đổi tên thuộc tính/key lightbox.
  - `AdminOrderOnBehalfTest`: option có thêm `data-search`.
- **Kết quả:** `php artisan test` → 567 pass, 1 fail (`SeoTest::test_private_routes_send_noindex_header`, đã fail từ trước).

## 4. Triển khai

- Không có migration mới.
- Sau khi pull cần build lại asset và xoá cache view:

```bash
cd src
npm run build
php artisan optimize:clear
```

## 5. Lưu ý

- Sửa đơn tính lại giá theo menu nên ghi đè các lần "Điều chỉnh giá" trước đó.
- Trang quản lý đơn không có bộ lọc theo ngày, nên link "Đơn hàng hôm nay" mở danh sách đơn của chiến dịch gần nhất.
- Link "Công nợ chưa thu" mở sổ công nợ không lọc, vì sổ chỉ lọc được một trạng thái; số trên thẻ gồm cả "chưa trả" lẫn "trả một phần".
- Các thay đổi giao diện chưa được kiểm tra bằng trình duyệt (Playwright MCP không kết nối được trong phiên làm việc).

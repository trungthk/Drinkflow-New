# Task: Giới hạn 1 chiến dịch hoạt động mỗi phòng, Bộ lọc & Chỉ báo tài trợ, Bổ sung tổng tiền tính riêng và Tối ưu UI/UX Loading Admin

**Ngày thực hiện:** 26/09/2026  
**Trạng thái:** ✅ Hoàn thành (Đã viết Unit & Feature tests đầy đủ, toàn bộ test pass)

---

## 1. Tổng quan mục tiêu

Đợt cập nhật này tập trung vào 5 nhóm tính năng và cải tiến chính cho phân hệ Quản trị (Admin):

1. **Ràng buộc mỗi phòng chỉ có 1 chiến dịch đang hoạt động (Single Running Campaign):**
   - Ngăn chặn tình trạng tạo mới, nhân bản (duplicate) hoặc kích hoạt (activate) chiến dịch khi phòng đã có chiến dịch đang `active` hoặc `closing`.
   - Khóa row cấp phòng (`lockForUpdate`) trong transaction để tránh race condition.
   - Trang Quản lý Đơn hàng (`/admin/{room}/orders`) tự động trỏ và hiển thị đơn hàng của chiến dịch đang chạy mới nhất (hoặc chiến dịch đóng gần nhất).
2. **Bộ lọc & Chỉ báo hình thức tài trợ chiến dịch (Sponsor Type Filters & Badges):**
   - Hỗ trợ lọc danh sách chiến dịch theo hình thức tài trợ (`none`, `full`, `item`, `budget`).
   - Hiển thị badge icon tài trợ (`volunteer_activism`) kèm tooltip tên người tài trợ / gói tài trợ cạnh tên chiến dịch.
   - Thêm badge đếm tổng số lượng chiến dịch phù hợp với bộ lọc.
3. **Bổ sung tổng tiền tính riêng vào Modal Kết thúc đơn (`CloseCampaignModal`):**
   - Tính toán và hiển thị trường "Tổng số tiền tính riêng" (`self_paid_total`) trong modal chốt kết thúc đơn để Admin nắm rõ trước khi đóng.
4. **Nâng cấp Badge trạng thái chiến dịch & Modal xác nhận hủy:**
   - Thay thế chấm tròn (`dotClass`) trong badge trạng thái chiến dịch bằng Material Symbol icons tương ứng (`task_alt`, `schedule`, `edit_document`, `hourglass_top`, `event_busy`, `inventory_2`, `history_toggle_off`).
   - Nâng cấp giao diện Modal xác nhận hủy chiến dịch (`#cancel-campaign-modal`) chuyên nghiệp hơn với icon cảnh báo và nút thao tác có icon.
5. **Tối ưu UX Loading & Skeleton khi tìm kiếm / lọc dữ liệu Admin:**
   - Hỗ trợ Skeleton placeholder cho Bảng (`table[data-skeleton="table"]`) và Biểu đồ (`[data-skeleton="chart"]`) khi submit bộ lọc hoặc bấm nút Tải lại.
   - Chuẩn hóa nút Tải lại (`x-admin.reload-button`) và nút Lọc (`filter_alt`) thành dạng icon-only gọn gàng, hỗ trợ co giãn mượt mà khi hiển thị spinner loading (`submit-loading.js`).
   - Tinh chỉnh Tooltip biểu đồ thống kê Dashboard không bị tràn / khuất khi cuộn trang.
6. **Đồng bộ đa ngôn ngữ (vi / en / ja):**
   - Cập nhật đầy đủ các key dịch mới vào 3 tệp `lang/vi/admin.php`, `lang/en/admin.php`, `lang/ja/admin.php`.

---

## 2. Chi tiết các thay đổi kỹ thuật

### 2.1. Backend & Ràng buộc Chiến dịch đang chạy (Single Running Campaign)

- **Tạo Action mới `App\Actions\Campaign\EnsureNoRunningCampaignAction`:**
  - Nhận `$roomId` và `$exceptCampaignId` (tuỳ chọn).
  - Sử dụng `Room::query()->whereKey($roomId)->lockForUpdate()->first()` trong DB Transaction để ngăn race condition.
  - Kiểm tra xem có chiến dịch nào thuộc phòng đang có trạng thái trong `CampaignStatus::running()` (`active`, `closing`) hay không. Nếu có, ném `ValidationException` với message `admin.campaign_running_exists`.
- **Cập nhật các Actions liên quan:**
  - `CreateCampaignAction`: Tiêm `EnsureNoRunningCampaignAction` và gọi kiểm tra trước khi insert campaign mới.
  - `DuplicateCampaignAction`: Kiểm tra trước khi duplicate chiến dịch sang trạng thái Draft.
  - `TransitionCampaignAction::activate()`: Kiểm tra trước khi chuyển trạng thái sang `Active`.
  - `UpdateCampaignAction`: Kiểm tra khi cập nhật trạng thái chiến dịch thành `Active`.
- **Mở rộng `CampaignStatus` Enum:**
  - Thêm phương thức tĩnh `CampaignStatus::running()`: trả về `[CampaignStatus::Active, CampaignStatus::Closing]`.
  - Bổ sung helper `icon(bool $expired = false): string` trả về mã icon Material Symbol tương ứng cho từng trạng thái.
- **Mở rộng Model `Room` & `Campaign`:**
  - `Room::hasActiveCampaign()`: Cập nhật kiểm tra theo `CampaignStatus::running()`.
  - `Room::latestOrderCampaign()`: Truy vấn lấy chiến dịch đang chạy (`running`) mới nhất; nếu không có thì lấy chiến dịch đã đóng (`Closed`) mới nhất.
  - `Campaign::SPONSOR_TYPES`: Định nghĩa hằng số mảng các loại tài trợ `['none', 'full', 'item', 'budget']`.
- **Cập nhật Controllers:**
  - `Admin\CampaignController::create()`: Kiểm tra nếu `$room->hasActiveCampaign()` thì redirect về trang danh sách chiến dịch kèm thông báo lỗi flash (hoặc trả 422 JSON nếu là request JSON).
  - `Admin\OrderController::page()`: Thay vì lấy campaign `Active`, chuyển sang sử dụng `$room->latestOrderCampaign()`, lọc danh sách đơn hàng theo `campaign_id` của campaign này.
  - `Admin\CampaignController::page()`: Xử lý thêm tham số lọc `sponsor_type`, tính toán `$sponsorTypeFilters`, `$hasCampaignFilters`, `$totalCampaigns`.
- **Cập nhật Form Request `CampaignPageRequest`:**
  - Thêm rule validate cho `sponsor_type` (`['nullable', Rule::in(['all', ...Campaign::SPONSOR_TYPES])]`).

### 2.2. Bổ sung `self_paid_total` trong Modal Đóng Chiến Dịch

- `AdminCampaignDetailService::getCloseSummary()`:
  - Bổ sung đọc cột `line_subtotal` từ các `OrderItem` không thuộc đơn order dùm và có `is_self_paid = true`.
  - Tính tổng `self_paid_total` và trả về trong mảng summary JSON.
- `resources/views/components/admin/close-campaign-modal.blade.php`:
  - Thêm dòng hiển thị `close_summary_self_paid_total` (màu violet) kèm chú thích `close_summary_self_paid_total_hint` ("Món tính riêng, thành viên tự trả, không được tài trợ").

### 2.3. Cải tiến Giao diện & JavaScript (UI/UX Loading & Skeletons)

- **`resources/js/admin/loading.js`:**
  - Thêm `chartSkeletonHtml()` và `showContentSkeletons(root)`: Quét và hiển thị animation skeleton cho `table[data-skeleton="table"]` và placeholder thanh biểu đồ `[data-skeleton="chart"]`.
  - Thêm `initFilterFormSkeletons()`: Bắt sự kiện submit form có thuộc tính `data-skeleton-on-submit` để tự động kích hoạt skeleton.
  - Bắt sự kiện `pageshow` (`event.persisted`) để khôi phục giao diện nếu người dùng điều hướng back/forward cache.
- **`resources/js/shared/submit-loading.js` & `resources/js/admin/ui-enhancements.js`:**
  - `renderSubmitLoading()`: Bổ sung hỗ trợ các nút icon-only có `data-icon-only`, mở rộng `padding-inline` và giữ `whitespace-nowrap` khi nút chuyển sang trạng thái spinner đang xử lý.
  - Thêm `restoreSubmitLoading()`: Khôi phục lại style ban đầu khi timeout hoặc form hoàn tất.
- **`resources/views/components/admin/reload-button.blade.php`:**
  - Chuyển thành nút icon-only vuông bo góc (`h-9 w-9`), hiển thị tooltip "Tải lại", tích hợp `data-reload-page`.
- **`resources/views/components/admin/campaign-status-badge.blade.php`:**
  - Hiển thị Material Symbol icon trước nhãn trạng thái thay cho chấm màu.
- **Các trang Blade Admin được tối ưu skeleton & toolbar:**
  - `admin/campaigns.blade.php`: Dropdown lọc `sponsor_type`, nút filter icon-only, badge icon tài trợ kèm tooltip, modal xác nhận hủy cải tiến.
  - `admin/orders.blade.php`, `admin/debts.blade.php`, `admin/users.blade.php`, `admin/audit.blade.php`: Thêm `data-skeleton-on-submit` cho form lọc, `data-skeleton="table"` cho bảng dữ liệu, chuẩn hóa nút lọc icon-only.
  - `admin/dashboard.blade.php` & `dashboard.js`: Gắn `data-skeleton="chart"`, điều chỉnh tính toán tọa độ tooltip biểu đồ luôn nằm trong bounds hiển thị.

### 2.4. Bản dịch đa ngôn ngữ (Localization)

Đã thêm đồng bộ vào `src/lang/vi/admin.php`, `src/lang/en/admin.php`, `src/lang/ja/admin.php`:
- `close_summary_self_paid_total`: Tổng số tiền tính riêng / Total self-paid amount / 個人負担の合計金額
- `close_summary_self_paid_total_hint`: Món tính riêng, thành viên tự trả, không được tài trợ / Self-paid items, paid by the member and not sponsored / 個人負担の品目（本人支払い・補助対象外）
- `campaign_running_exists`: Phòng đang có chiến dịch hoạt động. Hãy kết thúc hoặc hủy chiến dịch đó trước khi tạo hay kích hoạt chiến dịch mới.
- `total_campaigns_badge`: Tổng số: :count chiến dịch / Total: :count campaigns / 合計: :count 件のキャンペーン
- `filter_all_sponsor_types`: Tất cả hình thức tài trợ / All sponsorship types / すべての補助タイプ

---

## 3. Danh sách các file thay đổi / tạo mới

### 3.1. Tạo mới (New Files)
- `src/app/Actions/Campaign/EnsureNoRunningCampaignAction.php`
- `src/tests/Feature/AdminSingleRunningCampaignTest.php`
- `src/tests/Feature/AdminCampaignSponsorFilterTest.php`

### 3.2. Chỉnh sửa (Modified Files)
- **Backend Actions & Enums:**
  - `src/app/Actions/Campaign/CreateCampaignAction.php`
  - `src/app/Actions/Campaign/DuplicateCampaignAction.php`
  - `src/app/Actions/Campaign/TransitionCampaignAction.php`
  - `src/app/Actions/Campaign/UpdateCampaignAction.php`
  - `src/app/Enums/CampaignStatus.php`
- **Models & Services & Requests:**
  - `src/app/Models/Campaign.php`
  - `src/app/Models/Room.php`
  - `src/app/Services/Admin/AdminCampaignDetailService.php`
  - `src/app/Http/Requests/CampaignPageRequest.php`
- **Controllers:**
  - `src/app/Http/Controllers/Admin/CampaignController.php`
  - `src/app/Http/Controllers/Admin/OrderController.php`
- **Views & Components:**
  - `src/resources/views/admin/campaigns.blade.php`
  - `src/resources/views/admin/orders.blade.php`
  - `src/resources/views/admin/debts.blade.php`
  - `src/resources/views/admin/users.blade.php`
  - `src/resources/views/admin/audit.blade.php`
  - `src/resources/views/admin/dashboard.blade.php`
  - `src/resources/views/components/admin/campaign-status-badge.blade.php`
  - `src/resources/views/components/admin/close-campaign-modal.blade.php`
  - `src/resources/views/components/admin/reload-button.blade.php`
- **JavaScript & Assets:**
  - `src/resources/js/admin.js`
  - `src/resources/js/admin/loading.js`
  - `src/resources/js/admin/dashboard.js`
  - `src/resources/js/admin/ui-enhancements.js`
  - `src/resources/js/shared/submit-loading.js`
- **Localization:**
  - `src/lang/vi/admin.php`
  - `src/lang/en/admin.php`
  - `src/lang/ja/admin.php`
- **Unit & Feature Tests:**
  - `src/tests/Unit/CampaignStatusBadgeTest.php`
  - `src/tests/Feature/AdminCampaignSubViewsTest.php`

---

## 4. Kết quả kiểm thử (Test Results)

Tất cả các Unit và Feature Test liên quan đều đã chạy và vượt qua 100%:

```bash
php artisan test tests/Feature/AdminSingleRunningCampaignTest.php tests/Feature/AdminCampaignSponsorFilterTest.php tests/Unit/CampaignStatusBadgeTest.php tests/Feature/AdminCampaignSubViewsTest.php
```

- `AdminSingleRunningCampaignTest`:
  - `test_new_campaign_is_blocked_while_another_is_running` (PASS)
  - `test_order_management_lists_only_latest_campaign_orders` (PASS)
- `AdminCampaignSponsorFilterTest`:
  - `test_campaign_list_filters_by_sponsor_type_and_marks_sponsored_campaigns` (PASS)
- `CampaignStatusBadgeTest`:
  - `test_every_status_has_a_distinct_badge_style` (PASS)
  - `test_badge_component_renders_enum_styles` (PASS)
- `AdminCampaignSubViewsTest`:
  - `test_close_summary_returns_latest_counts_for_close_modal` (PASS - đã bao gồm kiểm tra `self_paid_total`)

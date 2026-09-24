# Task: Modal kết thúc đơn lấy dữ liệu mới nhất & liên kết sức khỏe hệ thống Superadmin

**Ngày thực hiện:** 24/09/2026
**Trạng thái:** ✅ Hoàn thành (đã chạy `php artisan test` toàn bộ: 474 passed, 2 skipped; `npm run build` OK)
**Chưa làm:** chưa kiểm tra giao diện thật bằng Playwright. Trình duyệt ban đầu bị một phiên khác chiếm, sau đó chưa đăng nhập admin/superadmin.

## Tổng quan

- Admin: nút **"Kết thúc đơn"** (trang thông tin chiến dịch) và **"Đóng chiến dịch"** (dashboard) gọi API lấy dữ liệu mới nhất mỗi lần mở modal. Hai trang dùng chung một modal.
- Modal xác nhận hiển thị đầy đủ số liệu về món, chi phí, đơn hàng và thành viên.
- Superadmin: các dòng Hàng đợi / Socket.IO / Email / Lưu trữ file trên trang tổng quan có liên kết. Dòng Lưu trữ file hiển thị driver và dung lượng đã dùng / tối đa.

---

## 1. API tóm tắt trước khi đóng chiến dịch

- Route mới: `GET /admin/{room}/campaigns/{campaign}/close-summary` (`admin.campaigns.close-summary`), xử lý bởi `Admin\CampaignController::closeSummary`.
- Chỉ trả chiến dịch thuộc đúng phòng (`assertCampaign`, phòng khác trả 404).
- Logic nằm ở `AdminCampaignDetailService::getCloseSummary()`. Service dùng cùng quy tắc với trang thông tin chiến dịch nhưng chỉ đọc các cột cần thiết:
  - Bỏ đơn đã hủy (`status = cancelled` hoặc có `cancelled_at`).
  - **Món:**
    - Món order dùm là món thuộc đơn con (`parent_id` khác null). Món order dùm không thể là món tính riêng.
    - Món tính riêng là món có `is_self_paid = true` trong đơn không phải order dùm.
    - Món tự order là phần còn lại.
    - Ba nhóm không trùng nhau, cộng lại bằng tổng món.
  - **Chi phí:**
    - Tổng tạm tính = tổng `orders.subtotal`.
    - Tổng giảm giá = `campaign.discount`; tổng chi phí phát sinh = `campaign.delivery_fee`.
    - Tổng tài trợ: nếu loại tài trợ là `full` thì bằng toàn bộ gross total, các loại khác bằng tổng `sponsor_amount` của các đơn.
    - Tổng thanh toán cuối cùng = tạm tính − giảm giá + phát sinh − tài trợ, không âm.
  - **Thành viên** (chỉ tính thành viên phòng đang active):
    - Tổng đơn tính cả đơn order dùm.
    - "Chưa order": chưa có đơn và chưa từ chối.
    - "Không tham gia": đã từ chối (`campaign_participants.status = declined`) và chưa đặt.
  - `is_closable`: chiến dịch còn ở trạng thái `active`/`closing`.

## 2. Modal dùng chung `x-admin.close-campaign-modal`

- Component mới `resources/views/components/admin/close-campaign-modal.blade.php` và JS mới `resources/js/admin/close-campaign-modal.js`, đăng ký trong `admin.js`.
- Mở modal bằng bất kỳ phần tử nào có `data-close-campaign-open` và `data-campaign-id`.
- Mỗi lần mở:
  - Gọi API `close-summary` với `cache: 'no-store'`.
  - Lớp phủ "Đang tải dữ liệu mới nhất..." hiện trong lúc tải và nút xác nhận bị khóa.
  - Mở lại nhanh thì request cũ bị hủy (`AbortController`).
- Lỗi tải dữ liệu, hoặc chiến dịch không còn mở (vd admin khác vừa đóng), được báo ngay trong modal và nút xác nhận vẫn bị khóa, nên admin không thể chốt dựa trên số liệu cũ.
- Bố cục:
  - Bốn ô số món: tự order / order dùm / tính riêng / tổng món.
  - Khung **Chi phí**.
  - Khung **Đơn hàng & thành viên**.
  - Checkbox tự tạo công nợ, mặc định được chọn.
  - Đã bỏ tên quán và chữ "Món đặt" theo yêu cầu.
- Sau khi đóng thành công, modal phát sự kiện `admin:campaign-closed`:
  - Trang thông tin chiến dịch tải lại trang.
  - Dashboard gọi `preventDefault()` rồi tải lại dữ liệu dashboard tại chỗ.
- Dọn code cũ:
  - Xóa `executeCloseCampaign` và markup modal cũ trong `campaign-info.blade.php`.
  - Xóa modal "Đóng Chiến Dịch" cũ trên dashboard. Modal này có checkbox "Gửi thông báo Slack" nhưng không gửi giá trị lên server.
  - Xóa code điều khiển modal trong `dashboard.js` và thuộc tính `data-close-url-template`, `data-members-ordered-text`.
- Modal "Đóng chiến dịch" ở trang danh sách chiến dịch (`campaigns.blade.php`) **chưa** đổi sang modal chung vì không nằm trong yêu cầu.

## 3. Superadmin: liên kết trong khung "Sức khỏe hệ thống"

- `superadmin/dashboard.blade.php`: các dòng đổi thành liên kết như sau.
  - Hàng đợi mở `superadmin.queue.page`.
  - Socket.IO mở `superadmin.socket.page`.
  - Email và Lưu trữ file mở `superadmin.system.page`. Chưa có trang riêng cho lưu trữ.
- CSS `a.sa-health-row` trong `superadmin.css`: thêm hiệu ứng hover và focus-visible.
- `StorageHealthService::check()` trả thêm `driver`, `used_bytes`, `total_bytes`; `free_bytes` vẫn giữ.
  - Dung lượng là của **ổ đĩa chứa thư mục gốc của disk** (`disk_total_space` / `disk_free_space`), không phải tổng kích thước file của app.
  - Disk không phải local (vd S3) trả `null` và UI hiện "Không đọc được dung lượng".
- Dòng Lưu trữ file hiện thêm dòng phụ, ví dụ `local · Đã dùng 180,5 GB / 476,3 GB`.

## 4. Bản dịch (vi/en/ja)

- Thêm:
  - `admin.close_summary_loading`, `close_summary_failed`, `close_summary_not_closable`.
  - `close_summary_own_items`, `close_summary_proxy_items`, `close_summary_self_paid_items`.
  - `close_summary_money_section`, `close_summary_gross_subtotal`, `close_summary_discount_total`, `close_summary_extra_fee_total`, `close_summary_sponsor_total`, `close_summary_final_total`, `close_summary_final_hint`.
  - `close_summary_people_section`, `close_summary_orders_count`, `close_summary_ordered_users`, `close_summary_pending_users`, `close_summary_declined_users`.
  - `superadmin.dashboard.storage_usage`, `storage_usage_unknown`.
- Xóa key không còn dùng: `admin.close_early_title`, `close_early_confirm`, `close_early_notify_slack`, `confirm_close_now`, `members_ordered_unit`, `ordered_members_label`.

## 5. Test

- `AdminCampaignSubViewsTest`:
  - `test_close_summary_returns_latest_counts_for_close_modal`: kiểm tra đủ các chỉ số với đơn thường, món tính riêng, đơn order dùm, đơn đã hủy, thành viên từ chối và thành viên đã rời phòng.
  - `test_close_summary_is_scoped_to_the_current_room`.
  - `test_info_page_and_dashboard_render_shared_close_campaign_modal`.
- `SystemHealthTest`:
  - `test_storage_health_check_reports_capacity_of_a_local_disk`.
  - `test_dashboard_health_rows_link_to_their_detail_pages`.
  - Test round-trip có sẵn được bổ sung kiểm tra `driver`.

## Việc còn lại

- Kiểm tra giao diện thật bằng Playwright:
  - Modal trên `/admin/{room}/dashboard` và `/admin/{room}/campaigns/{id}/info` (chiến dịch đang mở).
  - Trang `/superadmin`.
- Xác nhận với người dùng:
  - Ý nghĩa "Tổng thanh toán cuối cùng": hiện tại là số sau khi trừ tài trợ.
  - Ý nghĩa dung lượng lưu trữ: hiện tại là dung lượng ổ đĩa, không phải hạn mức của app.

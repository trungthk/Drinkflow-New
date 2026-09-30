# UPG-07 – Vận hành, dọn dữ liệu & hardening

**Mục tiêu:** Kiểm soát tăng trưởng dữ liệu phụ trợ, tránh xử lý đồng bộ nặng khi room lớn, và thu hẹp bề mặt tấn công còn lại.

> Mỗi task con dưới đây phải được quản trị xác nhận bằng lời (nêu rõ mã task) trước khi thực hiện. Xem [README](README.md#1-quy-trình-bắt-buộc-xác-nhận-trước-khi-thực-hiện).

---

## UPG-07.1 – Gửi thông báo cho cả room qua queue + bulk insert

- **Mức độ:** 🟡 Thấp → 🟠 khi room lớn
- **Hiện trạng:** `UserNotificationService::toRoom` (`src/app/Services/Notification/UserNotificationService.php:31`) tạo từng `UserNotification` cho từng thành viên (1 INSERT + 1 event realtime mỗi người). Nếu chạy đồng bộ trong request của admin, room vài trăm người sẽ làm request chậm.
- **Phương án đề xuất:**
  1. Xác minh luồng gọi hiện tại chạy đồng bộ hay đã qua queue.
  2. Nếu đồng bộ: chuyển thành Job (`ShouldQueue`), `chunk` thành viên, `insert()` hàng loạt, và gửi realtime một event theo room thay vì theo từng người (nếu realtime server hỗ trợ).
- **Tiêu chí hoàn thành:** Test job tạo đủ thông báo cho mọi thành viên active; request của admin không còn vòng lặp insert. `php artisan test` PASS.
- **Lưu ý:** Cần `php artisan queue:work` chạy trên server (không dùng Docker).
- **Xác nhận của quản trị:** ⏳ Chưa xác nhận
- **Trạng thái thực hiện:** ⬜ Chưa thực hiện

---

## UPG-07.2 – Chính sách dọn dữ liệu phụ trợ

- **Mức độ:** 🟡 Thấp
- **Hiện trạng:** Các bảng chỉ tăng, không có lịch dọn:
  - `sessions` (driver database; GC chỉ chạy theo xác suất lottery);
  - `user_notifications`;
  - `room_user_devices` đã `revoked_at` hoặc quá hạn (liên quan UPG-02.3).
- **Phương án đề xuất:**
  1. Command `drinkflow:prune` (hoặc dùng `Prunable` trên model), đăng ký trong `routes/console.php` chạy hằng ngày.
  2. Thời gian giữ lại do quản trị chọn, ví dụ: thông báo đã đọc > 90 ngày, thiết bị bị thu hồi > 30 ngày, session hết hạn.
- **Tiêu chí hoàn thành:** Test command xoá đúng bản ghi quá hạn và giữ bản ghi còn hạn; `php artisan schedule:list` thấy lịch mới. `php artisan test` PASS.
- **Cần quản trị quyết định:** Thời gian giữ lại cho từng loại dữ liệu.
- **Xác nhận của quản trị:** ⏳ Chưa xác nhận
- **Trạng thái thực hiện:** ⬜ Chưa thực hiện

---

## UPG-07.3 – Giới hạn và escape từ khoá tìm kiếm LIKE

- **Mức độ:** 🟡 Thấp
- **Hiện trạng:**
  - `UserRoomCampaignService::searchCampaigns` và `UserOrdersService` (tìm kiếm đơn) đưa nguyên chuỗi người dùng vào `LIKE '%...%'`, không escape `%` / `_`.
  - Không giới hạn độ dài `q`.
  - Tìm kiếm đơn dùng `orWhereHas('items')` với LIKE hai phía, nên phải quét nhiều bản ghi.
- **Phương án đề xuất:**
  1. Validate `q` (`max:100`).
  2. Escape `%`, `_`, `\` bằng helper dùng chung.
  3. Giữ LIKE nhưng chỉ trong phạm vi đã lọc theo user/room; đánh giá FULLTEXT sau nếu cần.
- **Tiêu chí hoàn thành:** Test từ khoá chứa `%`, `_` cho kết quả đúng nghĩa chữ; `q` quá dài → 422. `php artisan test` PASS.
- **Xác nhận của quản trị:** ⏳ Chưa xác nhận
- **Trạng thái thực hiện:** ⬜ Chưa thực hiện

---

## UPG-07.4 – Lộ trình bỏ `unsafe-inline` / `unsafe-eval` khỏi CSP

- **Mức độ:** 🟡 Thấp (tồn đọng đã biết, khối lượng lớn)
- **Hiện trạng:**
  - `SecurityHeaders` vẫn cho phép `'unsafe-inline'`, `'unsafe-eval'` trong `script-src` vì Blade còn nhiều inline handler/script, cùng với Alpine.js và Tailwind CDN JIT.
  - Đợt kiểm tra này **không** phát hiện XSS: các chỗ `innerHTML` ở trang room/global/public đều escape, `body_html` escape trước khi render. Tuy vậy, CSP hiện tại gần như không có tác dụng chặn nếu sau này phát sinh XSS.
- **Phương án đề xuất (chỉ lập kế hoạch ở bước này):**
  1. Kiểm kê inline script/handler trong `resources/views/public`, `user`, `components/global`.
  2. Chuyển sang file JS build bằng Vite (đưa bản dịch qua `data-i18n`).
  3. Dùng nonce cho script còn lại; thay Tailwind CDN bằng bản build.
  4. Bật `Content-Security-Policy-Report-Only` với CSP chặt để đo trước khi áp dụng.
- **Tiêu chí hoàn thành bước 1:** Có danh sách kiểm kê và ước lượng khối lượng, trình quản trị để tách thành các task con mới.
- **Xác nhận của quản trị:** ⏳ Chưa xác nhận
- **Trạng thái thực hiện:** ⬜ Chưa thực hiện

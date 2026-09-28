# Task: Superadmin – xoá quản trị viên, logo giống Admin, bỏ "Zero Trust", style bảng & form lọc mới

**Ngày thực hiện:** 28/09/2026
**Trạng thái:** ✅ Hoàn thành.
- `php artisan test`: toàn bộ pass, trừ `SeoTest::test_private_routes_send_noindex_header` (đã fail từ trước).
- `npm run build` OK.

---

## 1. Tổng quan

1. **Xoá quản trị viên** ở danh sách và trang chi tiết. Các liên kết liên quan được dọn trong một transaction.
2. **Logo superadmin giống bên admin:** ô gradient xanh có icon ly nước, tên đậm, dòng phụ chữ mono.
3. **Bỏ khối "Đã áp dụng Zero Trust"** ở cuối sidebar.
4. **Style mới, hiện đại** cho toàn bộ 12 bảng trên 10 trang superadmin và các form lọc.

---

## 2. Chi tiết thay đổi

### 2.1. Xoá quản trị viên

- **Giao diện:**
  - `superadmin/admins.blade.php`: nút "Xoá" cho admin thường. Tài khoản superadmin vẫn hiện "Được bảo vệ".
  - `superadmin/admin-detail.blade.php`: nút "Xoá quản trị viên", ẩn khi đang xem chính mình. Xoá xong quay về danh sách.
  - Cả hai dùng modal xác nhận chung `openSuperadminConfirm`, có mô tả dữ liệu bị ảnh hưởng.
- **`ManageAdminAction::delete()`:**
  - Chặn tự xoá chính mình (`superadmin.actions.cannot_delete_self`).
  - Giữ luật cũ: phải còn ít nhất một superadmin đang hoạt động.
  - Ghi audit `admin.deleted`, kèm danh sách phòng từng được phân công.
  - `removeRelations()` dọn dữ liệu một cách tường minh, không chỉ dựa vào FK:
    - **Xoá:** `admin_rooms`, `admin_audit_logs` (pivot), `admin_notifications`, `crawler_previews`.
    - **Đặt NULL**, giữ lại dữ liệu: `campaigns.creator_admin_id`, `orders.placed_by_admin_id`, `debt_adjustments.admin_id`, `debt_payments.created_by_admin_id`, `system_settings.updated_by_admin_id`, `versions.created_by_admin_id`.
    - **Giữ lại** làm lịch sử truy vết: `audit_logs`, `security_events` (`actor_type = admin`).
- Route `DELETE /superadmin/admins/{admin}` đã có sẵn; không đổi.

### 2.2. Logo & sidebar

- `superadmin/layout.blade.php`:
  - logo là link về dashboard, dùng cùng icon SVG với `components/admin/layout.blade.php`;
  - bỏ khối `.superadmin-trust`.
- `resources/css/superadmin.css`:
  - brand 32px gradient `#006948 → #047857`, tiêu đề 16px đậm, dòng phụ mono 11px;
  - xoá toàn bộ CSS của `.superadmin-trust`.
- Xoá các key `superadmin.layout.zero_trust` và `superadmin.layout.root_authority` (vi/en/ja).

### 2.3. Bảng & form lọc (CSS, khối "Modern data tables & filter toolbar")

- **Bảng (`.sa-table-wrap` / `.sa-table`):**
  - khung bo góc có viền, header nền `#f8fafc` chữ in hoa nhỏ;
  - hàng thoáng (13×16px), hover có vạch nhấn xanh;
  - trạng thái không xuống dòng; `min-width` 640px, cuộn ngang trên mobile.
- **Form lọc** (`form.superadmin-actions`, `.sa-filters`, kể cả form lồng trong hộp thư Thông báo):
  - thanh công cụ nền nhạt, full chiều ngang, nằm dưới tiêu đề;
  - các ô cao 38px, ô tìm kiếm có icon kính lúp, select có mũi tên riêng;
  - mobile: ô tìm kiếm full hàng, select chia đôi, nút Lọc full hàng.
- **Sửa 2 lỗi có sẵn:**
  - Rule nền xám của `.status-pill` ghi đè màu riêng của từng trạng thái, khiến badge "Đã đóng" gần như vô hình (chữ trắng trên nền trắng). Đã sửa bằng `:where()`.
  - Header `th.text-right` / `th.text-center` không căn được, vì rule gốc `text-align: left` mạnh hơn.

---

## 3. Kiểm thử

- **Test mới** `SuperadminDeleteAdminTest` (5 test):
  - xoá admin: dọn liên kết, giữ dữ liệu chung, giữ audit, có ghi audit xoá;
  - không tự xoá được;
  - xoá superadmin khi vẫn còn superadmin khác;
  - nút xoá hiện ở danh sách và chi tiết, ẩn với chính mình;
  - layout dùng logo mới, không còn "Zero Trust".
- **Giao diện:** trình duyệt test đăng nhập bằng admin room (403 ở superadmin). Vì vậy tôi dựng HTML thật của 10 trang bằng giả lập đăng nhập superadmin, xem với CSS đã build ở desktop và mobile, rồi đã xoá file tạm.

## 4. Triển khai

```bash
cd src
npm run build
php artisan optimize:clear
```

## 5. Lưu ý

- Nhật ký hoạt động của admin bị xoá được **giữ lại** để truy vết. Nếu cần xoá cả nhật ký thì bổ sung sau.

# Task: Chỉnh giao diện Room và loạt chỉnh sửa mobile cho Admin

**Ngày thực hiện:** 28/09/2026
**Trạng thái:** ✅ Hoàn thành.
- `npm run build` OK. `php artisan test`: 577 pass, 2 skipped, 1 fail. Test fail là `SeoTest::test_private_routes_send_noindex_header`, có từ trước và không liên quan đợt này.
- Mọi thay đổi đã được kiểm tra trên trang thật bằng Playwright ở 390×844 (mobile) và 1440×900 (desktop), có đo kích thước phần tử và chụp ảnh. Ảnh lưu ở `.playwright-mcp/`.

> **Quy ước "mobile" trong đợt này:** dưới mốc `lg` (1024px), dùng class `max-lg:*` hoặc bố cục mặc định rồi khôi phục bằng `lg:*`. Theo yêu cầu, **desktop giữ nguyên** ở mọi trang. Riêng user code trong modal chi tiết đơn được bỏ ở mọi kích thước (xem 2.9).

---

## 1. Tổng quan

- **Room (thành viên):** biểu đồ "Top nhà tài trợ" chuyển sang donut. Sửa hàng tên thứ trong tuần bị tràn ở biểu đồ 7 ngày. Thanh tab tự cuộn tới tab đang active trên mobile. Modal "bị khoá trong room" phủ kín màn hình.
- **Admin, trang con của chiến dịch:** bỏ chữ thừa ở thẻ tóm tắt, cột tên món tối thiểu 200px, nút copy email.
- **Admin, mobile:** chỉnh bố cục form lọc, nút bấm, thanh thao tác hàng loạt và modal ở các trang:
  - Dashboard, Chiến dịch (danh sách / sửa)
  - Đơn hàng, Sổ công nợ, Thành viên
  - Báo cáo, Nhật ký hoạt động

---

## 2. Chi tiết thay đổi

### 2.1. Room – Dashboard (`/rooms/{slug}/dashboard`)

- **"Top nhà tài trợ"** (`resources/js/room/dashboard-charts.js`):
  - Chuyển từ bar chart sang **donut**. Giữa vòng hiển thị tổng tài trợ.
  - Chú thích cho mỗi nhà tài trợ: tên, số tiền, %.
  - Tooltip khi rê chuột lên lát hoặc dòng chú thích.
  - 5 màu phân loại theo thứ tự hạng cố định, đã chạy validator màu cho người mù màu.
  - Key mới: `room.dashboard.top_sponsors_total`.
- **"Số món & giá trị 7 ngày gần nhất":**
  - SVG trước đây dùng `h-full` nên đẩy hàng tên thứ tràn ra ngoài thẻ. Đổi sang `flex-1 min-h-0` trong khung flex dọc.
  - Hàng nhãn có `pb-4`; nhãn giờ nằm trọn trong thẻ, cách mép dưới 32px.

### 2.2. Room – Header & modal bị khoá

- **Thanh tab** (`components/room/header.blade.php`, `resources/js/room/header.js`):
  - Tab active có `aria-current="page"`.
  - `scrollActiveTabIntoView()` chỉ cuộn ngang thanh tab để tab active nằm giữa, trang không bị cuộn dọc.
- **Modal bị khoá** (`components/room/blocked-modal.blade.php`):
  - `<main>` của layout room dùng `space-y-6`, làm modal `fixed` bị cộng `margin-top` 24px.
  - Thêm `!m-0`, bỏ `h-screen w-screen min-*`. Modal giờ phủ đủ 0→100% màn hình.

### 2.3. Admin – Dashboard (mobile)

- Thẻ chiến dịch đang live: mã chiến dịch nằm dưới tên.
- "Xem Orders & Chỉnh Giá" full width; "Điều chỉnh chiến dịch" và "Đóng chiến dịch" chia đôi một hàng.

### 2.4. Admin – Danh sách chiến dịch (mobile)

- Hàng 1: tìm kiếm và trạng thái.
- Hàng 2: hình thức tài trợ, nút Lọc, "Bỏ bộ lọc" (nếu có), Tải lại.
- Từ `lg` trở lên các nhóm dùng `lg:contents`, quay về một hàng như cũ.

### 2.5. Admin – Món & đơn hàng của chiến dịch (`campaigns/{id}/orders`)

- Thẻ tóm tắt chỉ hiện số: bỏ chữ "nhóm món" và "Đã đặt món" sau số lượng.
- Nhãn `admin.original_subtotal` bỏ phần tiếng Anh ở vi/ja: "Tổng giá trị menu gốc".
- Cột tên món tối thiểu 200px ở các tab "Danh sách món gộp", "Danh sách đơn hàng" và "Món theo phòng ban":
  - Với `table-fixed`, trình duyệt bỏ qua `min-width` trên ô.
  - Vì vậy đặt độ rộng tối thiểu cho **bảng** = tổng các cột cố định + 200px (680 / 832 / 688px). Trên mobile bảng cuộn ngang trong khung.
- Thêm nút copy email ở tab "User không tham gia" và "User chưa phản hồi" (cơ chế `[data-copy]` dùng chung).

### 2.6. Admin – Quản lý đơn (`orders/manage`)

- **Form lọc (mobile):**
  - Hàng 1: tìm kiếm và trạng thái.
  - Hàng 2: loại đơn, Lọc, "Bỏ bộ lọc", Tải lại.
  - Hai nhóm dùng `contents`, cộng một phần tử ngắt dòng `basis-full` chỉ có trên mobile.
- **Banner chiến dịch (mobile):** "Khoá chiến dịch" và "Chiến dịch & menu" chia đôi full hàng.
- **Modal chi tiết đơn:**
  - Mobile: trạng thái nằm dưới tiêu đề.
  - Cột Món tối thiểu 200px trên mobile. Bảng dùng `table-colgroup table-fixed` để thoát khỏi rule CSS chung tách mỗi `tr` thành một bảng riêng; `max-lg:w-[200px]`; bảng tối thiểu 440px.
  - **Bỏ user code ở mọi kích thước**, chỉ còn email.
- **Thanh thao tác hàng loạt (mobile):**
  - Trước đây thanh rộng 526px, tràn khỏi màn hình 390px.
  - Giờ bám hai mép màn hình và chia 3 hàng: số đơn đã chọn + bỏ chọn / trạng thái + áp dụng / huỷ đơn full width.
  - `max-lg:z-[45]`: nổi trên nút hỗ trợ (z-40), dưới các modal (z-50).

### 2.7. Admin – Sổ công nợ (`debts/ledger`, mobile)

- Các hàng của form lọc:
  - Hàng 1: tìm kiếm.
  - Hàng 2: thành viên + trạng thái, chia đều bằng `basis-[calc(50%-0.25rem)]`.
  - Hàng 3: thời gian, Lọc, "Bỏ bộ lọc".
- Nhãn thời gian giữ một dòng.
- Popup chọn thời gian căn trái trên mobile. Trước đây popup tràn ra mép trái màn hình (−103px).

### 2.8. Admin – Danh bạ thành viên (`room-users/directory`, mobile)

- Form lọc: tìm kiếm một hàng; trạng thái, Lọc, "Bỏ bộ lọc", Tải lại cùng một hàng full width.
- Thanh thao tác khi tích checkbox: số người đã chọn một dòng; "Mở khoá", "Khoá", "Xoá" một dòng riêng, chia đều.

### 2.9. Admin – Báo cáo (`reports/analytics`), tab công nợ

- Cột Thành viên 26% → 30%, Trạng thái 15% → 19%; cột "Số đợt nợ" và các cột tiền thu lại tương ứng.
- `reportTableHtml()` nhận thêm tuỳ chọn `minWidth`. Bảng công nợ tối thiểu 44rem, trên mobile cuộn ngang.
- Kết quả đo:

  | | Thành viên | Trạng thái |
  |---|---|---|
  | Desktop | 284 → 328px | 164 → 208px |
  | Mobile | ~150 → 211px | ~86 → 134px |

### 2.10. Admin – Nhật ký hoạt động (`audit/logs`)

- **Vỡ layout mobile:**
  - Các thẻ `sr-only` (position absolute) trong bảng thoát khỏi khung `overflow-x-auto` không có `position`, kéo trang rộng 777px trên màn hình 390px.
  - Thêm `relative` cho khung cuộn. Trang hết tràn, và modal chi tiết hiển thị đúng.
- **Nút "Đặt lại", "Lọc", "Tải lại" (mobile):** có chữ sau icon, chia đều full hàng.
  - Key mới `admin.filter_button` ("Lọc" / "Filter" / "絞り込み").
  - Component `<x-admin.reload-button>` có thêm prop `label` (mặc định `false`, các trang khác không đổi).

### 2.11. Admin – Sửa chiến dịch (`campaigns/{id}/edit`, mobile)

- **Header:** tên chiến dịch nằm dưới "Chỉnh sửa chiến dịch" (ẩn dấu ":"). "Xem chi tiết" và "Lưu thay đổi" chia đều full hàng.
- **Data Gateway** (component dùng chung với trang tạo chiến dịch):
  - "4. Kết quả JSON từ AI Agent" nằm trên phần mô tả.
  - Ẩn badge "AI CONVERTER".
  - Nút "Nạp vào Menu" (key mới `admin.data_gateway_btn_apply_to_menu_short`) cùng hàng full width với "Import file kết quả".

---

## 3. Kiểm thử

- Các nhóm test liên quan đều pass sau từng thay đổi: Dashboard, Campaign sub-views, Orders, Debts, Room users, Filter controls, Reload button, Audit, Data Gateway.
- **Kết quả toàn bộ:** `php artisan test` → 577 pass, 2 skipped, 1 fail (`SeoTest::test_private_routes_send_noindex_header`, có từ trước).
- **Playwright:** đo vị trí/kích thước, kiểm tra trang không tràn ngang (`scrollWidth <= innerWidth`), chụp ảnh mobile và desktop, console không có lỗi JS.
- Khi thử thanh thao tác hàng loạt chỉ mở modal xác nhận huỷ rồi bấm "Huỷ bỏ", không thao tác lên dữ liệu thật.

## 4. Triển khai

- Không có migration trong phần giao diện.

```bash
cd src
npm run build
php artisan optimize:clear
```

## 5. Lưu ý

- Trên mobile, nút hỗ trợ nổi ở góc phải có thể che một phần nút ở cuối thẻ, ví dụ "Đóng chiến dịch" ở dashboard admin. Chưa xử lý vì ngoài phạm vi yêu cầu.
- Các nút Lọc / Tải lại ở trang Chiến dịch, Đơn hàng, Thành viên vẫn chỉ có icon trên mobile. Mới trang Nhật ký hoạt động hiện chữ.
- Hàng nút Data Gateway trên mobile rộng ~130px mỗi nút nên chữ xuống 2 dòng. Rút ngắn nhãn "Import file kết quả" nếu muốn một dòng.
- **Thay đổi có sẵn trong working tree, không thuộc đợt này:** `public/guide-content/user/03,04,05,07-*.md` (bớt 35 dòng) và ảnh `images/09-ho-so-room.png`.

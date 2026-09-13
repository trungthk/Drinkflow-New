# Blade UI, Layout Components & i18n Rules

## 1. Hệ thống Layout Components
Không duplicate cấu trúc HTML (doctype, head, meta, scripts, header, footer) trong từng file view. Bắt buộc sử dụng Blade Layout Components:

- **Các trang Public (Khách / Công khai):**
  - Sử dụng layout: `<x-public.layout title="Tiêu đề trang">...</x-public.layout>`
  - Áp dụng cho: Landing page (`/`), About (`/about`), Contact (`/contact`), Terms (`/terms`), Privacy (`/privacy`), FAQ (`/faq`).
- **Các trang User Global (Thông tin cá nhân người dùng):**
  - Sử dụng layout: `<x-global.layout title="Tiêu đề trang" active="me|rooms|orders|statistics|profile|notifications">...</x-global.layout>`
  - Layout đã tích hợp sẵn Header (`<x-global.header>`), Footer (`<x-global.footer>`), Navigation tabs, CSRF token và Alpine.js.
  - Áp dụng cho: `/me`, `/me/rooms`, `/me/orders`, `/me/statistics`, `/me/profile`, `/me/notifications`.

## 2. Đa ngôn ngữ (i18n)
- Tuyệt đối không hardcode chuỗi text tiếng Việt hoặc tiếng Anh trực tiếp trong template Blade nếu chưa qua hàm localize.
- Sử dụng cú pháp `__('group.key')` hoặc `@lang('group.key')`.
- Khi bổ sung tính năng mới hoặc chỉnh sửa text, phải cập nhật đồng bộ các file từ điển trong `src/lang/`:
  - `src/lang/vi/*.php` (Tiếng Việt)
  - `src/lang/en/*.php` (Tiếng Anh)
  - `src/lang/ja/*.php` (Tiếng Nhật)

## 3. Captcha cho Form công khai
- Các biểu mẫu gửi thông tin công khai (như form liên hệ, gửi góp ý không đăng nhập) phải có cơ chế chống spam bằng `mews/captcha`.
- Luôn kiểm tra môi trường chạy có `ext-gd` hay không. Khi render ảnh captcha, đảm bảo đường dẫn font TTF là hợp lệ và có cơ chế fallback.
- Đi kèm với Rate Limiting ở route/middleware (ví dụ: `throttle:5,1` cho 5 requests/phút).

## 4. Tối ưu hình ảnh (Image Lazy Loading)
- Mọi thẻ `<img>` trong template Blade bắt buộc phải có thuộc tính `loading="lazy"`.
- Bắt buộc có thuộc tính `alt` có nghĩa (hoặc dùng hàm dịch `__('...')`), kèm theo xử lý lỗi fallback `onerror="this.onerror=null;this.src='...'"` để tránh broken image trên giao diện.

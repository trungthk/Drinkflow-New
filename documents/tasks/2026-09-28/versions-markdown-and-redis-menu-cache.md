# Task: Trang phiên bản hiển thị Markdown và cache menu phiên bản bằng Redis

**Ngày thực hiện:** 28/09/2026
**Trạng thái:** ✅ Hoàn thành.
- `php artisan test`: toàn bộ pass, trừ `SeoTest::test_private_routes_send_noindex_header` (đã fail từ trước, không liên quan).
- Đã kiểm tra trang thật `/versions/v2.3.1` bằng Playwright.

---

## 1. Tổng quan

1. **`/versions/{version}` hiển thị `changelog` dạng Markdown.** Trước đây nội dung in thô, còn nguyên `##`, `**`, `-`.
2. **Bỏ dòng tóm tắt dự phòng "Thông tin chi tiết về bản cập nhật DrinkFlow."**
3. **Menu phiên bản** (danh sách bên trái trang versions) được lưu trong **Redis**. Khi superadmin tạo, sửa hoặc xoá phiên bản thì key cache bị xoá.

---

## 2. Chi tiết thay đổi

### 2.1. Hiển thị Markdown

- `resources/views/public/versions.blade.php` render `changelog` qua `Version::renderMarkdown()`:
  - HTML thô trong nội dung bị escape;
  - link không an toàn (`javascript:`, `data:`…) bị loại.
- Dùng lại style Markdown `.guide-article` sẵn có của trang Hướng dẫn, không thêm CSS mới.
- `VersionService`: phiên bản không có tóm tắt riêng thì `summary = null`, nên không hiện khối tóm tắt.

### 2.2. Cache menu phiên bản bằng Redis

- `App\Models\Version`:
  - **`MENU_CACHE_KEY = 'versions:menu'`.**
  - **`menuRows()`:** đọc danh sách phiên bản (mới nhất trước) từ store `cache.versions_store` bằng `rememberForever`.
    - Chỉ cache dữ liệu thô từ DB; phần dịch theo ngôn ngữ làm sau khi đọc, nên một key dùng được cho cả vi/en/ja.
    - Nếu Redis không kết nối được: đọc thẳng DB và ghi cảnh báo `Versions menu cache unavailable…` vào log. Trang không bị lỗi.
  - **`clearMenuCache()`:** được gọi trong event `saved` / `deleted`. Superadmin tạo, sửa hay xoá phiên bản đều đi qua Eloquent nên luôn xoá được key.
- `VersionService::getAllVersions()` lấy dữ liệu từ `Version::menuRows()`.
- `config/cache.php`: thêm `versions_store` = `env('VERSIONS_CACHE_STORE', 'redis')`.
- `config/database.php`: `REDIS_CLIENT` mặc định là `phpredis` nếu có extension, không thì `predis`.
- `composer.json` / `composer.lock`: thêm `predis/predis ^3.0`, vì máy chưa có extension `phpredis`.
  - Lúc cài phải dùng `--ignore-platform-req=ext-zip`: PHP CLI trong PATH (XAMPP) thiếu `ext-zip`, mà package xuất Excel sẵn có yêu cầu.
  - Không có package nào khác bị nâng cấp.
- `phpunit.xml`: `VERSIONS_CACHE_STORE=array`, để test không gọi Redis thật.

---

## 3. Kiểm thử

- **`VersionPageTest`, 2 test mới:**
  - `changelog` render thành Markdown, HTML thô bị escape, không còn dòng tóm tắt dự phòng;
  - key `versions:menu` được tạo khi xem trang và bị xoá khi tạo, sửa, xoá phiên bản.
- **Redis thật** (Laragon, cổng 6379, client `predis`):
  - xem trang thì key được tạo;
  - lưu một phiên bản thì key bị xoá;
  - lần đọc sau key được tạo lại.
- **Playwright:** `/versions/v2.3.1` hiện đúng tiêu đề, danh sách và chữ đậm, không còn ký tự `##`. Console không có lỗi.

## 4. Triển khai

```bash
cd src
composer install          # cài predis/predis
php artisan config:clear
php artisan optimize:clear
```

- `.env`: đặt `REDIS_CLIENT=predis` (hoặc bỏ dòng này để tự chọn) nếu server **không** có extension `phpredis`. Có thể cần thêm `REDIS_HOST` / `REDIS_PORT` / `REDIS_PASSWORD`.
- Tuỳ chọn: `VERSIONS_CACHE_STORE` để đổi store (mặc định `redis`).

## 5. Lưu ý

- Ở máy local hiện tại, `.env` đặt `REDIS_CLIENT=phpredis` nhưng PHP không có extension này. Mỗi request vì vậy đọc DB và ghi cảnh báo vào log cho tới khi sửa `.env` như mục 4.

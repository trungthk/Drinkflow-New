# Environment & Database Rules: PostgreSQL (No Supabase)

## 1. Môi trường triển khai
- Thư mục gốc chứa file cấu hình Docker: `docker-compose.yml`, `Dockerfile`.
- Ứng dụng Laravel 12 nằm trọn vẹn trong `src/`.
- Dịch vụ cơ sở dữ liệu: Container `drinkflow-new-postgres-1` (dùng image `postgres:16-alpine`), kết nối qua host `postgres`, port `5432`, database `drinkflow`.

## 2. Nghiêm cấm Supabase
- **Dự án KHÔNG dùng Supabase.**
- Tuyệt đối không cài đặt hoặc import các thư viện Supabase client (như `@supabase/supabase-js`, SDK client-side).
- Browser/Client không bao giờ được phép kết nối hoặc query cơ sở dữ liệu trực tiếp. Toàn bộ đọc/ghi DB phải thông qua backend Laravel.

## 3. Quy tắc PostgreSQL trong Laravel
- **Ép kiểu dữ liệu (Strict Type Casting):**
  PostgreSQL kiểm tra kiểu dữ liệu rất nghiêm ngặt. Việc so sánh kiểu `BIGINT` với `VARCHAR` mà không cast sẽ sinh lỗi trực tiếp:
  ```sql
  -- LỖI TRÊN POSTGRES:
  SELECT * FROM rooms WHERE id = 'phong-ban-a';
  ```
  Do đó:
  - Khi tra cứu theo mã định danh chuỗi (`slug`, `code`), luôn chỉ định rõ cột tương ứng: `Room::where('slug', $slug)->firstOrFail()`.
  - Không truyền chuỗi slug vào phương thức tìm kiếm theo `id` (`Room::find($param)`).
- **Kiểu dữ liệu tiền tệ:**
  - Tiền tệ VND luôn lưu trữ dạng `BIGINT` hoặc `DECIMAL`, không dùng kiểu `FLOAT` để tránh sai số dấu phẩy động.
- **Tính toàn vẹn & Race Conditions:**
  - Bắt buộc khai báo ràng buộc khóa ngoại (Foreign Key) và chỉ mục duy nhất (Unique Index/Partial Unique Index) ở cấp độ cơ sở dữ liệu.
  - Các thao tác nhiều bước liên quan đến tiền bạc, đơn hàng hoặc nợ phải được bọc trong `DB::transaction()`.

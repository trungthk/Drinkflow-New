# DRINKFLOW AGENT RULES & SYSTEM INSTRUCTIONS

> **QUAN TRỌNG**: Tài liệu này định nghĩa các quy tắc cốt lõi cho mọi AI Agent (Antigravity, Gemini, Copilot, Codex, Cursor, Claude) khi làm việc trên dự án DrinkFlow. Mọi Agent phải tự động tuân thủ nghiêm ngặt các quy định dưới đây.

---

## 1. MÔI TRƯỜNG DỰ ÁN (ENVIRONMENT)

- **Mã nguồn Laravel**: Nằm trong thư mục con `src/` (chứa `composer.json`, `artisan`, `app/`, `resources/`, `routes/`, v.v.).
- **Docker Compose**: Dự án chạy qua Docker Compose tại root (`docker-compose.yml`):
  - `postgres`: Container `drinkflow-new-postgres-1` (PostgreSQL 16 Alpine, port 5432, db `drinkflow`, user `drinkflow`).
  - `app`: Container `drinkflow-new-app-1` (PHP 8.3-fpm/Nginx, port 8080).
  - `realtime`: Container `drinkflow-new-realtime-1` (Node.js Socket.IO Gateway, port 3001).
- **Thực thi lệnh**:
  - Chạy lệnh Artisan / Composer / PHP qua container:
    ```bash
    docker exec drinkflow-new-app-1 php artisan <command>
    ```
  - Hoặc từ host (nếu chạy local PHP/composer tương thích): `cd src && php artisan <command>`.
  - Kiểm tra test luôn dùng:
    ```bash
    docker exec drinkflow-new-app-1 php artisan test
    ```

---

## 2. QUY TẮC CƠ SỞ DỮ LIỆU: POSTGRESQL (TUYỆT ĐỐI KHÔNG SUPABASE)

1. **Tuyệt đối KHÔNG kết nối Supabase**:
   - Dự án **KHÔNG sử dụng Supabase** hay bất kỳ client-side database SDK nào (`@supabase/supabase-js`, REST direct, ...).
   - Cơ sở dữ liệu chính thức duy nhất là **PostgreSQL 16** (kết nối nội bộ qua container `postgres`).
   - Mọi thao tác truy vấn dữ liệu phải chạy qua Eloquent / Query Builder / Actions tại Laravel backend. Trình duyệt / Client không bao giờ được truy vấn trực tiếp DB.
2. **Tuân thủ Ép kiểu Chặt chẽ (Strict Typing) của PostgreSQL**:
   - Không so sánh string với column kiểu bigint/integer (ví dụ: không `where('id', $slug)` vì sẽ văng lỗi `operator does not exist: bigint = text`).
   - Luôn phân biệt rõ `id` (bigint) và `slug` / `code` (string) khi tìm kiếm Room, Campaign, Order.
   - Khi viết migration: dùng đúng data type (`bigInteger`, `string`, `decimal`/`unsignedBigInteger` cho tiền tệ).
   - Invariant quan trọng phải có Unique Index / Foreign Key ở DB level để chống race condition.

---

## 3. CẤU TRÚC CONTROLLER VÀ PHÂN QUYỀN ACTOR

Mọi Controller phải tuân thủ phân nhóm thư mục chặt chẽ:

```text
src/app/Http/Controllers/
├── User/
│   ├── Global/                   <-- TẤT CẢ trang thông tin người dùng toàn hệ thống (/me/*, /profile, /rooms, v.v.)
│   │   ├── DashboardController.php   (/me)
│   │   ├── ProfileController.php     (/me/profile, /me/payments, /me/devices, /me/feedback, /profile)
│   │   ├── RoomsController.php       (/me/rooms, POST /me/rooms/join, /rooms)
│   │   ├── OrdersController.php      (/me/orders, /history)
│   │   ├── AnalyticsController.php   (/me/statistics, /analytics)
│   │   └── NotificationController.php(/me/notifications, /notifications, /notifications/{id}/read)
│   ├── DashboardController.php   <-- Phạm vi Room cụ thể (/rooms/{room:slug})
│   ├── CampaignController.php    <-- Phạm vi Room cụ thể
│   ├── OrderController.php       <-- Phạm vi Room cụ thể
│   ├── DebtController.php        <-- Phạm vi Room cụ thể
│   └── RoomSettingController.php <-- Phạm vi Room cụ thể
├── Admin/                        <-- Quản trị Room (Owner / Room Admin)
└── Superadmin/                   <-- Quản trị toàn hệ thống
```

- **Quy tắc**: Các trang User Global không được để controller bừa bãi ở thư mục gốc `User/`. Phải đưa vào `App\Http\Controllers\User\Global\*`.

---

## 4. GIAO DIỆN & BLADE LAYOUT COMPONENTS

1. **Layout dùng chung**:
   - Trang Public (Landing, About, Contact, Terms, Privacy, FAQ): Bắt buộc dùng layout component `<x-public.layout title="...">...</x-public.layout>`.
   - Trang User Global (`/me/*`): Bắt buộc dùng layout component `<x-global.layout title="...">...</x-global.layout>`.
   - Header & Footer: Sử dụng `<x-global.header>` và `<x-global.footer>`.
2. **Không duplicate code layout**: Không copy nguyên khối `<html>`, `<head>`, scripts, header, footer sang từng file view.
3. **Đa ngôn ngữ (Multilingual)**:
   - Tất cả văn bản hiển thị trên UI phải dùng hàm `__('file.key')` hoặc `@lang('file.key')`.
   - Đảm bảo bổ sung key dịch đồng bộ vào cả 3 ngôn ngữ: `src/lang/vi/`, `src/lang/en/`, `src/lang/ja/`.

---

## 5. CAPTCHA VÀ BẢO MẬT FORM PUBLIC

- Mọi form public có nguy cơ bị spam (ví dụ form liên hệ /contact) phải tích hợp mã bảo vệ Captcha (`mews/captcha`).
- Do môi trường container có thể có sự khác biệt về fonts hoặc thư mục, cấu hình Captcha phải:
  - Kiểm tra tính sẵn sàng của extension `ext-gd`.
  - Chỉ định rõ ràng đường dẫn font hợp lệ hoặc fallback an toàn nếu thiếu font hệ thống.
  - Áp dụng Rate Limiter theo IP cho endpoint gửi form (`throttle:X,Y`).

---

## 6. QUY TRÌNH KIỂM THỬ VÀ ĐẢM BẢO CHẤT LƯỢNG (DEFINITION OF DONE)

Một tác vụ/tính năng chỉ hoàn thành khi:
1. Logic nghiệp vụ hoàn thiện, controller thin, validation rõ ràng.
2. Không leak secret, token, credentials.
3. Đã chạy kiểm tra tự động:
   ```bash
   docker exec drinkflow-new-app-1 php artisan test
   ```
   **Toàn bộ các test case phải PASS 100% (xanh lá cây), không có warning hay syntax error.**
4. Dọn dẹp sạch sẽ các controller, view, route rác không còn sử dụng.

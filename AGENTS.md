# DRINKFLOW AGENT RULES & SYSTEM INSTRUCTIONS

> **QUAN TRỌNG**: Tài liệu này định nghĩa các quy tắc cốt lõi cho mọi AI Agent (Antigravity, Gemini, Copilot, Codex, Cursor, Claude) khi làm việc trên dự án DrinkFlow. Mọi Agent phải tự động tuân thủ nghiêm ngặt các quy định dưới đây.

---

## 1. MÔI TRƯỜNG DỰ ÁN (ENVIRONMENT)

* **Framework chính**: Laravel / PHP.
* **Database**: MySQL.
* **Mã nguồn Laravel** nằm trong thư mục con `src/`, bao gồm:

```text
src/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/
├── resources/
├── routes/
├── storage/
├── tests/
├── artisan
├── composer.json
└── ...
```

### Không sử dụng Docker

Dự án **KHÔNG sử dụng Docker hoặc Docker Compose**.

Agent tuyệt đối không được:

* Tạo hoặc yêu cầu `docker-compose.yml`.
* Tạo Docker container cho PHP, MySQL, Redis, Node.js hoặc các service khác.
* Sử dụng `docker exec`, `docker compose exec`, `docker run` hoặc các lệnh Docker khác để thực thi ứng dụng.
* Giả định rằng project đang chạy bên trong container.

### Thực thi lệnh Laravel

Mọi lệnh Laravel phải chạy trực tiếp từ thư mục `src/`:

```bash
cd src
php artisan <command>
```

Ví dụ:

```bash
php artisan migrate
php artisan migrate:status
php artisan db:seed
php artisan optimize:clear
php artisan route:list
php artisan queue:work
php artisan schedule:run
php artisan test
```

### Composer

Composer chạy trực tiếp:

```bash
cd src
composer install
composer update
composer dump-autoload
```

### Development Server

Khi cần chạy Laravel development server:

```bash
cd src
php artisan serve
```

Không tự động chuyển sang Docker hoặc tạo container nếu môi trường local thiếu dependency. Agent phải báo rõ dependency nào đang thiếu.

---

## 2. QUY TẮC CƠ SỞ DỮ LIỆU: MYSQL

### 2.1. Database chính thức

Database chính thức duy nhất của DrinkFlow là:

**MySQL**

Cấu hình thông qua `.env` của Laravel, ví dụ:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=drinkflow
DB_USERNAME=root
DB_PASSWORD=
```

Không hardcode thông tin database trực tiếp trong source code.

### 2.2. Tuyệt đối KHÔNG sử dụng Supabase

Dự án **KHÔNG sử dụng Supabase**.

Agent tuyệt đối không được:

* Cài `@supabase/supabase-js`.
* Sử dụng Supabase SDK.
* Gọi Supabase REST API.
* Gọi trực tiếp database từ Browser/JavaScript.
* Tạo Supabase client.
* Đưa `SUPABASE_URL`, `SUPABASE_KEY`, service role key hoặc các credential Supabase vào project.
* Đề xuất Supabase làm database, authentication hoặc storage nếu không có yêu cầu thay đổi kiến trúc rõ ràng.

Mọi thao tác database phải đi qua Laravel Backend:

```text
Browser / Client
      ↓
Laravel Route
      ↓
Controller
      ↓
Action / Service
      ↓
Eloquent / Query Builder
      ↓
MySQL
```

Client tuyệt đối không truy vấn trực tiếp MySQL.

### 2.3. Eloquent và Query Builder

Ưu tiên sử dụng:

* Eloquent ORM.
* Laravel Query Builder.
* Repository/Action/Service khi logic phức tạp.

Không sử dụng raw SQL nếu Eloquent hoặc Query Builder có thể giải quyết rõ ràng và hiệu quả.

Nếu bắt buộc sử dụng raw SQL, câu SQL phải tương thích với **MySQL**.

### 2.4. Kiểu dữ liệu MySQL

Khi viết migration phải lựa chọn data type phù hợp:

```php
$table->id();
$table->unsignedBigInteger('user_id');
$table->string('slug');
$table->string('code');
$table->decimal('amount', 15, 2);
$table->boolean('is_active')->default(true);
$table->timestamp('processed_at')->nullable();
```

Luôn phân biệt rõ:

* `id`: integer / bigint.
* `slug`: string.
* `code`: string.
* Tiền tệ: `decimal` hoặc integer theo quy ước nghiệp vụ.
* Boolean: `boolean`.
* JSON data: `json`.

Không viết logic phụ thuộc vào PostgreSQL-specific syntax hoặc PostgreSQL-specific functions.

### 2.5. Database Constraints

Các invariant quan trọng phải được bảo vệ ở database level khi phù hợp:

* Primary Key.
* Foreign Key.
* Unique Index.
* Composite Unique Index.
* Index cho các column thường xuyên search/filter/join.

Ví dụ:

```php
$table->unique(['room_id', 'user_id']);
$table->index(['room_id', 'status']);
```

Không chỉ dựa vào application validation cho các constraint có khả năng xảy ra race condition.

---

## 3. CẤU TRÚC CONTROLLER VÀ PHÂN QUYỀN ACTOR

Mọi Controller phải tuân thủ phân nhóm thư mục chặt chẽ:

```text
src/app/Http/Controllers/

├── User/
│   ├── Global/
│   │   ├── DashboardController.php
│   │   ├── ProfileController.php
│   │   ├── RoomsController.php
│   │   ├── OrdersController.php
│   │   ├── AnalyticsController.php
│   │   └── NotificationController.php
│   │
│   ├── DashboardController.php
│   ├── CampaignController.php
│   ├── OrderController.php
│   ├── DebtController.php
│   └── RoomSettingController.php
│
├── Admin/
│
└── Superadmin/
```

### User Global

Các controller trong:

```text
App\Http\Controllers\User\Global\
```

phục vụ các chức năng toàn hệ thống như:

```text
/me
/me/profile
/me/payments
/me/devices
/me/feedback
/me/rooms
/me/orders
/me/statistics
/me/notifications
/profile
/history
/analytics
/notifications
```

### Room Scope

Các controller trực tiếp trong:

```text
App\Http\Controllers\User\
```

dành cho chức năng thuộc phạm vi một Room cụ thể:

```text
/rooms/{room:slug}
/rooms/{room:slug}/campaigns
/rooms/{room:slug}/orders
/rooms/{room:slug}/debts
/rooms/{room:slug}/settings
```

### Quy tắc

Các trang User Global không được đặt controller tùy tiện tại thư mục gốc `User/`.

Phải sử dụng:

```php
App\Http\Controllers\User\Global\*
```

Phân quyền phải được kiểm tra tại Laravel backend thông qua:

* Middleware.
* Policy.
* Gate.
* Form Request authorization.
* Service/Action nếu có business rule đặc biệt.

Không tin tưởng role hoặc permission gửi từ client.

---

## 4. GIAO DIỆN & BLADE LAYOUT COMPONENTS

### 4.1. Layout dùng chung

Trang Public:

```blade
<x-public.layout title="...">
    ...
</x-public.layout>
```

Áp dụng cho:

* Landing.
* About.
* Contact.
* Terms.
* Privacy.
* FAQ.

Trang User Global (`/me/*`):

```blade
<x-global.layout title="...">
    ...
</x-global.layout>
```

Header và Footer:

```blade
<x-global.header />
<x-global.footer />
```

### 4.2. Không duplicate layout

Không copy nguyên khối:

```html
<html>
<head>
<body>
<header>
<footer>
<script>
```

sang từng Blade view.

Logic/layout dùng chung phải được đưa vào:

* Blade Component.
* Layout.
* Partial phù hợp.

### 4.3. Đa ngôn ngữ

Tất cả văn bản hiển thị trên UI phải sử dụng:

```php
__('file.key')
```

hoặc:

```blade
@lang('file.key')
```

Khi thêm key mới phải cập nhật đồng bộ:

```text
src/lang/vi/
src/lang/en/
src/lang/ja/
```

Không hardcode text hiển thị trực tiếp trên Blade nếu nội dung đó cần dịch.

---

## 5. CAPTCHA VÀ BẢO MẬT FORM PUBLIC

Mọi public form có nguy cơ spam phải có biện pháp bảo vệ.

Ví dụ:

```text
/contact
/feedback
/register
```

nếu phù hợp với nghiệp vụ.

Captcha sử dụng:

```text
mews/captcha
```

Cấu hình phải:

* Kiểm tra `ext-gd`.
* Chỉ định font hợp lệ.
* Có fallback an toàn nếu thiếu font hệ thống.
* Không làm ứng dụng crash nếu môi trường thiếu font không bắt buộc.

Endpoint submit phải áp dụng Rate Limiter phù hợp:

```php
throttle:X,Y
```

Ví dụ:

```php
Route::post('/contact', ...)
    ->middleware('throttle:5,1');
```

Validation và Captcha phải được kiểm tra ở backend.

Không dựa hoàn toàn vào JavaScript validation.

---

## 6. QUY TẮC GIAO DIỆN HÌNH ẢNH (IMAGE LAZY LOADING)

Tất cả thẻ `<img>` trong:

```text
src/resources/views/
```

phải có:

```html
loading="lazy"
```

Ví dụ:

```blade
<img
    src="{{ $image }}"
    alt="{{ __('product.image_alt') }}"
    loading="lazy"
    onerror="this.src='/images/placeholder.png'"
>
```

Ảnh phải có:

* `alt` có ý nghĩa.
* Hỗ trợ đa ngôn ngữ khi phù hợp.
* Fallback khi ảnh lỗi.
* Không làm vỡ layout khi URL ảnh không tồn tại.

---

## 7. QUY CHUẨN CODE PHP

### 7.1. Strict Types

Các PHP class/service/controller/action phải khai báo:

```php
<?php

declare(strict_types=1);
```

### 7.2. Type Hinting đầy đủ

Parameters:

```php
public function findRoom(string $slug): ?Room
```

Return type:

```php
public function calculateTotal(Order $order): int
```

Properties:

```php
private OrderService $orderService;
```

Hạn chế sử dụng `mixed` nếu có thể xác định type cụ thể.

### 7.3. PHPDoc

Các method cần documentation rõ ràng khi cần thiết:

```php
/**
 * Calculate the total amount of an order.
 *
 * @param Order $order Order being calculated.
 * @return int Total amount in VND.
 *
 * @throws InvalidOrderException When the order data is invalid.
 */
public function calculateTotal(Order $order): int
{
    // ...
}
```

PHPDoc phải bổ sung thông tin hữu ích, không chỉ lặp lại type hint một cách máy móc.

### 7.4. Controller phải Thin

Controller chỉ nên chịu trách nhiệm:

```text
Request
   ↓
Validation / Authorization
   ↓
Action / Service
   ↓
Response / View
```

Không đưa business logic lớn trực tiếp vào Controller.

Logic phức tạp phải chuyển sang:

```text
App\Actions\
App\Services\
App\Domain\
```

tùy kiến trúc hiện tại của project.

### 7.5. Service ưu tiên tái sử dụng

Service không được gắn cứng với một Controller.

Ưu tiên thiết kế để có thể tái sử dụng giữa:

* Web Controller.
* API Controller.
* Console Command.
* Queue Job.
* Scheduled Task.
* Realtime integration.

### 7.6. Enum / Const

Không hardcode magic string/magic number rải rác:

```php
if ($order->status === 'completed') {
}
```

Ưu tiên PHP Enum:

```php
enum OrderStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
```

Hoặc Model Const nếu phù hợp với codebase:

```php
public const STATUS_ACTIVE = 'active';
```

Áp dụng cho:

* Status.
* Type.
* Role.
* Permission.
* Channel.
* Payment method.
* Các giá trị nghiệp vụ cố định.

---

## 8. ARTISAN, QUEUE VÀ SCHEDULE

Dự án không sử dụng Docker để chạy Laravel worker.

### Artisan

Luôn chạy từ:

```bash
cd src
```

Sau đó:

```bash
php artisan <command>
```

### Queue

Development:

```bash
php artisan queue:work
```

hoặc:

```bash
php artisan queue:listen
```

Production có thể sử dụng process manager của server như Supervisor để duy trì:

```bash
php artisan queue:work
```

Không đề xuất Docker container chỉ để chạy queue.

### Scheduler

Kiểm tra scheduler:

```bash
php artisan schedule:list
```

Chạy thủ công:

```bash
php artisan schedule:run
```

Production sử dụng cron gọi Laravel Scheduler theo cấu hình server.

---

## 9. QUY TRÌNH DATABASE MIGRATION

Mọi thay đổi schema phải thông qua Laravel Migration.

Tạo migration:

```bash
php artisan make:migration <migration_name>
```

Chạy migration:

```bash
php artisan migrate
```

Kiểm tra:

```bash
php artisan migrate:status
```

Rollback khi cần:

```bash
php artisan migrate:rollback
```

Không chỉnh schema production thủ công nếu thay đổi đó cần được đồng bộ qua source code.

Không tạo SQL migration riêng cho PostgreSQL hoặc Supabase.

Migration phải tương thích với **MySQL**.

---

## 10. BẢO MẬT

Agent phải đảm bảo không leak:

* Password.
* API Token.
* Secret Key.
* Database Credentials.
* Session.
* Cookie.
* OAuth credentials.
* Private key.

Không đưa secret vào:

```text
Git repository
Blade
JavaScript bundle
API response
Log
Error page
Public config endpoint
```

Các secret phải nằm trong:

```text
.env
```

và được truy cập thông qua Laravel configuration.

Không sử dụng `env()` trực tiếp trong business code.

Ưu tiên:

```php
config('services.example.key')
```

thay vì:

```php
env('EXAMPLE_KEY')
```

---

## 11. QUY TRÌNH KIỂM THỬ VÀ ĐẢM BẢO CHẤT LƯỢNG

Một task/tính năng chỉ được xem là hoàn thành khi:

1. Logic nghiệp vụ đã hoàn thiện.
2. Controller thin.
3. Validation rõ ràng.
4. Authorization được kiểm tra.
5. Type hint đầy đủ.
6. PHPDoc phù hợp.
7. Không leak secret/token/credentials.
8. Migration tương thích MySQL.
9. Không có dependency Supabase.
10. Không có dependency PostgreSQL-specific.
11. Không yêu cầu Docker để chạy.
12. Không còn controller/view/route/code rác do task tạo ra.

### Kiểm tra Laravel

Luôn chạy trực tiếp trong `src/`:

```bash
cd src
php artisan test
```

Toàn bộ test case liên quan phải **PASS**.

Không sử dụng:

```bash
docker exec ... php artisan test
```

### Kiểm tra Route

Khi thay đổi route/controller:

```bash
php artisan route:list
```

### Clear Cache

Khi thay đổi config/routes/views hoặc gặp cache cũ:

```bash
php artisan optimize:clear
```

### Database

Nếu task có migration:

```bash
php artisan migrate:status
php artisan migrate
```

Không chạy `migrate:fresh` trên database có dữ liệu thật nếu không được yêu cầu rõ ràng.

---

## 12. DEFINITION OF DONE CHO AI AGENT

Trước khi thông báo task đã hoàn thành, Agent phải tự kiểm tra:

```text
[ ] Không sử dụng Supabase
[ ] Không sử dụng PostgreSQL
[ ] Database tương thích MySQL
[ ] Không sử dụng Docker
[ ] Lệnh Laravel sử dụng php artisan
[ ] Controller đúng namespace/folder
[ ] Controller thin
[ ] Validation đầy đủ
[ ] Authorization đầy đủ
[ ] Không leak secret
[ ] UI text hỗ trợ vi/en/ja
[ ] Blade image có loading="lazy"
[ ] Migration có index/constraint phù hợp
[ ] Code sử dụng strict_types
[ ] Type hint đầy đủ
[ ] Enum/Const thay magic value khi phù hợp
[ ] Test liên quan đã chạy
[ ] php artisan test PASS
[ ] Không còn debug code
[ ] Không còn file/route/view không sử dụng
```

Nếu không thể chạy một bước kiểm tra do môi trường thiếu dependency, Agent phải báo rõ:

1. Bước nào chưa chạy được.
2. Nguyên nhân.
3. Lệnh cần chạy để xác nhận.

**Không được tự tuyên bố test PASS nếu Agent chưa thực sự chạy test.**

---

## 13. NGUYÊN TẮC ƯU TIÊN

Khi Agent gặp yêu cầu không rõ ràng, ưu tiên theo thứ tự:

```text
Security
   ↓
Data Integrity
   ↓
Business Logic
   ↓
Backward Compatibility
   ↓
Maintainability
   ↓
Performance
   ↓
UI/UX
```

Không tự ý thay đổi kiến trúc lớn, database engine hoặc dependency nền tảng khi task hiện tại không yêu cầu.

Các quyết định mặc định của DrinkFlow:

```text
Backend     : Laravel / PHP
Database    : MySQL
ORM         : Eloquent / Query Builder
Frontend    : Blade / JavaScript theo codebase hiện tại
Command     : php artisan
Container   : Không sử dụng Docker
Supabase    : Không sử dụng
PostgreSQL  : Không sử dụng
```

Các Agent phải giữ các nguyên tắc trên nhất quán trong toàn bộ quá trình phát triển DrinkFlow.

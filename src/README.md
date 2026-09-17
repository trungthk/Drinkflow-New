# DrinkFlow Laravel Application

Đây là ứng dụng Laravel 12 của DrinkFlow. Thư mục `src/` chứa web application, nghiệp vụ, schema PostgreSQL, giao diện Blade/React, bản dịch và test. Gateway Socket.IO nằm ở [realtime/README.md](../realtime/README.md); hướng dẫn tổng thể nằm tại [README root](../README.md).

## Phạm vi

- Public: landing page, terms, versions, contact và chuyển ngôn ngữ.
- User global: dashboard, hồ sơ, Room, orders, payments, statistics, notifications, devices, feedback và blocked-account flow.
- User theo Room: campaign/menu, cart, order, payment, debt, analytics, profile và room notifications.
- Admin theo Room: campaign lifecycle/menu, order management, debt ledger, room users, payment accounts, notification channels, reports, audit và food crawler.
- Superadmin: quản trị Room/Admin/Global User, campaigns/debts, system settings, maintenance, versions, audit, security, failed queues và socket monitoring.

Mọi truy vấn đi qua Eloquent/Query Builder/Action/Service trong Laravel. PostgreSQL 16 là database chính thức; không dùng Supabase hoặc client-side database SDK.

## Công nghệ

- PHP 8.2+ (Docker dùng PHP 8.3), Laravel 12, Eloquent.
- PostgreSQL 16 qua `pdo_pgsql`; session, cache và queue mặc định dùng database.
- Vite 7, Tailwind CSS 4, Blade components, React 19, Axios và React Toastify.
- Maatwebsite Excel, Intervention Image, Mews Captcha, Spatie Browsershot/Puppeteer.
- PHPUnit 11, Mockery và Laravel Pint.

## Cấu trúc

```text
src/
|-- app/
|   |-- Actions/         # Use case và state transition
|   |-- Constants/       # Hằng số ứng dụng
|   |-- Enums/           # Role/status/period nghiệp vụ
|   |-- Events/Listeners/ # Domain event, notification, realtime
|   |-- Exports/         # Excel/CSV exports
|   |-- Http/Controllers/ # Public, User, Admin, Superadmin
|   |-- Http/Middleware/  # Actor, Room access, locale, maintenance
|   |-- Http/Requests/    # Validation + authorization
|   |-- Models/           # Eloquent models
|   |-- Services/         # Domain services tái sử dụng
|   |-- View/             # View composers/components
|-- database/migrations/  # PostgreSQL schema và constraints
|-- database/seeders/     # DatabaseSeeder, VersionSeeder
|-- lang/{vi,en,ja}/      # Bản dịch đồng bộ
|-- resources/views/      # Blade layouts/components/pages
|-- resources/js/         # Frontend entrypoints
|-- routes/               # web.php, user.php, admin.php, superadmin.php
|-- tests/                # Feature và Unit tests
|-- config/               # app, services, crawler, retention...
`-- public/build/          # Vite production assets (generated)
```

## Actor và middleware

| Actor | Guard/middleware | Phạm vi |
| --- | --- | --- |
| User | `auth:web`, `global.user`, `room.user` | Global user và membership `RoomUser` hiện tại |
| Admin | `auth:admin`, `admin.room` | Các Room được phân công |
| Superadmin | `auth:admin`, `superadmin` | Toàn hệ thống |

`GlobalUser` là danh tính toàn hệ thống; `RoomUser` là membership riêng trong từng Room. Không dùng ID của hai lớp thay thế cho nhau. Controller global user phải nằm trong `App\Http\Controllers\User\Global`; logic nghiệp vụ đặt trong Action/Service.

## Thiết lập

### Docker (khuyến nghị)

Từ thư mục root:

```powershell
Copy-Item .env.example .env
Copy-Item src/.env.example src/.env
docker compose build app
docker compose up -d
```

Compose kết nối app/queue tới PostgreSQL bằng `DB_HOST=postgres`, tự migrate trước khi mở Laravel tại `http://localhost:8080`. Queue worker chạy cùng VPS để xử lý database queue và retry realtime; gateway chạy tại cổng `3001`.

### Chạy riêng trên host

Yêu cầu PHP 8.2+, Composer 2, Node.js 22 và PostgreSQL 16:

```powershell
Set-Location src
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

Nếu PostgreSQL chạy trên host, đổi `DB_HOST=127.0.0.1`. Chạy server/queue/scheduler/Vite riêng hoặc dùng `composer run dev`.

## Environment quan trọng

File mẫu là `.env.example`; không commit `.env`.

| Nhóm | Biến |
| --- | --- |
| App | `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL`, `APP_LOCALE` |
| Database | `DB_CONNECTION=pgsql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` |
| Runtime | `SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database` |
| Google | `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`, `GOOGLE_ALLOWED_DOMAINS` |
| Realtime | `REALTIME_URL`, `REALTIME_PUBLIC_URL`, `REALTIME_INTERNAL_SECRET`, `SOCKET_TOKEN_SECRET` (optional; shared with gateway) |
| Crawler | `FOOD_CRAWLER_CHROME_PATH`, binary Node/npm, timeout/headless/profile |
| Mail | `MAIL_*`; local mặc định ghi log OTP |

Sau khi đổi env:

```powershell
php artisan optimize:clear
```

## Database và seed

Migration bao phủ identity, room, campaign/menu, order, debt/payment, notification, audit/security, feedback, crawler và system settings. Không sửa migration đã chạy ở môi trường chia sẻ; tạo migration mới và giữ foreign key/unique constraint.

```powershell
php artisan migrate
php artisan db:seed
```

Seeder tạo dữ liệu demo và credential local:

| Vai trò | Email | Mật khẩu |
| --- | --- | --- |
| Admin | `admin@drinkflow.local` | `password` |
| Superadmin | `superadmin@drinkflow.local` | `password` |

Chỉ dùng credential này trong development; phải đổi/vô hiệu hóa trước production.

## Route groups

- Public: `/`, `/terms`, `/versions/{version?}`, `/contact`, `/lang/{locale}`.
- User auth: `/auth/google`, callback Google và `/logout`.
- User global: `/me/*`, `/profile`, `/notifications`, `/rooms`, `/history`, `/analytics`.
- User Room: `/rooms/{room}/*`.
- Admin auth/profile: `/admin/login`, forgot password, `/admin/profile/*`.
- Admin Room: `/admin/{room}/*`.
- Superadmin: `/superadmin/*`.

Ứng dụng không có `routes/api.php`; JSON/action endpoint nằm trong route web theo middleware actor. Liệt kê route bằng:

```powershell
php artisan route:list
```

## Nghiệp vụ, crawler và realtime

Campaign, Order và Debt được thay đổi qua Action/Service với enum trạng thái, transaction và authorization theo Room. Listener tạo notification và gửi event tới gateway qua `REALTIME_URL` + `X-Realtime-Secret`.

Admin có thể preview/import menu ShopeeFood bằng Chromium/Puppeteer. Notification channel hỗ trợ Chatwork, Slack, Telegram và webhook; secret phải được lưu mã hóa và che khi hiển thị.

Chi tiết token, channel, internal emit và health endpoint xem [realtime/README.md](../realtime/README.md).

## Queue và scheduler

```powershell
php artisan queue:work --tries=3
php artisan schedule:work
php artisan drinkflow:prune-operational-data
```

Scheduler gọi prune lúc 02:15 mỗi ngày để dọn audit log, admin notification đã đọc và crawler preview hết hạn.

## Kiểm thử và format

Theo quy ước dự án, chạy test bằng container:

```powershell
docker exec drinkflow-new-app-1 php artisan test
```

Ngoài Docker:

```powershell
php artisan test
php artisan test --filter=RealtimeFlowTest
vendor/bin/pint --test
npm run build
```

Docker engine và PostgreSQL phải healthy trước khi dùng `docker exec`.

## Quy tắc phát triển

- File PHP mới khai báo `declare(strict_types=1);`, type hint đầy đủ và PHPDoc.
- Dùng Enum/model constants cho status, role, type; không rải magic string.
- Dùng Form Request cho validation/authorization; controller giữ thin.
- UI dùng layout/component chung và cập nhật dịch đồng thời trong `lang/vi`, `lang/en`, `lang/ja`.
- Mọi Blade `<img>` có `loading="lazy"`, alt có nghĩa và fallback `onerror`.
- Không truy vấn PostgreSQL từ browser, không thêm Supabase SDK.
- Migration mới phải tôn trọng PostgreSQL strict typing, foreign key và unique index.
- Không đưa env, OAuth secret, password, token hoặc webhook secret vào commit/log.

Xem thêm [quy tắc dự án](../AGENTS.md), [coding convention](../documents/rules-code-convention.md) và [tài liệu tính năng](../documents/features/overview.md).

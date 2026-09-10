# DrinkFlow Feature Progress Checklist

## Database runtime

- ✅ Docker Compose PostgreSQL 16 service with persistent volume, healthcheck, and app dependency is running.
- ✅ PHP image includes `pdo_pgsql`; all migrations run successfully against PostgreSQL.
- ✅ Repeatable `DatabaseSeeder` provides demo admin, rooms, users, campaign menu, order, debt, and notifications.

> Checklist tiến độ dùng chung cho team và các AI tiếp theo. Chỉ đánh dấu ✅ khi đã có code và kiểm tra thực tế; ⬜ là chưa triển khai; 🟨 là mới có một phần. Khi hoàn thành một mục, cập nhật file này cùng thay đổi code và ghi test tương ứng.

**Cập nhật gần nhất:** 2026-09-10  
**Trạng thái repository:** Laravel 12 skeleton + domain foundation  
**Đặc tả gốc:** `overview.md`, `user.md`, `admin.md`, `superadmin.md`, `database.md`, `rules-code-convention.md`

**Quy ước vận hành:** AI tiếp theo phải đọc file này trước khi làm việc, không lặp lại mục ✅, tự triển khai mục ⬜ theo thứ tự ưu tiên, chạy kiểm tra phù hợp và cập nhật checklist ngay sau khi hoàn thành.

## Quy ước bàn giao

- Không lặp lại các mục ✅; kiểm tra file/route/action được ghi ở cột ghi chú trước khi làm tiếp.
- Mỗi feature hoàn chỉnh phải có: business rule, validation, authorization, transaction/constraint phù hợp, audit/realtime nếu cần và feature test.
- `GlobalUser` khác `RoomUser`; dữ liệu order/debt luôn scope qua `room_user_id` và `room_id`.
- Laravel là source of truth. Không thêm Supabase JS trực tiếp vào browser và không đưa business logic vào Socket.IO gateway.
- Sau mỗi nhóm thay đổi chạy `php artisan test` và `php artisan migrate` (không dùng destructive `migrate:fresh` trên dữ liệu thật).

## Nền tảng dữ liệu & kiến trúc

- ✅ Migration identity: `global_users`, `oauth_identities`.
- ✅ Migration Room: `rooms`, `room_users`, `room_user_devices`.
- ✅ Migration admin: `admin_accounts`, `admin_rooms`.
- ✅ Migration campaign/menu: `campaigns`, `campaign_items`, `campaign_item_sizes`, `campaign_item_toppings`.
- ✅ Migration order/debt: `orders`, `order_items`, `order_item_toppings`, `debts`.
- ✅ Partial unique index cho một active order / campaign / room user.
- 🟨 Debt adjustments/payments đã có schema, `RecordDebtPaymentAction` và `AdjustDebtAction` (partial payment + audit); settlement/sponsor allocation còn thiếu.
- ✅ Notification channels và system notification channels (credential column có encrypted cast/masked model).
- ✅ System settings, room settings, versions.
- ✅ Audit logs và security events.
- ⬜ PostgreSQL-specific migration verification và consistency constraints liên bảng.
- ⬜ Factories/seeders cho toàn bộ domain.
- ⬜ Chuẩn hóa/xóa dần bảng `users` mặc định Laravel sau khi hoàn tất migration auth.

## Model, Enum và auth foundation

- ✅ Domain models và các quan hệ chính trong `src/app/Models`.
- ✅ Enums: GlobalUserStatus, RoomUserStatus, CampaignStatus, OrderStatus, DebtStatus, AdminRole.
- ✅ Guard `web`/provider GlobalUser và guard `admin`/provider AdminAccount.
- ✅ `ResolveGlobalUser` kiểm tra global status.
- ✅ `ResolveRoomUser` kiểm tra membership/status theo Room.
- ✅ `EnsureAdminRoomAccess` kiểm tra `admin_rooms` và Superadmin bypass.
- ✅ Google OAuth/OpenID Connect redirect + state CSRF + code/token exchange + userinfo callback (`GoogleAuthController` và `GoogleOAuthService`).
- ✅ Global User dedup/link theo `(provider, provider_user_id)` trong `GoogleOAuthService`.
- ✅ Device token issuance/hash verification/revoke và Secure/HttpOnly cookie với expiry policy.
- ✅ Admin login API có throttling, session regeneration, logout, captcha session và room switcher.
- ⬜ Superadmin safeguard (không tự demote/xóa superadmin cuối cùng).

## Actor 1 — User

### Identity, Room và device

- ✅ `JoinRoomAction` tạo/resolve Room User duy nhất và sinh `user_code` theo Room.
- ✅ Join action bind/update trusted device hash, verified/last-seen timestamps.
- ✅ First-visit Room endpoint và màn hình xác thực/join Google.
- ✅ Returning-device flow khôi phục Global User từ trusted cookie, không OAuth lại.
- 🟨 New-device re-authentication theo Room và revoke service đã có; UI/admin revoke còn thiếu.
- 🟨 Room list API chỉ hiển thị membership active; dashboard/profile UI đã có, room switcher nâng cao còn thiếu.
- 🟨 Profile Global User API và danh sách Room đã có; profile UI đã có, cập nhật profile/re-auth sync còn thiếu.
- 🟨 Room User status API (Admin) và Global User status API (Superadmin) đã có transaction + audit; UI, revoke device và safeguard role còn thiếu.

### Campaign, order và thanh toán

- ✅ `StoreOrderRequest` validation cơ bản.
- ✅ `CreateOrderAction` kiểm tra campaign/Room/User/item/size/topping và tính tiền server-side.
- ✅ Order snapshot item/size/topping và transaction tạo order.
- ✅ Endpoint `POST /rooms/{room}/campaigns/{campaign}/orders`.
- 🟨 Campaign list/detail API có eager-load, pagination, smart search/category filter, dashboard card và màn hình đặt món; option nâng cao còn thiếu.
- ✅ Single-active-order API response includes existing order status/link; đặt món UI hiển thị link xem đơn hiện tại.
- 🟨 Order history API global/Room scope, filters, pagination, history UI và order detail UI; filter UI nâng cao còn thiếu.
- 🟨 Theo dõi order status có timeline và polling tự động trên trang chi tiết; Socket.IO realtime nâng cao còn thiếu.
- ✅ VietQR endpoint trả amount/account authoritative theo Order và PaymentAccount.
- 🟨 User debt API theo Room và personal analytics Room/global đã có; UI biểu đồ còn thiếu.
- 🟨 Notification order, campaign và payment reminder có DB/API/listener; realtime gateway và UI còn thiếu.
- ✅ Translation resources nền cho `vi`, `en`, `ja`.

## Actor 2 — Admin

- ✅ Room-scoped middleware và route group `auth:admin`.
- ✅ Endpoint đóng campaign: `POST /admin/{room}/campaigns/{campaign}/close`.
- ✅ `CloseCampaignAction` lock transaction, chuyển trạng thái và sinh debt cơ bản.
- ✅ Admin login UI/API, captcha session, throttling, session regeneration, logout và room switcher.
- ✅ Admin concept UI: shared sidebar/topbar theo Room, dashboard KPI/realtime, live orders, campaign/menu, công nợ, thành viên, VietQR/cấu hình, thông báo, báo cáo/audit và room switcher.
- ✅ Dashboard KPI, campaign/order list, active room users, debt summary và recent orders theo Room.
- ✅ Campaign CRUD/lifecycle: create, edit, activate, cancel, duplicate, archive, close và split bill.
- ✅ Food crawler: URL validation, timeout/retry, preview lưu tạm và import vào campaign.
- ✅ Menu item create/update/archive; size/topping create/update/delete và availability theo campaign.
- ✅ Live orders list/status transition, edit/cancel/delete/unlock API, Socket.IO room events và dashboard tự refresh theo `order.created/updated/deleted`.
- ✅ Item aggregator, CSV export và settlement/split-bill theo campaign đã đóng.
- ✅ Debt payment/adjustment/status, audit và split-bill allocation theo Room.
- ✅ Room User list/detail, block/unblock và revoke device với dữ liệu nhạy cảm được mask.
- ✅ Payment account list/create/update/delete, default active/Room và thông tin tài khoản được mask.
- ✅ Room settings và default sponsor/payment account.
- ✅ Room notification channels với credentials encrypted/masked, test và disable.
- ✅ Room reports với khoảng thời gian, pagination và thống kê món/cửa hàng.
- ✅ Room audit log chỉ trong Room được assign.

## Actor 3 — Superadmin

- ✅ Superadmin role middleware + protected routes + global dashboard API + UI dashboard đã có.
- ✅ Room CRUD/status enable/disable/archive API + counts + audit.
- ✅ Admin CRUD, block/unblock, password reset, role promotion/demotion API + audit.
- ✅ Assign/remove Admin ↔ Room API + audit.
- ✅ Global User search/list/detail/block/unblock/merge/membership/device revoke API.
- ✅ OAuth identity metadata API không expose credential/token.
- ✅ Global campaign/debt overview, force close/cancel và export debt API.
- ✅ Global notification channels API với encrypted/masked credentials.
- ✅ System settings typed API với secret masking + audit.
- ✅ Maintenance enable/disable/schedule API và middleware enforcement.
- ✅ System reset yêu cầu password + exact phrase + audit, giữ lại superadmin.
- ✅ Global audit logs API.
- ✅ Security center API và ghi nhận failed login/OAuth events.
- 🟨 Socket.IO monitoring API; số liệu live phụ thuộc gateway health integration.
- ✅ Queue/failed jobs list/retry/delete API.
- ✅ Version management/changelog/force refresh API.

> Phần Superadmin đã có backend API, authorization và các trang UI chính theo concept design; các form nâng cao có thể tiếp tục polish trong các vòng sau.

## Realtime, security và quality

- ✅ Laravel short-lived signed socket token endpoint và Node Socket.IO gateway verify token, auto-join channel.
- ✅ Channel authorization: `user:{roomUserId}`, `room:{roomId}`, `admin:{adminId}`, `superadmin`, `system`.
- ✅ Event bridge emit sau action transaction với payload nhỏ; client fetch lại dữ liệu authoritative.
- 🟨 Domain events/listeners cho campaign/order/notification đã có; debt realtime event còn có thể mở rộng.
- ⬜ Secrets encrypted, masked; không leak API/HTML/log/audit.
- ⬜ Rate limiting, correlation/request ID, structured logging.
- 🟨 Feature tests: OAuth/domain, dedup, join/device, cross-room denial, order lock, close campaign, debt, block, socket auth, secret exposure. OAuth/domain, dedup, join/device, trusted-token revoke, order lock và Admin/Superadmin authorization đã có test.
- ✅ Baseline tests hiện tại: `php artisan test` — 26 tests passed (86 assertions), không có failure.

## Các file đã triển khai

- `src/database/migrations/2026_09_10_000000_create_drinkflow_core_tables.php`
- `src/database/migrations/2026_09_10_010000_create_campaign_item_sizes_table.php`
- `src/app/Actions/User/JoinRoomAction.php`
- `src/app/Actions/Order/CreateOrderAction.php`
- `src/app/Actions/Campaign/CloseCampaignAction.php`
- `src/app/Http/Middleware/ResolveGlobalUser.php`
- `src/app/Http/Middleware/ResolveRoomUser.php`
- `src/app/Http/Middleware/EnsureAdminRoomAccess.php`
- `src/app/Http/Controllers/Superadmin/PageController.php`
- `src/resources/views/superadmin/`
- `src/resources/css/app.css`
- `src/routes/web.php`

## Việc nên làm tiếp theo

1. Bổ sung polish cho modal/form nâng cao của Superadmin theo concept design.
2. Mở rộng realtime debt event, rate limiting và structured logging.
3. Hoàn thiện các luồng UI nâng cao còn thiếu của User/Admin.

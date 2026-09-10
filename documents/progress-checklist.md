# DrinkFlow Feature Progress Checklist

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
- 🟨 Device token issuance/hash verification/revoke đã có `DeviceTrustService`; cookie Secure/HttpOnly và expiry policy còn thiếu.
- 🟨 Admin login API có throttling, session regeneration và logout; captcha/UI còn thiếu.
- ⬜ Superadmin safeguard (không tự demote/xóa superadmin cuối cùng).

## Actor 1 — User

### Identity, Room và device

- ✅ `JoinRoomAction` tạo/resolve Room User duy nhất và sinh `user_code` theo Room.
- ✅ Join action bind/update trusted device hash, verified/last-seen timestamps.
- ⬜ First-visit UI và redirect qua Google authentication.
- ⬜ Returning-device flow không yêu cầu OAuth lại.
- ⬜ New-device re-authentication và device revoke.
- 🟨 Room list API chỉ hiển thị membership active; chuyển Room/UI còn thiếu.
- 🟨 Profile Global User API và danh sách Room đã có; UI và cập nhật profile/re-auth sync còn thiếu.
- 🟨 Room User status API (Admin) và Global User status API (Superadmin) đã có transaction + audit; UI, revoke device và safeguard role còn thiếu.

### Campaign, order và thanh toán

- ✅ `StoreOrderRequest` validation cơ bản.
- ✅ `CreateOrderAction` kiểm tra campaign/Room/User/item/size/topping và tính tiền server-side.
- ✅ Order snapshot item/size/topping và transaction tạo order.
- ✅ Endpoint `POST /rooms/{room}/campaigns/{campaign}/orders`.
- 🟨 Campaign list/detail đã có User API, eager-load menu và pagination; smart search/filter UI/API còn thiếu.
- ⬜ Single-active-order error UX và unlock/cancel flow.
- 🟨 Order history API đã có scope Room User, filters và pagination; confirmation/detail UI còn thiếu.
- ⬜ Theo dõi order status và realtime `order.updated`.
- ⬜ VietQR authoritative amount/account.
- ⬜ User debt và personal analytics theo Room/global.
- ⬜ Notification campaign/order/payment.
- ⬜ Multi-language `vi`, `en`, `ja`.

## Actor 2 — Admin

- ✅ Room-scoped middleware và route group `auth:admin`.
- ✅ Endpoint đóng campaign: `POST /admin/{room}/campaigns/{campaign}/close`.
- ✅ `CloseCampaignAction` lock transaction, chuyển trạng thái và sinh debt cơ bản.
- ⬜ Admin login UI/API và room switcher.
- 🟨 Campaign/order list API theo Room đã có pagination; dashboard KPI/UI còn thiếu.
- 🟨 Campaign create + close đã có Admin API/action; edit/activate/cancel/duplicate/archive và UI còn thiếu.
- ⬜ Food crawler: URL validation, timeout/retry, preview rồi mới import.
- 🟨 Menu item create/update/archive và size/topping create đã có Admin API/action; update/delete options và availability UI còn thiếu.
- 🟨 Live orders list API và status transition API theo Room đã có; realtime/edit/cancel/delete UI và event broadcast còn thiếu.
- ⬜ Item aggregator và close-campaign settlement đầy đủ.
- 🟨 Debt payment/adjustment có action + audit và Admin API; mark-paid UI và settlement allocation còn thiếu.
- 🟨 Room User list + block/unblock API đã có; detail/device revoke/UI còn thiếu.
- 🟨 Payment account list/create/update API đã có, enforce một default active/Room; VietQR rendering/delete/UI còn thiếu.
- ⬜ Room settings.
- ⬜ Room notification channels (credentials encrypted/masked).
- ⬜ Room reports, pagination, normalized search.
- ⬜ Room audit log chỉ trong Room được assign.

## Actor 3 — Superadmin

- 🟨 Superadmin role middleware + protected routes đã có; login UI và dashboard global còn thiếu.
- ⬜ Room CRUD/enable/disable/archive.
- ⬜ Admin CRUD, block/unblock, password reset, role promotion/demotion.
- ⬜ Assign/remove Admin ↔ Room.
- 🟨 Global User list + block/unblock API đã có; search/detail/merge/remove membership/UI còn thiếu.
- ⬜ OAuth identity metadata management (không expose token).
- ⬜ Global campaign/debt overview và force close/cancel.
- ⬜ Global notification channels.
- 🟨 System settings service đã có typed get/set và mã hóa secret; Superadmin API/UI còn thiếu.
- 🟨 Maintenance middleware đã có chặn request khi `maintenance.enabled`; schedule/broadcast/UI còn thiếu.
- ⬜ System reset yêu cầu password + exact phrase + audit.
- ⬜ Global audit logs.
- ⬜ Security center/security events.
- ⬜ Socket.IO monitoring.
- ⬜ Queue/failed jobs management.
- ⬜ Version management/changelog/force refresh.

## Realtime, security và quality

- ⬜ Socket.IO gateway verify short-lived Laravel token.
- ⬜ Channel authorization: `user:{roomUserId}`, `room:{roomId}`, `admin:{adminId}`, `superadmin`, `system`.
- ⬜ Emit sau DB commit; payload nhỏ, client fetch lại dữ liệu authoritative.
- ⬜ Domain events/listeners/jobs cho campaign/order/debt/notification.
- ⬜ Secrets encrypted, masked; không leak API/HTML/log/audit.
- ⬜ Rate limiting, correlation/request ID, structured logging.
- 🟨 Feature tests: OAuth/domain, dedup, join/device, cross-room denial, order lock, close campaign, debt, block, socket auth, secret exposure. OAuth/domain, dedup, join/device, trusted-token revoke, order lock và Admin/Superadmin authorization đã có test.
- ✅ Baseline tests hiện tại: `php artisan test` — 10 tests passed (18 assertions).

## Các file đã triển khai

- `src/database/migrations/2026_09_10_000000_create_drinkflow_core_tables.php`
- `src/database/migrations/2026_09_10_010000_create_campaign_item_sizes_table.php`
- `src/app/Actions/User/JoinRoomAction.php`
- `src/app/Actions/Order/CreateOrderAction.php`
- `src/app/Actions/Campaign/CloseCampaignAction.php`
- `src/app/Http/Middleware/ResolveGlobalUser.php`
- `src/app/Http/Middleware/ResolveRoomUser.php`
- `src/app/Http/Middleware/EnsureAdminRoomAccess.php`
- `src/routes/web.php`

## Việc nên làm tiếp theo

1. Hoàn tất Google OAuth + trusted-device authentication trước khi mở rộng UI.
2. Tạo feature tests cho JoinRoom/CreateOrder/CloseCampaign và cross-room authorization.
3. Bổ sung audit/debt payments/notification/settings migrations.
4. Xây Admin campaign/menu/order screens rồi mới triển khai User UI realtime.
5. Sau đó triển khai Superadmin và Socket.IO gateway.

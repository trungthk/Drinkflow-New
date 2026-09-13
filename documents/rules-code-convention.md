# DrinkFlow Rules & Code Convention

## 1. Mục tiêu

Tài liệu này là rule chung cho toàn bộ codebase DrinkFlow.

Áp dụng cho:

- Laravel 12 (mã nguồn đặt trong thư mục `src/`).
- PHP 8.3+ (Docker container `drinkflow-new-app-1`).
- Blade & Blade Components (`<x-public.layout>`, `<x-global.layout>`, v.v.).
- TailwindCSS.
- Alpine.js nếu cần.
- PostgreSQL 16 Alpine (Docker Compose service `postgres`, database `drinkflow`, KHÔNG sử dụng Supabase).
- Redis optional.
- Tiny Node.js Socket.IO Gateway (`realtime/` container trên port 3001).

Mục tiêu:

- Code dễ đọc.
- Business rule tập trung.
- Không duplicate logic.
- Authorization rõ.
- Room isolation an toàn.
- Dễ test.
- Dễ refactor.
- Codex/AI generate code theo cùng convention.

---

# 2. Architecture Rule

## 2.1. Laravel là Source of Truth

Laravel là nơi duy nhất cho:

- Business logic.
- Database access.
- Authorization.
- Validation.
- Calculation.
- Order rules.
- Debt rules.
- Room rules.
- Socket authorization.
- Secret management.

Không đưa business logic sang Socket.IO Gateway.

Không query Database trực tiếp từ browser (tuyệt đối không kết nối Supabase hay direct DB client). Mọi tương tác dữ liệu phải thông qua backend Laravel.

---

## 2.2. Layer Responsibility

```text
Request
  │
  ▼
Middleware
  │
  ▼
FormRequest
  │
  ▼
Controller
  │
  ▼
Action / Service
  │
  ▼
Model / Query
  │
  ▼
Database (PostgreSQL 16)
```

Controller phải thin.

Controller không chứa:

- Complex calculation.
- Large query.
- Authorization logic lặp lại.
- Business transaction.
- Socket.IO implementation detail.

---

# 3. Folder Convention

Toàn bộ mã nguồn Laravel nằm trong thư mục `src/`:

```text
Drinkflow-New/
├── docker-compose.yml
├── Dockerfile
├── documents/
├── realtime/ (Socket.IO Gateway)
└── src/ (Laravel 12 Application)
    ├── app/
    │   ├── Actions/
    │   │   ├── Auth/
    │   │   ├── User/
    │   │   ├── Room/
    │   │   ├── Campaign/
    │   │   ├── Order/
    │   │   ├── Debt/
    │   │   └── Admin/
    │   ├── Enums/
    │   ├── Events/
    │   ├── Http/
    │   │   ├── Controllers/
    │   │   │   ├── User/
    │   │   │   │   ├── Global/ (Dashboard, Profile, Rooms, Orders, Analytics, Notification)
    │   │   │   │   ├── DashboardController (Room level)
    │   │   │   │   ├── CampaignController
    │   │   │   │   ├── OrderController
    │   │   │   │   ├── DebtController
    │   │   │   │   └── RoomSettingController
    │   │   │   ├── Admin/
    │   │   │   └── Superadmin/
    │   │   ├── Middleware/
    │   │   └── Requests/
    │   ├── Jobs/
    │   ├── Listeners/
    │   ├── Models/
    │   ├── Policies/
    │   ├── Queries/
    │   ├── Services/
    │   │   ├── Auth/
    │   │   ├── VietQr/
    │   │   ├── Notification/
    │   │   ├── Crawler/
    │   │   └── Realtime/
    │   └── View/
    │       └── Components/
    ├── database/
    │   ├── factories/
    │   ├── migrations/
    │   └── seeders/
    ├── resources/
    │   └── views/
    │       ├── components/ (x-public.layout, x-global.layout, x-global.header, ...)
    │       ├── user/ (global & room views)
    │       ├── admin/
    │       └── superadmin/
    └── routes/
        ├── web.php
        ├── user.php
        ├── admin.php
        └── superadmin.php
```

Không tạo folder mới nếu chỉ có một class không cần grouping.

---

# 4. Naming Convention

## Class

PascalCase:

```text
CreateOrderAction
CloseCampaignAction
ResolveRoomUser
SocketIoPublisher
```

## Method / Variable

camelCase:

```text
createOrder()
roomUser
finalAmount
```

## Database

snake_case:

```text
room_users
global_user_id
last_active_at
```

## Enum

PascalCase class + meaningful case:

```php
enum OrderStatus: string
{
    case Submitted = 'submitted';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
}
```

Không dùng string status rải rác trong code nếu đã có Enum.

---

# 5. Controller Rule

Controller nên có dạng:

```php
public function store(
    StoreOrderRequest $request,
    Campaign $campaign,
    CreateOrderAction $action
) {
    $order = $action->execute(
        campaign: $campaign,
        roomUser: $request->attributes->get('room_user'),
        data: $request->validated(),
    );

    return redirect()
        ->route('orders.show', $order)
        ->with('success', __('order.created'));
}
```

Không nên:

```php
public function store(Request $request)
{
    // 150 lines business logic
}
```

---

# 6. Action Rule

Business use case chính phải ưu tiên Action.

Ví dụ:

```text
CreateOrderAction
JoinRoomAction
BlockRoomUserAction
CloseCampaignAction
AdjustDebtAction
```

Action:

- Một use case chính.
- Có transaction nếu cần.
- Có thể gọi Service.
- Dispatch Event sau khi business state hợp lệ.

Signature nên rõ:

```php
public function execute(
    Campaign $campaign,
    RoomUser $roomUser,
    array $data
): Order
```

---

# 7. Service Rule

Service dùng cho capability reusable.

Ví dụ:

```text
GoogleIdentityService
CompanyDomainService
VietQrService
NotificationService
CrawlerService
SocketIoPublisher
```

Không tạo `SomethingService` chỉ để wrap một Model call đơn giản.

---

# 8. Query Rule

Nếu query phức tạp hoặc dùng nhiều nơi:

```text
app/Queries/
```

Ví dụ:

```text
RoomDashboardQuery
CampaignOrderSummaryQuery
DebtReportQuery
```

Không nhét report query lớn vào Model accessor.

---

# 9. Model Rule

Model chịu trách nhiệm:

- Relation.
- Cast.
- Scope đơn giản.
- Attribute helper.
- Domain helper nhỏ.

Model không nên chứa:

- External HTTP call.
- Notification dispatch.
- Large business transaction.
- UI formatting.
- Huge report queries.

---

# 10. Validation Rule

Mọi input user phải qua FormRequest nếu endpoint có business significance.

Ví dụ:

```text
StoreOrderRequest
JoinRoomRequest
StoreCampaignRequest
UpdateDebtRequest
```

Không chỉ validate frontend.

Không tin:

```text
total_price
discount
sponsor_amount
final_amount
room_id
room_user_id
global_user_id
```

nếu server có thể resolve/tính được.

---

# 11. Authorization Rule

## 11.1. User

```text
User
→ Own Global Identity
→ Own Room Membership
→ Own Orders
→ Own Debt
```

## 11.2. Admin

```text
Admin
→ Assigned Room Data
```

Admin không được:

```text
Global User administration
OAuth Identity administration
Global Settings
```

## 11.3. Superadmin

```text
Superadmin
→ Global System
```

Authorization phải enforce ở server.

Không coi việc ẩn button/menu là security.

---

# 12. Room Scope Rule

Mọi query room-based phải scope rõ.

Không:

```php
Order::latest()->get();
```

Nên:

```php
Order::query()
    ->where('room_id', $room->id)
    ->latest()
    ->get();
```

Hoặc reusable scope/query object.

Admin Room A không được query Room B.

---

# 13. Global User vs Room User Rule

Luôn phân biệt:

```text
Global User
→ identity toàn hệ thống

Room User
→ membership trong Room
```

Order, Debt, Room block:

```text
→ room_user_id
```

Google identity, Global block:

```text
→ global_user_id
```

Không dùng hai ID thay thế lẫn nhau.

---

# 14. Google OAuth Rule

Google OAuth chỉ dùng để xác minh identity.

Laravel callback phải verify:

```text
email_verified
company domain
provider identity
```

Không tin browser-sent Google profile.

Một Google identity:

```text
UNIQUE(provider, provider_user_id)
```

Không tạo duplicate Global User.

---

# 15. Device Identity Rule

Giữ:

```text
device_uuid
```

Nhưng:

```text
device_uuid != authentication secret
```

Trusted token:

- Random.
- Chỉ gửi cho đúng device.
- Lưu hash trong DB.
- Revocable.
- Có expiration policy nếu cần.

Ưu tiên Secure + HttpOnly cookie.

---

# 16. Order Rule

Server phải:

```text
Validate campaign
Validate Room User
Validate item
Validate topping
Calculate amount
Check active order
Create snapshots
Commit
```

Client chỉ preview.

Server là authoritative.

---

# 17. Single Active Order Rule

Bắt buộc enforce hai lớp:

```text
Laravel transaction
+
PostgreSQL partial unique index
```

Không chỉ:

```php
if (!$exists) {
    Order::create(...);
}
```

vì race condition.

---

# 18. Money Rule

Không dùng float cho money.

Recommended:

- Integer VND.
- Hoặc decimal cố định nếu sau này multi-currency.

Ví dụ VND:

```text
50000
```

thay vì:

```text
50000.0
```

Calculation phải server-side.

---

# 19. Transaction Rule

Bắt buộc transaction cho:

- Create Order.
- Join Room.
- Close Campaign.
- Split Bill.
- Debt Adjustment.
- Payment allocation.
- Admin room assignment nếu nhiều operation liên quan.

Không gọi external notification trước khi DB commit.

---

# 20. Event Rule

Flow:

```text
Action
→ Domain Event
→ Listener
→ Job / Realtime Publisher
```

Ví dụ:

```text
CreateOrderAction
→ OrderCreated
→ BroadcastOrderCreatedListener
→ SocketIoPublisher
```

Event name PHP:

```text
OrderCreated
CampaignClosed
RoomUserBlocked
```

Socket event:

```text
order.created
campaign.closed
room_user.blocked
```

---

# 21. Socket.IO Rule

Socket.IO Gateway:

- Transport only.
- Không query PostgreSQL Database trực tiếp.
- Không update DB.
- Không chứa business rule.
- Không quyết định Room permission từ client input.

Laravel cấp signed short-lived token.

Gateway verify token rồi auto-join channel.

---

# 22. Socket Channel Convention

```text
user:{roomUserId}
room:{roomId}
global-user:{globalUserId}
admin:{adminId}
superadmin
system
```

Order event dùng:

```text
user:{roomUserId}
```

Không dùng Global User channel cho order-specific event.

---

# 23. Realtime Payload Rule

Payload nhỏ.

Nên:

```json
{
  "order_id": 100,
  "campaign_id": 20,
  "room_id": 4
}
```

Không gửi full Eloquent model nếu không cần.

Frontend nhận socket event rồi gọi Laravel endpoint / partial để lấy dữ liệu mới.

---

# 24. Blade Rule

Blade chỉ render data được Controller/ViewModel/Query chuẩn bị.

Không query DB trực tiếp trong Blade.

Không:

```php
@php
    $orders = Order::latest()->get();
@endphp
```

Nên:

```php
return view('admin.orders.index', [
    'orders' => $orders,
]);
```

---

# 25. Blade Component Rule

Component dùng cho UI reusable.

Ví dụ:

```text
<x-ui.button>
<x-ui.modal>
<x-user.campaign-card>
<x-admin.order-table>
```

Không đưa business query vào Blade Component.

---

# 26. Alpine.js Rule

Chỉ dùng cho interaction nhỏ:

- Modal.
- Dropdown.
- Tabs.
- Mobile menu.
- Show/hide password.
- Quantity selector.
- Theme preview.

Không rebuild SPA bằng Alpine.

---

# 27. TailwindCSS Rule

Không dùng Bootstrap song song.

Ưu tiên:

- Reusable Blade Components.
- Utility classes có consistency.
- CSS variables cho runtime theme.

Không copy block class quá dài ở hàng chục nơi nếu có thể component hóa.

---

# 28. Route Convention

User:

```text
/
rooms/{room}
rooms/{room}/join
campaigns/{campaign}
history
profile
```

Admin:

```text
/admin/login
/admin/{room}/dashboard
/admin/{room}/campaigns
/admin/{room}/orders
/admin/{room}/debts
/admin/{room}/room-users
```

Superadmin:

```text
/superadmin
/superadmin/rooms
/superadmin/admins
/superadmin/global-users
/superadmin/system
```

Named route:

```text
user.rooms.show
admin.campaigns.index
superadmin.global-users.show
```

---

# 29. HTTP Method Rule

```text
GET
→ read

POST
→ create/action

PUT/PATCH
→ update

DELETE
→ delete/revoke
```

Không dùng GET để thay đổi state.

---

# 30. Error Handling Rule

Không expose stack trace production.

User-facing error:

- Ngắn.
- Không lộ internal detail.
- Có correlation/request ID nếu cần support.

Log internal:

- Context vừa đủ.
- Không log secret.
- Không log full OAuth token.
- Không log password.

---

# 31. Secret Rule

Các secret:

```text
Google client secret
Chatwork token
Slack webhook
Telegram bot token
DB password / credentials
Realtime internal secret
```

phải:

- `.env` hoặc encrypted DB config.
- Không render HTML.
- Không trả `/api/config`.
- Không commit Git.
- Không log plaintext.

UI chỉ hiển thị:

```text
Configured
••••••••••
```

---

# 32. Config Rule

Public config:

```text
app name
public version
feature flag không nhạy cảm
```

có thể render xuống frontend.

Sensitive config không được chung payload với public config.

---

# 33. Audit Rule

Audit các operation quan trọng:

- Room User block/unblock.
- Global User block/unblock.
- Admin assignment.
- Campaign close/cancel.
- Order delete/cancel.
- Debt adjustment.
- Settings change.
- Device revoke.
- OAuth identity change.
- System reset.

Không audit secret plaintext.

---

# 34. Logging Convention

Structured context:

```php
Log::info('order.created', [
    'order_id' => $order->id,
    'room_id' => $order->room_id,
    'room_user_id' => $order->room_user_id,
]);
```

Không:

```php
Log::info($request->all());
```

vì có thể chứa secret/personal data không cần thiết.

---

# 35. Queue Rule

Queue các việc chậm:

- Notification.
- Crawler.
- Report.
- Email.
- Close campaign post-processing nếu phù hợp.

Business-critical DB state phải commit trước.

Job phải idempotent nếu retry có thể xảy ra.

---

# 36. Crawler Rule

Crawler:

- Validate URL.
- Timeout.
- Retry hợp lý.
- User-Agent rõ nếu phù hợp.
- Preview trước import.
- Không auto insert toàn menu nếu chưa được xác nhận.
- Sanitize external content.

Nếu cần JS rendering, cô lập implementation.

Không đưa crawler business logic vào Socket.IO Gateway.

---

# 37. Enum Rule

Dùng PHP Enum cho:

```text
OrderStatus
CampaignStatus
RoomUserStatus
GlobalUserStatus
AdminRole
DebtStatus
NotificationType
```

Không dùng magic string lặp lại.

---

# 38. DTO Rule

Dùng DTO khi:

- Payload phức tạp.
- Data đi qua nhiều layer.
- Cần type rõ.
- Tránh array không cấu trúc.

Không bắt buộc DTO cho mọi form đơn giản.

---

# 39. Repository Rule

Không bắt buộc Repository Pattern.

Chỉ thêm repository nếu thật sự có:

- Multiple persistence source.
- Complex persistence abstraction.
- Test need rõ.

Eloquent + Query Object + Action thường đủ.

---

# 40. Comment Rule

Comment giải thích:

```text
WHY
```

Không comment lại:

```text
WHAT
```

Không:

```php
// Get user
$user = User::find($id);
```

Nên comment business constraint khó hiểu.

---

# 41. PHP Style

Tuân thủ:

- PSR-12.
- Laravel Pint.
- Strict type nếu team thống nhất.

Recommended:

```php
declare(strict_types=1);
```

cho service/action/domain class mới nếu không gây conflict codebase.

---

# 42. Return Type Rule

Ưu tiên khai báo type:

```php
public function execute(...): Order
```

Không bỏ type nếu có thể xác định rõ.

---

# 43. Nullable Rule

Không dùng nullable tràn lan.

Column nullable chỉ khi business cho phép thiếu dữ liệu.

Ví dụ:

```text
closed_at
→ nullable vì campaign chưa đóng

email
→ không nullable cho Global User
```

---

# 44. Test Rule

Mọi business rule quan trọng phải có Feature Test.

Test tối thiểu:

- Google OAuth domain.
- Global User deduplication.
- Join Room.
- Device authentication.
- Room authorization.
- Order creation.
- Single Active Order.
- Campaign close.
- Debt calculation.
- Room User block.
- Global User block.
- Socket authorization.
- Secret exposure.

---

# 45. Test Naming

Ví dụ:

```php
it('prevents an admin from accessing an unassigned room')
it('does not create duplicate global users for the same google identity')
it('allows only one active order per room user and campaign')
```

Tên test mô tả behavior, không mô tả implementation.

---

# 46. Factory Rule

Tạo Factory cho:

```text
GlobalUser
Room
RoomUser
AdminAccount
Campaign
CampaignItem
Order
Debt
```

Dùng state:

```php
RoomUserFactory::new()->blocked()
CampaignFactory::new()->active()
OrderFactory::new()->completed()
```

---

# 47. Migration Rule

Migration:

- Reversible nếu hợp lý.
- Có foreign key.
- Có index.
- Có unique constraint cho business invariant.
- Không phụ thuộc data random.

Data migration lớn nên tách command/script có kiểm soát.

---

# 48. Database Constraint Rule

Business invariant quan trọng nên enforce tại DB nếu có thể.

Ví dụ:

```text
UNIQUE(room_id, global_user_id)
UNIQUE(provider, provider_user_id)
UNIQUE(room_id, user_code)
```

Application validation không thay thế DB constraint.

---

# 49. Performance Rule

Tránh N+1.

Dùng:

```php
with()
withCount()
select()
```

khi phù hợp.

Không eager-load toàn bộ relation lớn mặc định.

Report lớn:

- Query aggregation.
- Index phù hợp.
- Pagination.
- Queue export nếu nặng.

---

# 50. Pagination Rule

List có thể lớn phải paginate.

Ví dụ:

- Global Users.
- Room Users.
- Orders.
- Audit Logs.
- Security Events.
- Campaign history.

Không `get()` hàng chục nghìn records vào Blade.

---

# 51. Search Rule

Vietnamese normalized search:

- Có `normalized_name`.
- Normalize cùng một rule khi write và search.

Không normalize mỗi record trong PHP sau khi load toàn bảng.

---

# 52. Date / Time Rule

Database lưu timestamp chuẩn.

Room có timezone riêng nếu cần.

Hiển thị theo Room/User context.

Không hard-code timezone trong business logic.

---

# 53. Status Transition Rule

Không cho status chuyển tùy ý.

Ví dụ Campaign:

```text
draft
→ scheduled
→ active
→ closing
→ closed
```

Cancel có rule riêng.

Order cũng cần transition hợp lệ.

Nên implement transition trong Action/Domain rule, không update status trực tiếp từ Controller.

---

# 54. Delete Rule

Ưu tiên business action:

```text
cancel
archive
disable
revoke
remove membership
```

thay vì hard delete.

Hard delete Global User / Room / Order chỉ khi requirement rõ và có audit/safeguard.

---

# 55. Superadmin Safeguard Rule

Không cho:

- Xóa superadmin cuối cùng.
- Self-demote nếu là superadmin cuối cùng.
- System reset không xác nhận.
- Expose system secret.

System reset cần:

```text
Password confirmation
+
Exact phrase
+
Audit log
```

---

# 56. Code Review Checklist

Trước khi merge:

- Có đúng Room scope không?
- Có authorization server-side không?
- Có query trực tiếp Database từ browser không?
- Có secret nào leak HTML/API/log không?
- Có trust client-calculated money không?
- Có transaction cho operation nhiều bước không?
- Có race condition không?
- Có DB constraint cho invariant quan trọng không?
- Có N+1 không?
- Có test business rule không?
- Socket event có emit sau commit không?
- Socket channel có đúng `room_user_id` / `room_id` không?
- Admin có vượt Room scope không?
- Global User và Room User có bị dùng nhầm không?

---

# 57. AI / Codex Rule

Khi Codex generate/refactor:

1. Đọc docs trước.
2. Không tự đổi architecture.
3. Không thêm SPA framework.
4. Tuyệt đối không kết nối Supabase hay thêm Supabase JS client (chỉ sử dụng PostgreSQL nội bộ qua backend Laravel).
5. Không đưa business logic vào Socket.IO Gateway.
6. Preserve behavior hiện có.
7. Refactor từng phase.
8. Chạy test sau mỗi phase.
9. Không bypass authorization để test pass.
10. Nếu spec mâu thuẫn, ưu tiên:
   - Security rules.
   - Actor boundary.
   - Database constraints.
   - Current architecture docs.

---

# 58. Definition of Done

Một feature được xem là hoàn thành khi:

```text
Business rule implemented
+
Validation
+
Authorization
+
Database constraint nếu cần
+
Audit nếu cần
+
Realtime nếu cần
+
Feature test
+
No secret exposure
+
No cross-room leakage
```

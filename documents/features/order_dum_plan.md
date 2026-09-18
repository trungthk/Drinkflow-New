# Kế Hoạch Triển Khai: Chức Năng "Order Dùm Người Khác"

## Tổng Quan

Cho phép một người dùng (A) đặt hàng thay mặt nhiều người khác (B, C, …) trong cùng chiến dịch. Hệ thống sẽ tự động tách thành **Đơn Cha** (của A) và **N Đơn Con** (của từng người được order dùm), ghi nhận công nợ đúng chủ, gửi thông báo qua DB + Socket.

---

## User Review Required

> [!IMPORTANT]
> **Unique constraint `orders_one_active_per_user_campaign`** hiện chặn 1 user chỉ có 1 đơn active/campaign. Đơn Con sẽ tạo thêm 1 đơn cho user được order dùm (B, C) — constraint này **không bị vi phạm** vì Đơn Con thuộc `room_user_id` của B/C (khác A). ✅ Không cần thay đổi constraint.

> [!IMPORTANT]
> **Debt Policy (enforceDebtPolicy)**: Khi tạo Đơn Con, hàm này sẽ kiểm tra công nợ của người được order dùm (B/C). Nếu B/C đang có công nợ vượt ceiling → sẽ báo lỗi. Đây là behavior **đúng** — cần xác nhận: có muốn bỏ qua debt check cho Đơn Con không, hay để nguyên?

> [!WARNING]
> **Giỏ hàng (session cart)** hiện không lưu thông tin "item này order cho ai". Cần bổ sung field `proxy_user_code` vào mỗi cart item. Cart item của người dùng chính (A) thì `proxy_user_code = null`.

---

## Quyết Định Thiết Kế (Đã Xác Nhận)

- ✅ **Mỗi cart item có thể gán cho người bất kỳ khác nhau** (B, C, D…) → hệ thống tách thành nhiều Đơn Con.
- ✅ **Nếu A không có item nào**: vẫn tạo **Đơn Cha rỗng** (subtotal=0) như placeholder để theo dõi người đặt.
- ✅ **Kiểm tra debt ceiling**: áp dụng cho cả người được order dùm (B, C). Nếu B vượt ceiling → báo lỗi cho A khi checkout.

---

## Proposed Changes

### 1. Database

#### [NEW] Migration: `add_parent_id_to_orders_table`

```sql
ALTER TABLE orders 
ADD COLUMN parent_id BIGINT NULL REFERENCES orders(id) ON DELETE SET NULL;
CREATE INDEX idx_orders_parent_id ON orders (parent_id);
```

**File:** `database/migrations/2026_09_18_100000_add_parent_id_to_orders_table.php`

---

### 2. Model

#### [MODIFY] [`Order.php`](file:///c:/laragon/www/Drinkflow-New/src/app/Models/Order.php)

- Thêm `parent_id` vào `$fillable`
- Thêm 2 relationships:
  - `parent(): BelongsTo` → `Order`
  - `children(): HasMany` → `Order`
- Thêm helper: `isParent(): bool`, `isChild(): bool`

---

### 3. Backend — Cart Session

#### [MODIFY] [`CampaignController.php`](file:///c:/laragon/www/Drinkflow-New/src/app/Http/Controllers/User/CampaignController.php) — `addToCart()`

Bổ sung field `proxy_user_code` (nullable string) vào mỗi cart item khi lưu session:

```php
$cart[] = [
    // ... existing fields ...
    'proxy_user_code' => $data['proxy_user_code'] ?? null, // NEW
];
```

#### [MODIFY] [`StoreCampaignCartRequest.php`](file:///c:/laragon/www/Drinkflow-New/src/app/Http/Requests/StoreCampaignCartRequest.php)

Thêm validation rule: `proxy_user_code: nullable|string|max:50`

---

### 4. Backend — Checkout / Tách Đơn

#### [NEW] `App\Actions\Order\CreateProxyOrdersAction.php`

Action mới chuyên xử lý việc **nhóm cart items theo `proxy_user_code`** và **tạo từng đơn** (1 Đơn Cha + N Đơn Con):

```
execute(Campaign $campaign, RoomUser $requesterRoomUser, array $data): Order (parent)

Thuật toán:
1. Phân nhóm $data['items'] theo proxy_user_code
   - Group null  → items của người đặt chính (A) → Đơn Cha
   - Group "xyz" → items cho user có user_code = xyz → Đơn Con
2. Lookup RoomUser cho từng proxy_user_code trong cùng room → nếu không tìm thấy → ValidationException
3. Tạo Đơn Cha (parent_id = null) qua CreateOrderAction cho A
4. Tạo từng Đơn Con (parent_id = parent_order->id) cho mỗi nhóm
5. Sau tất cả orders được tạo → dispatch OrderCreatedWithProxies event
6. Return $parentOrder
```

> [!NOTE]
> Nếu không có item nào của A (tất cả đều proxy), sẽ tạo một Đơn Cha "rỗng" với subtotal = 0, hoặc skip tạo Đơn Cha — cần xác nhận quyết định ở Open Questions.

#### [MODIFY] [`CreateOrderAction.php`](file:///c:/laragon/www/Drinkflow-New/src/app/Actions/Order/CreateOrderAction.php)

Bổ sung param `?int $parentId = null` vào `execute()` để truyền vào khi tạo Đơn Con.

#### [MODIFY] [`OrderController.php`](file:///c:/laragon/www/Drinkflow-New/src/app/Http/Controllers/User/OrderController.php) — `store()`

Phát hiện nếu có proxy items → gọi `CreateProxyOrdersAction` thay vì `CreateOrderAction`.

#### [MODIFY] [`StoreOrderRequest.php`](file:///c:/laragon/www/Drinkflow-New/src/app/Http/Requests/StoreOrderRequest.php)

Thêm validation:
```php
'items.*.proxy_user_code' => ['nullable', 'string', 'max:50'],
```

---

### 5. Backend — Logic Truy Vấn

#### [MODIFY] [`OrderController.php`](file:///c:/laragon/www/Drinkflow-New/src/app/Http/Controllers/User/OrderController.php) — `index()`

**Điều kiện query hiện tại** (chỉ lấy đơn của `room_user_id`):
```php
$query = $roomUser->orders()->where(...)
```

**Query mới (load thêm Đơn Con của Đơn Cha):**
```php
// Lấy cả đơn cha + đơn con thuộc về đơn cha của user
$query = Order::where(function ($q) use ($roomUser) {
    $q->where('room_user_id', $roomUser->id)          // đơn của chính mình
      ->orWhereHas('parent', fn ($p) => $p->where('room_user_id', $roomUser->id)); // đơn con của đơn cha mình
})->where('room_id', $room->id)...
```

---

### 6. Frontend — Giỏ Hàng

#### [MODIFY] [`campaign.blade.php`](file:///c:/laragon/www/Drinkflow-New/src/resources/views/user/campaign.blade.php)

**Thay đổi Cart Item Row** (dòng ~686–721): Thêm icon ✏️ Edit bên cạnh mỗi item trong giỏ hàng:
- Click icon → mở modal nhỏ cho phép nhập `user_code` của người được order dùm
- Nếu `proxy_user_code` khác null → hiển thị badge "Đặt dùm: [tên người]" bên dưới item

**Thêm Alpine state:**
```js
showProxyModal: false,
proxyEditingIndex: null,
proxyUserCode: '',
proxyUserLookupResult: null,   // {name, display_name} sau khi lookup
proxyUserLookupLoading: false,
proxyUserLookupError: null,
```

**API Lookup `user_code`** (xem mục Controller mới bên dưới):
- Gọi GET `/rooms/{room}/members/lookup?code=XYZ`
- Trả về `{display_name, user_code, avatar_url}` hoặc 404

**Submit checkout**: Gửi `items` kèm `proxy_user_code` field cho từng item.

**Alpine `confirmCart()`** — bổ sung `proxy_user_code` trong items payload:
```js
body: JSON.stringify({
  items: this.cartItems.map(item => ({
    ...item,
    proxy_user_code: item.proxy_user_code || null
  })),
  payment_method: 'transfer'
})
```

---

### 7. API Endpoint: Lookup Room Member

#### [NEW] `App\Http\Controllers\User\RoomMemberLookupController.php`

```
GET /rooms/{room}/members/lookup?code={user_code}
→ Trả về RoomUser info nếu tìm thấy trong room
→ 404 nếu không tìm thấy
```

#### [MODIFY] `routes/web.php` — thêm route lookup

---

### 8. Thông Báo

#### [NEW] `App\Events\ProxyOrdersCreated.php`

Event mang theo: `$parentOrder`, `array $childOrders`

#### [NEW] `App\Listeners\NotifyProxyOrderRecipients.php`

Lắng nghe `ProxyOrdersCreated`:
- Với mỗi Đơn Con → tạo `UserNotification` (type: `order.proxy_received`)
- Sau mỗi `UserNotification::create()` → dispatch `UserNotificationCreated` → socket tự gửi qua `PublishRealtimeEvent`

#### [MODIFY] `UserNotification` booted()

Thêm case `order.proxy_received` vào `match`:
```php
$type === 'order.proxy_received' => 'Đơn hàng ' . $orderCode . ' đã được ' . ($data['orderer_name'] ?? 'ai đó') . ' đặt dùm bạn.',
```

#### [MODIFY] `AppServiceProvider.php`

Đăng ký listener:
```php
Event::listen(ProxyOrdersCreated::class, NotifyProxyOrderRecipients::class);
```

---

## Verification Plan

### Automated Tests

```bash
docker exec drinkflow-new-app-1 php artisan test --filter="ProxyOrder"
```

Sẽ viết `tests/Feature/ProxyOrderTest.php` kiểm tra:
1. Tạo cart item có `proxy_user_code` → checkout → verify Đơn Cha + Đơn Con được tạo
2. `parent_id` của Đơn Con trỏ đúng về Đơn Cha
3. `room_user_id` của Đơn Con là đúng người được order dùm
4. Debt tạo đúng theo từng `room_user_id`
5. `UserNotification` được gửi đến người được order dùm
6. `proxy_user_code` không hợp lệ → ValidationException
7. Query `index()` → user A thấy Đơn Cha + Đơn Con; user B chỉ thấy Đơn Con của mình

### Manual Verification

- Kiểm tra UI giỏ hàng: icon Edit xuất hiện đúng chỗ, modal lookup hoạt động
- Xác nhận thông báo realtime đến đúng user được order dùm

---

## Tóm Tắt Các File Thay Đổi

| File | Loại | Mô tả |
|---|---|---|
| `database/migrations/2026_09_18_100000_add_parent_id_to_orders_table.php` | NEW | Thêm cột `parent_id` |
| `app/Models/Order.php` | MODIFY | Thêm `parent_id`, relationships `parent/children` |
| `app/Actions/Order/CreateProxyOrdersAction.php` | NEW | Logic tách đơn cha/con |
| `app/Actions/Order/CreateOrderAction.php` | MODIFY | Nhận param `parent_id` |
| `app/Http/Controllers/User/OrderController.php` | MODIFY | Gọi proxy action, sửa query `index()` |
| `app/Http/Controllers/User/RoomMemberLookupController.php` | NEW | API lookup user_code |
| `app/Http/Controllers/User/CampaignController.php` | MODIFY | Lưu `proxy_user_code` vào cart |
| `app/Http/Requests/StoreOrderRequest.php` | MODIFY | Validate `proxy_user_code` |
| `app/Http/Requests/StoreCampaignCartRequest.php` | MODIFY | Validate `proxy_user_code` |
| `app/Events/ProxyOrdersCreated.php` | NEW | Event sau khi tách đơn |
| `app/Listeners/NotifyProxyOrderRecipients.php` | NEW | Gửi notification đến người được order dùm |
| `app/Providers/AppServiceProvider.php` | MODIFY | Đăng ký event listener mới |
| `app/Models/UserNotification.php` | MODIFY | Thêm type `order.proxy_received` |
| `resources/views/user/campaign.blade.php` | MODIFY | UI edit proxy trong giỏ hàng |
| `routes/web.php` | MODIFY | Thêm route lookup member |
| `tests/Feature/ProxyOrderTest.php` | NEW | Feature test toàn bộ flow |

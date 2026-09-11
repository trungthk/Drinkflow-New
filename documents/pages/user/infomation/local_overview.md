# User Local Overview

## 1. Tổng quan

Tài liệu này mô tả toàn bộ các trang và chức năng dành cho **User Local trong Room**, tương ứng với **Room User** trong kiến trúc DrinkFlow.

Room User là identity theo phạm vi Room, được tạo từ Global User khi người dùng tham gia một Room cụ thể.

Kiến trúc:

```text
Global User
   │
   ▼
/me/*
   │
   ▼
Chọn Room
   │
   ▼
/rooms/{room}
   │
   ▼
Room User
   │
   ├── Room Home
   ├── Campaigns
   ├── Campaign Detail
   ├── Menu / Product
   ├── Order
   ├── Order History
   ├── Payments
   ├── Room Statistics
   ├── Room Profile
   ├── Room Notifications
   └── Live Popular Items Modal
```

Nguyên tắc:

```text
Global User
→ Identity toàn hệ thống

Room User
→ Identity/membership trong Room hiện tại
```

Mọi dữ liệu nghiệp vụ trong Room phải được scope theo:

```text
room_id
+
room_user_id
```

Không chỉ dựa vào `global_user_id`.

Laravel phải là source of truth.

Socket.IO chỉ dùng cho realtime transport.

---

# 2. Room Home

## 2.1. Route

```text
/rooms/{room}
```

Đây là trang chính sau khi User vào một Room.

## 2.2. Mục tiêu

Hiển thị:

- Thông tin Room.
- Campaign active.
- Campaign upcoming nếu có.
- Order hiện tại.
- Debt/payment cần xử lý.
- Room announcement.
- Quick Actions.
- Top 5 món được ưa chuộng khi Campaign đang live.

## 2.3. Ví dụ giao diện

```text
IT Team

Xin chào, Trung

--------------------------------

Campaign đang mở

Friday Coffee
Highlands Coffee

Deadline:
10:30

Sponsor:
200.000đ

[Order ngay]
[Top 5 món hot]

--------------------------------

Order hiện tại

Cà phê sữa đá L
90.000đ

[Đã gửi]

--------------------------------

Cần thanh toán
90.000đ
```

---

# 3. Room Header

Hiển thị:

- Room logo.
- Room name.
- Room description ngắn.
- Room User avatar/name.
- User code.
- Notification icon.
- Link về Global Dashboard.

Ví dụ:

```text
← DrinkFlow

IT Team

[Avatar] Trung
TRUNGLT
```

Link về:

```text
/me
```

---

# 4. Room Authentication / Join Confirmation

## 4.1. Route

```text
/rooms/{room}/join
```

Trang này xuất hiện khi:

```text
Global User đã xác định
+
chưa có Room User
```

## 4.2. Nội dung

```text
[Avatar]

Lâm Thành Trung
trung@company.com

Bạn chưa tham gia IT Team.

Thông tin Room:
IT Team
Technology Department

[Tham gia Room]
```

Không yêu cầu nhập lại:

- Name.
- Email.
- Avatar.

Sau khi xác nhận:

```text
Create Room User
→ Generate User Code
→ Register Device
→ Enter Room
```

Nếu device chưa nhận diện Global User:

```text
/rooms/{room}
→ Google Authentication
→ callback
→ Join Confirmation
```

---

# 5. Room Blocked Page

Khi:

```text
room_users.status = blocked
```

nhưng:

```text
global_users.status = active
```

thì chỉ block Room hiện tại.

Nội dung:

```text
Bạn hiện không thể truy cập Room IT Team.

Tài khoản DrinkFlow của bạn vẫn hoạt động.

Nếu cần hỗ trợ, vui lòng liên hệ Admin của Room.
```

CTA:

```text
[Quay về Room của tôi]
```

Đi tới:

```text
/me/rooms
```

Không hiển thị như Global Account bị block.

---

# 6. Campaign List

## 6.1. Route

```text
/rooms/{room}/campaigns
```

## 6.2. Tabs

```text
Đang mở
Sắp diễn ra
Đã kết thúc
```

## 6.3. Campaign Card

```text
Friday Coffee

Highlands Coffee

Deadline
10:30

Sponsor
200.000đ

24 người đã đặt

[Order ngay]
[Top 5 món hot]
```

Nếu User đã order:

```text
[Đã đặt]
[Xem Order]
```

Nếu closed:

```text
[Đã kết thúc]
```

---

# 7. Campaign Detail

## 7.1. Route

```text
/rooms/{room}/campaigns/{campaign}
```

Đây là trang trọng tâm của Room User.

## 7.2. Header

Hiển thị:

```text
Friday Coffee

Highlands Coffee

Deadline:
10:30

Created by:
Admin A

Sponsor:
200.000đ
```

Có countdown:

```text
Còn 25 phút
```

## 7.3. Campaign Info

Hiển thị:

- Restaurant.
- Deadline.
- Sponsor.
- Delivery fee.
- Discount.
- Flat price nếu có.
- Payment method.
- Campaign note.
- Number of participants.

## 7.4. User Order State

Nếu chưa order:

```text
Bạn chưa đặt món.

[Chọn món]
```

Nếu đã order:

```text
Order của bạn

Trà sữa Oolong L
90.000đ

[Đã gửi]
```

---

# 8. Live Top 5 Popular Items Modal

## 8.1. Mục tiêu

Khi Campaign đang live, User có thể mở Modal xem nhanh **Top 5 món đang được nhiều người chọn nhất**.

Feature này giúp:

- User tham khảo xu hướng chọn món trong team.
- Rút ngắn thời gian chọn món.
- Tăng tính tương tác cho Campaign.
- Giúp các món phổ biến nổi bật hơn.
- Tạo cảm giác Campaign đang có hoạt động realtime.

## 8.2. Vị trí CTA

Có thể đặt tại:

### Room Home

```text
[Top 5 món hot]
```

### Campaign Card

```text
Friday Coffee

[Order ngay]
[Top 5 món hot]
```

### Campaign Detail

```text
24 người đã đặt

[🔥 Xem Top 5 món]
```

## 8.3. Điều kiện hiển thị

Chỉ hiển thị khi:

```text
campaign.status = active
```

và Campaign có dữ liệu Order.

Nếu chưa có Order:

```text
Chưa có dữ liệu món phổ biến.
```

Có thể vẫn hiển thị button nhưng Modal show empty state.

## 8.4. Modal Layout

Ví dụ:

```text
🔥 Top 5 món đang được ưa chuộng

Friday Coffee
Highlands Coffee

--------------------------------

#1
Cà phê sữa đá
12 lượt chọn

#2
Trà đào cam sả
9 lượt chọn

#3
Matcha Latte
7 lượt chọn

#4
Trà sữa Oolong
6 lượt chọn

#5
Americano
4 lượt chọn

--------------------------------

Dữ liệu được cập nhật theo Campaign hiện tại.

[Đóng]
```

## 8.5. Thông tin mỗi item

Mỗi item có thể hiển thị:

- Ranking.
- Image.
- Item name.
- Total ordered quantity.
- Number of unique Room Users đã chọn.
- Price hiện tại.
- Category.
- Badge tăng/giảm nếu sau này muốn mở rộng.

MVP chỉ cần:

```text
rank
item name
ordered quantity
```

## 8.6. Cách tính Ranking

Khuyến nghị tính theo:

```text
SUM(order_items.quantity)
```

Chỉ tính Order active/hợp lệ.

Ví dụ status được tính:

```text
submitted
confirmed
ordering
ordered
delivering
completed
```

Không tính:

```text
cancelled
deleted
```

## 8.7. Tie-breaking Rule

Nếu hai món có cùng quantity:

Thứ tự ưu tiên:

```text
1. unique_users DESC
2. first_ordered_at ASC
3. campaign_item_id ASC
```

Điều này giúp ranking ổn định.

## 8.8. Query Concept

```text
campaign
   │
   ▼
valid orders
   │
   ▼
order_items
   │
   ▼
GROUP BY campaign_item
   │
   ▼
SUM(quantity)
   │
   ▼
ORDER BY total_quantity DESC
   │
   ▼
LIMIT 5
```

## 8.9. Scope bắt buộc

Query phải scope theo:

```text
room_id
campaign_id
```

Không được aggregate cross-room.

## 8.10. Endpoint

Có thể dùng:

```text
GET /rooms/{room}/campaigns/{campaign}/popular-items
```

Nếu trả JSON:

```json
{
  "data": [
    {
      "rank": 1,
      "item_id": 10,
      "name": "Cà phê sữa đá",
      "quantity": 12,
      "unique_users": 10
    }
  ]
}
```

Hoặc trả Blade partial:

```text
GET /rooms/{room}/campaigns/{campaign}/popular-items/partial
```

Mình khuyến nghị Blade partial để đồng nhất kiến trúc hiện tại.

## 8.11. Blade Partial

```text
resources/views/user/room/campaigns/partials/
└── popular-items.blade.php
```

Modal component:

```text
resources/views/components/user/room/
└── popular-items-modal.blade.php
```

## 8.12. Realtime Update

Khi có:

```text
order.created
order.updated
order.deleted
```

Modal không nhất thiết tự nhận full ranking qua Socket.IO.

Recommended flow:

```text
Order Event
   │
   ▼
Socket.IO signal
   │
   ▼
Campaign page
   │
   ▼
If Popular Modal is open
   │
   ▼
Refresh popular-items partial
```

Socket payload nhỏ:

```json
{
  "campaign_id": 20,
  "room_id": 4
}
```

Không gửi toàn bộ Top 5 ranking qua Socket.IO.

## 8.13. Refresh Strategy

Khi modal đang mở:

- Debounce refresh 1–2 giây.
- Không reload liên tục cho mỗi socket event nếu nhiều Order tới cùng lúc.
- Có thể cache ranking ngắn 5–10 giây nếu Campaign đông.

## 8.14. Privacy Rule

Không hiển thị:

- Tên User nào đã chọn món.
- Email.
- Room User ID.
- Order detail cá nhân.

Chỉ hiển thị aggregate data.

Ví dụ:

```text
Cà phê sữa đá
12 lượt chọn
```

Không:

```text
Trung, An, Minh đã chọn món này
```

trừ khi business yêu cầu riêng.

## 8.15. Empty State

```text
🔥 Top món

Chưa có đủ dữ liệu để xếp hạng.

Hãy là người đầu tiên đặt món!
```

## 8.16. Loading State

```text
Đang cập nhật Top 5 món...
```

## 8.17. Error State

```text
Không thể tải danh sách món phổ biến.

[Thử lại]
```

## 8.18. Mobile UI

Khuyến nghị Bottom Sheet:

```text
────────────────────
🔥 Top 5 món hot

#1 Cà phê sữa đá
   12 lượt

#2 Trà đào
   9 lượt

...
────────────────────
```

Desktop có thể dùng centered modal.

## 8.19. Security

Laravel phải kiểm tra:

- Global User active.
- Room User active.
- Room User thuộc Room.
- Campaign thuộc Room.
- Campaign được phép xem.

Không tin:

```text
room_id
campaign_id
```

chỉ vì browser truyền lên.

Route-model binding phải kèm Room scope.

---

# 9. Menu / Product Listing

Có thể tích hợp trực tiếp trong Campaign Detail.

Route riêng optional:

```text
/rooms/{room}/campaigns/{campaign}/menu
```

## 9.1. Search

```text
[Tìm món...]
```

Hỗ trợ:

```text
Trà sữa
tra sua
TRA SUA
```

## 9.2. Category

```text
Tất cả
Coffee
Tea
Milk Tea
Food
Topping
```

## 9.3. Product Card

```text
[Image]

Trà sữa Oolong

45.000đ

Milk Tea

[Chọn]
```

Nếu sold out:

```text
[Hết món]
```

---

# 10. Product Detail / Order Configuration

Có thể dùng modal hoặc bottom sheet.

Ví dụ:

```text
Trà sữa Oolong
45.000đ

Size

○ M
● L +10.000đ

Đá

0%
30%
50%
100%

Đường

0%
30%
50%
70%
100%

Topping

☑ Trân châu +10.000
☐ Pudding +10.000

Số lượng

[-] 1 [+]

Ghi chú

[Ít đá...]

Tạm tính
65.000đ

[Thêm vào Order]
```

Client chỉ preview giá.

Laravel phải tính lại khi submit.

---

# 11. Order Confirmation

Trước khi submit:

```text
Xác nhận Order

Trà sữa Oolong L

Đường: 50%
Đá: 30%

Topping:
Trân châu

Quantity:
1

Subtotal:
65.000đ

Sponsor:
-15.000đ

Cần trả:
50.000đ

Payment:
VietQR

[Quay lại]
[Xác nhận đặt]
```

Không tin từ frontend:

```text
subtotal
sponsor_amount
final_amount
```

---

# 12. Order Success / Thank-you

```text
Đặt món thành công 🎉
```

Hiển thị:

```text
Order #DF-1024

Trà sữa Oolong L
65.000đ

Sponsor
15.000đ

Cần thanh toán
50.000đ

[Thanh toán VietQR]
[Xem Order]
```

Có thể phát notification sound.

---

# 13. Current Order Detail

## 13.1. Route

```text
/rooms/{room}/orders/{order}
```

Hiển thị:

- Order code.
- Campaign.
- Items.
- Options.
- Amount.
- Sponsor.
- Payment.
- Status.
- Timeline.

Ví dụ:

```text
Order #DF-1024

Submitted
09:20

Confirmed
09:22

Ordered
09:35

Delivering
10:05
```

Realtime event:

```text
order.updated
```

Channel:

```text
user:{roomUserId}
```

---

# 14. Order Status

```text
submitted
confirmed
ordering
ordered
delivering
completed
cancelled
```

UI labels:

```text
submitted
→ Đã gửi

confirmed
→ Đã xác nhận

ordering
→ Đang đặt với quán

ordered
→ Quán đã nhận

delivering
→ Đang giao

completed
→ Hoàn thành

cancelled
→ Đã hủy
```

---

# 15. Single Active Order

Nếu User đã có active Order:

Không hiển thị:

```text
[Order ngay]
```

Thay bằng:

```text
[Xem Order của bạn]
```

Nếu User cố submit lại:

```text
Bạn đã có một Order đang hoạt động
trong Campaign này.
```

Laravel + PostgreSQL phải enforce rule này.

---

# 16. Room Order History

## 16.1. Route

```text
/rooms/{room}/orders
```

Khác với:

```text
/me/orders
```

Trang này chỉ hiển thị Order của Room User hiện tại.

Filter:

- Month.
- Campaign.
- Order Status.
- Payment Status.

---

# 17. Room Payments

## 17.1. Route

```text
/rooms/{room}/payments
```

Hiển thị payment/debt trong Room hiện tại.

Ví dụ:

```text
IT Team

Chưa thanh toán
180.000đ

Đã thanh toán tháng này
560.000đ
```

Không hiển thị data Room khác.

---

# 18. VietQR Payment

Có thể route:

```text
/rooms/{room}/payments/{payment}
```

hoặc modal.

Hiển thị:

```text
VietQR

Bank
ACB

Account
123456789

Account Name
NGUYEN VAN A

Amount
90.000đ

Content
DF1024 TRUNGLT

[QR]
```

User không chỉnh:

- Amount.
- Bank account.
- Transfer content.

---

# 19. Room Statistics

## 19.1. Route

```text
/rooms/{room}/statistics
```

Chỉ thống kê User trong Room hiện tại.

Ví dụ:

```text
IT Team

Total Orders
24

Total Spent
2.450.000đ

Sponsor Received
650.000đ
```

Top món:

```text
1. Cà phê sữa đá   8
2. Trà đào          6
3. Matcha Latte     4
```

Khác:

```text
/me/statistics
→ tất cả Room

/rooms/{room}/statistics
→ Room hiện tại
```

---

# 20. Room Profile

## 20.1. Route

```text
/rooms/{room}/profile
```

Hiển thị:

```text
[Avatar từ Global User]

Lâm Thành Trung
trung@company.com

Room
IT Team

User Code
TRUNGLT

Joined
15/08/2026

Status
Active
```

Mapping:

```text
Name / Email / Avatar
→ Global User

User Code / Joined / Status
→ Room User
```

Không thay đổi Google Identity từ trang này.

---

# 21. Room Notifications

Có thể dùng:

```text
/rooms/{room}/notifications
```

hoặc dùng `/me/notifications` với Room filter.

Nếu có dedicated page:

```text
IT Team Notifications

Campaign
Order
Payment
Room
```

Realtime:

```text
room:{roomId}
user:{roomUserId}
```

---

# 22. Room Announcement

Admin có thể đặt announcement.

Ví dụ:

```text
📢 IT Team

Hôm nay quán đóng đơn lúc 10:20 nhé!
```

Có thể dùng cho:

- Campaign note.
- Payment reminder.
- Room schedule.
- Temporary instruction.

---

# 23. Campaign Countdown

Campaign active hiển thị:

```text
Đóng đơn sau

00:24:15
```

Client có thể countdown bằng JS.

Deadline authoritative lấy từ Laravel.

Khi timer về 0:

```text
refresh campaign state
```

Không tự quyết định Campaign closed ở client.

---

# 24. Campaign Closed State

Nếu Campaign đã đóng:

```text
Campaign đã kết thúc.
```

Hiển thị:

- Order của User.
- Final amount.
- Sponsor.
- Payment status.

CTA:

```text
[Xem Order]
[Thanh toán]
[Quay về Room]
```

---

# 25. No Active Campaign State

```text
Hiện chưa có Campaign nào đang mở.

Bạn sẽ nhận thông báo khi Campaign mới được tạo.
```

CTA:

```text
[Xem lịch sử]
```

---

# 26. Room Access Error

## Room không tồn tại

```text
404
Room không tồn tại.
```

## Room disabled

```text
Room hiện đang tạm ngưng hoạt động.
```

## Chưa tham gia

```text
Confirm Join
```

## Room User blocked

```text
Room Access Denied
```

## Global User blocked

Redirect:

```text
/account-blocked
```

---

# 27. Room Navigation

## Desktop

```text
IT Team

Trang chủ
Campaigns
Lịch sử
Thanh toán
Thống kê

[Thông báo]
[Avatar]
```

Link:

```text
← Global Dashboard
```

về:

```text
/me
```

## Mobile

```text
Home
Campaign
Orders
Payments
Profile
```

---

# 28. Breadcrumb

Desktop:

```text
DrinkFlow
/
IT Team
/
Friday Coffee
```

Mobile:

```text
← IT Team
```

---

# 29. Room Switcher

Nếu User có nhiều Room:

```text
IT Team ▼
```

Chỉ hiển thị Room User hợp lệ.

Không load toàn bộ Room hệ thống.

---

# 30. Room Realtime Events

User có thể nhận:

```text
campaign.created
campaign.updated
campaign.closed
campaign.cancelled

order.created
order.updated
order.deleted

debt.updated

room.updated
room_user.blocked
room_user.unblocked
```

Channels:

```text
room:{roomId}
user:{roomUserId}
```

---

# 31. Room User Block Realtime

```text
Admin blocks Room User
        │
        ▼
room_user.blocked
        │
        ▼
Socket.IO
        │
        ▼
User Browser
        │
        ▼
Disable Room UI
        │
        ▼
Show Blocked Page
```

Laravel vẫn enforce ở request layer.

---

# 32. Campaign Created Realtime

```text
Admin Launch Campaign
        │
        ▼
campaign.created
        │
        ▼
room:{roomId}
        │
        ▼
Refresh Room Home
```

---

# 33. Order Deleted Realtime

```text
Admin Delete Order
        │
        ▼
order.deleted
        │
        ▼
user:{roomUserId}
        │
        ▼
Refresh Campaign State
        │
        ▼
User có thể Order lại
```

---

# 34. Room Search

Nếu server-side:

```text
/rooms/{room}/campaigns/{campaign}/items?q=tra+sua
```

Scope:

```text
room
+
campaign
```

Không search cross-room.

---

# 35. Room User Code

`user_code` thuộc Room User.

Ví dụ:

```text
IT Room
→ TRUNGLT

Marketing
→ TRUNGLT2
```

Không dùng `user_code` như Global Identity.

---

# 36. Room Request Context

Laravel phải resolve:

```text
Current Global User
Current Room
Current Room User
```

Flow:

```text
Request
  │
  ▼
ResolveGlobalUser
  │
  ▼
ResolveRoom
  │
  ▼
ResolveRoomUser
  │
  ▼
Controller
```

Không nhận `room_user_id` từ frontend rồi tin trực tiếp.

---

# 37. Route Structure

```php
Route::prefix('rooms/{room}')
    ->name('rooms.')
    ->group(function () {

        Route::get('/', ...)
            ->name('show');

        Route::get('/join', ...)
            ->name('join');

        Route::post('/join', ...)
            ->name('join.store');

        Route::middleware([
            'global.auth',
            'global.active',
            'room.user',
            'room.user.active',
        ])->group(function () {

            Route::get('/campaigns', ...)
                ->name('campaigns.index');

            Route::get(
                '/campaigns/{campaign}',
                ...
            )->name('campaigns.show');

            Route::get(
                '/campaigns/{campaign}/popular-items',
                ...
            )->name('campaigns.popular-items');

            Route::post(
                '/campaigns/{campaign}/orders',
                ...
            )->name('orders.store');

            Route::get('/orders', ...)
                ->name('orders.index');

            Route::get(
                '/orders/{order}',
                ...
            )->name('orders.show');

            Route::get('/payments', ...)
                ->name('payments.index');

            Route::get('/statistics', ...)
                ->name('statistics');

            Route::get('/profile', ...)
                ->name('profile');
        });
    });
```

---

# 38. Blade Structure

```text
resources/views/user/room/

├── home.blade.php
├── join/
│   └── confirm.blade.php
├── campaigns/
│   ├── index.blade.php
│   ├── show.blade.php
│   └── partials/
│       ├── campaign-card.blade.php
│       ├── menu.blade.php
│       └── popular-items.blade.php
├── orders/
│   ├── index.blade.php
│   ├── show.blade.php
│   ├── confirm.blade.php
│   └── success.blade.php
├── payments/
│   ├── index.blade.php
│   └── show.blade.php
├── statistics/
│   └── index.blade.php
├── profile/
│   └── index.blade.php
└── errors/
    ├── blocked.blade.php
    └── inactive.blade.php
```

---

# 39. Blade Components

```text
resources/views/components/user/room/

room-header.blade.php
room-switcher.blade.php
campaign-card.blade.php
campaign-countdown.blade.php
product-card.blade.php
product-configurator.blade.php
popular-items-modal.blade.php
order-summary.blade.php
order-status.blade.php
payment-card.blade.php
vietqr.blade.php
stat-card.blade.php
announcement.blade.php
```

---

# 40. Global User vs Room User Pages

| Global | Room Local |
|---|---|
| `/me` | `/rooms/{room}` |
| My Rooms | Current Room |
| All Orders | Room Orders |
| All Payments | Room Payments |
| Global Statistics | Room Statistics |
| Global Profile | Room Profile |
| All Notifications | Room Notifications |
| Devices / Security | Campaign / Menu |
| — | Live Top 5 Popular Items |

---

# 41. MVP Priority

## Phase 1

```text
/rooms/{room}
/rooms/{room}/join
/rooms/{room}/campaigns/{campaign}
/rooms/{room}/campaigns/{campaign}/popular-items
/rooms/{room}/orders/{order}
/rooms/{room}/orders
/rooms/{room}/payments
/rooms/{room}/profile
```

UI states:

```text
Product Configuration
Popular Items Modal
Order Confirmation
Order Success
No Active Campaign
Campaign Closed
Room Blocked
```

## Phase 2

```text
/rooms/{room}/campaigns
/rooms/{room}/statistics
/rooms/{room}/notifications
```

---

# 42. Tổng flow Room User

```text
Global Dashboard
      │
      ▼
Choose Room
      │
      ▼
/rooms/{room}
      │
      ▼
Resolve Room User
      │
      ├── Missing
      │     ↓
      │  Join Room
      │
      ├── Blocked
      │     ↓
      │  Access Denied
      │
      └── Active
            │
            ▼
         Room Home
            │
     ┌──────┼───────────────┐
     │      │               │
 Campaign  Orders        Payments
     │
     ├── Top 5 Popular Items
     │
     ▼
   Menu
     │
     ▼
 Configure
     │
     ▼
 Confirm
     │
     ▼
 Create Order
     │
     ▼
 Realtime Status
```

Nguyên tắc cuối cùng:

```text
Global User
→ Identity toàn hệ thống

Room User
→ Identity trong Room

Campaign / Order / Debt / Popular Items
→ Scope theo Room

Laravel
→ Source of truth

Socket.IO
→ Realtime signal
```

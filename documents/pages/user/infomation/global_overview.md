# User Overview

## 1. Tổng quan

Tài liệu này mô tả nhóm trang và chức năng dành cho **Global User** trong DrinkFlow.

Global User là danh tính người dùng ở cấp toàn hệ thống, được tạo hoặc nhận diện sau khi xác thực Google bằng email công ty. Một Global User có thể tham gia nhiều Room thông qua các bản ghi Room User khác nhau.

Phân tách kiến trúc:

```text
Public
   ↓
Google Authentication
   ↓
Global User
   ├── Global Dashboard
   ├── My Rooms
   ├── Global Order History
   ├── Global Statistics
   ├── Payments
   ├── Notifications
   ├── Global Profile
   ├── Devices & Sessions
   └── Account & Security
           ↓
       Enter Room
           ↓
       Room User
           ↓
       Campaign / Order
```

Nguyên tắc:

- `/me/*` là phạm vi Global User.
- `/rooms/{room}/*` là phạm vi Room User.
- Global User không được browse toàn bộ Room của hệ thống.
- Global User chỉ thấy dữ liệu thuộc chính mình.
- Mọi authorization phải được kiểm tra server-side bởi Laravel.
- Supabase PostgreSQL là database.
- Blade Components + TailwindCSS dùng cho frontend.
- Socket.IO dùng cho realtime.
- Laravel là source of truth cho business logic và authorization.

---

# 2. Global User Dashboard

## 2.1. Route

```text
/me
```

Đây là trang Home chính sau khi Global User được nhận diện.

Không tự động chuyển User vào một Room bất kỳ.

## 2.2. Mục tiêu

Cung cấp snapshot hoạt động của User trên toàn DrinkFlow:

- Global Profile.
- Room gần đây.
- Campaign đang active.
- Recent Orders.
- Tổng chi tiêu.
- Sponsor received.
- Notification chưa đọc.
- Quick Actions.

## 2.3. Summary

Ví dụ:

```text
Xin chào, Trung 👋

[Avatar]  Lâm Thành Trung
          trung@company.com

Room của bạn
3 Rooms

Order gần đây
12 Orders

Tổng chi tiêu
1.250.000đ

Sponsor nhận được
350.000đ
```

## 2.4. Room gần đây

Hiển thị khoảng 3–5 Room User truy cập gần nhất.

```text
IT Team

Campaign đang mở
Friday Coffee

[Vào Room]
```

Ưu tiên:

```text
last_accessed_at DESC
```

## 2.5. Campaign đang hoạt động

Có thể tổng hợp Campaign active từ các Room mà User đang có quyền truy cập.

```text
Friday Coffee
IT Team

Deadline: 10:30

[Order ngay]
```

Đi tới:

```text
/rooms/{room}/campaigns/{campaign}
```

Laravel phải xác minh membership trước khi trả dữ liệu.

## 2.6. Recent Orders

Hiển thị khoảng 5 Order gần nhất:

```text
Highlands Coffee
IT Team

Cà phê sữa đá x2
120.000đ

Paid
10/09/2026
```

Link:

```text
[Xem tất cả]
→ /me/orders
```

## 2.7. Notification Summary

Ví dụ:

```text
🔔 3 thông báo mới
```

## 2.8. Quick Actions

```text
[Room của tôi]
[Lịch sử Order]
[Thống kê]
[Thiết bị]
```

---

# 3. My Rooms

## 3.1. Route

```text
/me/rooms
```

## 3.2. Mục tiêu

Cho Global User xem những Room mà mình đã tham gia.

Không hiển thị danh sách toàn bộ Room trong hệ thống.

## 3.3. Room Card

```text
IT Team

Status: Active
Joined: 15/08/2026

12 Orders
850.000đ spent

[Vào Room]
```

Có thể hiển thị:

- Room name.
- Logo/avatar.
- Room User status.
- Joined date.
- Number of Orders.
- Active Campaign.
- Last accessed.

## 3.4. Room User Status

```text
active
blocked
inactive
```

Nếu:

```text
room_users.status = blocked
```

Room vẫn có thể xuất hiện trong danh sách nhưng không cho truy cập:

```text
Marketing

Bạn đã bị hạn chế truy cập Room này.

[Blocked]
```

## 3.5. Search

Nếu User tham gia nhiều Room:

```text
[Tìm Room của tôi...]
```

Search chỉ trên Room User đã có.

## 3.6. Empty State

```text
Bạn chưa tham gia Room nào.

Hãy sử dụng đường dẫn Room do Admin cung cấp
để bắt đầu sử dụng DrinkFlow.
```

Không cung cấp chức năng browse toàn bộ Room.

---

# 4. Global Order History

## 4.1. Route

```text
/me/orders
```

Order detail:

```text
/me/orders/{order}
```

## 4.2. Mục tiêu

Tổng hợp Order của chính Global User từ tất cả Room đã tham gia.

Khác với Order History trong một Room:

```text
/rooms/{room}/...
```

## 4.3. Danh sách

```text
Lịch sử Order

[All Rooms ▼]
[All Status ▼]
[Tháng này ▼]

Highlands Coffee
IT Team

10/09/2026 09:30

Cà phê sữa đá x2
120.000đ

[Paid]
```

## 4.4. Filter

Hỗ trợ:

- Room.
- Date range.
- Payment status.
- Order status.
- Shop.

## 4.5. Order Detail

Ví dụ:

```text
Order #DF-20260910-001

Room
IT Team

Campaign
Friday Coffee

Shop
Highlands Coffee

Cà phê sữa đá
Size L
Ít đá
x2

100.000đ

Topping
20.000đ

Subtotal       120.000
Sponsor        -30.000
Final           90.000

Payment
Paid
```

## 4.6. Authorization

Không được chỉ dựa vào Order ID trên URL.

Laravel phải xác minh Order thuộc Global User hiện tại thông qua ownership trực tiếp hoặc Room User relationship.

---

# 5. Global Statistics

## 5.1. Route

```text
/me/statistics
```

## 5.2. Mục tiêu

Thống kê hoạt động cá nhân của Global User trên toàn hệ thống.

Đây không phải báo cáo quản trị.

## 5.3. Summary Cards

```text
Total Orders
48

Total Items
63

Total Spent
4.850.000đ

Sponsor Received
1.250.000đ

Average / Order
101.000đ

Rooms
3
```

## 5.4. Spending Trend

Filter:

```text
This Month
3 Months
6 Months
This Year
Custom
```

## 5.5. Favorite Shops

```text
1. Highlands       18 orders
2. Phúc Long       12 orders
3. Katinat          8 orders
```

## 5.6. Favorite Products

```text
1. Cà phê sữa đá      12
2. Trà đào             9
3. Matcha Latte        7
```

## 5.7. Statistics by Room

```text
IT Team

Orders        32
Spent         3.200.000
Sponsor       900.000
```

Chỉ lấy dữ liệu của Global User hiện tại.

---

# 6. Global Profile

## 6.1. Route

```text
/me/profile
```

## 6.2. Nội dung

```text
[Avatar]

Lâm Thành Trung
trung@company.com

Google Account
✓ Verified
```

## 6.3. Google Managed Fields

Các field lấy từ Google:

- Name.
- Email.
- Avatar.

Hiển thị:

```text
Managed by Google
```

Không cho User tùy ý sửa email đã xác minh.

## 6.4. System Information

Có thể hiển thị:

- Joined DrinkFlow.
- Last Login.
- Account Status.

Không cần expose internal database ID nếu không có giá trị cho User.

## 6.5. Room Profiles

Có thể hiển thị:

```text
Room Profiles

IT Team
User Code: TRUNGLT

Marketing
User Code: TRUNGLT02
```

Các thay đổi thuộc Room phải được xử lý trong Room context nếu business rule cho phép.

---

# 7. Notifications

## 7.1. Route

```text
/me/notifications
```

## 7.2. Mục tiêu

Tổng hợp notification của Global User.

Nguồn notification:

- System.
- Room.
- Campaign.
- Order.
- Payment.
- Security.

## 7.3. Ví dụ

```text
🔔 Friday Coffee đã mở

IT Team
2 phút trước

[Order ngay]
```

```text
💰 Thanh toán đã được xác nhận

Order #DF-001
IT Team

10 phút trước
```

Security notification:

```text
🔐 Thiết bị mới vừa được xác thực

Chrome / Windows
11/09/2026 08:30
```

## 7.4. Filter

```text
All
Unread
System
Room
Order
Payment
Security
```

Actions:

```text
Mark as read
Mark all as read
```

## 7.5. Realtime

```text
Laravel
   ↓
Socket.IO
   ↓
Global User Channel
   ↓
Notification UI
```

Logical channel:

```text
global-user:{globalUserId}
```

Client không được tự join channel chỉ bằng `globalUserId`.

Socket server phải xác minh credential/token do Laravel cấp.

---

# 8. Devices & Sessions

## 8.1. Route

```text
/me/devices
```

Đây là trang quan trọng vì hệ thống sử dụng Trusted Device.

## 8.2. Device Card

```text
Chrome / Windows
Ho Chi Minh City

[Thiết bị hiện tại]

Last active:
11/09/2026 09:20

[Đăng xuất thiết bị]
```

Thiết bị khác:

```text
Safari / iPhone

Last active:
10/09/2026 18:20

[Đăng xuất thiết bị]
```

## 8.3. Không expose dữ liệu nhạy cảm

Không hiển thị:

```text
token
token_hash
full device_uuid
authentication secret
```

## 8.4. Revoke Device

Confirm:

```text
Bạn muốn đăng xuất thiết bị này?

Thiết bị sẽ phải xác thực lại
khi truy cập DrinkFlow.

[Hủy] [Đăng xuất]
```

## 8.5. Revoke All Other Devices

Có thể hỗ trợ:

```text
[Đăng xuất tất cả thiết bị khác]
```

Current Device được giữ lại trừ khi User chủ động logout.

---

# 9. Account & Security

## 9.1. Route

```text
/me/security
```

Có thể gộp vào `/me/devices` trong MVP.

## 9.2. Google Account

```text
Google Account

trung@company.com
✓ Connected
```

## 9.3. Authentication History

Ví dụ:

```text
Google Authentication
11/09/2026 08:30
Chrome / Windows

Trusted Device
10/09/2026 09:00
Chrome / Windows
```

Không hiển thị token hoặc credential.

## 9.4. Account Status

```text
Account
Active
```

Nếu Global User bị block, middleware chuyển sang Blocked Page thay vì cho truy cập bình thường.

---

# 10. Global Payments

## 10.1. Route

Khuyến nghị:

```text
/me/payments
```

Tên `payments` thân thiện hơn `debts` đối với User.

## 10.2. Summary

```text
Thanh toán

Chưa thanh toán
180.000đ

Đã thanh toán tháng này
850.000đ
```

## 10.3. Danh sách

```text
IT Team
Friday Coffee

Order #DF-001

Amount
90.000đ

[Unpaid]

[Thanh toán]
```

Filter:

```text
All
Unpaid
Paid
```

## 10.4. Payment Detail

Ví dụ:

```text
VietQR

90.000đ

Nội dung:
DF001 TRUNGLT
```

Không tự chuyển `paid` chỉ vì User click xác nhận đã chuyển khoản.

Nếu business rule cần, có thể dùng:

```text
unpaid
payment_submitted
paid
```

---

# 11. Tham gia Room mới

Global User không có trang browse toàn bộ Room.

Không đề xuất:

```text
/rooms
```

để liệt kê tất cả Room cho User.

Flow tham gia Room mới:

```text
Room URL
    ↓
/rooms/{room}
    ↓
Identify Global User
    ↓
Check Room User
    ↓
Confirm Join
    ↓
Create Room User
    ↓
Enter Room
```

Nếu Global User đã tồn tại, không yêu cầu nhập lại:

- Họ tên.
- Email.
- Avatar.

Hệ thống tiếp tục giữ các business field đã thiết kế như:

- Normalized name.
- User code.
- Device UUID.

---

# 12. Global Account Blocked Page

## 12.1. Route

```text
/account-blocked
```

Khi:

```text
global_users.status = blocked
```

User không được truy cập `/me` hoặc Room.

## 12.2. Nội dung

```text
Tài khoản của bạn hiện không thể sử dụng DrinkFlow.

Nếu cho rằng đây là nhầm lẫn,
vui lòng liên hệ người quản trị hệ thống.
```

Không expose:

- `blocked_by`.
- Internal note.
- Security reason.
- Audit information.

trừ khi có business requirement cụ thể.

---

# 13. Error / Access Denied Pages

Các trạng thái cần hỗ trợ:

```text
403
404
419
429
500
maintenance
```

Cần phân biệt Global Block và Room Block.

Ví dụ Room Block:

```text
Global Account Active
        │
        ▼
Room User Blocked
        │
        ▼
Bạn không thể truy cập Room này.
```

Không hiển thị Room Block như Global Account Block.

---

# 14. Global Navigation

## 14.1. Desktop

```text
DrinkFlow

Tổng quan
Room của tôi
Lịch sử
Thanh toán
Thống kê
Thông báo

[Avatar ▼]
```

Avatar menu:

```text
Hồ sơ
Thiết bị & bảo mật
Điều khoản
Phiên bản

Đăng xuất
```

## 14.2. Mobile

```text
Home
Rooms
Orders
Payments
Profile
```

Notification có thể dùng icon riêng trên header.

---

# 15. Route Structure

```php
Route::prefix('me')
    ->middleware([
        'global.auth',
        'global.active',
    ])
    ->name('me.')
    ->group(function () {

        Route::get('/', ...)->name('dashboard');

        Route::get('/rooms', ...)
            ->name('rooms');

        Route::get('/orders', ...)
            ->name('orders');

        Route::get('/orders/{order}', ...)
            ->name('orders.show');

        Route::get('/payments', ...)
            ->name('payments');

        Route::get('/statistics', ...)
            ->name('statistics');

        Route::get('/notifications', ...)
            ->name('notifications');

        Route::get('/profile', ...)
            ->name('profile');

        Route::get('/devices', ...)
            ->name('devices');

        Route::get('/security', ...)
            ->name('security');
    });
```

---

# 16. Blade Structure

```text
resources/views/user/global/

├── dashboard.blade.php
├── rooms/
│   └── index.blade.php
├── orders/
│   ├── index.blade.php
│   └── show.blade.php
├── payments/
│   └── index.blade.php
├── statistics/
│   └── index.blade.php
├── notifications/
│   └── index.blade.php
├── profile/
│   └── index.blade.php
├── devices/
│   └── index.blade.php
└── security/
    └── index.blade.php
```

Blade Components:

```text
resources/views/components/user/global/

room-card.blade.php
order-card.blade.php
stat-card.blade.php
notification-item.blade.php
device-card.blade.php
payment-card.blade.php
```

CSS/UI:

```text
TailwindCSS
```

---

# 17. Global User và Room User Boundary

| Global User | Room User |
|---|---|
| `/me` | `/rooms/{room}` |
| Global Profile | Room Profile |
| My Rooms | Current Room |
| All Orders | Room Orders |
| Global Statistics | Room Statistics |
| All Payments | Room Payment |
| Global Notifications | Room Notifications |
| Devices / Security | Campaign |
| Global Account Status | Order |

Luồng tổng thể:

```text
                    PUBLIC
                       │
             ┌─────────┼─────────┐
             │         │         │
             /       /terms   /versions
             │
             ▼
          Google
             │
             ▼
        GLOBAL USER
             │
             ▼
            /me
             │
   ┌─────────┼───────────────┐
   │         │               │
My Rooms   Orders        Statistics
   │         │               │
Payments  Notifications   Devices
   │
   ▼
Select Room
   │
   ▼
/rooms/{room}
   │
   ▼
       ROOM USER
   │
   ├── Campaign
   ├── Menu
   ├── Order
   ├── Room History
   └── Room Profile
```

---

# 18. Security Rules

Tất cả Global User pages phải tuân thủ:

1. Laravel xác định Global User từ authenticated session/credential.
2. Không tin `global_user_id` gửi từ frontend.
3. Query luôn scope theo Global User hiện tại.
4. Room data phải kiểm tra Room membership.
5. Blocked Room User không được truy cập dữ liệu Room tương ứng.
6. Global Block chặn toàn bộ protected Global/Room pages.
7. Không expose token, token hash, OAuth secret hoặc full trusted credential.
8. Socket.IO channel phải được authorize.
9. Blade chỉ render dữ liệu Laravel đã authorize.
10. Supabase database không thay thế Laravel authorization/business rules.

---

# 19. Phân chia triển khai

## Phase 1 — Core

Ưu tiên:

```text
/me
/me/rooms
/me/orders
/me/profile
/me/devices
/me/notifications
```

Lý do:

- Hoàn thiện Global User flow.
- Hỗ trợ multi-room.
- Hỗ trợ Trusted Device.
- Có lịch sử Order tổng hợp.
- Có notification realtime.

## Phase 2 — Extended

Bổ sung:

```text
/me/payments
/me/statistics
/me/security
```

`/me/security` có thể được gộp vào `/me/devices` nếu muốn giảm số trang.

---

# 20. Danh sách trang Global User

| Trang | Route | Phase |
|---|---|---|
| Dashboard | `/me` | Phase 1 |
| My Rooms | `/me/rooms` | Phase 1 |
| Order History | `/me/orders` | Phase 1 |
| Order Detail | `/me/orders/{order}` | Phase 1 |
| Global Profile | `/me/profile` | Phase 1 |
| Notifications | `/me/notifications` | Phase 1 |
| Devices & Sessions | `/me/devices` | Phase 1 |
| Payments | `/me/payments` | Phase 2 |
| Statistics | `/me/statistics` | Phase 2 |
| Account & Security | `/me/security` | Phase 2 |
| Account Blocked | `/account-blocked` | Core/System |

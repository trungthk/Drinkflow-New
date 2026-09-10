# 2. Actor: Admin

Admin là quản trị viên theo Room.

**Đây là điểm rất quan trọng:**

Admin != global administrator

Admin chỉ quản lý room được assign.

**Ví dụ:**

```text
Admin A
├── IT Room
└── HCM Room
```

```text
Admin B
└── Marketing Room
```

## 2.1. Admin Login

**Route:**

```text
/admin/login
```

**Feature:**

email.
password.
captcha.
login throttling.
session regeneration.
remember login tùy policy.
logout.

DrinkFlow hiện có login admin và captcha.

Không nên giữ default password khi production.

## 2.2. Admin Dashboard

**Dashboard theo room:**

- Campaign active
- Orders today
- Active Room Users
- Outstanding debts
- Total sponsored
- Total spending
- Payment pending

**Có thể thêm:**

- Last campaign
- Upcoming campaign
- Recent activities

Admin không thấy global system KPI.

## 2.3. Room Switcher

**Nếu admin quản lý nhiều room:**

```text
/admin/{room}
```

**Header có:**

```text
Current Room:
IT Department ▼
```

**Laravel kiểm tra:**

```text
admin_rooms
```

trước khi cho switch.

## 2.4. Campaign Management

**Admin được:**

- `create`
- `edit`
- `view`
- `activate`
- `close`
- `cancel`
- `duplicate`
- `archive`

**Campaign fields:**

```text
name
restaurant
creator
sponsor
deadline
max_budget
flat_price
delivery_fee
discount
payment_account
description
status
```

Những thông số này tương ứng khá sát với Campaign Creator hiện có.

## 2.5. Food Crawler

**Admin nhập:**

- ShopeeFood URL
- GrabFood URL
- website URL

**Laravel crawler:**

- fetch restaurant
- extract items
- extract price
- extract category
- extract image

Admin preview trước khi import.

**Flow:**

```text
URL
 ↓
Crawl
 ↓
Preview
 ↓
Admin select products
 ↓
Import campaign_items
```

Không auto insert tất cả mà không preview.

## 2.6. Menu Management

**Sau crawler:**

**Admin:**

- add item
- edit item
- delete item
- hide item
- set sold out
- change price
- change category
- manage topping
- manage size

**Có thể thêm:**

- bulk enable
- bulk disable
- bulk price update

## 2.7. Launch Campaign

**Khi launch:**

**Laravel validate:**

- Room
- Menu
- Deadline
- Payment account
- Sponsor
- Budget

**Sau đó:**

status = active

**Dispatch:**

CampaignCreated

**Socket:**

```text
room:{roomId}
campaign.created
```

**Notification:**

- Chatwork
- Telegram
- Slack
- Webhook

DrinkFlow hiện đã gửi thông báo khi campaign được mở.

## 2.8. Live Orders

Đây là feature trọng tâm của Admin.

Order phải liên kết với `room_user_id`. Khi hiển thị, Admin có thể xem các thông tin cần thiết từ Global User thông qua Room User như avatar, họ tên và email, nhưng chỉ trong phạm vi Room được phân quyền.

Quan hệ:

```text
Order
  │
  ▼
Room User
  │
  ▼
Global User
```

**Admin xem:**

- User
- Order
- Size
- Sugar
- Ice
- Topping
- Note
- Payment
- Price
- Time
- Status

DrinkFlow hiện đã có Live Orders realtime.

**Socket:**

- `order.created`
- `order.updated`
- `order.deleted`

**Channel:**

```text
room:{roomId}
```

**Frontend Blade có thể:**

```text
socket event
    ↓
GET Blade partial
    ↓
refresh table
```

## 2.9. Item Aggregator

**Admin cần view tổng hợp:**

- Trà sữa Oolong M       5
- Trà sữa Oolong L       3
- Americano M             4
- Pudding                 6
- Trân châu              10

Feature này đã tồn tại trong DrinkFlow.

**Có thể bổ sung:**

- Copy to clipboard
- Print
- Export

## 2.10. Edit / Delete Order

**Admin có quyền:**

- View
- Edit
- Delete
- Cancel
- Unlock

Trong Room của mình.

**Nếu xóa order:**

OrderDeleted

**Socket:**

```text
user:{userId}
```

**User frontend nhận:**

`order.deleted`

và được phép order lại.

Behavior này phù hợp với logic hiện tại.

## 2.11. Close Campaign

**Admin nhấn:**

Chốt Campaign

**Laravel:**

- Lock campaign
- Prevent new orders
- Snapshot orders
- Aggregate products
- Calculate delivery
- Calculate discount
- Calculate sponsor
- Calculate user debt
- Generate settlement

**Sau đó:**

CampaignClosed

**Socket:**

```text
room:{roomId}
campaign.closed
```

## 2.12. Split Bill

Calculation phải server-side.

**Ví dụ:**

- Products     500,000
- Delivery      30,000
- Voucher      -50,000

- Final        480,000
- Sponsor      200,000

Users owe    280,000

**Có thể phân bổ:**

- By order amount
- Equal split
- Sponsor first
- Flat price
- Custom allocation

## 2.13. Debt Management

**Admin xem:**

- User
- Campaign
- Original amount
- Sponsor amount
- Debt
- Paid
- Remaining
- Status

DrinkFlow hiện đã có Debt Statistics và sponsor allocation.

**Actions:**

- mark paid
- mark unpaid
- partial payment
- adjust debt
- add note

Mỗi adjustment cần audit log.

## 2.14. Room User Management

Admin quản lý **Room User** trong các Room được phân quyền, không quản lý Global User toàn hệ thống.

Admin được phép:

- Xem Room User.
- Xem tóm tắt Global Profile gồm avatar, họ tên và email công ty.
- Xem `user_code`, trạng thái, ngày tham gia và lần hoạt động gần nhất.
- Xem orders trong Room.
- Xem debt trong Room.
- Block Room User.
- Unblock Room User.
- Remove Room User khỏi Room nếu business cho phép.
- Xem các device đã đăng ký với Room User nếu policy cho phép.
- Revoke device của Room User nếu policy cho phép.

Admin không được phép:

- Chỉnh sửa Google identity.
- Thay đổi global email.
- Thay đổi OAuth provider identity.
- Global block / global unblock.
- Hard-delete Global User.
- Merge Global Users.
- Xem membership của các Room không được phân quyền.

Room-level block phải sử dụng:

```text
room_users.status = blocked
```

Không thay đổi:

```text
global_users.status
```

Vì vậy user có thể bị block trong một Room nhưng vẫn hoạt động ở Room khác.

### Room User Detail

Admin có thể xem:

- Avatar.
- Name.
- Email.
- Normalized Name.
- User Code.
- Joined At.
- Last Active.
- Status.
- Devices.
- Order Count.
- Debt.

Nguồn dữ liệu:

```text
Avatar / Name / Email
→ Global User

User Code / Status / Joined At
→ Room User
```

### Room User Device

Nếu bật chức năng quản lý device, Admin có thể xem:

- Device UUID dạng rút gọn.
- Verified At.
- Last Seen.
- Revoked At.
- Status.

Không hiển thị trusted token hoặc `token_hash`.

Khi revoke device, device đó phải xác thực lại theo flow User Authentication trước khi được tin cậy trở lại.

## 2.15. Payment Accounts

**Admin có thể:**

- add account
- edit account
- disable account
- set default

**Scope:**

`room`

**Fields:**

```text
bank_code
bank_name
account_number
account_name
is_default
status
```

DrinkFlow hiện có payment account và VietQR.

## 2.16. Room Settings

**Admin được chỉnh:**

- `name`
- `description`
- `avatar`
- default sponsor
- default payment account
- `timezone`
- `language`

DrinkFlow hiện đã có các room settings này.

**Không được chỉnh:**

- room owner
- global security
- global maintenance
- system DB
- global admin

## 2.17. Notification Channels

**Admin quản lý notification của room:**

- Chatwork
- Slack
- Telegram
- Webhook

Đây là tính năng hiện có.

**Admin được:**

- `configure`
- `enable`
- `disable`
- `test`

**Nhưng credential:**

`encrypted`

**và khi load form chỉ hiển thị:**

- ••••••••••
- Configured

Không trả token hiện tại xuống HTML.

## 2.18. Reports

**Admin xem report room:**

- Campaign count
- Order count
- Spending
- Sponsor amount
- Debt
- User participation
- Popular drinks
- Popular stores

**Filter:**

- `today`
- `week`
- `month`
- `quarter`
- `year`
- `custom`

## 2.19. Audit của Room

**Admin có thể xem audit liên quan room:**

- Campaign created
- Campaign edited
- Order deleted
- Debt edited
- Payment marked
- Room User blocked
- Settings changed

Nhưng không được xem audit của room khác.

# DrinkFlow Database Analysis & Design

## 1. Mục tiêu thiết kế

Database của DrinkFlow phải hỗ trợ đồng thời:

- Multi-room.
- Global User + Room User + Device Identity.
- Google OAuth identity.
- Campaign đặt món.
- Menu / campaign item / topping.
- Order và Single Active Order Lock.
- Debt / settlement / sponsor allocation.
- Payment Account / VietQR.
- Notification channels.
- Admin / Superadmin.
- Audit log.
- Realtime Socket.IO.
- Queue / failed jobs.
- Version management.
- System settings.
- Security monitoring.

Nguyên tắc quan trọng:

```text
Laravel
→ Source of truth

Supabase PostgreSQL
→ Primary relational database

Socket.IO
→ Realtime transport only
```

Browser không được truy cập trực tiếp Supabase.

---

# 2. Database Principles

## 2.1. Room là business boundary chính

Hầu hết dữ liệu nghiệp vụ phải có `room_id` hoặc được truy ra chắc chắn thông qua relation có `room_id`.

Ví dụ:

```text
rooms
├── room_users
├── campaigns
├── payment_accounts
├── notification_channels
└── room_audit_logs
```

Admin chỉ được đọc dữ liệu của Room được assign.

Superadmin được đọc global data.

---

## 2.2. Global identity khác Room membership

Không dùng một bảng `users` chung cho tất cả nghiệp vụ.

Mô hình:

```text
global_users
    │
    ├── oauth_identities
    │
    └── room_users
            │
            └── room_user_devices
```

Ý nghĩa:

- `global_users`: identity toàn hệ thống.
- `oauth_identities`: identity provider như Google.
- `room_users`: membership trong từng Room.
- `room_user_devices`: trusted device theo Room User.

---

## 2.3. Order và Debt phải tham chiếu Room User

Các dữ liệu sau nên gắn với `room_user_id`:

- Orders.
- Debt.
- Room block.
- Room activity.
- Room-specific analytics.

Không gắn trực tiếp với `global_user_id` nếu nghiệp vụ thuộc Room.

---

# 3. Core Tables

## 3.1. `global_users`

Đại diện cho identity toàn hệ thống.

```text
id
name
normalized_name
email
avatar_url
status
last_login_at
created_at
updated_at
```

Recommended constraints:

```text
PRIMARY KEY(id)
UNIQUE(email)
INDEX(status)
```

Status:

```text
active
blocked
disabled
```

Laravel Enum đề xuất:

```php
enum GlobalUserStatus: string
{
    case Active = 'active';
    case Blocked = 'blocked';
    case Disabled = 'disabled';
}
```

---

## 3.2. `oauth_identities`

Lưu identity provider.

```text
id
global_user_id
provider
provider_user_id
provider_email
created_at
updated_at
```

Constraints:

```text
FOREIGN KEY(global_user_id) REFERENCES global_users(id)
UNIQUE(provider, provider_user_id)
INDEX(global_user_id)
```

Provider hiện tại:

```text
google
```

Không lưu plaintext OAuth access token hoặc refresh token nếu không thật sự cần.

Nếu bắt buộc phải lưu token, phải encrypt bằng Laravel application encryption và không expose xuống UI.

---

## 3.3. `rooms`

```text
id
name
slug
description
avatar_url
status
timezone
language
settings
created_at
updated_at
```

Constraints:

```text
UNIQUE(slug)
INDEX(status)
```

Status:

```text
active
disabled
archived
```

`settings` có thể dùng `jsonb` cho các config phụ ít query.

Không nên nhét business-critical relational data vào JSON nếu có nhu cầu filter/report/index.

---

## 3.4. `room_users`

Membership của Global User trong Room.

```text
id
room_id
global_user_id
user_code
display_name
normalized_name
status
joined_at
last_active_at
created_at
updated_at
```

Constraints:

```text
FOREIGN KEY(room_id) REFERENCES rooms(id)
FOREIGN KEY(global_user_id) REFERENCES global_users(id)

UNIQUE(room_id, global_user_id)
UNIQUE(room_id, user_code)

INDEX(room_id, status)
INDEX(global_user_id)
INDEX(last_active_at)
```

Status:

```text
active
blocked
removed
```

Lưu ý:

```text
global_users.status = blocked
```

chặn toàn hệ thống.

Trong khi:

```text
room_users.status = blocked
```

chỉ chặn Room cụ thể.

---

## 3.5. `room_user_devices`

```text
id
room_user_id
device_uuid
token_hash
verified_at
last_seen_at
revoked_at
created_at
updated_at
```

Constraints:

```text
FOREIGN KEY(room_user_id) REFERENCES room_users(id)
UNIQUE(room_user_id, device_uuid)
INDEX(device_uuid)
INDEX(last_seen_at)
```

Không dùng `device_uuid` một mình làm authentication secret.

`token_hash` phải là hash của trusted device token, không lưu plaintext.

---

# 4. Admin & Superadmin

## 4.1. `admin_accounts`

```text
id
name
email
password
role
status
last_login_at
created_at
updated_at
```

Role:

```text
admin
superadmin
```

Constraints:

```text
UNIQUE(email)
INDEX(role)
INDEX(status)
```

Enum:

```php
enum AdminRole: string
{
    case Admin = 'admin';
    case SuperAdmin = 'superadmin';
}
```

---

## 4.2. `admin_rooms`

Pivot phân quyền Room cho Admin.

```text
id
admin_id
room_id
created_at
```

Constraints:

```text
FOREIGN KEY(admin_id) REFERENCES admin_accounts(id)
FOREIGN KEY(room_id) REFERENCES rooms(id)

UNIQUE(admin_id, room_id)
INDEX(room_id)
```

Superadmin không cần record trong `admin_rooms`.

---

# 5. Campaign

## 5.1. `campaigns`

```text
id
room_id
name
restaurant
creator_admin_id
sponsor_name
deadline
max_budget
flat_price
delivery_fee
discount
payment_account_id
description
status
started_at
closed_at
created_at
updated_at
```

Constraints:

```text
FOREIGN KEY(room_id) REFERENCES rooms(id)
FOREIGN KEY(creator_admin_id) REFERENCES admin_accounts(id)
FOREIGN KEY(payment_account_id) REFERENCES payment_accounts(id)

INDEX(room_id, status)
INDEX(deadline)
INDEX(created_at)
```

Status:

```text
draft
scheduled
active
closing
closed
cancelled
archived
```

---

## 5.2. `campaign_items`

```text
id
campaign_id
name
normalized_name
category
description
image_url
base_price
status
sort_order
source_url
source_item_key
created_at
updated_at
```

Constraints:

```text
FOREIGN KEY(campaign_id) REFERENCES campaigns(id)
INDEX(campaign_id, status)
INDEX(normalized_name)
INDEX(category)
```

Status:

```text
active
hidden
sold_out
temporarily_unavailable
```

---

## 5.4. `campaign_item_toppings`

```text
id
campaign_item_id
name
price
status
sort_order
created_at
updated_at
```

---

# 6. Orders

## 6.1. `orders`

```text
id
room_id
campaign_id
room_user_id
payment_method
subtotal
delivery_amount
discount_amount
sponsor_amount
final_amount
status
note
submitted_at
completed_at
cancelled_at
created_at
updated_at
```

Constraints:

```text
FOREIGN KEY(room_id) REFERENCES rooms(id)
FOREIGN KEY(campaign_id) REFERENCES campaigns(id)
FOREIGN KEY(room_user_id) REFERENCES room_users(id)

INDEX(room_id, created_at)
INDEX(campaign_id, status)
INDEX(room_user_id, created_at)
INDEX(payment_method)
```

Order status:

```text
submitted
confirmed
ordering
ordered
delivering
completed
cancelled
```

`room_id` có thể suy ra từ Campaign, nhưng giữ trực tiếp giúp:

- Query/report nhanh.
- Scope rõ.
- Audit dễ.
- Authorization đơn giản hơn.

Laravel phải validate consistency:

```text
orders.room_id == campaigns.room_id
orders.room_id == room_users.room_id
```

---

## 6.2. Single Active Order Lock

Rule:

```text
Một Room User chỉ có một active order trong một Campaign.
```

Không chỉ kiểm tra ở application layer.

Nên enforce bằng PostgreSQL partial unique index.

Ví dụ concept:

```sql
CREATE UNIQUE INDEX uniq_active_order_per_room_user_campaign
ON orders (campaign_id, room_user_id)
WHERE status IN (
    'submitted',
    'confirmed',
    'ordering',
    'ordered',
    'delivering'
);
```

Laravel vẫn phải dùng transaction.

Flow:

```text
DB Transaction
    │
    ▼
lockForUpdate()
    │
    ▼
Check existing active order
    │
    ▼
Create order
    │
    ▼
Commit
```

---

## 6.3. `order_items`

Order phải snapshot dữ liệu tại thời điểm đặt.

```text
id
order_id
campaign_item_id
item_name
size_name
unit_price
quantity
ice_percent
sugar_percent
line_subtotal
note
created_at
updated_at
```

Không chỉ dựa vào Campaign Item hiện tại vì giá/menu có thể thay đổi sau đó.

---

## 6.4. `order_item_toppings`

```text
id
order_item_id
campaign_item_topping_id
topping_name
unit_price
quantity
subtotal
created_at
updated_at
```

Snapshot:

- Topping name.
- Unit price.

---

# 7. Debt & Settlement

## 7.1. `debts`

```text
id
room_id
campaign_id
room_user_id
original_amount
sponsor_amount
adjustment_amount
paid_amount
remaining_amount
status
note
created_at
updated_at
```

Constraints:

```text
FOREIGN KEY(room_id) REFERENCES rooms(id)
FOREIGN KEY(campaign_id) REFERENCES campaigns(id)
FOREIGN KEY(room_user_id) REFERENCES room_users(id)

UNIQUE(campaign_id, room_user_id)
INDEX(room_id, status)
INDEX(room_user_id, status)
```

Status:

```text
unpaid
partial
paid
waived
```

---

## 7.2. `debt_adjustments`

Không chỉnh debt mà không có history.

```text
id
debt_id
admin_id
type
amount
reason
before_amount
after_amount
created_at
```

Type:

```text
increase
decrease
waive
correction
```

Mọi adjustment phải tạo audit log.

---

## 7.3. `debt_payments`

```text
id
debt_id
amount
payment_method
reference
paid_at
created_by_admin_id
created_at
```

Không chỉ dùng một boolean `paid`.

Cho phép partial payment.

---

# 8. Payment Accounts

## 8.1. `payment_accounts`

```text
id
room_id
bank_code
bank_name
account_number
account_name
is_default
status
created_at
updated_at
```

Constraints:

```text
FOREIGN KEY(room_id) REFERENCES rooms(id)
INDEX(room_id, status)
```

Rule:

- Chỉ một default payment account active cho mỗi Room.
- Nên enforce bằng transaction hoặc partial unique index nếu phù hợp.

---

# 9. Notification

## 9.1. `notification_channels`

```text
id
room_id
type
name
config_encrypted
status
created_at
updated_at
```

Type:

```text
chatwork
slack
telegram
webhook
```

Không lưu token rời thành các column dễ expose nếu không cần.

`config_encrypted` nên sử dụng encrypted cast / Laravel encryption.

UI chỉ trả:

```text
configured = true
masked_value = ••••••••••
```

Không trả secret hiện tại.

---

## 9.2. `system_notification_channels`

Notification cấp hệ thống.

```text
id
type
name
config_encrypted
status
created_at
updated_at
```

Chỉ Superadmin quản lý.

---

# 10. Settings

## 10.1. `system_settings`

```text
id
key
value
type
is_secret
updated_by_admin_id
created_at
updated_at
```

Không nên lưu secret plaintext.

Các key ví dụ:

```text
app.name
app.default_language
app.default_timezone
auth.google_enabled
auth.allowed_company_domains
auth.require_verified_email
maintenance.enabled
upload.max_size
crawler.timeout
session.device_trust_days
```

Nếu setting là secret:

- Encrypt.
- Không render raw value ra HTML.

---

## 10.2. `room_settings`

Nếu Room Settings phát triển lớn, nên tách khỏi `rooms.settings`.

```text
id
room_id
key
value
type
is_secret
updated_at
```

Nếu config ít và đơn giản có thể giữ `jsonb` trong `rooms.settings`.

Không dùng cả hai song song mà không có ownership rõ.

---

# 11. Audit

## 11.1. `audit_logs`

```text
id
actor_type
actor_id
event
target_type
target_id
room_id
ip_address
user_agent
device_uuid
before_data
after_data
metadata
created_at
```

Actor type:

```text
user
admin
superadmin
system
```

Target type:

```text
global_user
room_user
room_user_device
oauth_identity
campaign
order
debt
payment_account
room
admin
system_setting
```

`before_data`, `after_data`, `metadata` dùng `jsonb`.

Index:

```text
INDEX(actor_type, actor_id)
INDEX(target_type, target_id)
INDEX(room_id, created_at)
INDEX(event)
INDEX(created_at)
```

Không lưu secret/token đầy đủ vào audit.

---

# 12. Version Management

## 12.1. `versions`

```text
id
version
title
changelog
release_date
force_refresh
important
created_by_admin_id
created_at
updated_at
```

Constraint:

```text
UNIQUE(version)
```

---

# 13. Security Events

## 13.1. `security_events`

```text
id
type
severity
actor_type
actor_id
room_id
ip_address
device_uuid
metadata
created_at
```

Type ví dụ:

```text
failed_login
blocked_ip
invalid_socket_token
unauthorized_room_access
rate_limit_hit
google_oauth_failure
invalid_company_domain
oauth_identity_mismatch
device_authentication_failure
device_revoked
suspicious_room_registration
```

Không lưu credential nhạy cảm trong metadata.

---

# 14. Realtime & Socket.IO

Không cần lưu tất cả Socket.IO event vào database.

Laravel publish event sau khi transaction thành công.

Flow:

```text
DB Transaction
    │
    ▼
Commit
    │
    ▼
Domain Event
    │
    ▼
Realtime Publisher
    │
    ▼
Socket.IO
```

Không emit event trước khi transaction commit.

Event payload nên nhỏ:

```json
{
  "order_id": 100,
  "campaign_id": 20,
  "room_id": 4
}
```

Frontend nhận signal rồi fetch lại dữ liệu authoritative từ Laravel.

---

# 15. Queue

Laravel dùng database hoặc Redis queue tùy môi trường.

Business jobs ví dụ:

```text
SendChatworkNotification
SendTelegramNotification
SendSlackNotification
SendWebhookNotification
CrawlMenu
CloseCampaign
GenerateReport
```

Nếu dùng Laravel failed jobs, giữ table tiêu chuẩn:

```text
jobs
job_batches
failed_jobs
```

---

# 16. Recommended Foreign Key Strategy

## Restrict

Dùng khi không muốn xóa parent nếu đang có dữ liệu quan trọng.

Ví dụ:

```text
rooms
campaigns
orders
global_users
```

## Cascade

Chỉ dùng với dữ liệu phụ có ownership rõ.

Ví dụ:

```text
order
→ order_items
→ order_item_toppings
```

Có thể cascade delete order items khi hard-delete order trong test/dev.

Production nên ưu tiên soft-delete / status nếu cần audit.

---

# 17. Soft Delete Strategy

Không nên áp dụng `SoftDeletes` cho mọi bảng.

Recommended:

### Có thể dùng SoftDeletes

- `global_users`
- `rooms`
- `admin_accounts`
- `campaigns`

nếu business cần restore.

### Không nhất thiết

- Pivot.
- Audit logs.
- Security events.
- Debt payments.
- Order snapshots.

Order production nên ưu tiên:

```text
status = cancelled
```

thay vì hard delete.

Nếu business vẫn cần Admin delete order để unlock, nên cân nhắc:

```text
deleted_at
```

và partial unique index phải loại trừ deleted rows.

---

# 18. Data Consistency Rules

Các rule phải enforce:

```text
room_users.room_id
=
campaigns.room_id
=
orders.room_id
```

Order Item phải thuộc Campaign Item của đúng Campaign.

Topping phải thuộc đúng Campaign Item.

Debt phải thuộc cùng:

```text
room
campaign
room_user
```

Payment Account của Campaign phải thuộc cùng Room.

Admin thao tác phải được authorize với Room.

---

# 19. Transaction Boundaries

Bắt buộc transaction cho:

## Create Order

```text
Validate
→ Check active order
→ Create Order
→ Create Order Items
→ Create Toppings
→ Commit
```

## Close Campaign

```text
Lock Campaign
→ Prevent new orders
→ Snapshot
→ Aggregate
→ Calculate settlement
→ Generate debts
→ Set closed
→ Commit
```

## Debt Adjustment

```text
Lock Debt
→ Calculate adjustment
→ Save
→ Create adjustment history
→ Audit
→ Commit
```

## Join Room

```text
Resolve Global User
→ Check duplicate Room User
→ Create Room User
→ Register device
→ Commit
```

---

# 20. Recommended Indexes

Ít nhất:

```text
global_users.email
oauth_identities(provider, provider_user_id)

room_users(room_id, status)
room_users(global_user_id)
room_users(room_id, user_code)

room_user_devices(room_user_id, device_uuid)
room_user_devices(device_uuid)

campaigns(room_id, status)
campaigns(deadline)

campaign_items(campaign_id, status)

orders(campaign_id, status)
orders(room_user_id, created_at)
orders(room_id, created_at)

debts(room_id, status)
debts(room_user_id, status)

audit_logs(room_id, created_at)
audit_logs(target_type, target_id)

security_events(type, created_at)
```

Không index mọi column.

Chỉ index theo query/report thực tế.

---

# 21. Naming Convention

Table:

```text
snake_case
plural
```

Ví dụ:

```text
global_users
room_users
payment_accounts
```

Foreign key:

```text
{singular_table}_id
```

Ví dụ:

```text
room_id
global_user_id
room_user_id
campaign_id
```

Boolean:

```text
is_default
is_secret
force_refresh
important
```

Timestamp nghiệp vụ:

```text
joined_at
verified_at
last_seen_at
submitted_at
closed_at
paid_at
revoked_at
```

Status:

- Dùng string + PHP Enum.
- Không dùng magic integer nếu không có lý do rõ.

---

# 22. Migration Order

Recommended migration sequence:

```text
1. global_users
2. oauth_identities
3. rooms
4. room_users
5. room_user_devices
6. admin_accounts
7. admin_rooms
8. payment_accounts
9. campaigns
10. campaign_items
11. campaign_item_sizes
12. campaign_item_toppings
13. orders
14. order_items
15. order_item_toppings
16. debts
17. debt_adjustments
18. debt_payments
19. notification_channels
20. system_notification_channels
21. system_settings
22. audit_logs
23. security_events
24. versions
```

---

# 23. ERD Overview

```text
global_users
 ├── oauth_identities
 └── room_users
      ├── room_user_devices
      ├── orders
      └── debts

rooms
 ├── room_users
 ├── admin_rooms
 ├── campaigns
 │    ├── campaign_items
 │    │    ├── campaign_item_sizes
 │    │    └── campaign_item_toppings
 │    └── orders
 │         └── order_items
 │              └── order_item_toppings
 ├── debts
 ├── payment_accounts
 └── notification_channels

admin_accounts
 ├── admin_rooms
 ├── campaigns
 ├── debt_adjustments
 └── audit_logs
```

---

# 24. Database Rules Summary

```text
Global identity
→ global_users

Google identity
→ oauth_identities

Room membership
→ room_users

Trusted device
→ room_user_devices

Room business data
→ room_id

Order / Debt identity
→ room_user_id

Admin authorization
→ admin_rooms

Global administration
→ superadmin

Secrets
→ encrypted

Realtime
→ after DB commit

Single Active Order
→ Laravel transaction + PostgreSQL partial unique index
```

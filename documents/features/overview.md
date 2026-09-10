# 4. Ma trận quyền tổng quát

DrinkFlow sử dụng ba actor chính:

- **User**: quản lý identity và dữ liệu của chính mình, tham gia một hoặc nhiều Room.
- **Admin**: quản lý dữ liệu trong các Room được phân quyền.
- **Superadmin**: quản trị Global User và toàn bộ hệ thống.

Đối với User, identity được chia thành:

```text
Google Identity
      │
      ▼
Global User
      │
      ▼
Room User
      │
      ▼
Device Identity
```

Trong đó:

- **Global User** là danh tính chung trên toàn DrinkFlow.
- **Room User** là membership của Global User trong từng Room.
- **Device Identity** giúp thiết bị đã xác thực truy cập lại Room nhanh.
- Google OAuth dùng để xác minh danh tính bằng email công ty.

## 4.1. Permission Matrix

| Feature | User | Admin | Superadmin |
| --- | --- | --- | --- |
| Google Authentication | ✅ Self | — | — |
| Global User registration | ✅ Self | ❌ | ✅ Manage |
| Global Profile | ✅ Own | Room summary only | ✅ |
| Room registration | ✅ Self | ❌ | ✅ Manage |
| Trusted Device authentication | ✅ | ❌ | ✅ Manage |
| Room Users | Own | ✅ Assigned Room | ✅ |
| Global Users | Own Profile | ❌ | ✅ |
| Global User Block | ❌ | ❌ | ✅ |
| Room User Block | ❌ | ✅ Assigned Room | ✅ |
| OAuth Identity Management | Auth only | ❌ | ✅ |
| Device revoke | Own flow | Optional Assigned Room | ✅ |
| Campaign view | ✅ | ✅ Assigned Room | ✅ |
| Campaign create | ❌ | ✅ Assigned Room | ✅ |
| Campaign close | ❌ | ✅ Assigned Room | ✅ |
| Đặt món | ✅ | — | — |
| Live Orders | Own order | ✅ Assigned Room | ✅ |
| Debt cá nhân | ✅ | ✅ Assigned Room | ✅ |
| Debt management | ❌ | ✅ Assigned Room | ✅ |
| Payment Account | ❌ | ✅ Assigned Room | ✅ |
| Notification config | ❌ | ✅ Assigned Room | ✅ |
| Room settings | ❌ | ✅ Giới hạn | ✅ |
| Create Room | ❌ | ❌ | ✅ |
| Create Admin | ❌ | ❌ | ✅ |
| Assign Admin | ❌ | ❌ | ✅ |
| Global settings | ❌ | ❌ | ✅ |
| Google/company-domain settings | ❌ | ❌ | ✅ |
| Maintenance | ❌ | ❌ | ✅ |
| Audit global | ❌ | ❌ | ✅ |
| System reset | ❌ | ❌ | ✅ |
| Socket monitoring | ❌ | ❌ | ✅ |

Nguyên tắc quan trọng:

```text
Room User != Global User
```

Admin chỉ quản lý Room User trong phạm vi Room được phân quyền. Superadmin mới có quyền quản lý Global User.

# 5. Socket.IO Event Matrix

Socket.IO chỉ đóng vai trò realtime transport. Laravel vẫn là source of truth và quyết định authorization.

## 5.1. Socket Channels

```text
user:{roomUserId}
room:{roomId}
global-user:{globalUserId}
admin:{adminId}
superadmin
system
```

Trong đó:

- `user:{roomUserId}` dùng cho event cá nhân gắn với Room như order/debt/block.
- `room:{roomId}` dùng cho event chung của Room.
- `global-user:{globalUserId}` chỉ dùng cho event global của chính user khi thật sự cần.
- `admin:{adminId}` dùng cho event riêng của Admin.
- `superadmin` dùng cho event global dành cho Superadmin.
- `system` dùng cho event hệ thống được phép broadcast.

## 5.2. Event Matrix

| Event | User | Admin | Superadmin |
| --- | --- | --- | --- |
| `campaign.created` | ✅ Room | ✅ Room | ✅ |
| `campaign.updated` | ✅ Room | ✅ Room | ✅ |
| `campaign.closed` | ✅ Room | ✅ Room | ✅ |
| `order.created` | Owner / Room User | ✅ Room | Optional |
| `order.updated` | Owner / Room User | ✅ Room | Optional |
| `order.deleted` | Owner / Room User | ✅ Room | Optional |
| `debt.updated` | Owner / Room User | ✅ Room | Optional |
| `room_user.blocked` | Owner / Room User | ✅ Room | ✅ |
| `room_user.unblocked` | Owner / Room User | ✅ Room | ✅ |
| `global_user.blocked` | Owner / Global User | ❌ | ✅ |
| `room.updated` | Member | ✅ Room | ✅ |
| `admin.updated` | ❌ | Admin owner | ✅ |
| `system.maintenance` | ✅ | ✅ | ✅ |
| `system.security-alert` | ❌ | ❌ | ✅ |

Order-related events phải sử dụng Room User channel:

```text
user:{roomUserId}
```

Không dùng Global User channel cho order nếu event chỉ thuộc một Room.

Ví dụ:

```text
Global User #15

IT:
Room User #100

Marketing:
Room User #217
```

Order trong Marketing emit:

```text
user:217
```

không emit:

```text
global-user:15
```

## 5.3. Socket Token cho User

Flow:

```text
Laravel
   │
   ▼
Resolve Global User
   │
   ▼
Resolve Room User
   │
   ▼
Authorize Room
   │
   ▼
Issue Short-lived Socket Token
```

Payload ví dụ:

```json
{
  "actor_type": "user",
  "global_user_id": 15,
  "room_user_id": 217,
  "room_id": 4
}
```

Socket.IO gateway verify token và tự join các channel đã được Laravel authorize:

```text
user:217
room:4
```

Browser không được tự claim hoặc quyết định:

```text
global_user_id
room_user_id
room_id
```

# 6. Phân tách UI

```text
resources/views/

├── layouts/
│   ├── user.blade.php
│   ├── admin.blade.php
│   └── superadmin.blade.php
│
├── user/
│   ├── auth/
│   ├── rooms/
│   ├── home.blade.php
│   ├── campaign.blade.php
│   ├── order.blade.php
│   ├── history.blade.php
│   └── profile.blade.php
│
├── admin/
│   ├── dashboard.blade.php
│   ├── campaigns/
│   ├── orders/
│   ├── debts/
│   ├── room-users/
│   ├── reports/
│   ├── payment-accounts/
│   └── room-settings/
│
└── superadmin/
    ├── dashboard.blade.php
    ├── rooms/
    ├── admins/
    ├── global-users/
    ├── oauth-identities/
    ├── campaigns/
    ├── debts/
    ├── notifications/
    ├── system/
    ├── audit/
    └── security/
```

Components:

```text
resources/views/components/

├── ui/
├── user/
├── admin/
└── superadmin/
```

# 7. Menu đề xuất

## User

- Trang chủ.
- Chiến dịch.
- Lịch sử.
- Thống kê.
- Room của tôi.
- Cá nhân.

## Admin

- Dashboard.
- Campaigns.
- Live Orders.
- Debt.
- Room Users.
- Payment Accounts.
- Reports.
- Room Settings.
- Notifications.

## Superadmin

- Dashboard.
- Rooms.
- Admins.
- Global Users.
- Campaigns.
- Debt Overview.
- Notifications.
- System Settings.
- Versions.
- Queue.
- Socket Status.
- Security.
- Audit Logs.

# 8. Actor Boundary

## 8.1. User Scope

```text
USER
 │
 ├── Own Global Identity
 │    ├── Name
 │    ├── Email
 │    ├── Avatar
 │    └── Google Identity
 │
 ├── Own Room Membership
 │    ├── User Code
 │    ├── Status
 │    └── Joined Date
 │
 ├── Own Device Identity
 │
 └── Own Business Data
      ├── Orders
      ├── Debt
      └── Analytics
```

## 8.2. Admin Scope

```text
ADMIN
 │
 └── Assigned Rooms
      ├── Room Users
      ├── Campaigns
      ├── Orders
      ├── Debt
      ├── Payment Accounts
      ├── Reports
      ├── Notifications
      └── Room Settings
```

Admin có thể xem profile summary của Global User thông qua Room User, nhưng không được quản lý Global User hoặc OAuth Identity.

Admin block:

```text
room_users.status = blocked
```

## 8.3. Superadmin Scope

```text
SUPERADMIN
 │
 └── Global System
      ├── Global Users
      ├── OAuth Identities
      ├── Room Users
      ├── Devices
      ├── Rooms
      ├── Admins
      ├── Campaigns
      ├── System Settings
      ├── Security
      └── Audit Logs
```

Superadmin global block:

```text
global_users.status = blocked
```

Global block không cần thay đổi `room_users.status`. Membership của user được giữ nguyên để có thể khôi phục đúng trạng thái khi global unblock.

# 9. User Data Model Terminology

Toàn bộ hệ thống phải thống nhất terminology:

```text
global_users
oauth_identities
room_users
room_user_devices
```

Không sử dụng chung chung `users`, `user_devices` hoặc `room_members` cho schema User mới nếu các bảng đó không còn là source of truth.

Quan hệ:

```text
Global User
 │
 ├── OAuth Identities
 │
 └── Room Users
       │
       ├── Room
       ├── Orders
       ├── Debt
       └── Room User Devices
```

Các dữ liệu thuộc Room như:

```text
Order
Debt
Room block
Room activity
Room analytics
```

phải ưu tiên liên kết với:

```text
room_user_id
```

Các dữ liệu identity/global như:

```text
Google identity
Global profile
Global block
Global personal analytics
```

liên kết với:

```text
global_user_id
```

# 10. Authorization Principle

Nguyên tắc xuyên suốt:

```text
User
→ Own Global Identity
→ Own Room Memberships
→ Own Devices
→ Own Orders / Debt

Admin
→ Assigned Room Data
→ Room Users
→ Never Global User Administration

Superadmin
→ Global System
→ Global Users
→ OAuth Identities
→ All Room Memberships
```

Các boundary này phải được enforce đồng thời ở:

- Laravel Middleware.
- Laravel Policies.
- Query Scopes.
- Controllers / Actions.
- Blade UI.
- Reports.
- Socket.IO token.
- Socket.IO channels.
- Audit Logs.

Không được coi việc ẩn menu hoặc button ở frontend là authorization.

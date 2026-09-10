# 3. Actor: Superadmin

Superadmin là actor quản lý toàn bộ DrinkFlow.

Không nên coi Superadmin chỉ là “Admin có thêm vài menu”.

Nó là system administration layer.

## 3.1. Superadmin Dashboard

**Global dashboard:**

- Total Rooms
- Total Global Users
- Total Admins

- Active Campaigns
- Orders Today

Outstanding Debt

- Socket Connections
- Queue Health
- System Health

**Có thể thêm:**

- DB latency
- Supabase status
- Socket.IO status
- Queue failed jobs
- Notification errors

## 3.2. Room Management

**Superadmin CRUD:**

- create room
- edit room
- enable room
- disable room
- archive room

**Fields:**

```text
name
slug
description
avatar
status
settings
created_at
```

**Có thể xem:**

- room users count
active room users
blocked room users
- admins count
- campaign count
- orders count

## 3.3. Admin Management

Đây là feature chỉ Superadmin có.

**Actions:**

- create admin
- edit admin
- block admin
- unblock admin
- reset password
- assign rooms
- remove rooms
- promote superadmin
- demote superadmin

**Nên có safeguard:**

Không cho superadmin tự demote chính mình
nếu đây là superadmin cuối cùng.

## 3.4. Admin → Room Assignment

**UI:**

Admin: Nguyen Van A

Assigned Rooms

- [x] IT
- [x] Marketing
- [ ] Accounting

**Database:**

```text
admin_rooms
```

**Sau thay đổi:**

AdminRoomsUpdated

Socket token lần sau phải reflect permissions mới.

## 3.5. Global User Management

Superadmin quản lý Global User trên toàn hệ thống.

Mô hình:

```text
Global User
├── OAuth Identities
├── Room Memberships
└── Room User Devices
```

Superadmin có thể xem:

- Avatar.
- Name.
- Normalized Name.
- Email.
- Global status.
- OAuth provider identity.
- Room memberships.
- Devices.
- Last Login.
- Created At.

Actions:

- Search Global Users.
- View Global User.
- Global block.
- Global unblock.
- View Room memberships.
- Remove Room membership khi cần.
- View devices.
- Revoke device.
- Merge duplicated Global Users theo quy trình an toàn.
- Delete Global User nếu business cho phép.

Khác với Admin:

```text
Admin
→ block room_users.status trong Room được phân quyền

Superadmin
→ block global_users.status trên toàn hệ thống
```

Khi `global_users.status = blocked`:

- Không cho truy cập Room.
- Không cho order.
- Không cho đăng ký Room mới.
- Không cấp Socket.IO token mới.
- Các Room User cũ vẫn giữ nguyên trạng thái riêng.

Không cần đổi toàn bộ `room_users.status` khi Global User bị global block. Nhờ đó khi global unblock, các membership cũ vẫn được bảo toàn.

### OAuth Identity Management

Superadmin có thể xem metadata identity:

- Provider.
- Provider Email.
- Provider User ID.
- Linked At.
- Last Login nếu có.

Không hiển thị OAuth access token, refresh token hoặc credential nhạy cảm.

OAuth identity được lưu riêng trong:

```text
oauth_identities
```

Target audit nên phân biệt:

```text
global_user
room_user
room_user_device
oauth_identity
```

## 3.6. Global Campaign Management

**Superadmin xem tất cả:**

- Room
- Campaign
- Admin
- Restaurant
- Orders
- Value
- Status

**Có quyền:**

- `view`
- force close
- force cancel

Nhưng nên hạn chế việc chỉnh sửa trực tiếp campaign đang chạy nếu không cần.

## 3.7. Global Debt Overview

**Superadmin xem:**

- Total debt
- Debt by room
- Debt by month
- Debt by user
- Debt by campaign
- Sponsor exposure

Có thể export.

## 3.8. Global Notification Settings

**Phân biệt:**

Room notification

`vs`

System notification

**Room:**

Admin quản lý

**Global:**

Superadmin quản lý

**Ví dụ global channel:**

```text
System error → Dev Telegram
Security alert → Slack
Failed queue → Chatwork
```

## 3.9. System Settings

Các thiết lập authentication cấp hệ thống cũng thuộc quyền Superadmin:

- Google OAuth Enabled.
- Allowed Company Domains.
- Require Verified Email.

Ví dụ:

```text
company.com
company.co.jp
subsidiary.com
```

Admin không được thay đổi các thiết lập này vì chúng ảnh hưởng authentication của toàn hệ thống.


**Các setting cấp hệ thống:**

- Application name
- Default language
- Default timezone
- Maintenance
- Version
- Default limits
- Maximum upload
- Crawler settings
- Session settings

DrinkFlow hiện có System Settings và maintenance.

Sau khi có Superadmin thì phần này nên chuyển khỏi Admin.

## 3.10. Maintenance Mode

**Chỉ Superadmin:**

- Enable
- Disable
- Schedule

**Ví dụ:**

```text
Maintenance:
10/09/2026
22:00 - 22:30
```

**Socket:**

`system.maintenance`

**Broadcast:**

all connected clients

## 3.11. System Reset

Feature nguy hiểm nhất.

DrinkFlow hiện đã có reset toàn bộ hệ thống.

**Sau khi có Superadmin:**

- Admin: NO
- Superadmin: YES

**Nên yêu cầu:**

- Password confirmation
- +
- Type exact phrase

RESET DRINKFLOW

Và ghi audit.

**Không nên reset:**

superadmin account

trừ khi có quy trình riêng.

## 3.12. Audit Logs

**Superadmin xem toàn bộ:**

```text
actor
actor_type
event
target
room
ip
device
timestamp
before
after
```

**Actor:**

- `user`
- `admin`
- `superadmin`
- `system`

**Ví dụ:**

```text
superadmin#1
updated
admin#15
rooms: [1,2] → [1,2,5]
```

## 3.13. Security Center

**Mình khuyên thêm riêng cho Superadmin:**

- Failed login
- Blocked IP
- Suspicious request
- Invalid socket token
- Unauthorized room access
- Rate limit hit

Không cần quá phức tạp nhưng rất hữu ích.


Các security event liên quan User Identity nên theo dõi:

- Google OAuth failure.
- Invalid company domain.
- OAuth identity mismatch.
- Duplicate identity attempt.
- Device authentication failure.
- Device revoked.
- Suspicious Room registration.

## 3.14. Socket.IO Monitoring

**Superadmin có thể xem:**

Socket server status

- Connected users
- Connected admins
- Connected superadmins

- Connections by room
- Recent disconnects
- Authentication failures

Không cần xem raw socket payload trừ debug mode.

## 3.15. Queue / Failed Jobs

**Superadmin:**

- view failed jobs
- retry job
- delete failed job

**Job ví dụ:**

- SendChatworkNotification
- SendTelegramNotification
- CrawlMenu
- CloseCampaign
- GenerateReport

## 3.16. Version Management

Hệ thống hiện đã có version/update information phía User.

**Superadmin quản lý:**

- Version
- Release date
- Title
- Change log
- Force refresh
- Important flag

**Ví dụ:**

`v2.1.0`

- - Thêm multi-room
- - Nâng cấp realtime
- - Cải thiện bảo mật

User/Admin chỉ xem.

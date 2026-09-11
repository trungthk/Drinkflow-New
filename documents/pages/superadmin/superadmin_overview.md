# Superadmin Overview

## 1. Tổng quan

Superadmin là actor quản trị cấp cao nhất của DrinkFlow, tập trung vào:

- Governance toàn hệ thống.
- Quản lý Room.
- Quản lý Admin.
- Quản lý Global User.
- Giám sát Campaign ở cấp hệ thống.
- Duyệt Feedback.
- Quản lý Version.
- System Settings.
- Security.
- Audit Logs.
- Queue / Jobs.
- Socket.IO / Realtime.
- System Health.
- Maintenance.

Nguyên tắc phân quyền:

```text
Global User
→ dữ liệu cá nhân toàn hệ thống

Room User
→ membership và nghiệp vụ cá nhân trong Room

Admin
→ vận hành các Room được phân quyền

Superadmin
→ governance + system administration toàn hệ thống
```

Superadmin không tập trung vào vận hành Order/Payment hằng ngày và **không quản lý báo cáo doanh thu hoặc công nợ của Room**.

---

# 2. Superadmin Dashboard

## 2.1. Route

```text
/superadmin
```

Mục tiêu là cung cấp cái nhìn tổng quan về tình trạng hệ thống.

Ví dụ:

```text
DrinkFlow System Overview

Rooms
12

Global Users
248

Room Memberships
516

Admins
18

Live Campaigns
4

Orders Today
386

Feedback Pending Review
12
```

Các nhóm widget:

```text
Users
Rooms
Campaigns
Feedback
System Health
Security
```

Không hiển thị:

```text
Revenue
Outstanding Debt
Room Debt
Sponsor Debt
Collection Rate
```

---

# 3. Global Statistics

Các metric chính:

```text
Total Global Users
Active Users
Blocked Users

Total Room Memberships

Total Rooms
Active Rooms
Disabled Rooms

Total Admins

Live Campaigns
Campaigns Today

Orders Today
Feedback Pending Review
```

Phân biệt:

```text
Global Users
!=
Room Memberships
```

Một Global User có thể thuộc nhiều Room.

---

# 4. System Activity Overview

Có thể có chart:

```text
Daily Active Users

Orders / Day

Campaigns / Day

New Global Users / Day

New Room Memberships / Day

Feedback / Day
```

Không cần realtime từng giây cho chart.

Có thể:

```text
Load
→ Aggregate
→ Cache
→ Refresh định kỳ
```

---

# 5. Global Live Campaigns

Widget:

```text
🔴 Live Campaigns

Friday Coffee
IT Team

Orders       24
Deadline     10:30

[View]
```

Superadmin có thể xem tất cả Campaign đang live.

Không hiển thị revenue/financial summary của Room.

Superadmin chủ yếu:

```text
Monitor
Inspect
Audit
Override khi thật sự cần
```

Không phải primary Campaign operator.

---

# 6. Room Management

## 6.1. Route

```text
/superadmin/rooms
```

Danh sách:

| Room | Users | Admins | Live Campaign | Status |
|---|---:|---:|---:|---|
| IT Team | 45 | 3 | 1 | Active |
| Marketing | 30 | 2 | 0 | Active |

Filter:

```text
Active
Disabled
Archived
```

Search:

```text
Room Name
Slug
Admin
```

Không hiển thị:

- Revenue của Room.
- Debt của Room.
- Payment statistics.

---

# 7. Create Room

## 7.1. Route

```text
/superadmin/rooms/create
```

Form:

```text
Room Name

Slug

Description

Timezone

Default Language

Status
```

Flow:

```text
Create Room
    ↓
Assign Admin
    ↓
Configure Room Defaults
```

Không cần cấu hình toàn bộ Campaign defaults trong bước tạo Room.

---

# 8. Room Detail

## 8.1. Route

```text
/superadmin/rooms/{room}
```

Header:

```text
IT Team

Status
Active

Users
45

Admins
3

Campaigns
126

Orders
2.840
```

Tabs:

```text
Overview
Admins
Room Users
Campaigns
Settings
Audit
```

Không có:

```text
Payments
Debt
Revenue
```

ở Superadmin Room Detail.

---

# 9. Room Overview

Có thể hiển thị:

```text
Room
IT Team

Created
15/08/2026

Status
Active

Global Members
45

Admins
3

Campaigns
126

Live Campaigns
1

Orders
2.840
```

Recent Activity:

```text
Campaign created

Admin assigned

Room User joined

Room settings changed
```

---

# 10. Room Status Management

Superadmin có thể:

```text
Activate
Disable
Archive
```

Ví dụ:

```text
Disable IT Team?
```

Nếu Room disabled:

```text
Room User
→ không truy cập

Admin
→ không vận hành Campaign mới

Historical data
→ giữ nguyên
```

Action phải audit.

---

# 11. Assign Admin to Room

Trong Room Detail:

```text
Admins

Nguyễn Văn A
Admin

Trần Văn B
Admin

[+ Assign Admin]
```

Superadmin có thể:

```text
Assign
Remove
View Admin
```

Remove Admin khỏi Room không xóa Global User.

---

# 12. Admin Management

## 12.1. Route

```text
/superadmin/admins
```

Danh sách:

```text
Lâm Thành Trung

trung@company.com

Role
Admin

Rooms
3

Status
Active

[View]
```

Search:

```text
Name
Email
Room
```

Filter:

```text
Admin
Superadmin
Active
Disabled
```

---

# 13. Admin Detail

## 13.1. Route

```text
/superadmin/admins/{admin}
```

Hiển thị:

```text
Lâm Thành Trung

Email
trung@company.com

Role
Admin

Status
Active

Assigned Rooms

- IT Team
- Marketing
- Accounting
```

Actions:

```text
Assign Room
Remove Room
Disable Admin
Promote
Demote
```

---

# 14. Promote / Demote

Admin không phải identity riêng.

Mô hình:

```text
Global User
    │
    └── Admin Privilege
```

Promote:

```text
Global User
→ Admin
```

Demote:

```text
Admin
→ Global User only
```

Không tạo/xóa duplicate account.

---

# 15. Superadmin Safeguard

Không được:

```text
Demote superadmin cuối cùng

Disable superadmin cuối cùng

Delete quyền superadmin cuối cùng
```

Rule phải enforce server-side.

---

# 16. Global User Management

## 16.1. Route

```text
/superadmin/global-users
```

Danh sách:

```text
Avatar

Lâm Thành Trung
trung@company.com

Status
Active

Rooms
3

Last Login
11/09/2026 08:30

[View]
```

Search:

```text
Name
Email
```

Filter:

```text
Active
Blocked
Disabled
```

---

# 17. Global User Detail

## 17.1. Route

```text
/superadmin/global-users/{globalUser}
```

Tabs:

```text
Profile
OAuth Identity
Rooms
Devices
Orders
Feedback
Audit
```

Header:

```text
[Avatar]

Lâm Thành Trung
trung@company.com

Google Verified

Status
Active
```

Không cần Payment/Debt tab ở Superadmin.

---

# 18. OAuth Identity

Hiển thị safe metadata:

```text
Provider
Google

Provider Email
trung@company.com

Provider User ID
123456789...

Linked At
10/09/2026
```

Không hiển thị:

```text
access_token
refresh_token
client_secret
```

---

# 19. Global User Room Memberships

```text
Rooms

IT Team
Active

Marketing
Blocked

Accounting
Active
```

Superadmin có thể:

```text
View
Remove Membership
Restore Membership
```

Không hard delete historical data.

---

# 20. Global User Devices

Ví dụ:

```text
Chrome / Windows

Device ID
df-device-12ab••••

Last Seen
11/09/2026 09:30

Status
Trusted

[Revoke]
```

Không expose:

```text
token_hash
trusted token
secret
```

Actions:

```text
Revoke Device
Revoke All Devices
```

---

# 21. Global Block User

Superadmin có quyền:

```text
global_users.status = blocked
```

Khi block:

```text
Global Dashboard
→ blocked

All Rooms
→ blocked

Join Room
→ blocked

Order
→ blocked

New Socket Token
→ blocked
```

Không thay đổi:

```text
room_users.status
```

để giữ membership history.

---

# 22. Global Unblock

Update:

```text
global_users.status = active
```

Room-level state vẫn độc lập.

Ví dụ:

```text
Global User
Active

IT Team
Active

Marketing
Blocked
```

---

# 23. Merge Duplicate Global Users

Tool đặc biệt dành cho Superadmin.

Ví dụ:

```text
Global User #10
trung@company.com

Global User #85
trung@company.com
```

Flow:

```text
Select Primary
      ↓
Select Duplicate
      ↓
Preview
      ↓
Merge

OAuth Identities
Room Memberships
Devices
Order References
Feedback
Audit Mapping

      ↓
Archive Duplicate
```

Không hard delete ngay.

Có thể để Phase 2.

---

# 24. Campaign Overview

## 24.1. Route

```text
/superadmin/campaigns
```

Mục tiêu:

```text
Global Campaign Monitoring
```

Filter:

```text
Room
Status
Date
Admin
```

List:

```text
Friday Coffee

IT Team

Admin
Nguyễn Văn A

Status
Live

Orders
24

Participants
30

[View]
```

Không hiển thị:

```text
Revenue
Debt
Sponsor Debt
Room Financial Summary
```

---

# 25. Campaign Detail

Superadmin có thể xem:

```text
Campaign Information

Room

Admin

Status

Deadline

Participants

Ordered Users

Declined Users

No Action Users

Orders

Menu Summary

Sponsors

Activity History
```

Sponsor ở đây chỉ nhằm hiểu cấu hình Campaign, không phải báo cáo công nợ/tài chính của Room.

---

# 26. Campaign Intervention

Superadmin có thể có quyền đặc biệt:

```text
Force Close
Cancel Campaign
```

Chỉ dùng khi:

- Admin không xử lý được.
- Campaign bị lỗi.
- Có sự cố hệ thống.
- Cần can thiệp quản trị.

Action phải:

```text
Strong Confirmation
+
Reason
+
Audit Log
```

Superadmin không phải primary Campaign operator.

---

# 27. Campaign Participation Overview

Có thể hiển thị:

```text
Total Users
30

Ordered
22

Declined
3

No Action
5
```

Dữ liệu từ:

```text
campaign_participants
```

Không suy diễn từ toàn bộ Room Users nếu Campaign có target riêng.

---

# 28. Feedback Moderation

## 28.1. Route

```text
/superadmin/feedback
```

Feedback gồm:

```text
Global User
+
Rating 1–5
+
Content
```

Ví dụ:

```text
★★★★★

Ứng dụng dễ dùng.

11/09/2026

Public
No

[Review]
```

Filter:

```text
All
Pending
Public
Private

1 ★
2 ★
3 ★
4 ★
5 ★
```

---

# 29. Feedback Review

Superadmin xem:

```text
Rating
★★★★☆

Content
Giao diện mobile khá dễ dùng.

Submitted At
11/09/2026
```

Actions:

```text
[Keep Private]
[Publish]
```

Rule:

```text
is_public = true
AND
rating >= feedback_public_min_rating
```

Default:

```text
feedback_public_min_rating = 3
```

Nếu rating thấp hơn ngưỡng:

```text
Publish
→ disabled
```

---

# 30. Feedback Dashboard Summary

Superadmin Dashboard có thể hiển thị:

```text
Feedback Today
18

Average Rating
4.1

Pending Review
7

Public
56
```

Distribution:

```text
5 ★    42
4 ★    30
3 ★    15
2 ★     5
1 ★     3
```

Internal Average Rating nên tính trên **tất cả Feedback**, không chỉ public.

Điều này giúp Superadmin thấy đánh giá thực tế của hệ thống.

---

# 31. Version Management

## 31.1. Route

```text
/superadmin/versions
```

Danh sách:

```text
v2.3.0

Multi-room Authentication

Published
10/09/2026

[Edit]
```

Status:

```text
draft
published
archived
```

Actions:

```text
Create
Edit
Preview
Publish
Unpublish
Archive
```

---

# 32. Create Version

Form:

```text
Version

[v2.3.1]

Title

[Feedback & Campaign Improvements]

Summary

[...]

Content Markdown

[................]

Release Date

[11/09/2026]

Important
[ ]

Force Refresh
[ ]
```

---

# 33. Version Preview

Trước Publish:

```text
[Preview]
```

Render giống:

```text
/versions/{version}
```

Kiểm tra:

- Markdown.
- Title.
- Summary.
- Badge.
- Release Date.
- Previous/Next navigation.

---

# 34. Publish Version

Flow:

```text
Draft
  ↓
Validate
  ↓
Publish
  ↓
Clear Cache
  ↓
Update Latest Version
  ↓
Optional Notification
```

Event:

```text
system.version.published
```

---

# 35. System Settings

## 35.1. Route

```text
/superadmin/settings
```

Tabs:

```text
General
Authentication
Feedback
Realtime
Queue
Security
Maintenance
```

---

# 36. General Settings

```text
Application Name

Default Language

Default Timezone

Upload Max Size

Current Version
```

Không dùng unrestricted key/value editor nếu setting có thể ảnh hưởng hệ thống.

---

# 37. Authentication Settings

```text
Google OAuth Enabled

Allowed Company Domains

Require Verified Email

Trusted Device Duration
```

Ví dụ:

```text
Allowed Domains

company.com
company.co.jp
```

Admin thường không được chỉnh.

---

# 38. Feedback Settings

```text
Feedback Enabled

Daily Feedback Limit

Public Minimum Rating
```

Mapping:

```text
feedback_enabled

feedback_daily_limit

feedback_public_min_rating
```

Default:

```text
true

1

3
```

---

# 39. Realtime Settings

Hiển thị:

```text
Socket.IO Enabled

Socket Gateway Status

Token TTL

Realtime Notifications
```

Không hiển thị:

```text
SOCKET_SIGNING_SECRET
```

Chỉ hiển thị:

```text
Configured
```

---

# 40. Queue Settings

Chỉ expose business-safe settings.

Ví dụ:

```text
Notification Queue Enabled

Crawler Queue Enabled

Max Retry
```

Không biến UI thành editor tùy ý cho infrastructure config.

---

# 41. Security Center

## 41.1. Route

```text
/superadmin/security
```

Summary:

```text
Failed Login Today
12

OAuth Failure
3

Invalid Domain
2

Unauthorized Room Access
5

Device Revoked
4

Invalid Socket Token
8
```

---

# 42. Security Events

Event types:

```text
failed_login

google_oauth_failure

invalid_company_domain

oauth_identity_mismatch

device_authentication_failure

device_revoked

unauthorized_room_access

invalid_socket_token

rate_limit_hit

suspicious_room_registration
```

List:

```text
11/09/2026 09:30

invalid_company_domain

Email Domain
gmail.com

IP
xxx.xxx.xxx.xxx
```

---

# 43. Security Event Detail

Hiển thị:

- Event Type.
- Severity.
- Actor.
- Room nếu có.
- IP.
- Safe device metadata.
- Timestamp.
- Safe event metadata.

Không expose:

- Token.
- OAuth secret.
- Session secret.
- Cookie.
- Authorization header.

---

# 44. Audit Logs

## 44.1. Route

```text
/superadmin/audit
```

Ví dụ:

```text
11/09/2026 10:15

Actor
Admin A

Event
order.price_adjusted

Target
Order #DF-1024

Room
IT Team
```

Filters:

```text
Actor Type
Actor
Event
Target Type
Room
Date
```

---

# 45. Audit Detail

Ví dụ:

```text
Event
order.price_adjusted

Before
45.000đ

After
49.000đ

Changed By
Admin A

Reason
GrabFood price changed
```

Có thể lưu:

```text
before_data
after_data
metadata
```

Không lưu secret.

---

# 46. Important Audit Events

Bắt buộc audit:

```text
Global User block/unblock

Room User block/unblock

Admin assignment/removal

Room activate/disable/archive

Campaign force close/cancel

Order price adjustment

Device revoke

OAuth identity change

System setting change

Feedback publish/private

Version publish

Maintenance mode

System reset
```

---

# 47. System Health

## 47.1. Route

```text
/superadmin/system/health
```

Hiển thị:

```text
Laravel
Healthy

Supabase / PostgreSQL
Healthy

Redis
Healthy

Queue
Healthy

Scheduler
Healthy

Socket.IO
Healthy
```

Có:

```text
Last Checked
11/09/2026 10:20
```

---

# 48. Database Health

Database sử dụng Supabase/PostgreSQL.

Hiển thị:

```text
Supabase PostgreSQL

Connected

Latency
42ms

Status
Healthy
```

Không expose:

```text
DB_PASSWORD
SUPABASE_SERVICE_ROLE_KEY
connection string
```

---

# 49. Redis Health

```text
Redis

Connected

Memory
120 MB

Queue Length
18
```

---

# 50. Socket.IO Health

```text
Socket.IO

Connected

Active Connections
128

Rooms
12

Last Heartbeat
5 seconds ago
```

Có thể hiển thị:

```text
Recent Disconnect Rate
```

---

# 51. Queue Management

## 51.1. Route

```text
/superadmin/system/queue
```

Summary:

```text
Pending Jobs
24

Processing
3

Failed
2
```

Tabs:

```text
Pending
Failed
Batches
```

---

# 52. Failed Jobs

Ví dụ:

```text
SendNotification

Failed At
11/09/2026 10:15

Attempts
3

[Retry]
[Delete]
```

Sanitize payload trước khi render.

Không expose secret.

---

# 53. Retry Failed Job

Action:

```text
[Retry]
```

Confirm:

```text
Retry this job?
```

Audit:

```text
queue.job_retried
```

---

# 54. Scheduler Monitoring

Hiển thị:

```text
Last Scheduler Run
11/09/2026 10:20

Status
Healthy
```

Scheduled Jobs:

```text
CloseExpiredCampaigns
Every Minute

CleanOldSessions
Daily

GenerateAggregates
Daily
```

---

# 55. System Reports

## 55.1. Route

```text
/superadmin/reports
```

Reports tập trung vào usage và system governance:

```text
User Growth

Room Growth

Campaign Activity

Order Volume

Feedback

System Usage
```

Không có:

```text
Revenue Report

Payment Report

Debt Report

Sponsor Debt Report

Collection Rate
```

---

# 56. Global Campaign Report

Ví dụ:

| Room | Campaigns | Orders | Participants |
|---|---:|---:|---:|
| IT | 35 | 680 | 820 |
| Marketing | 22 | 390 | 470 |

Filters:

```text
Date Range
Room
```

Không đưa financial amount vào report Superadmin.

---

# 57. Global User Growth

Charts:

```text
New Global Users / Month

New Room Memberships / Month

Active Users / Month
```

Phân biệt Global User và Room Membership.

---

# 58. Room Growth

Có thể hiển thị:

```text
New Rooms / Month

Active Rooms

Disabled Rooms

Average Members / Room
```

---

# 59. Feedback Report

```text
Average Rating
4.1

Total Feedback
350

Public
190

Private
160
```

Distribution:

```text
5 ★
4 ★
3 ★
2 ★
1 ★
```

Internal report tính trên tất cả Feedback.

---

# 60. System Usage Report

Có thể gồm:

```text
Daily Active Users

Monthly Active Users

Campaigns Created

Orders Created

Room Join Activity

Feedback Submitted
```

Mục tiêu là đánh giá mức độ sử dụng hệ thống, không phải tài chính.

---

# 61. Maintenance

## 61.1. Route

```text
/superadmin/system/maintenance
```

UI:

```text
Maintenance Mode

Status
OFF

Message

[Hệ thống đang bảo trì...]

[Enable Maintenance]
```

Confirm mạnh trước khi bật.

---

# 62. Maintenance Notification

Có thể:

```text
Notify Users

5 minutes before
```

Realtime event:

```text
system.maintenance
```

---

# 63. System Reset

Nếu thực sự cần:

```text
/superadmin/system/reset
```

Phải có safeguard mạnh:

```text
Password Confirmation

Type:

RESET DRINKFLOW
```

và audit.

Không đặt action này cạnh setting thông thường.

---

# 64. Backup Monitoring

Superadmin có thể xem trạng thái backup:

```text
Database Backup

Last Backup
11/09/2026 02:00

Status
Success
```

Không nhất thiết cho download backup trực tiếp từ browser.

Không expose storage credentials.

---

# 65. System Notifications Management

## 65.1. Route

```text
/superadmin/notifications
```

Dành cho system-level notifications:

```text
System Maintenance

Version Released

Global Announcement

Security Notice
```

Không dùng cho Campaign notification hằng ngày.

Campaign notification thuộc Admin.

---

# 66. Global Announcement

Superadmin có thể gửi:

```text
📢 DrinkFlow

Phiên bản mới v2.3.1 đã được phát hành.
```

Target:

```text
All Users

All Admins

Selected Rooms
```

Nếu chọn Selected Rooms, Superadmin có thể gửi thông báo cấp hệ thống nhưng scope tới các Room cụ thể.

---

# 67. Superadmin Notifications

Superadmin nên nhận:

```text
Security Alert

Queue Failure

System Health Error

High Error Rate

Feedback Pending

OAuth Failure Spike

Room Disabled

Admin Permission Changed
```

Không nhận từng:

```text
order.created
```

toàn hệ thống vì tạo quá nhiều noise.

---

# 68. Superadmin Navigation

Sidebar:

```text
DrinkFlow Superadmin

Dashboard

MANAGEMENT
Rooms
Admins
Global Users
Campaigns
Feedback

CONTENT
Versions
Notifications

ANALYTICS
Reports

SYSTEM
Settings
Security
Audit Logs
Queue
System Health
Maintenance

──────────────

Global User
Logout
```

Không có:

```text
Payments & Debt
Revenue
Financial Reports
```

Badge:

```text
Feedback    12
Queue        2
Security     3
```

---

# 69. Route Structure

```php
Route::prefix('superadmin')
    ->middleware([
        'global.auth',
        'global.active',
        'superadmin',
    ])
    ->name('superadmin.')
    ->group(function () {

        Route::get('/', ...)
            ->name('dashboard');

        Route::resource('rooms', ...);

        Route::get('/admins', ...)
            ->name('admins.index');

        Route::get('/admins/{admin}', ...)
            ->name('admins.show');

        Route::get('/global-users', ...)
            ->name('global-users.index');

        Route::get('/global-users/{globalUser}', ...)
            ->name('global-users.show');

        Route::get('/campaigns', ...)
            ->name('campaigns.index');

        Route::get('/campaigns/{campaign}', ...)
            ->name('campaigns.show');

        Route::get('/feedback', ...)
            ->name('feedback.index');

        Route::patch(
            '/feedback/{feedback}/publish',
            ...
        )->name('feedback.publish');

        Route::patch(
            '/feedback/{feedback}/private',
            ...
        )->name('feedback.private');

        Route::resource('versions', ...);

        Route::get('/notifications', ...)
            ->name('notifications');

        Route::get('/reports', ...)
            ->name('reports.index');

        Route::get('/settings', ...)
            ->name('settings');

        Route::get('/security', ...)
            ->name('security.index');

        Route::get('/audit', ...)
            ->name('audit.index');

        Route::get('/system/queue', ...)
            ->name('system.queue');

        Route::get('/system/health', ...)
            ->name('system.health');

        Route::get('/system/maintenance', ...)
            ->name('system.maintenance');
    });
```

Không có Superadmin route riêng cho:

```text
payments
debts
revenue
```

---

# 70. Blade Structure

```text
resources/views/superadmin/

├── dashboard.blade.php
│
├── rooms/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   └── show.blade.php
│
├── admins/
│   ├── index.blade.php
│   └── show.blade.php
│
├── global-users/
│   ├── index.blade.php
│   └── show.blade.php
│
├── campaigns/
│   ├── index.blade.php
│   └── show.blade.php
│
├── feedback/
│   └── index.blade.php
│
├── versions/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   └── preview.blade.php
│
├── notifications/
│   └── index.blade.php
│
├── reports/
│   └── index.blade.php
│
├── settings/
│   └── index.blade.php
│
├── security/
│   ├── index.blade.php
│   └── show.blade.php
│
├── audit/
│   ├── index.blade.php
│   └── show.blade.php
│
└── system/
    ├── health.blade.php
    ├── queue.blade.php
    └── maintenance.blade.php
```

Không có:

```text
payments/
debts/
revenue/
```

---

# 71. Blade Components

```text
resources/views/components/superadmin/

sidebar.blade.php

stat-card.blade.php
health-card.blade.php
security-card.blade.php

room-card.blade.php
admin-card.blade.php
global-user-card.blade.php

campaign-card.blade.php
campaign-participation.blade.php

feedback-card.blade.php
rating-distribution.blade.php

version-status.blade.php

audit-row.blade.php
security-event.blade.php

queue-status.blade.php
maintenance-panel.blade.php

confirm-danger-modal.blade.php
```

---

# 72. Superadmin Realtime

Superadmin không subscribe tất cả Room events mặc định.

System-level events:

```text
system.health_changed

system.queue_failed

system.security_alert

system.maintenance

feedback.created

room.created

room.status_changed

admin.changed
```

Channel:

```text
superadmin
```

Khi mở Campaign Detail, có thể subscribe riêng Campaign/Room cần quan sát.

---

# 73. Permission Model

MVP:

```text
Superadmin
→ full system governance access
```

Có thể chuẩn bị:

```text
system.manage

room.manage

admin.manage

global_user.manage

campaign.monitor

feedback.manage

version.manage

security.view

audit.view

queue.manage

maintenance.manage
```

Không cần over-engineer permission UI ở Phase 1.

---

# 74. Superadmin không nên làm thường xuyên

Superadmin không nên là actor chính cho:

```text
Campaign creation

Order processing

Order price adjustment

Payment confirmation

Debt processing

Room User daily operations
```

Các nghiệp vụ trên thuộc Admin.

Superadmin tập trung:

```text
Monitor
Configure
Govern
Audit
Secure
Maintain
Override when necessary
```

---

# 75. MVP Priority

## Phase 1

```text
Dashboard

Rooms
Room Detail
Room Status

Admin Assignment

Admins

Global Users
Global Block / Unblock
Device Revoke

Feedback Moderation

Versions

System Settings

Audit Logs

System Health
```

## Phase 2

```text
Global Campaign Overview

Security Center

Queue Management

System Reports

Maintenance UI

Global Notifications

Merge Global Users
```

## Phase 3

```text
Advanced Security Analytics

Advanced Role / Permission

Automated Alerting

Advanced Backup Monitoring
```

---

# 76. Actor Boundary

| Global User | Room User | Admin | Superadmin |
|---|---|---|---|
| `/me` | `/rooms/{room}` | `/admin` | `/superadmin` |
| Own Identity | Room Membership | Assigned Rooms | All System |
| My Rooms | Current Room | Campaign Operations | Room Management |
| Own Orders | Current Room Order | All Room Orders | Campaign Monitoring |
| Own Payments | Room Payments | Payment/Debt Operations | Không quản lý tài chính Room |
| Feedback | Campaign/Menu | Room Users | Global Users |
| Devices | Top Items | Reports | Admins |
| Notifications | Room Notifications | Room Settings | System Settings |
| — | — | — | Feedback Moderation |
| — | — | — | Versions |
| — | — | — | Audit |
| — | — | — | Security |
| — | — | — | System Health |

---

# 77. Business Boundary cuối cùng

```text
ROOM FINANCIAL OPERATIONS

Payment
Debt
Sponsor Debt
Room Financial Logic

→ Admin responsibility
```

```text
SUPERADMIN

Global Users
Rooms
Admins
Campaign Monitoring
Feedback
Versions
System Settings
Security
Audit
System Health
Maintenance

→ Governance responsibility
```

Superadmin có thể xem các Campaign và Order ở mức cần thiết để kiểm tra hệ thống, nhưng không có dashboard/report chuyên biệt về:

```text
Room Revenue
Room Debt
Sponsor Debt
Payment Collection
Financial Performance
```

Điều này giữ boundary rõ ràng:

```text
Admin
→ Operations

Superadmin
→ Governance + System Administration
```

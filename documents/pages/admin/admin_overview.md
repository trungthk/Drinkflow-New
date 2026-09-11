# Admin Overview

## 1. Tổng quan

Admin là actor quản trị **một hoặc nhiều Room được phân quyền**.

Admin không phải Superadmin.

Nguyên tắc:

```text
Admin
→ Assigned Rooms only

Superadmin
→ Global System
```

Admin chịu trách nhiệm vận hành:

- Room.
- Campaign.
- Live Orders.
- Giá thực tế của Order.
- Sponsor.
- Debt/Payment.
- Room Users.
- Reports.
- Room Settings.
- Realtime vận hành.

Admin không được:

- Quản lý Global User toàn hệ thống.
- Global block User.
- Quản lý Superadmin.
- Quản lý System Settings global.
- Duyệt Feedback public.
- Quản lý Version.
- Quản lý Room không được assign.

---

# 2. Admin Dashboard

## 2.1. Route

```text
/admin
```

Nếu Admin chỉ quản lý một Room, có thể redirect:

```text
/admin/rooms/{room}
```

Nếu Admin quản lý nhiều Room, `/admin` là dashboard tổng hợp.

## 2.2. Summary

```text
Xin chào, Admin

Rooms
3

Campaign đang Live
2

Orders hôm nay
86

Tổng giá trị hôm nay
6.850.000đ

Chưa thanh toán
1.250.000đ
```

## 2.3. Live Campaign

```text
🔴 LIVE

Friday Coffee
IT Team

Deadline
10:30

Participants     24
Orders           22
Total          1.850.000đ

[Xem Campaign]
```

Realtime bằng Socket.IO.

---

# 3. Admin Room Switcher

Nếu Admin quản lý nhiều Room:

```text
IT Team ▼

IT Team
Marketing
Accounting
```

Danh sách Room lấy từ quan hệ phân quyền Admin.

Không load toàn bộ Room hệ thống.

Sau khi chọn:

```text
/admin/rooms/{room}
```

---

# 4. Room Admin Dashboard

## 4.1. Route

```text
/admin/rooms/{room}
```

Đây là màn hình vận hành chính.

```text
IT Team

🔴 Friday Coffee đang Live

Deadline       10:30
Còn            00:24:15

Participants   24
Orders         22
Items          31

Subtotal       1.950.000đ
Sponsor         -200.000đ
Need Pay       1.750.000đ

[Xem Orders]
[Quản lý Campaign]
```

## 4.2. Quick Actions

```text
[+ Tạo Campaign]
[Orders]
[Payments]
[Users]
[Reports]
```

---

# 5. Campaign Management

## 5.1. Route

```text
/admin/rooms/{room}/campaigns
```

Tabs:

```text
Live
Upcoming
Completed
Cancelled
```

Campaign card:

```text
🔴 LIVE

Friday Coffee

Deadline
10:30

24 Participants
22 Orders
1.850.000đ

[Quản lý]
```

Admin chỉ quản lý Campaign thuộc Room hiện tại.

---

# 6. Create Campaign — Fast Create First

## 6.1. Mục tiêu

Tạo Campaign phải nhanh nhất có thể.

Không bắt Admin nhập nhiều field nếu thông tin đã có trong Room Settings hoặc có thể tái sử dụng.

Mục tiêu UX:

```text
Open Create Campaign
→ Chọn nguồn Menu
→ Review nhanh
→ Publish
```

Không biến Create Campaign thành form dài.

---

## 6.2. Giá trị mặc định từ Room Settings

Các field sau mặc định lấy từ cấu hình Room:

```text
campaign_name_template
default_sponsors
default_max_budget
default_deadline
default_payment_account
default_notification_settings
```

Ví dụ Room Settings:

```text
Tên Campaign mặc định:
Friday Coffee

Sponsor mặc định:
Manager A

Max Budget:
2.000.000đ

Deadline mặc định:
10:30
```

Khi tạo Campaign:

```text
Tên Campaign
[Friday Coffee]

Deadline
[10:30]

Ngân sách tối đa
[2.000.000đ]
```

Admin chỉ sửa khi cần.

---

# 7. Nguồn dữ liệu Menu khi tạo Campaign

Admin phải có nhiều cách tạo Menu nhanh.

## 7.1. Tái sử dụng Campaign cũ

```text
Nguồn Menu

● Campaign trước
○ Import JSON
○ Crawl URL
○ Danh sách đã lưu
```

Chọn:

```text
Campaign trước

Friday Coffee - 04/09/2026
```

Sau đó:

```text
[Use Menu]
```

Copy:

- Product.
- Category.
- Price.
- Image.
- Metadata cần thiết.

Không copy Order cũ.

---

## 7.2. Import JSON

Admin có thể import JSON menu đã chuẩn hóa.

JSON có thể được tạo bằng:

- ChatGPT.
- Gemini.
- Script nội bộ.
- Tool khác.

Ví dụ:

```json
{
  "shop": "Highlands Coffee",
  "categories": [
    {
      "name": "Coffee",
      "products": [
        {
          "name": "Cà phê sữa đá",
          "price": 45000
        }
      ]
    }
  ]
}
```

Flow:

```text
Paste JSON
   ↓
Validate Schema
   ↓
Preview
   ↓
Import
```

Không insert trực tiếp nếu JSON invalid.

---

## 7.3. Crawler từ URL

Admin nhập URL:

```text
GrabFood
ShopeeFood
Website hỗ trợ
```

Flow:

```text
URL
 ↓
Crawler
 ↓
Extract Menu
 ↓
Normalize
 ↓
Preview
 ↓
Import
```

Crawler cần:

- Timeout.
- Error handling.
- Preview.
- Không auto publish nếu dữ liệu chưa review.

---

## 7.4. Danh sách đã lưu trước đó

Nếu hệ thống đã có danh sách menu/cửa hàng được lưu từ Campaign cũ hoặc import trước đó:

```text
Danh sách đã lưu

Highlands Coffee
Phúc Long
Katinat
```

Admin chọn:

```text
Highlands Coffee
```

và import Menu.

Lưu ý:

Không cần module Shop Management riêng.

Danh sách này chỉ là nguồn tái sử dụng dữ liệu menu, không phải một phân hệ Shop CRUD độc lập.

---

# 8. Product Model

Product trong DrinkFlow là sản phẩm đơn.

Không cần Product Options management riêng.

Không có module:

```text
Product Options
```

Nếu Campaign data có size/topping từ nguồn import thì có thể lưu như dữ liệu snapshot của item/order theo format đã định nghĩa, nhưng Admin không cần một màn hình quản trị Product Options riêng.

---

# 9. Sponsor Selection

## 9.1. Sponsor có thể chọn nhiều người

Khi tạo Campaign, Admin có thể chọn nhiều Sponsor.

Nguồn Sponsor:

```text
Room Users
```

Điều kiện:

```text
room_users.status = active
AND
Global User verified
```

UI:

```text
Sponsors

☑ Nguyễn Văn A
☑ Trần Văn B
☐ Lê Văn C
```

Không cho chọn User chưa verify hoặc không thuộc Room.

---

## 9.2. Sponsor mặc định

Room Settings có thể lưu:

```text
default_sponsor_room_user_ids
```

Khi tạo Campaign:

```text
Sponsor

[Nguyễn Văn A]
[Trần Văn B]
```

Admin có thể:

- Giữ mặc định.
- Bỏ.
- Thêm Sponsor khác.

---

# 10. Sponsor Rules

Campaign hỗ trợ đủ 3 loại.

## 10.1. Fixed Campaign Amount

Ví dụ:

```text
Sponsor Budget
500.000đ
```

Tổng Sponsor đóng:

```text
500.000đ
```

Sau đó phân bổ theo business rule.

Nếu nhiều Sponsor:

```text
Sponsor A  300.000đ
Sponsor B  200.000đ
```

hoặc chia đều nếu không cấu hình riêng.

---

## 10.2. Per User

Ví dụ:

```text
Sponsor
20.000đ / User
```

Nếu 15 User order:

```text
Sponsor Total
300.000đ
```

Có thể giới hạn bởi:

```text
max_budget
```

Ví dụ:

```text
20.000 x 15 = 300.000

Max Budget = 250.000

Actual Sponsor = 250.000
```

---

## 10.3. Percentage

Ví dụ:

```text
Sponsor
20%
```

Order:

```text
100.000đ
```

Sponsor:

```text
20.000đ
```

User:

```text
80.000đ
```

Có thể áp dụng:

```text
max_budget
```

để giới hạn tổng tài trợ.

---

# 11. Sponsor Configuration UI

Form ngắn:

```text
Sponsor

[+ Chọn Sponsor]

Rule

○ Fixed Campaign Amount
○ Per User
● Percentage

Value

[20] %

Max Budget

[2.000.000đ]
```

Nếu Room đã có default:

Admin chỉ cần review và Publish.

---

# 12. Campaign Preview

Trước khi Publish:

```text
Friday Coffee

Deadline
10:30

Menu
32 món

Sponsors
Nguyễn Văn A
Trần Văn B

Rule
20%

Max Budget
2.000.000đ

[Back]
[Publish]
```

---

# 13. Campaign Publish

Laravel validate:

- Room hợp lệ.
- Admin có quyền.
- Deadline hợp lệ.
- Sponsor thuộc Room và verified.
- Sponsor rule hợp lệ.
- Menu có dữ liệu.
- Max Budget hợp lệ nếu cần.

Sau Publish:

```text
campaign.status = active
```

Emit:

```text
campaign.created
```

---

# 14. Campaign Detail / Live Control Center

## 14.1. Route

```text
/admin/rooms/{room}/campaigns/{campaign}
```

Đây là Control Center cho Campaign đang Live.

```text
🔴 LIVE

Friday Coffee

Đóng đơn sau
00:24:15

Participants       24
Orders             22
Items              31
Total       1.850.000đ

[Orders]
[Top Items]
[Payments]

[Đóng Campaign]
```

Realtime cập nhật:

- Orders.
- Totals.
- Popular Items.
- Participants.
- Payment state.

---

# 15. Live Orders

Bảng:

```text
User        Code       Items       Amount       Status

Trung       TRUNGLT    2            90.000      Submitted
An          ANNV       1            65.000      Confirmed
Minh        MINHNT     3           120.000      Submitted
```

Actions:

```text
[Xem]
[Confirm]
[Cancel]
```

Nếu business cho phép:

```text
[Delete]
```

Delete cần confirmation.

---

# 16. Live Campaign Order Detail

## 16.1. Route

```text
/admin/rooms/{room}/orders/{order}
```

Nếu Campaign đang `active`, Admin được quyền chỉnh giá thực tế của Order.

Lý do:

- GrabFood thay đổi giá.
- ShopeeFood thay đổi giá.
- Voucher thay đổi.
- Giá thực tế trên delivery app khác giá import/crawl.

---

# 17. Admin Adjust Order Price

## 17.1. UI

Ví dụ:

```text
Order #DF-1024

User
TRUNGLT

--------------------------------

Cà phê sữa đá

Giá ban đầu
45.000đ

Giá thực tế
[49.000đ]

--------------------------------

Trà đào

Giá ban đầu
50.000đ

Giá thực tế
[47.000đ]

--------------------------------

[Recalculate]
[Save Changes]
```

Admin có thể chỉnh:

```text
actual_unit_price
```

hoặc giá snapshot tương ứng của từng Order Item.

Không chỉnh trực tiếp:

```text
final_amount
sponsor_amount
debt_amount
```

Các giá trị này phải được Laravel tính lại.

---

# 18. Price Adjustment Business Flow

Khi Admin Save:

```text
Admin changes Order Item price
        │
        ▼
Validate Campaign is Live
        │
        ▼
DB Transaction
        │
        ├── Update actual item price
        ├── Recalculate subtotal
        ├── Recalculate sponsor
        ├── Apply sponsor rule
        ├── Apply max budget logic
        ├── Recalculate final amount
        ├── Recalculate current debt if any
        └── Audit price change
        │
        ▼
Commit
        │
        ▼
Domain Event
OrderPriceAdjusted
        │
        ├── Notify User
        └── Socket.IO
```

---

# 19. Order Price Audit

Mọi thay đổi giá phải có history.

Ví dụ:

```text
order_price_adjustments

id
order_id
order_item_id

old_price
new_price

changed_by_admin_id

reason nullable

created_at
```

Admin có thể nhập optional note:

```text
Lý do

[Giá GrabFood thay đổi]
```

Không được silently overwrite giá mà không có audit.

---

# 20. Recalculate Sponsor sau Price Change

Sponsor phải tính lại đúng theo rule Campaign.

## Fixed Campaign Amount

Nếu tổng Order thay đổi:

Sponsor pool giữ nguyên fixed amount nhưng allocation cho từng Order có thể cần tính lại.

## Per User

Nếu số User order không thay đổi:

```text
Sponsor/User
```

giữ nguyên.

Nhưng final amount Order phải tính lại theo giá mới.

## Percentage

Nếu Order tăng từ:

```text
100.000
→ 120.000
```

Sponsor 20%:

```text
20.000
→ 24.000
```

trừ trường hợp đã chạm `max_budget`.

---

# 21. Recalculate toàn Campaign nếu cần

Nếu sponsor có shared budget:

```text
fixed campaign amount
hoặc
percentage + max budget
```

một thay đổi giá Order có thể ảnh hưởng allocation của nhiều User.

Khi đó:

```text
Adjust Order Price
      │
      ▼
Recalculate Campaign Sponsor Allocation
      │
      ▼
Recalculate affected Orders
      │
      ▼
Recalculate Debt
```

Không chỉ recalculate Order vừa sửa nếu rule tài trợ dùng shared pool.

---

# 22. User Notification khi giá thay đổi

User đã Order phải nhận notification.

Ví dụ:

```text
Giá Order của bạn đã được cập nhật.

Campaign:
Friday Coffee

Số tiền cũ:
90.000đ

Số tiền mới:
98.000đ

Lý do:
Giá thực tế trên GrabFood thay đổi.

[Xem Order]
```

Realtime:

```text
order.price_adjusted
```

Channel:

```text
user:{roomUserId}
```

---

# 23. Socket.IO Price Update

Flow:

```text
Admin Save Price
      │
      ▼
Laravel Transaction
      │
      ▼
Commit
      │
      ▼
order.price_adjusted
      │
      ▼
Socket.IO
      │
      ▼
user:{roomUserId}
      │
      ▼
User refreshes Order state
```

Không gửi full financial state qua Socket.

Frontend fetch lại authoritative Order từ Laravel.

---

# 24. Top 5 Popular Items

Admin Dashboard Widget:

```text
🔥 Top món

#1 Cà phê sữa đá
12 items
10 users
540.000đ

#2 Trà đào
9 items
8 users
405.000đ
```

Hiển thị:

- Quantity.
- Unique Users.
- Amount.

Dùng cùng service với User Local:

```text
PopularCampaignItemsService
```

---

# 25. Full Item Summary

Admin có:

```text
[Xem tất cả món]
```

Ví dụ:

| Món | Qty | Users | Amount |
|---|---:|---:|---:|
| Cà phê sữa đá | 12 | 10 | 540.000 |
| Trà đào | 9 | 8 | 405.000 |
| Matcha | 7 | 7 | 385.000 |

Dùng khi Admin tổng hợp đặt món.

---

# 26. Campaign Participation Tracking

Để Final Summary đúng, hệ thống phải theo dõi trạng thái của từng Room User với Campaign.

Đề xuất:

```text
campaign_participants

id
campaign_id
room_user_id

notification_received_at nullable

status

acted_at nullable

created_at
updated_at
```

Status:

```text
pending
ordered
declined
```

Ý nghĩa:

```text
pending
→ đã nhận Campaign/notification nhưng chưa Order và chưa từ chối

ordered
→ đã tạo Order hợp lệ

declined
→ chủ động từ chối tham gia
```

---

# 27. Decline Campaign

Room User có thể:

```text
[Tôi không tham gia]
```

Update:

```text
campaign_participants.status = declined
```

Realtime về Admin:

```text
campaign.participant_declined
```

Điều này giúp Final Summary chính xác.

---

# 28. Campaign Participant Counters

Trong Live Control Center có thể hiển thị:

```text
Total Users
30

Ordered
22

Declined
3

Pending
5
```

Trong đó:

```text
Total Users
=
số Room Users eligible/đã nhận Campaign
```

```text
Pending
=
Total - Ordered - Declined
```

Nhưng nên dựa vào `campaign_participants`, không tính động từ toàn bộ Room Users nếu Campaign target thay đổi.

---

# 29. Close Campaign

Admin click:

```text
[Đóng Campaign]
```

Modal:

```text
Đóng Friday Coffee?

Total Users
30

Ordered
22

Declined
3

Pending
5

Orders
22

[ ] Cho phép ghi nợ

[Hủy]
[Đóng Campaign]
```

---

# 30. Allow Debt when Closing Campaign

Khi đóng Campaign, Admin có option:

```text
Cho phép ghi nợ
```

Nếu bật:

```text
allow_debt = true
```

Laravel sẽ tạo/recalculate Debt sau final settlement.

---

# 31. Debt Calculation khi đóng Campaign

Flow:

```text
Close Campaign
     │
     ▼
Lock Campaign
     │
     ▼
Freeze Orders
     │
     ▼
Calculate Final Order Prices
     │
     ▼
Calculate Sponsor Allocation
     │
     ▼
Determine User Payables
     │
     ▼
Allow Debt?
     │
     ├── No
     │    └── Normal Payment Flow
     │
     └── Yes
          │
          ├── Create/Recalculate User Debts
          └── Create/Recalculate Sponsor Debts
     │
     ▼
Campaign Closed
```

---

# 32. User Debt

Nếu User chưa thanh toán đủ:

```text
User Payable
90.000đ

Paid
0đ

Debt
90.000đ
```

Debt:

```text
debtor_type = room_user
```

---

# 33. Sponsor Debt

Nếu Sponsor chưa hoàn tất nghĩa vụ tài trợ:

```text
Sponsor A

Committed
300.000đ

Paid
100.000đ

Debt
200.000đ
```

Debt:

```text
debtor_type = sponsor
```

Có thể liên kết Sponsor về:

```text
room_user_id
```

vì Sponsor là verified Room User.

---

# 34. Debt Model

Nên thiết kế debt hỗ trợ cả User và Sponsor.

Ví dụ:

```text
debts

id
room_id
campaign_id

debtor_type
debtor_room_user_id

original_amount
paid_amount
remaining_amount

status

created_at
updated_at
```

`debtor_type`:

```text
user
sponsor
```

---

# 35. Sponsor Allocation Snapshot

Khi Campaign đóng, phải snapshot allocation.

Ví dụ:

```text
campaign_sponsor_allocations

id
campaign_id
sponsor_room_user_id

rule_type
rule_value

committed_amount
allocated_amount
paid_amount
debt_amount

created_at
updated_at
```

Để sau này thay đổi Room Settings không làm thay đổi lịch sử Campaign.

---

# 36. Campaign Final Summary

Sau khi Campaign đóng:

```text
Friday Coffee

Completed
```

## Participation Summary

```text
Total Users
30

Users Ordered
22

Users Declined
3

Users No Action
5
```

`Users No Action` là:

```text
đã nhận Campaign/notification
AND
không declined
AND
không Order
```

---

# 37. Financial Summary

```text
Orders
22

Items
35

Subtotal
2.150.000đ

Sponsor
200.000đ

User Payables
1.950.000đ

User Debt
350.000đ

Sponsor Debt
100.000đ
```

---

# 38. Item Summary

```text
Cà phê sữa đá     x12
Trà đào            x9
Matcha             x7
...
```

Actions:

```text
[Copy Summary]
[Export]
```

---

# 39. Campaign Final Summary Detail

Có thể chia tabs:

```text
Overview
Items
Users
Sponsors
Debts
Payments
```

## Users Tab

```text
TRUNGLT
Ordered
90.000đ
Debt 0đ
```

```text
ANNV
Declined
```

```text
MINHNT
No Action
```

## Sponsors Tab

```text
Nguyễn Văn A

Committed
300.000đ

Debt
50.000đ
```

---

# 40. Room Orders

## 40.1. Route

```text
/admin/rooms/{room}/orders
```

Đây là lịch sử toàn Room.

Filters:

- Date.
- Campaign.
- User.
- Order Status.
- Payment Status.

Search:

- Order Code.
- User Name.
- User Code.

---

# 41. Payments & Debt Management

## 41.1. Route

```text
/admin/rooms/{room}/payments
```

Summary:

```text
Total Receivable
4.850.000đ

Paid
4.100.000đ

User Debt
550.000đ

Sponsor Debt
200.000đ
```

Tabs:

```text
All
Users
Sponsors
Paid
Debt
```

---

# 42. Payment Confirmation

Ví dụ:

```text
TRUNGLT

Friday Coffee

90.000đ

[Confirm Paid]
```

Laravel cập nhật:

```text
paid_amount
remaining_amount
status
paid_at
confirmed_by
```

Emit:

```text
payment.updated
```

---

# 43. Bulk Payment Confirmation

```text
☑ TRUNGLT      90.000
☑ ANNV         65.000
☑ MINHNT      120.000

[Xác nhận 3 thanh toán]
```

Phải có confirmation modal.

---

# 44. Room Users Management

## 44.1. Route

```text
/admin/rooms/{room}/users
```

Danh sách:

```text
Avatar

Lâm Thành Trung
TRUNGLT

trung@company.com

Active

Orders: 24
Joined: 15/08/2026

[View]
```

Search:

- Name.
- Email.
- User Code.

Filter:

- Active.
- Blocked.
- Inactive.

---

# 45. Room User Detail

## 45.1. Route

```text
/admin/rooms/{room}/users/{roomUser}
```

Hiển thị:

```text
GLOBAL IDENTITY

Lâm Thành Trung
trung@company.com
Google Verified
```

và:

```text
ROOM IDENTITY

User Code
TRUNGLT

Joined
15/08/2026

Status
Active
```

Admin không được chỉnh:

- Global name.
- Google email.
- Global account status.

---

# 46. Block / Unblock Room User

Block:

```text
room_users.status = blocked
```

Không thay đổi:

```text
global_users.status
```

Realtime:

```text
room_user.blocked
room_user.unblocked
```

---

# 47. Room User Metrics

Trong User Detail:

```text
Orders
24

Total Spent
2.450.000đ

User Debt
90.000đ

Sponsor Commitments
300.000đ

Sponsor Debt
50.000đ
```

Nếu User đồng thời là Sponsor, hiển thị cả hai vai trò.

---

# 48. Reports

## 48.1. Route

```text
/admin/rooms/{room}/reports
```

MVP:

- Campaign Report.
- Order Report.
- Payment/Debt Report.
- User Report.
- Product Report.
- Sponsor Report.

---

# 49. Campaign Report

Ví dụ:

| Campaign | Total Users | Ordered | Declined | No Action | Orders | Amount |
|---|---:|---:|---:|---:|---:|---:|
| Friday Coffee | 30 | 22 | 3 | 5 | 22 | 2.15M |

Filter:

```text
Today
This Week
This Month
Custom
```

---

# 50. Product Report

```text
Top Products

Cà phê sữa đá
120

Trà đào
95

Matcha Latte
78
```

Có:

- Quantity.
- Order count.
- Unique users.
- Amount.

---

# 51. Sponsor Report

Ví dụ:

| Sponsor | Campaigns | Committed | Paid | Debt |
|---|---:|---:|---:|---:|
| Nguyễn Văn A | 10 | 3.0M | 2.8M | 200K |

---

# 52. User Report

Ví dụ:

| User | Orders | Amount | User Debt |
|---|---:|---:|---:|
| TRUNGLT | 24 | 2.45M | 90K |
| ANNV | 21 | 2.10M | 0 |

Không dùng dữ liệu này để đánh giá hiệu suất nhân sự.

---

# 53. Room Settings

## 53.1. Route

```text
/admin/rooms/{room}/settings
```

Admin chỉ chỉnh Room-level settings.

---

# 54. Campaign Default Settings

Đây là phần quan trọng để Create Campaign nhanh.

Cấu hình:

```text
Default Campaign Name
Default Deadline
Default Max Budget

Default Sponsors
Default Sponsor Rule
Default Sponsor Value

Default Payment Account
Default Notification Settings
```

Ví dụ:

```text
Default Campaign Name
Friday Coffee

Default Deadline
10:30

Default Max Budget
2.000.000đ

Default Sponsor Rule
Percentage

Default Value
20%
```

---

# 55. Room Payment Settings

Ví dụ:

```text
Bank
ACB

Account Number
******789

Account Name
ABC COMPANY
```

Không expose credential không cần thiết.

---

# 56. Admin Notifications

Route:

```text
/admin/notifications
```

Notification:

- New Order.
- Order Price Change result.
- Payment Submitted.
- Campaign Deadline.
- Room User Joined.
- Sponsor Debt.
- User Debt.

---

# 57. Admin Realtime

Channels:

```text
admin:{adminId}
admin-room:{roomId}
```

Events:

```text
campaign.updated
campaign.participant_declined

order.created
order.updated
order.deleted
order.price_adjusted

payment.updated
payment.submitted

debt.updated

room_user.joined
```

Socket server phải validate Admin có quyền với Room.

---

# 58. Admin Profile

Route:

```text
/admin/profile
```

Nếu Admin đồng thời là Global User:

```text
Lâm Thành Trung

trung@company.com

Role
Admin

Managed Rooms
3
```

Không tạo identity duplicate nếu không cần.

---

# 59. Admin Permissions

MVP:

```text
Admin
→ full operational access
  trong assigned Room
```

Có thể chuẩn bị permission:

```text
room.manage
campaign.manage
order.manage
order.adjust_price
payment.manage
debt.manage
room_user.manage
report.view
room_settings.manage
```

---

# 60. Admin không được làm gì

Admin không được:

- View unrelated Rooms.
- Manage Superadmins.
- Manage Global Users globally.
- Global block User.
- Change System Settings.
- Manage Feedback approval.
- Manage application Versions.
- Change `feedback_daily_limit`.
- Change `feedback_public_min_rating`.
- Manage Admin của Room không liên quan.

---

# 61. Admin Navigation

Sidebar:

```text
DrinkFlow Admin

Dashboard

ROOM
IT Team ▼

Overview

Campaigns
Orders
Payments & Debt
Users

ANALYTICS
Reports

SETTINGS
Room Settings

──────────────

Notifications
Global User
Logout
```

Không có:

```text
Shops
Products
Product Options
```

như module quản trị độc lập.

Menu data được xử lý trong Campaign creation/import flow.

---

# 62. Route Structure

```php
Route::prefix('admin')
    ->middleware([
        'global.auth',
        'global.active',
        'admin',
    ])
    ->name('admin.')
    ->group(function () {

        Route::get('/', ...)
            ->name('dashboard');

        Route::get('/notifications', ...)
            ->name('notifications');

        Route::prefix('rooms/{room}')
            ->middleware('admin.room')
            ->name('rooms.')
            ->group(function () {

                Route::get('/', ...)
                    ->name('dashboard');

                Route::resource(
                    'campaigns',
                    ...
                );

                Route::get(
                    '/campaigns/{campaign}/popular-items',
                    ...
                )->name('campaigns.popular-items');

                Route::post(
                    '/campaigns/{campaign}/close',
                    ...
                )->name('campaigns.close');

                Route::get('/orders', ...)
                    ->name('orders.index');

                Route::get('/orders/{order}', ...)
                    ->name('orders.show');

                Route::patch(
                    '/orders/{order}/prices',
                    ...
                )->name('orders.adjust-prices');

                Route::get('/payments', ...)
                    ->name('payments.index');

                Route::get('/users', ...)
                    ->name('users.index');

                Route::get('/users/{roomUser}', ...)
                    ->name('users.show');

                Route::get('/reports', ...)
                    ->name('reports.index');

                Route::get('/settings', ...)
                    ->name('settings');
            });
    });
```

---

# 63. Blade Structure

```text
resources/views/admin/

├── dashboard.blade.php
├── rooms/
│   ├── dashboard.blade.php
│   ├── campaigns/
│   │   ├── index.blade.php
│   │   ├── create.blade.php
│   │   ├── edit.blade.php
│   │   ├── show.blade.php
│   │   ├── preview.blade.php
│   │   └── final-summary.blade.php
│   ├── orders/
│   │   ├── index.blade.php
│   │   └── show.blade.php
│   ├── payments/
│   │   └── index.blade.php
│   ├── users/
│   │   ├── index.blade.php
│   │   └── show.blade.php
│   ├── reports/
│   └── settings/
├── notifications/
└── profile/
```

---

# 64. Blade Components

```text
resources/views/components/admin/

sidebar.blade.php
room-switcher.blade.php

stat-card.blade.php

live-campaign-card.blade.php
campaign-card.blade.php
campaign-countdown.blade.php

campaign-source-selector.blade.php
campaign-sponsor-selector.blade.php
campaign-sponsor-rule.blade.php

live-order-table.blade.php
order-status.blade.php
order-price-editor.blade.php

popular-items.blade.php
item-summary.blade.php

payment-status.blade.php
debt-summary.blade.php

room-user-card.blade.php

confirm-modal.blade.php
```

---

# 65. Admin Realtime Architecture

```text
                   Laravel
                      │
          ┌───────────┼────────────┐
          │           │            │
       Orders      Payments      Campaign
          │           │            │
          └───────────┼────────────┘
                      │
                      ▼
                 Domain Events
                      │
                      ▼
                  Socket.IO
                      │
                      ▼
             admin-room:{roomId}
                      │
                      ▼
              Admin Dashboard
                      │
        ┌─────────────┼─────────────┐
        │             │             │
      Orders       Counters      Top Items
```

Socket chỉ signal state change.

Financial data luôn fetch lại từ Laravel.

---

# 66. MVP Priority

## Phase 1

```text
Admin Dashboard
Room Dashboard

Fast Create Campaign
Reuse Campaign Menu
Import JSON
Crawler URL

Multiple Sponsors
3 Sponsor Rules

Live Campaign Control Center
Participant Counters

Live Orders
Order Detail
Adjust Order Price
Realtime User Notification

Top 5 Items
Full Item Summary

Close Campaign
Allow Debt
User Debt
Sponsor Debt

Final Summary

Payments
Room Users
Block / Unblock

Room Settings
```

## Phase 2

```text
Advanced Reports

Bulk Payments

Sponsor Reports

Admin Notifications

Advanced Permissions

More Campaign Data Import Sources
```

---

# 67. Actor Boundary

| Global User | Room User | Admin |
|---|---|---|
| `/me` | `/rooms/{room}` | `/admin` |
| Global Identity | Room Membership | Assigned Room Management |
| My Rooms | Current Room | Campaign Management |
| All Personal Orders | Room Personal Orders | All Room Orders |
| Personal Payments | Room Payments | Payments & Debt |
| Feedback | Campaign / Menu | Sponsor Management |
| Devices | Top 5 Items | Reports |
| Notifications | Order | Room Settings |

Superadmin:

```text
Global Users
Admins
All Rooms
System Settings
Feedback Moderation
Versions
System Reports
Audit Logs
System Health
```

---

# 68. Business Rules Summary

```text
CREATE CAMPAIGN
→ Fast-first
→ Defaults from Room
→ Menu reuse/import/crawler
→ Multi Sponsor
→ Fixed / Per User / Percentage


LIVE CAMPAIGN
→ Realtime Orders
→ Admin can adjust actual Order price
→ Recalculate financial logic
→ Recalculate Sponsor allocation
→ Recalculate Debt if needed
→ Notify affected User


CAMPAIGN PARTICIPATION
→ Ordered
→ Declined
→ Pending / No Action


CLOSE CAMPAIGN
→ Final settlement
→ Optional Allow Debt
→ User Debt
→ Sponsor Debt
→ Final Participation Summary
→ Final Financial Summary
```

Nguyên tắc cuối cùng:

```text
Admin
→ vận hành nhanh

Laravel
→ tự tính business logic

Room Settings
→ giảm thao tác lặp

Realtime
→ cập nhật ngay

Audit
→ lưu thay đổi tài chính

Campaign Snapshot
→ bảo toàn lịch sử
```

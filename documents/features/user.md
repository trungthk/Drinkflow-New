# 1. Actor: User

User là người sử dụng DrinkFlow hằng ngày để tham gia các chiến dịch đặt nước/đồ ăn trong một hoặc nhiều Room.

User được thiết kế theo mô hình **Global User + Room User + Device Identity**:

```text
Google Identity
      │
      ▼
Global User
      │
      ├──────────────┐
      ▼              ▼
Room User A       Room User B
      │              │
      ▼              ▼
Device Identity   Device Identity
```

Trong đó:

- **Global User**: danh tính chung của nhân viên trên toàn bộ DrinkFlow, được xác minh qua Google bằng email công ty.
- **Room User**: hồ sơ/thành viên của Global User trong từng Room.
- **Device Identity**: thông tin thiết bị đã được đăng ký để các lần truy cập tiếp theo vào Room không cần xác thực Google lại.
- Một Global User có thể tham gia nhiều Room.
- Admin chỉ quản lý Room User trong Room được phân quyền.
- Superadmin có thể quản lý Global User trên toàn hệ thống.

---

## 1.1. User Identity Architecture

User identity được chia thành ba lớp:

1. Global User.
2. Room User.
3. Device Identity.

Không sử dụng Room User như identity global của toàn hệ thống.

### Global User

Global User đại diện cho danh tính thật của nhân viên trong DrinkFlow.

Global User được tạo hoặc nhận diện sau khi xác thực thành công thông qua Google OAuth / OpenID Connect bằng email công ty.

Thông tin lấy từ Google:

- Họ tên (`name`).
- Email (`email`).
- Avatar (`picture`).
- Google Provider User ID (`sub`).
- Trạng thái xác minh email (`email_verified`).

Laravel phải xác minh identity từ Google OAuth callback. Không tin các thông tin Google do browser tự gửi lên.

### Room User

Room User đại diện cho Global User trong một Room cụ thể.

Một Global User có thể có nhiều Room User:

```text
Global User #15
├── Room User #100 → IT
├── Room User #217 → Marketing
└── Room User #305 → Accounting
```

Mỗi Room User có các thông tin riêng:

- `room_id`
- `global_user_id`
- `user_code`
- `display_name`
- `normalized_name`
- `status`
- `joined_at`
- `last_active_at`

### Device Identity

Giữ nguyên cơ chế `device_uuid`.

Device Identity dùng để xác định thiết bị đã được đăng ký cho Room User nào, giúp user truy cập lại Room nhanh mà không phải Google OAuth mỗi lần.

---

## 1.2. Xác thực Google bằng email công ty

Khi user truy cập một Room lần đầu và thiết bị chưa có Room User hợp lệ, hệ thống hiển thị màn hình xác thực tài khoản.

Ví dụ:

```text
Bạn cần xác thực tài khoản công ty
trước khi tham gia Room này.

[ Tiếp tục với Google ]
```

User phải sử dụng tài khoản Google thuộc domain email công ty.

### Google OAuth Flow

```text
User
  │
  ▼
Room
  │
  ▼
Chưa có Room User trên device
  │
  ▼
Google Authentication
  │
  ▼
Google OAuth Callback
  │
  ▼
Verify email_verified
  │
  ▼
Verify Company Domain
  │
  ▼
Resolve / Create Global User
```

Laravel phải kiểm tra:

- OAuth callback hợp lệ.
- `email_verified = true`.
- Email thuộc domain công ty được cho phép.
- Google identity chưa bị revoke hoặc không hợp lệ.

Ví dụ cấu hình:

```env
GOOGLE_ALLOWED_DOMAINS=company.com
```

Có thể hỗ trợ nhiều domain công ty nếu cần:

```text
company.com
company.co.jp
subsidiary.com
```

Không cho phép Gmail cá nhân nếu hệ thống được cấu hình chỉ dành cho email công ty.

Ví dụ:

```text
user@gmail.com
→ Reject

user@company.com
→ Accept
```

Thông báo khi không hợp lệ:

> Tài khoản này không thuộc email công ty. Vui lòng đăng nhập bằng tài khoản Google Workspace được công ty cung cấp.

---

## 1.3. Tạo / nhận diện Global User

Sau khi Google authentication thành công, Laravel tiến hành nhận diện Global User.

Ưu tiên nhận diện bằng Google Provider User ID (`sub`).

Flow:

```text
Google Callback
      │
      ▼
Find OAuth Identity by provider + sub
      │
      ├── Found
      │     │
      │     ▼
      │  Global User
      │
      └── Not Found
            │
            ▼
       Check existing email
            │
            ▼
       Link or Create
       Global User
```

Nếu Global User chưa tồn tại, tạo mới từ thông tin Google.

Global User lưu:

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

Google identity nên được lưu riêng:

```text
oauth_identities

id
global_user_id
provider
provider_user_id
provider_email
created_at
updated_at
```

Ví dụ:

```text
provider = google
provider_user_id = Google sub
```

Không tạo duplicate Global User khi cùng một Google identity đăng nhập từ thiết bị khác.

---

## 1.4. Chuẩn hóa tên

Giữ nguyên nghiệp vụ chuẩn hóa tên hiện tại.

Ví dụ:

```text
Nguyễn Thành Trung
        ↓
NGUYEN THANH TRUNG
```

Không overwrite tên thật lấy từ Google.

Phải lưu cả:

```text
name = Nguyễn Thành Trung
normalized_name = NGUYEN THANH TRUNG
```

`name` dùng để hiển thị profile thân thiện.

`normalized_name` dùng cho các nghiệp vụ hiện tại như:

- Generate user code.
- Search không dấu.
- Matching dữ liệu.
- Hiển thị theo format nghiệp vụ nếu cần.

---

## 1.5. Đăng ký Room User

Sau khi Global User được xác định, Laravel kiểm tra Global User đã tham gia Room hiện tại chưa.

```text
Global User
      │
      ▼
Check Room User
      │
      ├── Exists
      │     │
      │     ▼
      │  Register/Resolve Device
      │
      └── Missing
            │
            ▼
       Create Room User
            │
            ▼
       Register Device
```

Nếu chưa có Room User, hệ thống tạo mới.

Database:

```text
room_users

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

Không yêu cầu user nhập lại họ tên, email hoặc avatar vì các thông tin này đã có từ Global User.

---

## 1.6. User Code

Giữ nguyên logic `user_code` hiện tại.

Ví dụ:

```text
Nguyễn Thành Trung
        ↓
NGUYEN THANH TRUNG
        ↓
NGUYENTHANHTRUNG
```

Nếu trùng trong cùng Room:

```text
NGUYENTHANHTRUNG
NGUYENTHANHTRUNG2
NGUYENTHANHTRUNG3
```

`user_code` chỉ cần unique trong phạm vi Room:

```text
UNIQUE(room_id, user_code)
```

Không bắt buộc global unique.

Ví dụ Global User có thể có cùng user code ở nhiều Room:

```text
IT Room
→ NGUYENTHANHTRUNG

Marketing Room
→ NGUYENTHANHTRUNG
```

---

## 1.7. Device UUID và Trusted Device

Giữ nguyên `device_uuid`.

Sau khi Room User được tạo hoặc xác định, thiết bị hiện tại được bind với Room User.

Database:

```text
room_user_devices

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

Browser có thể giữ `device_uuid`.

Credential dùng để xác thực device không được lưu plaintext.

Ưu tiên sử dụng Secure + HttpOnly cookie do Laravel quản lý cho trusted token.

Không sử dụng `device_uuid` một mình như bằng chứng xác thực.

---

## 1.8. Flow lần đầu truy cập Room

Ví dụ user truy cập:

```text
/rooms/it
```

Laravel kiểm tra:

1. Device hiện tại đã được nhận diện chưa.
2. Device đã có Room User của Room hiện tại chưa.
3. Global User có active không.
4. Room User có active không.

Flow tổng quát:

```text
GET /rooms/{room}
        │
        ▼
Resolve Device
        │
        ▼
Room User exists?
   │             │
  YES            NO
   │             │
   ▼             ▼
Validate      Global User
Status        recognized?
   │          │        │
   ▼         YES       NO
Enter Room    │         │
              ▼         ▼
         Confirm Join  Google OAuth
              │         │
              │         ▼
              │    Resolve/Create
              │     Global User
              │         │
              └────┬────┘
                   ▼
            Create Room User
                   │
                   ▼
            Register Device
                   │
                   ▼
               Enter Room
```

Nếu thiết bị chưa xác định được Global User:

- Hiển thị Google authentication.
- Xác minh email công ty.
- Resolve hoặc Create Global User.
- Create Room User nếu cần.
- Bind device.
- Redirect vào Room.

---

## 1.9. Các lần truy cập tiếp theo cùng Room

Khi user quay lại một Room đã đăng ký trước đó, Laravel sử dụng Room User + Device Identity để xác thực.

Flow:

```text
GET /rooms/{room}
      │
      ▼
Resolve Device
      │
      ▼
Resolve Room User
      │
      ▼
Validate:
- Device token valid
- Device not revoked
- Global User active
- Room User active
      │
      ▼
Enter Room
```

Nếu tất cả hợp lệ:

- Không hiển thị Google authentication.
- Không yêu cầu nhập lại họ tên.
- Không yêu cầu chọn lại tài khoản.
- Không tạo Room User mới.
- Cập nhật `last_seen_at`.
- Cập nhật `last_active_at`.

---

## 1.10. Global User đã tồn tại và tham gia Room mới

Đây là flow khi user đã từng sử dụng DrinkFlow nhưng order trong một Room mới.

Ví dụ:

```text
Global User: Nguyễn Thành Trung

Đã tham gia:
- IT Room

Chưa tham gia:
- Marketing Room
```

Khi truy cập Marketing Room từ device đã nhận diện được Global User, không yêu cầu Google OAuth lại.

Hiển thị màn hình xác nhận:

```text
[Avatar]

Nguyễn Thành Trung
trung@company.com

Bạn chưa tham gia Room Marketing.

[ Tham gia Room ]
```

User chỉ cần xác nhận tham gia.

Sau đó Laravel:

1. Kiểm tra Global User hợp lệ.
2. Kiểm tra Room hợp lệ.
3. Kiểm tra chưa tồn tại Room User.
4. Generate `user_code`.
5. Create Room User.
6. Bind device với Room User.
7. Redirect vào Room.

Không tạo Global User mới.

Không yêu cầu nhập lại:

- Họ tên.
- Email.
- Avatar.

---

## 1.11. Truy cập từ thiết bị mới

Nếu Global User đã tồn tại nhưng user truy cập bằng một thiết bị chưa được xác thực:

```text
New Device
    │
    ▼
Google Authentication
    │
    ▼
Resolve existing Global User
    │
    ▼
Check Room User
    │
    ├── Exists
    │      │
    │      ▼
    │ Register New Device
    │      │
    │      ▼
    │  Enter Room
    │
    └── Missing
           │
           ▼
      Confirm / Create
        Room User
           │
           ▼
      Register Device
           │
           ▼
        Enter Room
```

Không tạo duplicate Global User.

Google authentication trên device mới dùng để chứng minh thiết bị mới thuộc đúng Global User.

---

## 1.12. Middleware xác thực User

Tách rõ middleware:

```text
ResolveGlobalUser
ResolveRoomUser
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
Check Global Status
   │
   ▼
Check Room Status
   │
   ▼
Controller
```

### ResolveGlobalUser

Có trách nhiệm:

- Resolve trusted device/session.
- Xác định Global User hiện tại.
- Kiểm tra Global User active.
- Không tin `global_user_id` từ browser.

### ResolveRoomUser

Có trách nhiệm:

- Xác định Room hiện tại.
- Kiểm tra Global User có Room User tương ứng.
- Kiểm tra Room User active.
- Kiểm tra device được phép sử dụng Room User.
- Không tin `room_user_id` hoặc `room_id` do browser tự claim.

Không load toàn bộ users hoặc rooms xuống browser để xác thực.

---

## 1.13. Global Block và Room Block

Global User status và Room User status là hai cấp khác nhau.

### Admin

Admin chỉ được block user trong Room mà Admin quản lý:

```text
room_users.status = blocked
```

Ví dụ:

```text
Marketing Room → blocked
IT Room        → active
```

User vẫn có thể sử dụng IT Room.

### Superadmin

Superadmin được block Global User:

```text
global_users.status = blocked
```

Khi Global User bị block:

```text
IT Room         → denied
Marketing Room  → denied
Accounting Room → denied
```

Authorization:

```text
global_users.status
        │
        ├── blocked → Deny toàn hệ thống
        │
        ▼
room_users.status
        │
        ├── blocked → Deny Room hiện tại
        │
        ▼
      Allow
```

---

## 1.14. Chọn / truy cập Room

Nếu Global User đã tham gia nhiều Room, user chỉ nhìn thấy những Room mình là thành viên.

Ví dụ:

- IT.
- Marketing.
- Accounting.
- HCM Office.
- Can Tho Office.

Feature:

- Xem Room hiện tại.
- Chuyển Room nếu đã tham gia nhiều Room.
- Hiển thị avatar, tên Room, description.
- Nhận Campaign theo Room.
- Hiển thị trạng thái membership nếu cần.

Không được:

```text
GET tất cả rooms
```

mà không kiểm tra membership hoặc quyền truy cập.

Nếu user mở trực tiếp URL của một Room chưa tham gia, áp dụng flow **Global User đã tồn tại và tham gia Room mới**.

---

## 1.15. Xem Campaign đang hoạt động

Home hiển thị:

- Campaign đang mở.
- Tên quán.
- Creator.
- Sponsor.
- Thời gian chốt.
- Ngân sách.
- Trạng thái.
- Payment information phù hợp.

Trạng thái nên chuẩn hóa:

- `draft`
- `scheduled`
- `active`
- `closing`
- `closed`
- `cancelled`

User chỉ thấy:

- `active`
- `scheduled` nếu business muốn cho xem trước.

Realtime:

- `campaign.created`
- `campaign.updated`
- `campaign.closed`
- `campaign.cancelled`

Socket Room:

```text
room:{roomId}
```

---

## 1.16. Smart Search món

User có thể:

- Search tên món.
- Search không dấu.
- Filter category.
- Search topping.
- Debounce input.
- Sort theo giá nếu cần.

Ví dụ:

```text
Trà sữa
tra sua
TRA SUA
```

đều ra kết quả tương đương.

Nên thực hiện search client-side nếu menu nhỏ.

Nếu menu lớn:

```text
GET /campaigns/{campaign}/items?q=tra+sua
```

---

## 1.17. Xem chi tiết món

Hiển thị:

- Tên món.
- Ảnh.
- Giá.
- Category.
- Size.
- Topping.
- Description.
- Availability.

Có thể bổ sung:

```text
sold_out
temporarily_unavailable
max_quantity
```

---

## 1.18. Cấu hình món

User được chọn:

- Size.
- Ice.
- Sugar.
- Topping.
- Số lượng.
- Ghi chú.
- Payment method.

Ví dụ:

```text
Trà sữa Oolong

Size: M
Ice: 30%
Sugar: 50%

Topping:
[x] Trân châu
[x] Pudding

Note:
Ít trân châu

Payment:
VietQR
```

Client có thể tính giá preview.

Laravel phải tính lại giá authoritative khi submit.

---

## 1.19. Đặt hàng

Endpoint:

```text
POST /campaigns/{campaign}/orders
```

Laravel phải thực hiện:

1. Resolve Global User.
2. Resolve Room User.
3. Validate Global User active.
4. Validate Room User active.
5. Validate Campaign active.
6. Validate Room User thuộc Room của Campaign.
7. Validate item thuộc Campaign.
8. Validate item available.
9. Validate size/topping/options.
10. Calculate total server-side.
11. Check Single Active Order.
12. Create Order.
13. Create Order Items.
14. Create Order Item Toppings.
15. Dispatch domain event.
16. Emit realtime signal qua Socket.IO.

Không tin các giá trị từ browser như:

```text
total_price
discount
sponsor_amount
final_amount
global_user_id
room_user_id
room_id
```

Order phải gắn với Room User hiện tại.

---

## 1.20. Single Active Order Lock

Một Room User chỉ được có một active order cho một Campaign.

Ví dụ:

```text
Room User: Trung
Campaign: Starbucks 10/09

Order #100
status = active

→ Không được tạo Order #101
```

Chỉ khi Admin:

- `delete`
- `cancel`
- `unlock`

order hiện tại thì User mới được đặt lại.

Laravel + PostgreSQL phải enforce rule này.

Không chỉ kiểm tra ở frontend.

---

## 1.21. Order Confirmation

Sau khi order thành công, hiển thị:

- Thank-you modal.
- Order summary.
- Món.
- Quantity.
- Total.
- Sponsor.
- Số tiền user cần trả.
- Payment method.
- Note.
- Thời gian đặt.

Order confirmation phải sử dụng dữ liệu Laravel trả về sau khi order được lưu thành công.

---

## 1.22. Theo dõi trạng thái Order

Có thể chuẩn hóa status:

- `submitted`
- `confirmed`
- `ordering`
- `ordered`
- `delivering`
- `completed`
- `cancelled`

User nhận realtime:

```text
order.updated
```

Ví dụ:

```text
Admin cập nhật:
ordered

        ↓
Socket.IO

        ↓
user:{roomUserId}

        ↓
UI cập nhật:
"Quán đã nhận đơn"
```

---

## 1.23. Socket.IO cho User

Socket identity của User phải dựa trên Room User hiện tại.

Laravel cấp short-lived signed token chứa:

```text
actor_type
global_user_id
room_user_id
room_id
expiration
```

Ví dụ:

```json
{
  "actor_type": "user",
  "global_user_id": 15,
  "room_user_id": 100,
  "room_id": 4
}
```

Recommended channels:

```text
user:{roomUserId}
room:{roomId}
```

Có thể có Global User channel cho các event cấp hệ thống nếu thật sự cần:

```text
global-user:{globalUserId}
```

Nhưng các event liên quan order phải ưu tiên Room User channel để tránh gửi dữ liệu order của Room A sang context Room B.

Client không được tự quyết định channel authorization.

Không chấp nhận việc browser tự gửi:

```text
global_user_id
room_user_id
room_id
```

rồi Socket.IO tin trực tiếp.

Laravel phải quyết định các channel hợp lệ khi cấp socket token.

---

## 1.24. Realtime Events cho User

User có thể nhận:

### Global/System Events

- `system.maintenance`
- `notification.message`

### Room Events

- `campaign.created`
- `campaign.updated`
- `campaign.closed`
- `campaign.cancelled`
- `room.updated`

### Personal / Room User Events

- `order.updated`
- `order.deleted`
- `debt.updated`
- `user.blocked`
- `user.unblocked`

Channel tương ứng:

```text
room:{roomId}
user:{roomUserId}
```

Socket.IO chỉ đóng vai trò realtime signal.

Laravel vẫn là source of truth.

---

## 1.25. VietQR

Nếu payment = transfer, User xem:

- QR.
- Bank.
- Account.
- Amount.
- Transfer content.

Laravel sinh dữ liệu QR.

User không được chỉnh amount phía client.

Thông tin payment account phải được lấy theo Room/Campaign hiện tại và phải qua authorization.

---

## 1.26. Lịch sử Order

Trang:

```text
/history
```

Filter:

- Ngày.
- Tháng.
- Room.
- Campaign.
- Status.
- Payment.

Global User có thể xem lịch sử của chính mình trên các Room được phép truy cập.

Khi đang ở context một Room, mặc định chỉ hiển thị order của Room User hiện tại.

User không được xem order của user khác.

---

## 1.27. User Analytics

Có thể hiển thị:

- Tổng số order.
- Tổng số ly.
- Tổng số tiền.
- Tổng sponsor received.
- Top món.
- Top quán.
- Order theo tháng.

Có thể có hai scope:

### Current Room

Analytics của Room User hiện tại.

### Global Personal

Analytics tổng hợp của chính Global User trên các Room mà user đã tham gia.

User chỉ xem dữ liệu của chính mình.

---

## 1.28. Notification

User có thể nhận:

- Campaign mới.
- Campaign sắp đóng.
- Order accepted.
- Order changed.
- Campaign closed.
- Payment reminder.
- Room membership changes.
- Account blocked/unblocked.
- System maintenance.

Realtime event:

```text
notification.message
```

Channels:

```text
user:{roomUserId}
room:{roomId}
```

Global notification nếu cần:

```text
global-user:{globalUserId}
```

---

## 1.29. Profile

User có profile cấp Global.

Thông tin hiển thị:

- Avatar.
- Họ tên.
- Normalized name.
- Email công ty.
- Danh sách Room đã tham gia.
- Trạng thái tài khoản.

Các thông tin lấy từ Google như:

- Họ tên.
- Email.
- Avatar.

không nên cho browser tự ý thay đổi thành identity khác.

Nếu cần refresh thông tin Google, thực hiện thông qua quy trình re-auth/sync được Laravel kiểm soát.

Thông tin Room User như `user_code` vẫn được quản lý độc lập theo từng Room.

---

## 1.30. Multi-language

Giữ:

- `vi`
- `en`
- `ja`

Có thể dùng Laravel translation:

```text
lang/vi/
lang/en/
lang/ja/
```

---

## 1.31. Database liên quan đến User

Kiến trúc database đề xuất:

```text
global_users
    │
    ├── oauth_identities
    │
    └── room_users
            │
            └── room_user_devices
```

### `global_users`

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

### `oauth_identities`

```text
id
global_user_id
provider
provider_user_id
provider_email
created_at
updated_at
```

Unique constraint đề xuất:

```text
UNIQUE(provider, provider_user_id)
```

Email cũng nên có constraint/index phù hợp với rule nghiệp vụ của hệ thống.

### `room_users`

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

Unique constraints:

```text
UNIQUE(room_id, global_user_id)
UNIQUE(room_id, user_code)
```

### `room_user_devices`

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

---

## 1.32. Eloquent Relationships

Quan hệ chính:

```text
GlobalUser
 │
 ├── hasMany OAuthIdentity
 │
 └── hasMany RoomUser
              │
              ├── belongsTo Room
              └── hasMany RoomUserDevice
```

Global User không trực tiếp đại diện cho membership của một Room.

Các nghiệp vụ:

- Order.
- Debt.
- Room block.
- Room activity.
- Room-specific analytics.

nên liên kết với `room_user_id`.

Các nghiệp vụ global:

- Google identity.
- Global block.
- Global profile.
- Global personal analytics.

liên kết với `global_user_id`.

---

## 1.33. Route đề xuất

Google authentication:

```text
GET /auth/google
GET /auth/google/callback
```

Room:

```text
GET  /rooms/{room}
GET  /rooms/{room}/join
POST /rooms/{room}/join
```

Campaign:

```text
GET  /rooms/{room}/campaigns/{campaign}
POST /rooms/{room}/campaigns/{campaign}/orders
```

History:

```text
GET /history
```

Profile:

```text
GET /profile
```

Các route phải resolve Room và User server-side.

Không tin ID từ browser nếu chưa qua authorization.

---

## 1.34. Security Rules

Bắt buộc tuân thủ các rule sau:

- Chỉ chấp nhận Google account thuộc company domain được cấu hình.
- `email_verified` phải hợp lệ.
- Không tin name/email/avatar do browser tự gửi thay cho Google identity.
- Một Google identity không được tạo nhiều Global User.
- Một Global User không được có duplicate Room User trong cùng Room.
- Một Global User được phép tham gia nhiều Room.
- `user_code` unique theo Room.
- Device hợp lệ truy cập lại cùng Room không phải OAuth lại.
- Device mới phải xác minh identity.
- Device token bị revoke phải yêu cầu xác thực lại.
- `device_uuid` không được dùng một mình như authentication secret.
- Admin block Room User không ảnh hưởng Room khác.
- Superadmin block Global User phải chặn toàn bộ Room.
- Browser không thể giả `global_user_id`.
- Browser không thể giả `room_user_id`.
- Browser không thể giả `room_id`.
- Browser không thể subscribe Socket.IO channel ngoài quyền.
- Không load toàn bộ users hoặc rooms xuống browser để xác thực.
- Không expose OAuth credential, token hoặc secret xuống HTML.
- Order phải được tạo dựa trên Room User đã resolve server-side.

---

## 1.35. Feature Tests bắt buộc

### Google Authentication

- Company Google account đăng nhập thành công.
- Gmail cá nhân bị reject khi không thuộc allowed domain.
- Email chưa verified bị reject.
- OAuth callback không hợp lệ bị reject.
- Google identity đã tồn tại resolve đúng Global User.
- Không tạo duplicate Global User.

### Global User

- Tạo Global User lần đầu thành công.
- Lưu name, normalized name, email, avatar đúng.
- Một Global User có thể tham gia nhiều Room.
- Global blocked user không truy cập được bất kỳ Room nào.

### Room User

- Tạo Room User khi vào Room lần đầu.
- Không tạo duplicate Room User.
- User code được generate đúng.
- User code unique trong Room.
- User code có thể giống nhau ở hai Room khác nhau.
- Room blocked user chỉ bị chặn tại Room tương ứng.

### Device

- Device mới yêu cầu Google authentication nếu chưa xác định Global User.
- Device đã đăng ký truy cập lại Room không OAuth lại.
- Device revoked bị từ chối.
- Device không thể claim Room User khác.
- Device của Room A không tự động được coi là Room User của Room B nếu chưa đăng ký.

### New Room

- Global User đã tồn tại truy cập Room mới hiển thị thông tin profile để xác nhận.
- Không yêu cầu nhập lại name/email/avatar.
- Confirm join tạo Room User mới.
- Không tạo Global User mới.
- Sau khi join, device được bind với Room User mới.

### Order

- User không thuộc Room không được order.
- Global blocked user không được order.
- Room blocked user không được order tại Room bị block.
- User không thể giả Room User để order thay người khác.
- Single Active Order được enforce server-side.
- Giá order được tính lại server-side.

### Socket.IO

- User chỉ join được `user:{roomUserId}` của chính mình.
- User chỉ join được `room:{roomId}` đã được authorize.
- User Room A không subscribe được private user channel của Room B.
- Fake `global_user_id` bị reject.
- Fake `room_user_id` bị reject.
- Fake `room_id` bị reject.
- Expired socket token bị reject.
- Revoked device không được cấp socket token mới.

---

## 1.36. User Authentication Summary

Luồng identity cuối cùng:

```text
Google OAuth
     │
     ▼
Global Identity
     │
     ▼
Global User
     │
     ▼
Room Registration
     │
     ▼
Room User
     │
     ▼
Device Trust
     │
     ▼
Fast Authentication
for Subsequent Access
```

Nguyên tắc:

```text
Google
→ Xác minh "Bạn là ai?"

Global User
→ Danh tính của bạn trong toàn hệ thống

Room User
→ Danh tính/membership của bạn trong Room hiện tại

Device Identity
→ Giúp thiết bị đã xác thực truy cập lại nhanh

Laravel
→ Source of truth và authorization

Socket.IO
→ Realtime transport
```

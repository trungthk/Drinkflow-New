# Feedback / Đánh giá hệ thống

## 1. Mục tiêu

Cho phép Global User đánh giá trải nghiệm sử dụng DrinkFlow và gửi nội dung góp ý ngắn.

Mỗi Feedback gồm:

- Điểm đánh giá từ 1 đến 5.
- Nội dung góp ý.
- Thời gian gửi.

Superadmin có quyền duyệt Feedback để công khai cho những User khác xem.

---

## 2. Routes

Trang Feedback:

```text
/me/feedback
```

Gửi Feedback:

```text
POST /me/feedback
```

Không cần trang Feedback Detail riêng trong giai đoạn đầu.

---

## 3. Giao diện

Trang `/me/feedback` gồm hai khu vực chính:

```text
Đánh giá DrinkFlow

Bạn cảm thấy DrinkFlow thế nào?

☆ ☆ ☆ ☆ ☆

1 = Rất không hài lòng
5 = Rất hài lòng

Góp ý của bạn

[Nhập nội dung góp ý...]

[Gửi đánh giá]
```

Bên dưới là:

```text
Đánh giá từ mọi người

★★★★★
4.8 / 5

------------------------------

★★★★★

Ứng dụng dễ sử dụng và
order khá nhanh.

10/09/2026

------------------------------

★★★★☆

Giao diện đơn giản, dễ dùng.

09/09/2026
```

---

## 4. Rating

Điểm đánh giá sử dụng thang điểm:

```text
1 → 5
```

UI:

```text
☆ ☆ ☆ ☆ ☆
```

Sau khi chọn:

```text
★ ★ ★ ★ ☆

4 / 5
```

Ý nghĩa tham khảo:

```text
1 ★ → Rất không hài lòng
2 ★ → Chưa hài lòng
3 ★ → Bình thường
4 ★ → Hài lòng
5 ★ → Rất hài lòng
```

Laravel validate:

```text
required
integer
min:1
max:5
```

---

## 5. Nội dung Feedback

User nhập nội dung góp ý:

```text
Góp ý của bạn

[DrinkFlow khá dễ sử dụng,
mong muốn cải thiện thêm giao diện mobile.]
```

Validation đề xuất:

```text
required
string
max:1000
```

Không cần:

- Feedback Type.
- Priority.
- Impact.
- Attachment.
- Screenshot.
- Ticket status.
- Admin reply.
- Timeline.

---

## 6. Giới hạn Feedback theo ngày

Mặc định:

```text
1 Global User
=
1 Feedback / ngày
```

Rule phải được kiểm tra server-side.

Ví dụ:

```text
User A

11/09/2026
→ đã Feedback

User A

11/09/2026
→ không được Feedback thêm

User A

12/09/2026
→ được Feedback lại
```

Không giới hạn theo Room.

Feedback thuộc:

```text
Global User
```

---

## 7. Config giới hạn Feedback

Không hard-code:

```text
1 feedback/day
```

Nên có System Setting:

```text
feedback_daily_limit = 1
```

Superadmin có thể thay đổi:

```text
1
2
3
5
...
```

Ví dụ:

```text
feedback_daily_limit = 3
```

thì mỗi Global User được gửi tối đa 3 Feedback/ngày.

Laravel kiểm tra:

```text
count feedback
where global_user_id = current user
and created_at thuộc ngày hiện tại

< feedback_daily_limit
```

Nên tính ngày theo timezone hệ thống đã cấu hình, tránh lệch ngày do UTC.

---

## 8. Khi hết lượt Feedback

Nếu User đã đạt giới hạn:

```text
Cảm ơn bạn đã gửi đánh giá hôm nay.

Bạn đã sử dụng hết lượt đánh giá
trong ngày.

Bạn có thể tiếp tục đánh giá vào ngày mai.
```

Ẩn hoặc disable:

```text
[Gửi đánh giá]
```

Có thể hiển thị:

```text
Lượt đánh giá hôm nay

1 / 1
```

Nếu config là 3:

```text
2 / 3
```

Frontend chỉ hỗ trợ UX.

Laravel vẫn phải enforce limit.

---

## 9. Feedback Public

Feedback sau khi User gửi:

```text
is_public = false
```

Mặc định không công khai.

Superadmin xem Feedback và quyết định:

```text
Approve Public
```

Sau khi duyệt:

```text
is_public = true
```

Feedback mới có thể xuất hiện cho User khác.

---

## 10. Điều kiện hiển thị công khai

Một Feedback chỉ được hiển thị public khi đồng thời:

```text
is_public = true
AND
rating >= feedback_public_min_rating
```

Tức là:

| Rating | Superadmin duyệt | Public |
|---:|:---:|:---:|
| 1 ★ | Có | Không |
| 2 ★ | Có | Không |
| 3 ★ | Có | Có |
| 4 ★ | Có | Có |
| 5 ★ | Có | Có |
| 3–5 ★ | Không | Không |

---

## 11. Config điểm tối thiểu để Public

Không nên hard-code `3`.

System Setting:

```text
feedback_public_min_rating = 3
```

Default:

```text
3
```

Superadmin có thể thay đổi sau này.

Ví dụ:

```text
feedback_public_min_rating = 4
```

thì chỉ Feedback:

```text
4 ★
5 ★
```

được phép xuất hiện public sau khi duyệt.

---

## 12. Public Feedback List

Global User có thể xem các Feedback đã được phép công khai.

Ngay tại:

```text
/me/feedback
```

section:

```text
Đánh giá từ mọi người
```

Query:

```text
is_public = true

AND

rating >= feedback_public_min_rating
```

Sort mặc định:

```text
published_at DESC
```

Ví dụ:

```text
★★★★★

5 / 5

Rất tiện khi team order đồ uống,
không cần tổng hợp bằng chat nữa.

10/09/2026
```

---

## 13. Privacy

Khuyến nghị không hiển thị đầy đủ danh tính người đánh giá.

Có thể hiển thị:

```text
Trung L.
```

hoặc:

```text
Người dùng DrinkFlow
```

Không public:

- Email.
- User Code.
- Global User ID.
- Device.
- Room membership.

MVP có thể đơn giản dùng:

```text
Người dùng DrinkFlow
```

để tránh phát sinh thêm privacy rule.

---

## 14. Rating Summary

Trang Feedback có thể hiển thị:

```text
Đánh giá từ cộng đồng

★★★★★

4.6 / 5

128 đánh giá
```

Khuyến nghị điểm trung bình tổng quan được tính trên các Feedback đã được công khai:

```text
AVG(rating)

WHERE
is_public = true
AND rating >= feedback_public_min_rating
```

MVP chỉ cần:

```text
Average Rating
+
Total Public Feedback
```

---

## 15. Submit Flow

```text
Global User
     │
     ▼
/me/feedback
     │
     ▼
Check Daily Limit
     │
     ├── Reached
     │      ↓
     │   Disable Submit
     │
     └── Available
            │
            ▼
       Select 1–5 ★
            │
            ▼
       Enter Feedback
            │
            ▼
       Laravel Validate
            │
            ▼
       Create Feedback
            │
            ▼
       is_public = false
            │
            ▼
       Wait Superadmin Review
```

---

## 16. Superadmin Flow

```text
User sends Feedback
       │
       ▼
Pending Review
       │
       ▼
Superadmin
       │
       ├── Keep Private
       │
       └── Approve Public
                │
                ▼
          Check Rating
                │
        ┌───────┴───────┐
        │               │
Rating < minimum   Rating >= minimum
        │               │
        ▼               ▼
    Not Public        Public
```

---

## 17. Database

Bảng:

```text
feedbacks
```

Đề xuất:

```text
id

global_user_id

rating
content

is_public

reviewed_by_superadmin_id nullable
reviewed_at nullable
published_at nullable

created_at
updated_at
```

Không cần:

```text
type
priority
status
source_url
room_id
campaign_id
order_id
browser
device
app_version
resolved_at
```

---

## 18. Index

Đề xuất:

```text
INDEX(global_user_id, created_at)

INDEX(is_public, rating, published_at)
```

Index đầu phục vụ Daily Limit Check.

Index thứ hai phục vụ Public Feedback List.

---

## 19. System Settings

Bổ sung:

```text
feedback_enabled = true

feedback_daily_limit = 1

feedback_public_min_rating = 3
```

Ý nghĩa:

```text
feedback_enabled
→ Bật/tắt chức năng Feedback

feedback_daily_limit
→ Số Feedback tối đa mỗi Global User/ngày

feedback_public_min_rating
→ Điểm tối thiểu để Feedback đủ điều kiện public
```

---

## 20. Authorization

Chỉ Global User đang active mới được gửi Feedback.

Laravel resolve authenticated Global User.

Không nhận:

```text
global_user_id
```

từ frontend.

User không được:

```text
approve feedback
change is_public
change published_at
xem feedback private của người khác
```

---

## 21. Route Structure

```php
Route::prefix('me')
    ->middleware([
        'global.auth',
        'global.active',
    ])
    ->name('me.')
    ->group(function () {

        Route::get('/feedback', ...)
            ->name('feedback.index');

        Route::post('/feedback', ...)
            ->name('feedback.store');
    });
```

Chỉ cần:

```text
GET /me/feedback
POST /me/feedback
```

cho phía Global User.

---

## 22. Blade Structure

```text
resources/views/user/global/feedback/

└── index.blade.php
```

Components:

```text
resources/views/components/user/global/

feedback-form.blade.php
rating-stars.blade.php
feedback-card.blade.php
rating-summary.blade.php
```

Không cần:

```text
create.blade.php
show.blade.php
feedback-timeline.blade.php
feedback-status.blade.php
```

---

## 23. Giao diện tổng thể

```text
┌────────────────────────────────────────┐
│ Góp ý cho DrinkFlow                    │
│                                        │
│ Bạn cảm thấy DrinkFlow thế nào?        │
│                                        │
│ ☆  ☆  ☆  ☆  ☆                         │
│                                        │
│ Góp ý                                  │
│ ┌────────────────────────────────────┐ │
│ │                                    │ │
│ └────────────────────────────────────┘ │
│                                        │
│ Lượt hôm nay: 0 / 1                   │
│                                        │
│                  [Gửi đánh giá]        │
└────────────────────────────────────────┘


┌────────────────────────────────────────┐
│ Đánh giá từ mọi người                  │
│                                        │
│ ★★★★★   4.6 / 5                       │
│ 128 đánh giá                           │
│                                        │
│ ────────────────────────────────────── │
│ ★★★★★                                  │
│                                        │
│ DrinkFlow rất tiện khi team order...   │
│                                        │
│ Người dùng DrinkFlow · 10/09/2026      │
│                                        │
│ ────────────────────────────────────── │
│ ★★★★☆                                  │
│                                        │
│ Giao diện đơn giản và dễ sử dụng.      │
│                                        │
│ Người dùng DrinkFlow · 09/09/2026      │
└────────────────────────────────────────┘
```

---

## 24. Business Rules tổng kết

```text
Global User Active
        │
        ▼
Feedback Enabled?
        │
        ▼
Daily Feedback Count
        │
        ▼
count < configured limit
        │
        ▼
Rating 1 → 5
+
Feedback Content
        │
        ▼
Create Feedback
        │
        ▼
Private by Default
        │
        ▼
Superadmin Review
        │
        ▼
Approve Public?
        │
        ▼
Rating >= configured minimum?
        │
        ▼
Show to Global Users
```

Default:

```text
feedback_daily_limit = 1
feedback_public_min_rating = 3
```

Một Global User mặc định được gửi 1 đánh giá/ngày. Feedback gồm điểm 1–5 và nội dung góp ý. Feedback mặc định là private. Chỉ Feedback được Superadmin duyệt công khai và có rating từ ngưỡng cấu hình trở lên mới xuất hiện trong danh sách đánh giá dành cho Global User.

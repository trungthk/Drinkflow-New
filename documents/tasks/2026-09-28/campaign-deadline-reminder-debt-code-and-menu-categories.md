# Task: Nhắc sắp hết hạn chiến dịch (web + socket), mã công nợ mới, lọc danh mục rỗng, chi tiêu theo gross total và mở công khai hướng dẫn

**Ngày thực hiện:** 28/09/2026
**Trạng thái:** ✅ Hoàn thành. `php artisan test`: 577 pass, 2 skipped, 1 fail.
- Test fail là `SeoTest::test_private_routes_send_noindex_header` (header `X-Robots-Tag` của `/rooms/missing`). Lỗi này đã có trước đợt này và vẫn fail khi bỏ hết thay đổi.
- `npm run build` OK. Migration mới đã chạy trên MySQL local.

---

## 1. Tổng quan

1. **Nhắc chiến dịch sắp hết giờ đặt món**
   - Thành viên chưa đặt món nhận thông báo web kèm socket.
   - Admin của room nhận thông báo tóm tắt kèm socket trên mọi trang admin.
2. **Mã công nợ mới** dạng `<yymmdd><roomId><4 ký tự A-Z0-9>`, ví dụ `2609281XIAU`. Chỉ áp dụng cho công nợ tạo mới.
3. **Menu không còn danh mục rỗng:**
   - Khi admin nhập menu (Data Gateway, file `.md`/`.json`, dán JSON, crawler, dùng lại menu cũ).
   - Khi thành viên xem menu để đặt.
4. **Dashboard admin:** biểu đồ "Xu hướng chiến dịch & chi tiêu 7 ngày" tính chi tiêu theo **gross total** của chiến dịch.
5. **Hướng dẫn công khai:** mở `/guides/08-bao-mat-thiet-bi` và `/guides/11-tai-khoan-bi-khoa`. Trước đây hai trang này trả 404, trong khi các bài công khai khác vẫn link tới.

---

## 2. Chi tiết thay đổi

### 2.1. Nhắc sắp hết hạn chiến dịch

**Nghiệp vụ (đã chốt với người dùng)**

| Hạng mục | Quyết định |
|---|---|
| Thời điểm nhắc | Cố định toàn hệ thống: `config('campaign.deadline_reminder_minutes')`, env `CAMPAIGN_DEADLINE_REMINDER_MINUTES`, mặc định 15 phút; `0` = tắt. |
| Số lần nhắc | 1 lần cho mỗi mốc deadline. Gia hạn deadline sẽ nhắc lại theo mốc mới. |
| Kênh ngoài (Telegram/Discord/Slack) | Không gửi. |
| Phía admin | Nhận trên mọi trang admin của room (toast + chuông), không chỉ Dashboard. |

**Điều kiện một chiến dịch được nhắc**
- `status = active`, chưa khoá đặt món (`ordering_locked_at` null).
- Room đang `active`.
- `now < deadline <= now + N phút`.
- Chưa nhắc cho đúng deadline hiện tại.

**Người nhận**
- **Thành viên:**
  - Phải là RoomUser `active`, và global user cũng `active`.
  - Chưa có đơn nào ở trạng thái khác `cancelled` trong chiến dịch.
  - Không nằm trong danh sách `declined` (`campaign_participants`).
- **Admin:** mọi admin `active` của room, mỗi người 1 `AdminNotification` tóm tắt, ví dụ "Còn 3/12 thành viên chưa đặt món".

**Chống gửi trùng**
- Migration `2026_09_28_000000_add_deadline_reminder_sent_for_to_campaigns_table`:
  - thêm cột `campaigns.deadline_reminder_sent_for` (timestamp, nullable);
  - thêm index `(status, deadline)`.
- Cột này lưu **giá trị deadline** đã nhắc, không phải thời điểm gửi. Khi admin gia hạn, deadline đổi nên chiến dịch tự đủ điều kiện nhắc lại.
- Trước khi gửi, service "claim" chiến dịch bằng một câu `UPDATE` có điều kiện. Scheduler chạy chồng hoặc chạy nhiều server cũng chỉ gửi 1 lần.

**Thành phần**

| Thành phần | Ghi chú |
|---|---|
| `App\Services\Campaign\CampaignDeadlineReminderService` | Chọn chiến dịch, claim, gửi cho thành viên + admin; `formatTime()` hiển thị giờ theo `app.timezone`. |
| `App\Console\Commands\RemindCampaignDeadlines` | `drinkflow:remind-campaign-deadlines`, đăng ký `everyMinute()->withoutOverlapping()` trong `routes/console.php`. |
| `config/campaign.php` | Số phút nhắc. |
| `NotificationType::CampaignDeadlineReminder` | `campaign.deadline_reminder`. |
| `App\Events\AdminNotificationCreated` | Event mới, `PublishRealtimeEvent` gửi `admin.notification.created` tới kênh riêng `admin:{id}`. |
| `realtime/server.js` | Cho phép event `admin.notification.created` (private, roomless) và kênh `admin:\d+`. |
| `resources/js/admin/realtime.js` | Socket dùng chung cho mọi trang admin có room. Hiện toast và thêm mục vào chuông khi đúng room. Lấy token mới mỗi lần reconnect. Trang không có room hoặc superadmin vẫn dùng socket guest (chỉ nghe bảo trì). |
| `resources/js/global/desktop-notification.js` | `handleRealtimeNotification()`: toast trong trang cho `campaign.deadline_reminder`, kể cả khi chưa bật desktop notification. |
| `NotificationPresentationService` | Tiêu đề và nội dung nhắc hiển thị theo ngôn ngữ đang chọn, dựng từ `data`. Link tới trang đặt món chỉ khi chiến dịch còn orderable. Icon `alarm`. |
| `UserNotificationService` | Loại mới nằm ở tab "Phòng/Đơn". |
| Layout admin | `data-room-id`, `data-admin-socket-token-url`, `data-admin-realtime-i18n` trên `<body>`. |

**Bản dịch (vi/en/ja)**
- `messages.campaign_deadline_reminder_title`, `messages.campaign_deadline_reminder_body`.
- `admin.audit_event_campaign_deadline_reminder`, `admin.campaign_deadline_reminder_body`.

### 2.2. Mã công nợ mới

- `CodeGeneratorService::generateDebtCode(?int $roomId, ?CarbonInterface $date)`:
  - định dạng `ymd` + room id + 4 ký tự ngẫu nhiên `A-Z0-9` (dùng `random_int`);
  - vẫn kiểm tra trùng trong bảng `debts`.
- `Debt::booted()` truyền `room_id` khi tạo công nợ mới chưa có mã. Công nợ đã có mã, kể cả mã cũ `DEB-YYYYMMDD-XXXX`, giữ nguyên. Không có migration dữ liệu.

### 2.3. Lọc danh mục menu không có món

- **Admin**, `resources/js/admin/campaign-create.js` (dùng chung cho trang tạo và trang sửa chiến dịch):
  - `applyMenuItems()` / `importableMenuItems()` bỏ món không có tên và trim tên món, tên danh mục.
  - Danh mục được suy ra từ món, nên danh mục không còn món hợp lệ tự biến mất.
  - Luồng dán JSON và crawler cũng đi qua bước lọc này.
- **Data Gateway** (`components/admin/data-gateway-converter.blade.php`):
  - đọc được khối ```json nằm giữa file `.md` (có chữ trước hoặc sau);
  - bỏ món không tên;
  - thông báo đếm đúng số món thực sự được nạp.
- **Server** (`CampaignItem`):
  - accessor/mutator `category` trim và gộp khoảng trắng; để trống thì thành `null`;
  - `CampaignItem::categoriesOf()` trả các danh mục có ít nhất 1 món.
- **Thành viên:** `UserRoomCampaignService` chỉ lấy danh mục từ món `active`. Danh mục chỉ có món ngừng bán/hết hàng hoặc chỉ gồm khoảng trắng không hiện thành tab.

### 2.4. Dashboard admin – chi tiêu 7 ngày theo gross total

- `AdminDashboardService::weeklyGrossSpendingByDay()`:
  - mỗi chiến dịch **tạo trong ngày** cộng `Campaign::grossTotal()` = tổng subtotal đơn chưa huỷ + phí ship − giảm giá, trước tài trợ;
  - bỏ qua chiến dịch nháp, đã huỷ hoặc chưa có đơn;
  - lấy số liệu cả 7 ngày bằng một truy vấn.
- Trước đây cộng `final_amount` của đơn, tức số tiền sau tài trợ. Đối chiếu dữ liệu local: `CMP-20260922-OSCB` = 89.000 + 20.000 − 35.000 = **74.000đ**, cách tính cũ ra 0đ.
- Bỏ badge "Đang chạy" (thẻ Chiến dịch đang mở) và "Cần đối soát" (thẻ Công nợ chưa thu), thay bằng icon cùng kiểu với các thẻ khác. Xoá key `admin.active_now` không còn dùng.

### 2.5. Hướng dẫn công khai

- `UserGuideService`: bỏ `NON_PUBLIC_PREFIXES` (`08-`, `11-`), `listPublic()` và `isPublicSlug()`. `GuideController` (public) và `SeoController` (sitemap) dùng `list()`.
- `/guides/08-bao-mat-thiet-bi` và `/guides/11-tai-khoan-bi-khoa` trả 200, có trong danh sách và sitemap.

---

## 3. Kiểm thử

- **Test mới:**
  - `CampaignDeadlineReminderTest` (7 test): đúng người nhận, không gửi trùng, gia hạn thì nhắc lại, bỏ qua chiến dịch ngoài cửa sổ/khoá/không deadline/đã đóng, tắt bằng config, kênh socket `admin:{id}`, hiển thị theo ngôn ngữ.
  - `CampaignMenuCategoriesTest` (2 test): chuẩn hoá danh mục, chỉ liệt kê danh mục có món đang bán.
- **Test bổ sung:**
  - `AdminDashboardTrendTest`: chi tiêu theo gross total; thẻ số liệu không còn badge.
  - `CodeGeneratorServiceTest`: công nợ tạo mới nhận mã theo room; mã gán sẵn được giữ nguyên.
- **Test sửa assertion do hành vi được yêu cầu thay đổi:**
  - `CodeGeneratorServiceTest::test_generate_debt_code_format`: định dạng mã công nợ mới.
  - `SeoTest::test_public_guides_are_available_and_in_sitemap`: bài 08/11 giờ công khai.
- **Kết quả:** `php artisan test` → 577 pass, 2 skipped, 1 fail (`SeoTest::test_private_routes_send_noindex_header`, đã fail từ trước).
- **Chưa kiểm tra end-to-end trên trình duyệt:** luồng nhắc hạn qua socket, vì socket server local (cổng 3001) không chạy trong phiên làm việc.

## 4. Triển khai

```bash
cd src
php artisan migrate          # thêm campaigns.deadline_reminder_sent_for + index (status, deadline)
npm run build
php artisan optimize:clear
```

- **Khởi động lại** `realtime/server.js` để nhận event và kênh mới.
- Cần có **cron** gọi `php artisan schedule:run` mỗi phút và **queue worker** (`php artisan queue:work`). Listener đang dùng `ShouldQueue`. Ở local chạy `php artisan schedule:work` + `php artisan queue:work`.
- Tuỳ chọn: đặt `CAMPAIGN_DEADLINE_REMINDER_MINUTES` trong `.env` (mặc định 15).

## 5. Lưu ý

- Nội dung thông báo lưu trong DB theo ngôn ngữ mặc định của app. Khi hiển thị lại trong danh sách/chuông thì được dịch theo ngôn ngữ hiện tại; toast realtime phía admin cũng dịch theo trang.
- Mã công nợ mới không có dấu phân cách, nên room id nhiều chữ số khó tách khỏi phần ngẫu nhiên khi đọc bằng mắt. Mã vẫn duy nhất.
- Bài admin `guide-content/admin/07-thanh-vien-room.md` có link `../user/11-tai-khoan-bi-khoa.md`, dạng mà bộ viết lại link chưa hỗ trợ. Chưa sửa.

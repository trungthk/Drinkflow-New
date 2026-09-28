# Task: Quy tắc truy cập phòng – domain email được tham gia, IP được phép / bị chặn

**Ngày thực hiện:** 28/09/2026
**Trạng thái:** ✅ Hoàn thành.
- `php artisan test`: 613 pass, 2 skipped, 1 fail. Test fail là `SeoTest::test_private_routes_send_noindex_header`, có từ trước.
- `npm run build` OK.

---

## 1. Tổng quan

Admin phòng cấu hình được, riêng cho từng phòng, ở trang **Cài đặt phòng → "Quy tắc truy cập"**. Cả 3 ô đều nhập dạng tags; để trống = không giới hạn.

1. **Domain email được phép tham gia** (vd `thk-hd.vn`, `drinkflow.com`): 0 hoặc nhiều domain.
2. **IP được phép truy cập phòng:** 0 hoặc nhiều IP (IPv4/IPv6).
3. **IP bị chặn:** 0 hoặc nhiều IP (IPv4/IPv6). Luôn thắng danh sách được phép.

Kèm hai middleware tương ứng: `room.ip` và `room.email_domain`.

---

## 2. Chi tiết thay đổi

### 2.1. Lưu trữ & quy tắc

- **`room_settings`:** 3 key mới `allowed_email_domains`, `allowed_ips`, `blocked_ips`, kiểu mới `RoomSetting::TYPE_JSON` (mảng JSON). Không cần migration.
- **`App\Services\Room\RoomAccessPolicy`** (đăng ký `scoped`, cache danh sách theo từng request):
  - `emailAllowed()`: so domain phần sau `@`, không phân biệt hoa thường;
  - `ipAllowed()`: IP bị chặn luôn từ chối; có danh sách cho phép thì chỉ IP trong danh sách mới được vào;
  - IP được chuẩn hoá qua `inet_pton`/`inet_ntop`, nên `::1` và `0:0:0:0:0:0:0:1` là một;
  - `ensureIpAllowed()` / `ensureEmailAllowed()` ném `App\Exceptions\RoomAccessDeniedException` (HTTP 403) kèm thông điệp đã dịch.
- **`UpdateRoomSettingsRequest`:**
  - trước khi validate: trim, chữ thường, bỏ `@` ở đầu domain;
  - validate domain theo regex, IP theo rule `ip`, tối đa 100 mục mỗi danh sách;
  - thông báo lỗi nêu rõ giá trị sai.
- **`UpdateRoomSettingsAction`:** lưu danh sách đã chuẩn hoá (loại trùng); payload trả mảng (mặc định `[]`).

### 2.2. Middleware (`bootstrap/app.php`, `routes/user.php`)

| Alias | Class | Gắn vào |
|---|---|---|
| `room.ip` | `EnsureRoomIpAllowed` | `/rooms/{room}`, trang/API tham gia, toàn bộ nhóm route thành viên `/rooms/{room}/*` (30 route) |
| `room.email_domain` | `EnsureRoomEmailDomainAllowed` | `GET` / `POST /rooms/{room}/join` |

- **`JoinRoomAction`:** kiểm tra lại domain, để mọi đường tham gia đều tuân thủ.
- **`/me/rooms/join`** (tham gia bằng link): báo lỗi IP/domain ngay trên form thay vì trang 403.
- **Trang `errors/403`:** hiện lý do khi lỗi là `RoomAccessDeniedException`. API trả JSON `{ message }`.

### 2.3. Giao diện

- **Component `<x-admin.tags-input>`** + `resources/js/admin/tags-input.js`:
  - thêm tag bằng Enter / phẩy / khoảng trắng; dán được nhiều giá trị;
  - xoá bằng Backspace hoặc nút ×;
  - kiểm tra domain / IPv4 / IPv6, trùng lặp và giới hạn 100 mục ngay trên trình duyệt;
  - bộ đếm `n/100`;
  - chuỗi hiển thị lấy qua `data-i18n`, không hardcode trong JS.
- **`admin/settings.blade.php`:** mục "Quy tắc truy cập" với 3 ô, có hiện IP hiện tại của admin để đối chiếu.
- **`admin/settings.js`:**
  - gửi 3 danh sách khi lưu;
  - trước khi lưu, tự thêm phần chữ đang gõ dở trong ô vào danh sách;
  - hiện lỗi validate đầu tiên từ server thay vì lỗi chung.
- **Bản dịch vi/en/ja:** `admin.room_access_*`, `admin.room_allowed_*`, `admin.room_blocked_*`, `admin.tags_remove`, `room.access.ip_denied`, `room.access.email_domain_denied`.

---

## 3. Kiểm thử

- **Test mới** `RoomAccessRulesTest` (7 test):
  - lưu và chuẩn hoá danh sách, xoá danh sách;
  - từ chối domain/IP sai và danh sách quá 100 mục;
  - IP bị chặn không mở được trang HTML và API, không tham gia được;
  - danh sách cho phép, và danh sách chặn thắng;
  - chỉ domain được phép mới tham gia được (cả qua link phòng);
  - phòng không cấu hình thì không bị giới hạn;
  - trang cài đặt render đủ 3 ô.
- **Chưa kiểm tra bằng trình duyệt:** Playwright MCP đang bị một phiên khác chiếm ("Browser is already in use").

## 4. Triển khai

```bash
cd src
npm run build
php artisan optimize:clear
```

- Không có migration.
- Nếu app chạy sau proxy hoặc load balancer, cần cấu hình trusted proxies, để `$request->ip()` là IP thật của người dùng.

## 5. Lưu ý

- Quy tắc domain chỉ áp dụng lúc **tham gia**; thành viên đã có trong phòng không bị ảnh hưởng. Quy tắc IP áp dụng cho mọi truy cập của thành viên.
- Quy tắc chỉ áp dụng cho phía thành viên. Trang quản trị của admin phòng không bị chặn, nên admin không tự khoá mình.
- Chỉ hỗ trợ IP đơn lẻ, chưa hỗ trợ dải CIDR.

# Controller & Architecture Rules

## 1. Phân tầng kiến trúc (Architecture Layers)
Mọi luồng xử lý tuân thủ thứ tự:
`Request` -> `Middleware` -> `FormRequest` -> `Controller` -> `Action/Service` -> `Model/Query` -> `Database`

## 2. Thin Controller
- Controller chỉ chịu trách nhiệm nhận request, gọi FormRequest validation, gọi Action/Service và trả về response (View hoặc JSON).
- Không viết các phép tính toán phức tạp, query dài dòng, hay transaction nhiều bước trực tiếp trong Controller.

## 3. Phân nhóm Controller (Actor Boundaries)
- **User Global (`App\Http\Controllers\User\Global\*`):**
  - Dành riêng cho các trang thông tin cá nhân và quản lý tài khoản người dùng trên toàn hệ thống.
  - Các Controller bắt buộc nằm trong thư mục `src/app/Http/Controllers/User/Global/`:
    - `DashboardController`: Dashboard tổng quan `/me`.
    - `ProfileController`: Hồ sơ, bảo mật, thanh toán cá nhân `/me/profile`, `/me/payments`, `/me/devices`, `/me/feedback`.
    - `RoomsController`: Danh sách và tìm kiếm phòng tham gia `/me/rooms`, tham gia phòng `POST /me/rooms/join`.
    - `OrdersController`: Lịch sử đơn hàng toàn hệ thống `/me/orders`.
    - `AnalyticsController`: Thống kê chi tiêu cá nhân `/me/statistics`.
    - `NotificationController`: Thông báo toàn hệ thống `/me/notifications`.
- **User Room-Scoped (`App\Http\Controllers\User\*`):**
  - Các Controller xử lý logic bên trong một phòng cụ thể (scoped by room).
  - Ví dụ: `CampaignController`, `OrderController`, `DebtController`, `RoomSettingController`.
- **Admin (`App\Http\Controllers\Admin\*`):**
  - Quản trị viên phòng (Room Owner/Admin).
- **Superadmin (`App\Http\Controllers\Superadmin\*`):**
  - Quản trị viên hệ thống toàn cục.

## 4. Chuẩn mực code PHP (Strict Typing, DocBlock, Enums/Consts)
- **Strict Types & Full Typing**:
  - Luôn đặt `declare(strict_types=1);` ở đầu mọi file PHP.
  - Khai báo kiểu (Type hint) cho mọi tham số truyền vào (params), giá trị trả về (return type) của function/method, và class properties.
- **Đầy đủ PHP Document (PHPDoc)**:
  - Mọi function/method phải có block comment `/** ... */` đầy đủ:
    - Mô tả chức năng ngắn gọn, rõ ràng.
    - `@param <Type> $<name> <Mô tả tham số>`
    - `@return <Type> <Mô tả giá trị trả về>`
    - `@throws <Exception> <Trường hợp ném lỗi (nếu có)>`
- **Tối ưu khả năng tái sử dụng Service (Reusable Services)**:
  - Service không được phụ thuộc trực tiếp vào HTTP Request instance của Web nếu không cần thiết; nhận tham số dạng primitives hoặc Model instance.
  - Các logic truy vấn hoặc tính toán chung phải được đóng gói thành các hàm độc lập để có thể tái sử dụng ở Controller, Job, Command hoặc Event Listener.
- **Quản lý hằng số qua Model Enum hoặc Model Const**:
  - Tuyệt đối không dùng magic strings/magic numbers trong code.
  - Sử dụng **PHP 8.1+ Enums (`App\Enums\*`)** (ví dụ `RoomStatus`, `CampaignStatus`, `PaymentStatus`, ...) hoặc hằng số `const` định nghĩa trong Eloquent Model tương ứng.

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

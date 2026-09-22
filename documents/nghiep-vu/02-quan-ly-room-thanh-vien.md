# 2. Quản lý Room & Thành viên

[← Về Tổng quan nghiệp vụ](overview.md)

**Room** là đơn vị tổ chức trung tâm của DrinkFlow — mỗi phòng ban/nhóm là một Room độc lập với thành viên, chiến dịch, công nợ và cấu hình riêng.

## 2.1. Vòng đời Room

```mermaid
stateDiagram-v2
    [*] --> Active: Superadmin tạo Room
    Active --> Inactive: tạm ngưng hoạt động
    Inactive --> Active: kích hoạt lại
    Active --> Archived: lưu trữ (ngừng vĩnh viễn)
    Inactive --> Archived
    Archived --> [*]
```

Chỉ **Superadmin** tạo/đổi trạng thái Room ([bài 11](11-quan-tri-he-thong-superadmin.md)); Admin chỉ vận hành bên trong Room đã được gán, không tạo Room mới được.

## 2.2. Vòng đời thành viên (RoomUser)

```mermaid
stateDiagram-v2
    [*] --> Active: Global User tham gia qua link mời
    Active --> Blocked: Admin chặn (vi phạm / nợ quá hạn)
    Blocked --> Active: Admin duyệt lại
    Active --> Removed: Admin xoá khỏi Room
    Removed --> Active: Admin khôi phục
```

Điểm quan trọng: **`RoomUserStatus` hoàn toàn độc lập với `GlobalUserStatus`** — bị `Blocked` tại 1 Room không ảnh hưởng quyền truy cập các Room khác hay Cổng thông tin cá nhân `/me` của Global User đó (`JoinRoomAction`, `SetRoomUserStatusAction`).

## 2.3. Luồng tham gia Room

```mermaid
sequenceDiagram
    participant U as Global User
    participant J as JoinPageController
    participant A as JoinRoomAction
    U->>J: GET /rooms/{room}/join
    alt chưa là thành viên
        J-->>U: hiển thị trang xác nhận tham gia
        U->>J: POST /rooms/{room}/join
        J->>A: execute(room, globalUser)
        A-->>J: tạo RoomUser (status=active, user_code sinh tự động)
        J-->>U: redirect vào Dashboard Room
    else đã là thành viên active
        J-->>U: redirect thẳng vào Dashboard Room
    end
```

Mỗi `RoomUser` được cấp một **`user_code`** ngẫu nhiên — dùng làm định danh tra cứu khi đặt hộ ([bài 4](04-dat-mon-gio-hang.md)) và hiển thị trong bảng đối soát thay vì lộ email.

## 2.4. Thiết bị tin cậy (Device Trust)

Mỗi lần đăng nhập trên thiết bị mới có thể tạo một bản ghi `RoomUserDevice` gắn với `RoomUser` cụ thể (không phải Global User) — cho phép bỏ qua xác thực lại trong một khoảng thời gian. Admin có thể **thu hồi** từng thiết bị (`admin.room-user-devices.revoke`) khi nghi ngờ mất máy — xem chi tiết ở [bài 10](10-bao-mat-thiet-bi-audit.md).

## Tham chiếu mã nguồn

`App\Models\Room`, `App\Models\RoomUser`, `App\Models\RoomUserDevice` · `App\Enums\RoomStatus`, `RoomUserStatus` · `App\Actions\User\JoinRoomAction`, `SetRoomUserStatusAction`, `RestoreRoomUserAction`, `AdminAddRoomUserAction` · `App\Events\RoomMembershipUpdated`.

# DrinkFlow Project Instructions & Guidelines

Xem chi tiết đầy đủ tại file [AGENTS.md](file:///c:/laragon/www/Drinkflow-New/AGENTS.md) và thư mục [.agents/rules/](file:///c:/laragon/www/Drinkflow-New/.agents/rules/).

## Tóm tắt các quy tắc cốt lõi:
1. **Môi trường**: Ứng dụng Laravel đặt trong `src/`. Chạy qua Docker Compose (`drinkflow-new-app-1`, `drinkflow-new-postgres-1`, `drinkflow-new-realtime-1`).
2. **Database**: Tuyệt đối **KHÔNG sử dụng Supabase**. Dự án sử dụng **PostgreSQL 16**. Không dùng client-side DB SDK từ browser.
3. **Controller**:
   - User Global: Các Controller quản lý người dùng toàn hệ thống (`/me/*`) đặt tại `App\Http\Controllers\User\Global\*`.
   - User Room-Scoped: Controller trong phòng (`/rooms/{room:slug}`) đặt tại `App\Http\Controllers\User\*`.
4. **Layout**:
   - Dùng `<x-public.layout>` cho các trang public.
   - Dùng `<x-global.layout>` cho các trang cá nhân `/me/*`.
5. **Testing**: Luôn chạy `docker exec drinkflow-new-app-1 php artisan test` sau mỗi thay đổi để đảm bảo 100% test pass.
6. **Hình ảnh (Image)**: Mọi thẻ `<img>` phải có thuộc tính `loading="lazy"`.
7. **PHP Coding Standard**:
   - Khai báo `declare(strict_types=1);` và định kiểu (Type) đầy đủ cho parameters, returns, properties.
   - Function/Method phải có DocBlock đầy đủ (mô tả, `@param`, `@return`).
   - Service thiết kế ưu tiên tái sử dụng (reusable).
   - Hằng số quản lý qua ModelEnum (PHP 8.1+ Enums) hoặc Model `const`.

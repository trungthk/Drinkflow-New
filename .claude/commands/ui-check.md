---
description: Mở trang DrinkFlow bằng Playwright MCP để kiểm tra giao diện thực tế (lỗi console, CDN 404, bản dịch vi/en/ja, bố cục)
argument-hint: <đường dẫn trang, vd: /admin/mens-est/debts> [mô tả điều cần kiểm tra]
---

Kiểm tra giao diện thực tế cho: $ARGUMENTS

Thực hiện lần lượt, dùng các tool `mcp__playwright__browser_*`:

1. **Chuẩn bị**: chạy `docker compose ps` để chắc chắn stack đang chạy (app tại `http://localhost:8080`). Nếu không chạy, báo người dùng thay vì tự khởi động. Nếu vừa sửa JS/CSS, chạy `cd src && npm run build` trước.
2. **Mở trang**: `browser_navigate` tới `http://localhost:8080` + đường dẫn được yêu cầu. Nếu bị chuyển tới trang đăng nhập, dừng lại và nhờ người dùng đăng nhập trong cửa sổ trình duyệt Playwright (không tự hỏi hay lưu mật khẩu), rồi tiếp tục.
3. **Lỗi kỹ thuật**: đọc `browser_console_messages` (lỗi JS) và `browser_network_requests` (request 4xx/5xx, đặc biệt script CDN như `qrcode`, `socket.io`, font).
4. **Nội dung & bản dịch**: dùng `browser_snapshot` để đọc nội dung. Lặp lại với `/lang/vi`, `/lang/en`, `/lang/ja` (mỗi lần điều hướng tới `/lang/{locale}` rồi quay lại trang) và tìm chuỗi bị thiếu dịch, hiển thị dạng key (`admin.xxx`), hoặc còn hardcode một ngôn ngữ.
5. **Bố cục**: `browser_take_screenshot` ở 1440x900, sau đó `browser_resize` 390x844 (mobile) và chụp lại; tìm tràn ngang, chữ bị cắt, bảng lệch cột header/body.
6. **Tương tác** (nếu yêu cầu nhắc tới modal/form): click để mở modal, kiểm tra nội dung và lỗi console sau khi mở. Không gửi form làm thay đổi dữ liệu thật trừ khi người dùng đồng ý.
7. **Báo cáo**: tóm tắt ngắn gọn theo từng mục (lỗi console, request lỗi, bản dịch, bố cục), nêu file cần sửa nếu xác định được. Chỉ sửa code khi được yêu cầu.

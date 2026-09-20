# DrinkFlow – Hướng dẫn cho Claude Code

@AGENTS.md

Quy tắc chi tiết nằm ở [AGENTS.md](AGENTS.md) (import ở trên) và [.agents/rules/](.agents/rules/). File này chỉ bổ sung phần dành riêng cho Claude Code.

## Lệnh thường dùng

| Việc | Lệnh |
| --- | --- |
| Chạy toàn bộ test | `docker exec drinkflow-new-app-1 php artisan test` (hoặc `cd src && php artisan test` khi Docker không chạy) |
| Danh sách route | `docker exec drinkflow-new-app-1 php artisan route:list` |
| Build asset (Vite) | `cd src && npm run build` – **bắt buộc chạy lại sau khi sửa `src/resources/js` hoặc `src/resources/css`**, vì `src/public/build/` không được commit |
| Khởi động toàn bộ stack | `node start.js` (hoặc `docker compose up -d`) – app tại http://localhost:8080 |

## Quy ước làm việc

- Mọi văn bản hiển thị trên UI (kể cả chuỗi dựng trong JS) phải dịch qua `__()` và có đủ key ở `src/lang/{vi,en,ja}/`. JS nhận bản dịch qua thuộc tính `data-i18n` (JSON) trên phần tử Blade, không hardcode chuỗi trong file JS.
- Không sửa test assertion chỉ để "cho qua"; chỉ đổi khi hành vi nghiệp vụ được yêu cầu thay đổi.
- Không đọc, in hay commit `.env`, `src/.env`, `realtime/.env` (đã bị chặn trong `.claude/settings.json`).
- File `src/lang/*`, Blade và JS trong repo có thể dùng CRLF; giữ nguyên kiểu xuống dòng của file khi sửa.

## Kiểm tra giao diện thực tế (Playwright MCP)

Server MCP `playwright` được khai báo trong [.mcp.json](.mcp.json) (cần Node.js/`npx`; lần đầu chạy sẽ tải trình duyệt Chromium). Dùng lệnh `/ui-check` để kiểm tra một trang, hoặc gọi trực tiếp các tool `mcp__playwright__browser_*`.

- Địa chỉ app: `http://localhost:8080`. Trang admin: `/admin/login`, `/admin/{room-slug}/...`. Đổi ngôn ngữ: `/lang/{vi|en|ja}` (lưu trong session rồi redirect về trang trước).
- **Không** ghi tài khoản/mật khẩu vào repo hay memory. Khi cần đăng nhập, hỏi người dùng hoặc để họ tự đăng nhập trong cửa sổ trình duyệt Playwright; phiên đăng nhập được giữ lại giữa các lần chạy.
- Ưu tiên `browser_snapshot` (cây accessibility) để đọc nội dung/kiểm tra bản dịch; dùng `browser_take_screenshot` khi cần xem bố cục. Luôn kiểm tra `browser_console_messages` và `browser_network_requests` để bắt lỗi JS và tài nguyên CDN bị 404.
- Chỉ dùng trình duyệt cho môi trường local/dev; không đăng nhập hay thao tác dữ liệu trên môi trường production.
- Ảnh chụp và log lưu ở `.playwright-mcp/` (đã nằm trong `.gitignore`).

Sau khi sửa UI/JS: build asset, chạy test, rồi mở trang thật bằng Playwright để xác nhận; đừng chỉ dựa vào việc test pass.

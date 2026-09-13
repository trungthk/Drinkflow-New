# Testing & Verification Rules

## 1. Kiểm thử tự động bắt buộc
Mỗi khi hoàn thiện một chức năng, sửa lỗi hoặc tái cấu trúc (refactor), Agent bắt buộc phải chạy bộ test tự động của dự án:

```bash
docker exec drinkflow-new-app-1 php artisan test
```

## 2. Tiêu chuẩn Pass
- **100% tests phải PASS (Xanh)** trước khi coi tác vụ là hoàn thành.
- Không được phép sửa đổi test assertion một cách tùy tiện để "cho qua" nếu logic nghiệp vụ thực tế bị vi phạm.
- Nếu có lỗi phát sinh trong quá trình chạy test:
  1. Phân tích nguyên nhân gốc rễ (Root Cause).
  2. Khắc phục triệt để logic hoặc dữ liệu fixture/migration.
  3. Chạy lại toàn bộ test suite để đảm bảo không gây ra lỗi hồi quy (regression).

## 3. Dọn dẹp Code cũ (Dead Code)
- Khi chuyển đổi hoặc tái cấu trúc các Controller/View/Route cũ sang cấu trúc mới, Agent phải chủ động rà soát và xóa bỏ các file không còn sử dụng để tránh nhầm lẫn cho các tác vụ tiếp theo.
- Kiểm tra danh sách route bằng:
  ```bash
  docker exec drinkflow-new-app-1 php artisan route:list
  ```

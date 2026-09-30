# Kế hoạch nâng cấp bảo mật & hiệu năng – Public / User Global / User Room

**Nguồn:** Báo cáo kiểm tra bảo mật và hiệu năng ngày 30/09/2026 (đọc code + kiểm tra index MySQL thực tế).
**Phạm vi:** Trang Public (`/`, `/contact`, `/guides`, `/check-order/*`...), User Global (`/me/*`), User Room (`/rooms/{room}/*`).  Bổ sung: khu vực Admin của Room (UPG-08).
**Trạng thái chung:** ⏳ Chờ quản trị xác nhận từng task con. Chưa có thay đổi code nào.

---

## 1. Quy trình bắt buộc: xác nhận trước khi thực hiện

> **QUAN TRỌNG – áp dụng cho mọi AI Agent và lập trình viên.**
> Mỗi task con (mã `UPG-xx.y`) **chỉ được bắt đầu thực hiện sau khi quản trị xác nhận bằng lời** cho đúng mã task đó.
> Việc quản trị đồng ý một task con **không** có nghĩa là đồng ý các task con khác, kể cả task con cùng nhóm.

**Xác nhận hợp lệ** là câu trả lời trực tiếp của quản trị (trong hội thoại hoặc ghi vào file này) có nêu rõ mã task. Ví dụ:

- `Đồng ý thực hiện UPG-01.1`
- `Đồng ý UPG-02.1 và UPG-02.2, UPG-02.3 để sau`
- `Đồng ý UPG-05.2 nhưng giữ nguyên xuất Excel toàn bộ lịch sử`

Kèm theo xác nhận, quản trị có thể điều chỉnh phương án. Nếu có điều chỉnh, phải cập nhật lại mục **Phương án** của task trước khi làm.

**Không được coi là xác nhận:**

- Im lặng, hoặc "ok" chung chung không nêu mã task.
- Xác nhận cho một task khác hoặc cho cả nhóm lớn một cách mơ hồ ("làm hết đi" – cần hỏi lại để liệt kê rõ các mã).
- Suy luận của Agent rằng task "nhỏ, an toàn nên làm luôn".

**Sau khi được xác nhận, Agent phải:**

1. Ghi vào ô **Xác nhận của quản trị** của task: nội dung câu xác nhận (trích nguyên văn), ngày xác nhận.
2. Đổi **Trạng thái thực hiện** sang `🔄 Đang thực hiện`.
3. Thực hiện đúng phạm vi đã xác nhận, không mở rộng sang task khác.
4. Chạy kiểm tra theo **Tiêu chí hoàn thành** (`php artisan test`, `npm run build` nếu sửa JS/CSS, migration trên MySQL nếu có).
5. Đổi trạng thái sang `✅ Hoàn thành` kèm kết quả test thật (số pass/fail). Không tự tuyên bố PASS nếu chưa chạy.
6. Báo lại quản trị và **dừng lại** chờ xác nhận task tiếp theo.

**Ký hiệu trạng thái**

| Ô | Giá trị |
|---|---|
| Xác nhận của quản trị | `⏳ Chưa xác nhận` · `✅ Đã xác nhận` · `❌ Từ chối` · `⏸ Hoãn` |
| Trạng thái thực hiện | `⬜ Chưa thực hiện` · `🔄 Đang thực hiện` · `✅ Hoàn thành` · `⚠️ Bị chặn` |

---

## 2. Danh sách task lớn

| File | Task lớn | Số task con | Mức cao nhất |
|---|---|---|---|
| [01-toan-ven-du-lieu-don-hang.md](01-toan-ven-du-lieu-don-hang.md) | UPG-01 – Toàn vẹn dữ liệu đơn hàng trên MySQL | 3 | 🔴 Cao |
| [02-phien-dang-nhap-va-thiet-bi.md](02-phien-dang-nhap-va-thiet-bi.md) | UPG-02 – Phiên đăng nhập & thiết bị tin cậy | 4 | 🔴 Cao |
| [03-ro-ri-du-lieu-json.md](03-ro-ri-du-lieu-json.md) | UPG-03 – Rò rỉ dữ liệu qua JSON / API | 4 | 🔴 Cao |
| [04-kiem-soat-truy-cap-room.md](04-kiem-soat-truy-cap-room.md) | UPG-04 – Kiểm soát truy cập Room | 3 | 🟠 Trung bình |
| [05-hieu-nang-user-global.md](05-hieu-nang-user-global.md) | UPG-05 – Hiệu năng trang User Global (`/me/*`) | 5 | 🔴 Cao |
| [06-hieu-nang-user-room.md](06-hieu-nang-user-room.md) | UPG-06 – Hiệu năng trang User Room | 4 | 🔴 Cao |
| [07-van-hanh-va-hardening.md](07-van-hanh-va-hardening.md) | UPG-07 – Vận hành, dọn dữ liệu & hardening | 4 | 🟡 Thấp |
| [08-admin-room.md](08-admin-room.md) | UPG-08 – Khu vực Admin của Room (`/admin/{room}/*`, `/admin/profile`) | 9 | 🔴 Cao |

## 3. Thứ tự đề xuất

1. **UPG-01.1** – chặn đơn trùng trên MySQL (ảnh hưởng trực tiếp tiền/công nợ).
2. **UPG-02.1**, **UPG-02.2** – đăng xuất thiết bị khác phải có hiệu lực.
3. **UPG-03.1 → UPG-03.3** – bỏ dữ liệu thừa khỏi JSON.
4. **UPG-05.1 → UPG-05.3**, **UPG-06.1** – các điểm tăng tuyến tính theo số đơn.
5. Các task còn lại.

Thứ tự trên chỉ là đề xuất; quản trị quyết định task nào làm trước.

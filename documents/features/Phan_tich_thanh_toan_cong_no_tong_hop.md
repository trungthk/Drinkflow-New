# Phân tích chức năng thanh toán công nợ tổng hợp

**Ngày lập:** 25/09/2026  
**Phạm vi:** Một người dùng có nhiều công nợ theo đơn hàng, được gửi một yêu cầu thanh toán chung và chờ quản trị viên duyệt.

## 1. Quyết định thiết kế

Giữ bảng `debts` hiện tại. Mỗi công nợ đơn hàng là một bản ghi **con**; một bản ghi `debts` mới đóng vai trò **cha**, đại diện cho đúng một yêu cầu thanh toán tổng hợp. `debts.parent_id` của các bản ghi con trỏ tới cha. Chỉ tạo cha khi có **từ hai công nợ đủ điều kiện trở lên**. Không tạo bảng yêu cầu/chi tiết riêng, không thêm `type`, cờ phân loại hay `request_key`.

Ví dụ: D1 còn 500.000đ, D2 còn 300.000đ, D3 còn 700.000đ. Tạo P1 trạng thái `pending`, gán `parent_id = P1.id` cho D1–D3; số tiền yêu cầu P1 = 1.500.000đ, **tính từ số dư của ba con ở lúc gửi yêu cầu**. Trong lúc P1 chờ duyệt, đơn hàng mới D4 tạo công nợ 400.000đ với `parent_id = NULL`. D4 không thuộc P1.

> **Điểm cần chốt với cấu trúc dữ liệu hiện tại:** Nếu số dư công nợ con có thể đổi trong lúc chờ duyệt, `parent_id` một mình không lưu được số tiền của từng con tại lúc gửi. Thiết kế tối giản dưới đây **khóa mọi thao tác làm đổi số dư các con đã gắn cha** cho đến khi duyệt hoặc từ chối. Nếu vẫn phải cho thanh toán riêng, chỉnh đơn/hoàn tiền trong khoảng này, phải bổ sung số tiền chốt trên từng con (hoặc một cấu trúc lưu lịch sử); không thể chỉ dựa vào `SUM` số dư hiện tại.

## 2. Dữ liệu trong `debts`

| Trường | Ý nghĩa |
|---|---|
| `id`, `user_id`, `order_id`, số dư hiện tại | Các trường đang dùng của công nợ đơn hàng; cha có `order_id = NULL`. |
| `parent_id` nullable, index | NULL khi công nợ đơn hàng chưa gom; bằng ID cha khi đang nằm trong yêu cầu. Cha luôn có `parent_id = NULL`. |
| `status` | Với cha: `pending`, `approved`, `rejected` (có thể thêm `cancelled` nếu nghiệp vụ cần). Với con: giữ trạng thái công nợ đang có, không lấy trạng thái cha để thay thế. |
| Thông tin yêu cầu | Phương thức, mã giao dịch, chứng từ, `requested_at`, `approved_at/by`, `rejected_at/by/reason` đặt ở cha hoặc tái sử dụng cột hiện có. |

**Nhận diện bằng quan hệ `parent_id`:** Bản ghi cha là bản ghi có `parent_id IS NULL` **và được ít nhất hai bản ghi khác trỏ tới**; công nợ đơn hàng đã gom có `parent_id IS NOT NULL`; công nợ đơn hàng độc lập có `parent_id IS NULL` và không có bản ghi nào trỏ tới. Cha bị từ chối vẫn giữ liên kết con để duy trì khả năng nhận diện. Cách nhận diện này đòi hỏi truy vấn `EXISTS`/`NOT EXISTS` trên `parent_id`, kể cả khi tính báo cáo và hạn mức.

Cha là **yêu cầu**, không phải một khoản nợ phát sinh thêm. Mọi phép cộng công nợ và kiểm tra hạn mức phải loại cha khỏi tổng và chỉ tính công nợ đơn hàng một lần. Không cập nhật thủ công số tiền cha khi các con thay đổi: với các con đang pending, việc thay đổi số dư bị chặn. Nếu bảng hiện có bắt buộc cha phải có `amount`, dùng giá trị chốt từ tổng các con lúc tạo và xem đó là dữ liệu kiểm tra; lúc duyệt phải đối chiếu lại tổng.

**Ràng buộc:** Con và cha cùng `user_id` (và cùng tenant/store nếu áp dụng); không gán cha làm con, không có nhiều cấp, không tự trỏ. Một cha phải có ít nhất hai con và giữ liên kết với chúng; đảm bảo bằng transaction và kiểm tra nghiệp vụ vì khóa ngoại đơn lẻ không ép được số con tối thiểu. Index `debts(parent_id)` và `debts(user_id, status)`; khóa ngoại self-reference nếu tương thích quy trình xóa hiện tại. Không xóa cứng bản ghi cha hoặc con đã tham gia yêu cầu.

## 3. Quy tắc nghiệp vụ

1. Khi gửi thanh toán tất cả, backend lấy công nợ độc lập của user có số dư > 0 (`parent_id IS NULL` và không có con trỏ tới); loại tất cả công nợ đã gom. **Dưới hai bản ghi thì không tạo cha**: một công nợ dùng chức năng thanh toán riêng hiện có. Không nhận tổng tiền từ frontend làm nguồn tin cậy.
2. Gán các con vào một cha `pending` duy nhất. Trong thời gian pending, con không được thanh toán riêng, gom vào yêu cầu khác, hoặc thay đổi số dư bằng nghiệp vụ khác. Con mới phát sinh sau thời điểm gửi vẫn mua và nợ bình thường, miễn còn hạn mức thực tế.
3. Pending **không thanh toán công nợ, không tăng hạn mức**. `credit_used = SUM(số dư công nợ đơn hàng thực tế)`; `available_credit = MAX(0, credit_limit - credit_used)`; loại bản ghi có con trỏ tới khỏi phép SUM, bao gồm cả cha đã `rejected`; vẫn tính mọi công nợ đơn hàng, kể cả con đang pending và nợ mới. Việc kiểm tra hạn mức khi tạo đơn phải dùng cùng quy tắc và khóa giao dịch thích hợp để tránh hai đơn đồng thời cùng vượt hạn mức.
4. Trên UI, `Tổng công nợ thực tế` bao gồm các con pending; `Đang chờ duyệt` là tổng số dư các con có cha pending; `Có thể gửi yêu cầu` là tổng số dư các con chưa có cha. Đây là ba chỉ tiêu khác nhau; không trừ tiền pending khỏi hạn mức.
5. Một cha chỉ được quyết định một lần: `pending → approved` hoặc `pending → rejected`. Không có `approved → pending`, không gửi duyệt/approve lần thứ hai; không revert approved. Nếu duyệt sai, xử lý theo quy trình điều chỉnh kế toán riêng sau này, không sửa ngược yêu cầu đã duyệt.
6. Từ chối: lưu người duyệt, lý do và thời gian; giữ cha `rejected` và liên kết con–cha để nhận diện và truy vết. Các con được phép thanh toán riêng qua luồng hiện có, nhưng không gom lại vào cha mới. Khi thanh toán riêng, không xóa con hoặc xóa liên kết. Duyệt: giữ liên kết con–cha, không gửi lại.

## 4. Luồng tạo yêu cầu

Trong một transaction:

1. Xác thực người dùng và phương thức thanh toán.
2. Khóa một hàng ổn định theo user (ví dụ hàng tài khoản/hạn mức) để tuần tự hóa mọi thao tác gom nợ và thanh toán riêng của user. Khóa các công nợ đủ điều kiện theo thứ tự ID. Mọi luồng sửa số dư/gán `parent_id` phải tuân thủ cùng quy ước khóa.
3. Lấy **toàn bộ** công nợ độc lập đủ điều kiện tại thời điểm transaction; kiểm tra quyền sở hữu, số dư > 0, `parent_id IS NULL`, không có con trỏ tới, và **số công nợ ≥ 2**. Tính tổng bằng kiểu tiền chính xác (VND integer hoặc decimal), không dùng float. Nếu chỉ có một công nợ, trả về lựa chọn thanh toán riêng.
4. Tạo cha `pending`; gán `parent_id` cho các con bằng cập nhật có điều kiện `parent_id IS NULL` và xác nhận đúng số hàng. Lưu tổng đã tính và chứng từ.
5. Commit rồi hiển thị mã yêu cầu. Nếu cập nhật có điều kiện thất bại, rollback và cho tải lại danh sách.

Việc gửi thêm yêu cầu trong lúc P1 pending chỉ gom các nợ mới đủ điều kiện, và chỉ khi có ít nhất hai nợ; tuyệt đối không gửi lại những con của P1. Hai lần bấm đồng thời được tuần tự hóa qua khóa user; lần thứ hai phải tính lại danh sách và không tạo cha nếu còn dưới hai nợ. Không có khóa yêu cầu để nhận diện các lần retry sau khi giao dịch đã hoàn tất: client cần tải lại trạng thái mới thay vì tự động giả định lần gửi lại là yêu cầu cũ.

## 5. Luồng quản trị viên duyệt/từ chối

**Duyệt**, trong một transaction: khóa cha, kiểm tra `parent_id IS NULL`, có ít nhất hai con và `status = pending`; khóa tất cả con `parent_id = cha.id` theo ID; đối chiếu user, từng số dư và tổng với giá trị đã chốt. Nếu thiếu con hoặc số dư thay đổi, trả `conflict`, không duyệt một phần. Xác nhận chứng từ/giao dịch thực tế; ghi khoản thanh toán vào cơ chế thanh toán từng công nợ hiện có, mỗi con đúng một lần, rồi cập nhật số dư/trạng thái con; cuối cùng chuyển cha thành `approved` và lưu người/thời gian. Chỉ commit khi tất cả thành công. Gắn mã tham chiếu cha vào bút toán/thanh toán nếu hệ thống có trường phù hợp. API nhận yêu cầu duyệt lần hai phải từ chối vì cha không còn `pending`.

**Từ chối**, trong một transaction: khóa cha và các con, yêu cầu cha `pending` và có ít nhất hai con, cập nhật cha `rejected` cùng lý do/người/thời gian; giữ `parent_id` của con và không sửa số dư. API từ chối lần hai cũng phải từ chối.

Không dùng truy vấn “mọi công nợ chưa trả của user” lúc duyệt: nợ phát sinh sau khi gửi yêu cầu không thuộc cha. Trường hợp số dư thay đổi do đường ghi cũ chưa tuân thủ khóa: chặn duyệt, hiển thị chênh lệch để xử lý thủ công; không tự sửa tổng hoặc tự áp dụng sang nợ mới.

## 6. Ví dụ xuyên suốt

| Thời điểm | D1 | D2 | D3 | D4 mới | Cha P1 | Công nợ thực tế | Hạn mức còn lại nếu giới hạn 3.000.000đ |
|---|---:|---:|---:|---:|---|---:|---:|
| Trước yêu cầu | 500.000 | 300.000 | 700.000 | — | — | 1.500.000 | 1.500.000 |
| Gửi P1 | 500.000 | 300.000 | 700.000 | — | Pending 1.500.000 | 1.500.000 | 1.500.000 |
| Mua thêm D4 | 500.000 | 300.000 | 700.000 | 400.000 | Pending 1.500.000 | 1.900.000 | 1.100.000 |
| Duyệt P1 | 0 | 0 | 0 | 400.000 | Approved 1.500.000 | 400.000 | 2.600.000 |

Nếu P1 bị từ chối ở bước thứ ba, công nợ vẫn là 1.900.000đ, hạn mức còn 1.100.000đ; D1–D3 được giải phóng để gửi yêu cầu khác.

## 7. Màn hình và kiểm tra nghiệm thu

- User thấy tổng nợ, chờ duyệt, có thể gửi yêu cầu và hạn mức còn lại. Những con đã gắn cha hiển thị “Chờ duyệt” và tắt thao tác thanh toán riêng. Yêu cầu cha hiển thị danh sách con, tổng, chứng từ, trạng thái.
- Admin thấy danh sách con và số dư đã chốt, tổng tiền, chứng từ, thời điểm gửi; nút duyệt/từ chối chỉ khả dụng khi `pending`, backend vẫn kiểm tra trạng thái bất kể UI.
- Kiểm tra: không có nợ hoặc chỉ có một nợ thì không tạo cha; gửi trùng/double click; hai phiên gửi đồng thời; D4 sinh sau P1; mua hàng khi pending vừa trong/vượt hạn mức; đã trả một phần trước khi gom; duyệt/từ chối lặp; con của cha bị từ chối có thể trả riêng nhưng không gom lại; lỗi giữa lúc ghi thanh toán nhiều con phải rollback toàn bộ; dữ liệu con bị thay đổi ngoài luồng phải báo conflict; báo cáo không cộng trùng cha.

## 8. Kế hoạch tích hợp

1. Rà soát schema thực tế của `debts`, logic thanh toán từng công nợ, nơi tính hạn mức và các báo cáo. Ánh xạ chính xác tên cột hiện có trước khi viết migration.
2. Thêm `parent_id` và chỉ mục; metadata yêu cầu tận dụng các cột hiện có hoặc bổ sung theo nhu cầu nghiệp vụ, không dùng cột phân loại; cập nhật tất cả phép tổng hợp để loại cha. Giữ nguyên dữ liệu cũ như công nợ đơn hàng.
3. Cho các luồng thanh toán riêng/sửa công nợ từ chối thao tác với con có cha `pending` hoặc `approved`. Cho con có cha `rejected` thanh toán riêng nhưng giữ nguyên liên kết. Gom nợ và duyệt dùng transaction cùng quy tắc khóa; rà soát các job/webhook ghi công nợ.
4. Tích hợp màn hình user/admin, xử lý idempotency, ghi nhận người duyệt, kiểm thử các tình huống mục 7 trước khi bật tính năng.

**Giới hạn thiết kế:** Khi chuyển con từ cha bị từ chối sang cha mới, cha cũ có thể không còn con, trở nên không thể phân biệt bằng `parent_id` với công nợ độc lập. Do đó **không thể vừa chỉ dùng `parent_id` để phân biệt, vừa cho chuyển tất cả con sang yêu cầu mới mà vẫn giữ bản ghi cha từ chối một cách an toàn**. Cần chọn một trong hai quy tắc triển khai: (A) cha bị từ chối và các con giữ nguyên liên kết, chỉ cho thanh toán riêng theo quy trình xử lý cha bị từ chối, không gom lại các con đó; hoặc (B) cho gom lại nhưng phải có thêm dấu hiệu nhận diện cha/lịch sử ngoài `parent_id`. Tài liệu chọn **A** cho phạm vi chỉ dùng `parent_id`: con của cha `rejected` có thể thanh toán riêng qua luồng hiện có, không tạo cha mới từ những con đó. Khi cần gửi lại yêu cầu tổng hợp sau từ chối, phải mở rộng mô hình dữ liệu.

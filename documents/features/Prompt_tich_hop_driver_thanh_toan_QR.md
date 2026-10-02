# Prompt triển khai: Quản lý driver thanh toán QR và lịch sử callback

## Bối cảnh và mục tiêu

Hãy khảo sát cấu trúc dự án, luồng thanh toán hiện tại, quy ước code, phân quyền và giao diện quản trị trước khi chỉnh sửa. Chuẩn hóa thanh toán QR thành kiến trúc nhiều driver để dễ quản trị và mở rộng.

Phase này hỗ trợ hai driver:

1. **Hệ thống**: tạo và hiển thị mã QR thanh toán từ thông tin ngân hàng/cấu hình nội bộ. Miễn phí, không tự xác nhận giao dịch.
2. **SePay**: tích hợp theo tài liệu chính thức, có gói dịch vụ, webhook/API và xác nhận giao dịch tự động theo khả năng của SePay.

Mỗi giao dịch thanh toán qua QR phải lưu được driver đã dùng và có trang tra cứu lịch sử callback liên quan. Lịch sử có thể không có bản ghi với driver không gửi callback, hoặc có một/nhiều callback với driver hỗ trợ callback. Tuyệt đối không tạo callback giả để lấp dữ liệu.

## 1. Khảo sát và nguyên tắc thay đổi

- Tìm luồng tạo QR, tạo đơn/giao dịch, cập nhật trạng thái thanh toán và webhook hiện tại.
- Xác định phần driver Hệ thống hiện viết trực tiếp trong server; di chuyển phần này vào cấu trúc driver mà không làm mất hành vi đang dùng.
- Tái sử dụng bảng, model, service, UI và phân quyền hiện có nếu phù hợp; không tạo dữ liệu hoặc luồng thanh toán trùng lặp.
- Không thay đổi nghiệp vụ ngoài phạm vi cần thiết. Không đánh dấu đơn đã thanh toán chỉ vì QR được tạo, đã hiển thị hoặc khách quay lại trang.
- Nếu có xung đột với nghiệp vụ đang chạy, ghi rõ trước/sau thay đổi và chọn phương án bảo toàn an toàn dữ liệu giao dịch.

## 2. Kiến trúc nhiều driver

Tạo abstraction/interface và cơ chế đăng ký/chọn driver phù hợp framework hiện tại. Mỗi driver là một adapter riêng; nghiệp vụ đơn hàng không phụ thuộc trực tiếp vào payload hoặc class riêng của một nhà cung cấp.

Tối thiểu, thiết kế các khả năng phù hợp như:

- Đọc metadata và cấu hình driver.
- Tạo dữ liệu thanh toán/mã QR.
- Xác minh và xử lý callback/webhook khi driver hỗ trợ.
- Chuẩn hóa dữ liệu giao dịch về định dạng nội bộ.
- Đối soát giao dịch nếu driver hỗ trợ.

Triển khai:

- `SystemDriver`: tạo/hiển thị QR từ cấu hình nội bộ, miễn phí, không tự xác nhận thanh toán.
- `SePayDriver`: tích hợp tạo QR/thanh toán và xử lý xác nhận giao dịch theo tài liệu SePay.

Driver Hệ thống hiện được viết trực tiếp trong server phải được chuyển vào kiến trúc này. Tránh rải điều kiện theo tên driver ở nhiều nơi; thêm driver sau này cần chủ yếu bổ sung adapter và cấu hình.

## 3. Gói dịch vụ và lựa chọn driver trong admin

Admin có thể chọn một trong hai driver, xem thông tin tích hợp và các gói tương ứng của driver đó.

### Driver Hệ thống

- Hiển thị gói **Miễn phí**.
- Không có phí giao dịch hoặc hạn mức của nhà cung cấp.
- Nếu cần, có thể hiển thị số liệu nội bộ như lượt tạo QR, nhưng phải ghi rõ đây là usage nội bộ, không phải quota nhà cung cấp.
- Nêu rõ driver chỉ hiển thị QR và không tự nhận biết giao dịch đã thanh toán.

### SePay

- Hiển thị danh sách gói SePay, bắt đầu với gói Free.
- Kiểm tra giá, chu kỳ, hạn mức, cách tính quota và điều kiện vượt hạn mức từ tài liệu/bảng giá chính thức tại thời điểm triển khai.
- Lưu nguồn thông tin và ngày kiểm tra. Với nội dung chưa công bố/chưa xác minh, ghi “Chưa xác định” hoặc “Liên hệ nhà cung cấp”, không tự điền số.
- Phân biệt rõ giao dịch nhận tiền, giao dịch chuyển đi/hoàn tiền và quota API/webhook. Không gộp thành một giới hạn chung nếu nhà cung cấp định nghĩa riêng.
- Nếu đăng ký/nâng cấp phải thực hiện trên trang SePay, cung cấp hướng dẫn/liên kết và cho admin ghi nhận trạng thái. Không giả định chọn gói trong CMS sẽ thay đổi subscription thật.

Tách **catalog gói** khỏi **gói đã đăng ký/đang dùng**. Giao diện phải phân biệt được gói tham khảo, gói đã chọn trong ứng dụng và trạng thái đăng ký thực tế tại nhà cung cấp.

## 4. Ghi nhận driver cho từng giao dịch QR

Với mọi giao dịch thanh toán QR, lưu driver đã tạo phương thức thanh toán đó vào bản ghi giao dịch thanh toán (hoặc quan hệ tương đương hiện có). Tối thiểu cần truy được:

- Driver/provider code và tên hiển thị hoặc snapshot cần thiết để lịch sử vẫn dễ hiểu nếu tên/cấu hình driver đổi về sau.
- Mã giao dịch nội bộ, đơn hàng liên quan, loại/chiều giao dịch, số tiền, tiền tệ và trạng thái.
- Mã giao dịch ngoài từ provider nếu có.
- Thời điểm tạo yêu cầu QR, thời điểm thanh toán/cập nhật trạng thái.
- Tham chiếu cấu hình/tài khoản merchant đã dùng ở mức an toàn; không lưu hoặc hiển thị secret không cần thiết.

Không suy ra driver hiện tại từ cấu hình mặc định khi xem giao dịch cũ. Driver của giao dịch phải phản ánh chính xác driver được dùng lúc tạo giao dịch.

## 5. Lưu lịch sử callback theo từng giao dịch

Mỗi lần endpoint nhận callback/webhook liên quan tới thanh toán QR, lưu một bản ghi lịch sử riêng trước hoặc trong khi xử lý để có thể điều tra cả callback thành công lẫn lỗi. Hỗ trợ một giao dịch có nhiều callback/attempt; không ghi đè callback trước bằng callback mới.

Lịch sử callback nên lưu tối thiểu các trường phù hợp với kiến trúc hiện tại:

- Giao dịch thanh toán nội bộ và driver/provider liên quan.
- Provider event ID hoặc transaction ID nếu có.
- Thời điểm nhận callback.
- HTTP method/path và thông tin nguồn cần thiết cho điều tra; không lưu credential nhạy cảm trong headers.
- Payload gốc đã lọc dữ liệu nhạy cảm hoặc bản lưu an toàn có kiểm soát.
- Kết quả xác minh chữ ký/xác thực.
- Kết quả xử lý: đã xử lý, trùng lặp, từ chối, lỗi, chưa khớp giao dịch hoặc trạng thái tương đương.
- HTTP status/response được trả cho provider, thời lượng xử lý nếu có.
- Lý do lỗi/không khớp và tham chiếu tới giao dịch/đơn hàng sau khi đối chiếu.
- Idempotency/deduplication key nếu có.

Yêu cầu xử lý:

- Ghi nhận từng lần callback được gửi lại; không cộng usage hoặc xử lý nghiệp vụ thanh toán lặp.
- Dùng mã giao dịch/provider event và cơ chế idempotency để tránh ghi nhận tiền hai lần.
- Callback không xác thực, sai định dạng, không khớp giao dịch hoặc sai số tiền vẫn cần có lịch sử kiểm tra, nhưng tuyệt đối không được làm đơn chuyển sang đã thanh toán.
- Lưu payload tối thiểu cần thiết, có giới hạn truy cập và chính sách retention phù hợp. Che token, API key, chữ ký bí mật, thông tin tài khoản nhạy cảm và dữ liệu không cần thiết.
- Với driver Hệ thống không gửi callback, trang chi tiết hiển thị rõ “Driver này không hỗ trợ callback tự động” và lịch sử callback rỗng; không tạo bản ghi callback giả. Nếu có thao tác xác nhận thủ công, ghi vào audit log riêng và phân biệt với callback từ provider.

## 6. Trang tra cứu cho Admin và Superadmin

Cho phép cả **Admin** và **Superadmin** kiểm tra giao dịch QR và callback history, theo quy tắc phân quyền/phạm vi dữ liệu đang có trong dự án:

- Admin chỉ xem giao dịch thuộc cửa hàng/tenant/phạm vi được cấp quyền.
- Superadmin được xem theo phạm vi hệ thống mà vai trò hiện tại cho phép.
- Không nới quyền vượt quy tắc hiện hữu; kiểm tra quyền cả ở backend/API, không chỉ ẩn nút trên giao diện.

Tại chi tiết giao dịch QR, hiển thị:

- Driver đã dùng và thông tin giao dịch liên quan.
- Trạng thái thanh toán, số tiền, thời gian và mã tham chiếu.
- Timeline callback gồm một hoặc nhiều lần nhận, thời điểm, kết quả xác thực, kết quả xử lý, lỗi và phản hồi HTTP.
- Payload callback ở chế độ có kiểm soát, có che dữ liệu nhạy cảm; chỉ vai trò đủ quyền mới được xem chi tiết phù hợp.
- Nếu không có callback, giải thích lý do theo driver, không biểu thị là lỗi mặc định.

Cung cấp tìm kiếm/lọc theo đơn hàng, mã giao dịch nội bộ, mã giao dịch provider, driver, trạng thái callback và khoảng thời gian nếu phù hợp UI hiện có.

## 7. Theo dõi usage và cảnh báo gói

- Tính usage theo đúng đơn vị và chu kỳ của gói/provider.
- Callback lặp không làm tăng usage giao dịch.
- Hiển thị đã dùng, còn lại/vượt hạn mức, chu kỳ và thời điểm đồng bộ gần nhất khi có quota.
- Có thể cấu hình cảnh báo khi gần đạt/vượt hạn mức.
- Không tự ngắt thanh toán, webhook hoặc bỏ callback vì vượt quota; vẫn lưu giao dịch và lịch sử để đối soát. Chính sách dừng dịch vụ chỉ thực hiện khi có căn cứ nhà cung cấp và yêu cầu nghiệp vụ.
- Driver Hệ thống không hiển thị quota nhà cung cấp.

## 8. Bảo mật và audit

- Xác minh webhook SePay bằng phương thức chính thức được hỗ trợ.
- Không đánh dấu thành công từ redirect hoặc dữ liệu frontend.
- Không để lộ token/secret trong response, UI, exception hoặc log.
- Che/mã hóa thông tin nhạy cảm theo chuẩn hiện có.
- Ghi audit log cho thay đổi driver, cấu hình, gói, trạng thái đăng ký và thao tác xác nhận thủ công.
- Kiểm soát quyền xem payload callback và thông tin giao dịch theo vai trò.

## 9. Giao diện quản trị

Tối thiểu gồm:

### Danh sách driver

Hiển thị driver, mô tả, trạng thái, phương thức xác nhận thanh toán, gói hiện tại và hành động xem chi tiết/cấu hình.

### Chi tiết driver

Hiển thị cách thanh toán, hỗ trợ callback hay không, trạng thái kết nối, danh sách gói tương ứng, giá/chu kỳ/quota đã xác minh, nguồn và ngày kiểm tra thông tin.

### Gói và usage

Hiển thị gói đang sử dụng thực tế, trạng thái đăng ký, chu kỳ, usage và cảnh báo. Driver Hệ thống phải ghi rõ “Miễn phí — chỉ tạo/hiển thị QR; không tự xác nhận giao dịch”.

### Chi tiết giao dịch QR

Hiển thị driver snapshot của giao dịch và lịch sử callback đầy đủ theo quyền truy cập. Tách callback history khỏi audit log và thao tác xác nhận thủ công.

Các trạng thái cần có: chưa cấu hình, đã cấu hình, chưa đăng ký, đang hoạt động, lỗi kết nối, không giới hạn, không áp dụng, chưa xác định, callback thành công, callback trùng, callback bị từ chối và callback lỗi.

## 10. Kiểm thử và tiêu chí nghiệm thu

- Có hai driver Hệ thống và SePay; SePay có catalog bắt đầu với gói Free, Hệ thống có gói Miễn phí.
- Driver Hệ thống tạo/hiển thị QR nhưng không tự xác nhận thanh toán.
- Logic Hệ thống được chuyển khỏi phần code viết trực tiếp trong server sang kiến trúc driver mà không làm mất luồng hiện hữu ngoài yêu cầu.
- Mỗi giao dịch QR lưu đúng driver được sử dụng lúc tạo; thay đổi driver mặc định sau đó không làm đổi lịch sử giao dịch cũ.
- Mỗi callback/attempt được lưu thành bản ghi riêng và liên kết đúng giao dịch/driver.
- Hỗ trợ callback lặp hoặc nhiều callback mà không ghi nhận thanh toán/usage hai lần.
- Callback sai chữ ký, sai số tiền, sai định dạng hoặc không khớp giao dịch không thể đánh dấu đơn đã thanh toán; vẫn tra cứu được lịch sử nhận callback phù hợp.
- Admin bị giới hạn theo phạm vi dữ liệu hiện tại; Superadmin xem được theo quyền hệ thống hiện có.
- Secret và dữ liệu nhạy cảm không bị lộ trong UI/API/log.
- Migration, phân quyền, validation, lọc/tìm kiếm và các luồng thanh toán liên quan hoạt động đúng.

Chạy kiểm tra có ý nghĩa cho các rủi ro trên; không tạo test chỉ lặp lại implementation.

## 11. Quy trình thực hiện và báo cáo

1. Khảo sát code, schema, luồng QR, webhook, phân quyền Admin/Superadmin và các trang quản trị liên quan.
2. Báo cáo ngắn cấu trúc hiện tại, phần có thể tái sử dụng và các module dự kiến thay đổi.
3. Xác minh tài liệu/bảng giá SePay chính thức; ghi nguồn và thông tin còn chưa xác minh.
4. Triển khai abstraction và hai driver; di chuyển driver Hệ thống khỏi code viết trực tiếp trong server.
5. Bổ sung lưu driver snapshot trên giao dịch QR và bảng/lịch sử callback theo từng lần nhận.
6. Bổ sung giao diện quản lý gói, usage, chi tiết giao dịch và callback history cho Admin/Superadmin theo quyền.
7. Chạy kiểm tra phù hợp, sửa lỗi phát hiện được.
8. Báo cáo file/module thay đổi, migration cần chạy, cấu hình cần thiết, cách kiểm tra callback history và kết quả xác minh.

Không ghi đè các tác vụ đang chạy hoặc thay đổi nghiệp vụ ngoài phạm vi cần thiết. Nếu thiếu thông tin để triển khai an toàn, nêu rõ giả định trong báo cáo và ưu tiên bảo toàn dữ liệu giao dịch.

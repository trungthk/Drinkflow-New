Hãy kiểm tra cấu trúc dự án hiện tại, sau đó phân tích và triển khai chức năng crawl menu cửa hàng Highlands Coffee bằng PHP/Laravel.

## 1. Mục tiêu

Xây dựng chức năng để người dùng nhập URL thực đơn của một thương hiệu. Hệ thống chỉ cho phép crawl các thương hiệu đã được cấu hình sẵn và nhận diện thương hiệu bằng hostname của URL.

Trong phạm vi task này, triển khai crawler cho Highlands Coffee:

- URL thực đơn bắt đầu:
  https://www.highlandscoffee.com.vn/vn/san-pham.html
- Domain người dùng cung cấp:
  highlandscoffee.com
- Trang thực đơn thực tế đang dùng hostname:
  www.highlandscoffee.com.vn

Trước khi triển khai, kiểm tra cấu hình và cách nhận diện thương hiệu hiện có. Tạo cơ chế allowlist domain có thể mở rộng cho các thương hiệu khác. Xác minh domain bằng cách parse hostname và so khớp chính xác với domain được cho phép hoặc subdomain hợp lệ của domain đó. Không dùng phép kiểm tra contains/substring, nhằm tránh chấp nhận các hostname giả mạo như `highlandscoffee.com.fake-site.test`.

Cấu hình domain rõ ràng, ví dụ trong config hoặc bảng dữ liệu thương hiệu. Đưa cả `highlandscoffee.com` và `highlandscoffee.com.vn` vào allowlist chỉ sau khi xác minh đây là các domain được phép dùng. Không tự động cho phép mọi domain có chữ “highlandscoffee”.

## 2. Luồng crawl

1. Nhận URL đầu vào.
2. Kiểm tra URL hợp lệ, chỉ cho phép giao thức HTTP/HTTPS và hostname nằm trong allowlist của thương hiệu.
3. Nhận diện thương hiệu Highlands Coffee.
4. Tải trang thực đơn `/vn/san-pham.html`.
5. Tìm các liên kết danh mục sản phẩm trong phần menu chính, bao gồm danh mục cấp cao và danh mục con.
6. Chuẩn hóa và loại bỏ các URL trùng lặp.
7. Truy cập từng trang danh mục đã tìm thấy.
8. Chỉ lấy danh sách món trong vùng nội dung chính của danh mục; không lấy menu điều hướng, footer hoặc khu vực “Sản phẩm khác” nếu khu vực đó chỉ là liên kết gợi ý giữa các danh mục.
9. Nếu danh mục không có món hợp lệ thì bỏ qua danh mục đó và ghi nhận trạng thái phù hợp trong log/kết quả crawl.
10. Với mỗi món trong danh sách:
    - Lấy tên món, URL chi tiết, ảnh, mô tả và giá nếu danh mục đã cung cấp.
    - Nếu danh sách danh mục chưa có giá hoặc thiếu dữ liệu chi tiết cần thiết, truy cập trang chi tiết món để lấy dữ liệu.
    - Chỉ đọc thông tin của món chính trong phần nội dung chi tiết; không lấy giá hoặc thông tin từ danh sách món liên quan trên trang.
    - Nếu trang chi tiết có size hoặc các lựa chọn khác, lưu chúng theo cấu trúc dữ liệu hiện có của dự án. Không tự suy diễn giá riêng cho từng size nếu trang nguồn không cung cấp giá tương ứng.
11. Chuẩn hóa giá về số nguyên VND, bỏ dấu phân cách hàng nghìn và ký hiệu tiền tệ. Ví dụ: `45,000 VNĐ` thành `45000`.
12. Loại bỏ món trùng trước khi lưu.

## 3. Quy tắc nhận diện dữ liệu trùng

Ưu tiên xác định trùng theo thứ tự:

1. URL sản phẩm sau khi chuẩn hóa, bỏ query string/trailing slash không cần thiết.
2. ID sản phẩm của nguồn, nếu tìm thấy.
3. Nếu nguồn không có ID ổn định, so sánh tên món đã chuẩn hóa cùng thương hiệu và các thuộc tính định danh phù hợp.

Không chỉ dựa vào tên món để gộp các bản ghi nếu chúng có URL nguồn hoặc thuộc tính khác nhau. Không tạo nhiều bản ghi khi cùng một URL món xuất hiện trong nhiều danh mục hoặc khu vực gợi ý. Cần thống nhất hành vi khi trùng: bỏ qua món đã thu thập, không ghi đè dữ liệu hiện có nếu task không được yêu cầu cập nhật dữ liệu.

Nếu cùng một món được tìm thấy ở nhiều danh mục, lưu quan hệ danh mục theo khả năng schema hiện có; nếu schema chỉ hỗ trợ một danh mục, chọn danh mục xuất hiện đầu tiên theo thứ tự crawl và ghi rõ quyết định đó.

## 4. Yêu cầu kỹ thuật Laravel

- Tận dụng kiến trúc, model, bảng, luồng nhập menu và quy ước code đang có. Không tạo schema hoặc API mới nếu dự án đã có cấu trúc tương đương.
- Tách phần xử lý Highlands Coffee thành adapter/service riêng để sau này có thể bổ sung crawler cho các thương hiệu khác.
- Dùng HTTP client hiện có của Laravel hoặc thư viện đã cài trong dự án.
- Dùng Symfony DomCrawler/CssSelector nếu đã có; nếu chưa, đánh giá dependency phù hợp trước khi thêm.
- Chỉ dùng trình duyệt headless như Playwright khi xác minh rằng dữ liệu cần thiết được render bằng JavaScript và không thể lấy hợp lý bằng HTML/HTTP client.
- Có timeout, giới hạn số lần thử lại, xử lý HTTP status lỗi, redirect, HTML rỗng và lỗi parse.
- Giới hạn tốc độ request, thêm khoảng nghỉ phù hợp giữa các trang; không tạo tải lớn lên website nguồn.
- Không dùng proxy, không né CAPTCHA, xác thực hoặc các biện pháp chặn truy cập.
- Không gọi URL ngoài domain allowlist trong quá trình crawl. Kiểm tra hostname của mọi URL liên kết trước khi request.
- Bảo đảm crawl có thể dừng an toàn, ghi log lỗi theo URL và tiếp tục với danh mục/món khác khi một request lỗi.
- Tránh xử lý đồng thời quá nhiều request. Nếu dự án có queue, dùng queue theo cơ chế sẵn có.
- Có thể dùng cache kết quả HTTP trong thời gian ngắn để tránh tải lại cùng một URL trong một lượt crawl.

## 5. Kết quả và lưu dữ liệu

Trả dữ liệu theo schema menu hiện có của dự án. Nếu chưa có schema phù hợp, trước tiên hãy đề xuất mapping và chỉ tạo migration/model mới khi thật sự cần.

Tối thiểu dữ liệu mỗi món cần có:

- Thương hiệu
- Danh mục
- Tên món
- Mô tả
- Giá VND
- URL nguồn
- URL ảnh
- Các lựa chọn/size nếu có
- Thời điểm crawl

Nếu giá không tìm thấy sau khi kiểm tra trang danh mục và trang chi tiết, không tự gán giá bằng 0. Bỏ qua việc tạo/cập nhật món theo quy tắc hiện có hoặc đánh dấu thiếu giá để người dùng xem xét; ghi rõ hành vi được chọn trong code và kết quả crawl.

Kết quả chạy cần tóm tắt:

- Số danh mục tìm thấy
- Số danh mục có món
- Số danh mục bỏ qua vì rỗng
- Số món phát hiện
- Số món lưu mới
- Số món bị bỏ qua vì trùng
- Số món lỗi hoặc không lấy được giá
- Các URL lỗi kèm nguyên nhân

## 6. Giao diện và tích hợp

Tích hợp vào luồng quản lý/import menu hiện có. Người dùng nhập URL và nhận thông báo tiến trình/kết quả theo cách phù hợp với giao diện dự án.

Không làm thay đổi chức năng crawl/import của các thương hiệu khác. Các domain không nằm trong allowlist phải bị từ chối trước khi gửi request đến website.

## 7. Kiểm tra

Tạo hoặc cập nhật kiểm thử có ý nghĩa cho các trường hợp:

- URL Highlands hợp lệ được nhận diện đúng.
- Hostname giả mạo có chứa chuỗi `highlandscoffee.com` bị từ chối.
- URL thuộc domain không được cấu hình bị từ chối.
- Danh mục rỗng được bỏ qua.
- Danh mục có món được parse đúng.
- Giá được chuẩn hóa đúng sang số nguyên VND.
- Trang chi tiết chỉ lấy dữ liệu của món chính, không lấy món trong khu vực liên quan.
- URL sản phẩm xuất hiện lặp lại chỉ được xử lý một lần.
- Request lỗi ở một món không làm dừng toàn bộ lượt crawl.

Dùng fixture HTML hoặc mock HTTP để kiểm thử, không phụ thuộc vào website thật khi chạy test.

## 8. Cách làm việc

Trước khi sửa code:

1. Khảo sát cấu trúc dự án và luồng crawl/import menu hiện tại.
2. Nêu các file dự kiến chỉnh sửa và cách mapping dữ liệu vào schema hiện có.
3. Sau đó triển khai theo đúng kiến trúc của dự án.

Khi hoàn tất, báo cáo ngắn gọn:

- Các file đã thay đổi
- Luồng hoạt động
- Cách chạy/chọn URL
- Kết quả kiểm thử
- Các giới hạn còn lại, đặc biệt khi cấu trúc HTML phía Highlands thay đổi
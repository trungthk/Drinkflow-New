---
name: review-pr

description: Skill review PR dành cho DrinkFlow. So sánh thay đổi với master và review dưới các góc độ bug, bảo mật, hiệu năng và tuân thủ quy ước; phân loại theo 6 mức severity và xuất kết quả theo định dạng để dán vào spreadsheet hoặc Chatwork.
---

# review-pr

## Quy trình thực hiện

1. **Kiểm tra cập nhật mới nhất** (điều kiện tiên quyết trước khi review)

   1. Chạy `git fetch` để lấy thông tin mới nhất từ remote. Khi so sánh với target branch, từ bước này trở đi luôn sử dụng trực tiếp `origin/master` thay vì `master` local. Điều này nhằm tránh bị ảnh hưởng trong trường hợp branch `master` không tồn tại ở local hoặc hiện không được checkout.

   2. Kiểm tra source branch (branch hiện tại) có đang chậm hơn remote hay không.

      ```
      git rev-list --count HEAD..origin/$(git rev-parse --abbrev-ref HEAD)
      ```

      Nếu kết quả khác `0`, source branch chưa được cập nhật mới nhất.

   3. Nếu branch chưa được cập nhật, **dừng review** và không thực hiện các bước tiếp theo như lấy diff hoặc review. Thông báo rõ tên branch đang bị chậm và yêu cầu người dùng cập nhật branch bằng `git pull` hoặc thao tác tương đương.

   4. Nếu command trên bị lỗi, ví dụ đang ở trạng thái detached HEAD hoặc source branch chưa được push lên remote nên `origin/<branch-name>` không tồn tại, hãy thông báo rằng không thể tự động xác định trạng thái cập nhật và hỏi người dùng có muốn tiếp tục hay không.

2. **Xác nhận định dạng output** (sau khi vượt qua kiểm tra cập nhật)

   - Sử dụng tool `AskUserQuestion` để hỏi người dùng muốn kết quả review ở định dạng nào: "Định dạng dán vào spreadsheet" hoặc "Định dạng dán vào Chatwork".
   - Sử dụng định dạng được chọn ở bước 7.

3. Lấy diff giữa branch hiện tại và `origin/master`.

   ```
   git diff origin/master...HEAD
   ```

   Đồng thời dùng `git status` để kiểm tra danh sách file thay đổi.

4. Tập trung vào diff (các dòng thay đổi) và review theo phần **Góc độ review** bên dưới.

5. Loại khỏi kết quả các nội dung thuộc phần **Những nội dung không cần chỉ ra (ngoài phạm vi)**.

6. Phân loại các vấn đề theo định nghĩa trong phần **Severity**.

7. Xuất kết quả theo định dạng đã chọn ở bước 2. Không đăng comment trực tiếp lên Pull Request.

   - Nếu là định dạng Chatwork: hiển thị trong code block để có thể copy trực tiếp.
   - Nếu là định dạng spreadsheet: tạo file `.csv`, sau đó thông báo đường dẫn lưu file và hướng dẫn import vào Google Sheets.

Các thao tác Git phải tuân theo chính sách trong `CLAUDE.md`. Chỉ sử dụng các command chỉ đọc như `git status`, `git diff`, `git log`, `git fetch`, cùng với `git rev-list`, `git rev-parse` được dùng cho bước kiểm tra cập nhật của Skill này và các command khác không làm thay đổi trạng thái repository.

Các thao tác làm thay đổi trạng thái local như `git pull` chỉ được yêu cầu người dùng thực hiện; Skill không tự chạy các thao tác đó.

## Ràng buộc khi thực hiện review

- **Về cơ bản chỉ review nội dung trong diff (`git diff`).** Tránh đọc rộng các file nằm ngoài diff. Chỉ tham chiếu tối thiểu những phần ngoài diff khi thực sự cần để hiểu ngữ cảnh.

- **Không thực hiện các kiểm tra yêu cầu giao tiếp với hệ thống bên ngoài.** Không gọi external API, không truy vấn vulnerability database, không tải package information hoặc thực hiện các kiểm tra cần network access.

- Nếu do các giới hạn trên mà không thể review đầy đủ, ví dụ diff không đủ để xác định phạm vi ảnh hưởng hoặc không thể kết luận mức độ an toàn nếu thiếu thông tin vulnerability bên ngoài, xử lý theo một trong hai cách:

  - Nếu cần xác định trước khi bắt đầu review, hỏi người dùng.
  - Nếu khó kết luận đối với một issue cụ thể, ghi rõ điều đó trong kết quả review với severity `Q` (Câu hỏi) hoặc `FYI` (Tham khảo).

## Góc độ review

- **Bug / tính chính xác**: lỗi logic, thiếu xử lý null/exception, sai boundary value hoặc các vấn đề làm chức năng hoạt động sai.
- **Bảo mật**: SQL injection, XSS, thiếu authorization, lộ thông tin nhạy cảm và các vấn đề tương tự.
- **Hiệu năng**: query dư thừa, N+1 query, vòng lặp kém hiệu quả và các vấn đề tương tự.
- **Tuân thủ quy ước / khả năng đọc hiểu**: tuân theo pattern hiện có, code trùng lặp.
- **Kiểm chứng đối nghịch**: chủ động thử phản chứng với giả định "có thể implementation này đang sai" để kiểm tra chất lượng.

## Những nội dung không cần chỉ ra (ngoài phạm vi)

- Phản đối thiết kế của code hiện có nếu phần đó không nằm trong diff của PR.
  - Tuân theo chính sách trong `CLAUDE.md`: tôn trọng specification, design và implementation hiện có, không thực hiện thay đổi quá mức cần thiết.

- Không review diff của lock file như `composer.lock`, `package-lock.json`, `yarn.lock`, v.v.
  - Đây là file được tool tự động sinh ra nên các góc độ review như khả năng đọc hiểu hoặc logic không phù hợp.

## Severity (6 mức)

Sử dụng 6 label theo phong cách Conventional Comments. Thứ tự hiển thị từ quan trọng đến nhẹ như sau.

| Thứ tự | Label | Ý nghĩa | Mục đích / tiêu chí sử dụng |
| --- | --- | --- | --- |
| 1 | Q (Câu hỏi) | Question | Những nội dung cần câu trả lời, ví dụ implementation có mục đích không rõ ràng. Yêu cầu đối phương phản hồi. |
| 2 | MUST (Bắt buộc) | Must | Những vấn đề không thể approve nếu chưa sửa. Đánh giá dựa trên cả **mức độ ảnh hưởng production** và **có cần block merge hay không**, bao gồm cả trường hợp ảnh hưởng production nhỏ nhưng không được phép merge do vi phạm security policy. |
| 3 | IMO (Đề xuất) | In my opinion | Quan điểm cá nhân hoặc đề xuất nhỏ. Có thể cân nhắc tạo task hoặc sửa. Nếu chủ yếu thuộc phạm vi sở thích/ý kiến hơn là có hiệu quả cải thiện cụ thể, dùng mức này. |
| 4 | NR (Không gấp) | No rush | Chưa cần sửa ngay nhưng là đề xuất cải thiện cụ thể nên giải quyết trong tương lai. Có thể cân nhắc tạo task hoặc sửa. Nếu có thể kỳ vọng hiệu quả cải thiện cụ thể, dùng mức này; đây cũng là tiêu chí phân biệt khi phân vân giữa IMO và NR. |
| 5 | NITS (Tiểu tiết) | Nitpick | Những nhận xét rất nhỏ, mang tính soi chi tiết. Có thể bỏ qua. |
| 6 | FYI (Tham khảo) | For your information | Chia sẻ thông tin tham khảo hoặc lưu lại điểm cần biết. Không yêu cầu phản hồi hoặc action. Nếu cần phản hồi, dùng Q. |

## Định dạng output

Xuất kết quả theo một trong hai định dạng được người dùng chọn ở bước 2. Không đăng kết quả lên Pull Request.

Quy tắc chung:

- Liệt kê severity theo thứ tự `Q → MUST → IMO → NR → NITS → FYI`.
- Có thể bỏ qua severity không có issue.
- Nếu không có issue nào, thông báo rõ điều đó.
- Nếu muốn lưu lại thông tin bổ sung khó phân loại, chẳng hạn một điểm đã kiểm tra và xác định không có vấn đề, ghi dưới dạng `FYI`.

Định dạng Chatwork phải được đặt trong code block để có thể copy và paste trực tiếp.

Định dạng spreadsheet phải được xuất thành file theo quy định bên dưới.

### (A) Định dạng spreadsheet

Không xuất dưới dạng text để copy/paste trực tiếp vào chat. **Phải tạo file `.csv` thực tế để người dùng import bằng "File > Import" của Google Sheets.**

Lý do: khi paste plain text bằng Ctrl+V, Google Sheets không xử lý quoting bằng dấu double quote như một CSV parser chuẩn. Các newline hoặc tab thực tế có thể khiến nội dung bị chia thành nhiều hàng/cột. Khi import file, CSV parser chuẩn sẽ xử lý chính xác newline và dấu phẩy bên trong cell đã được quote.

- Định dạng: CSV tiêu chuẩn, phân tách bằng dấu phẩy, quote bằng double quote, tuân theo RFC 4180.
- Không có header.
- Có 3 cột:
  - `file path`
  - `Severity`
  - `Nội dung nhận xét`

- Cell `Nội dung nhận xét` phải được bao quanh bằng double quote và có cấu trúc nhiều dòng:

  1. Dòng 1: `[Danh mục review] Tóm tắt`
  2. Dòng 2: Vị trí liên quan như số dòng, tên method, v.v.
  3. Dòng 3 trở đi: Giải thích chi tiết issue.

- Nếu cell chứa dấu phẩy hoặc double quote, escape double quote thành `""` theo chuẩn CSV.

- Sắp xếp các dòng theo thứ tự severity. Không xuất dòng cho severity không có issue.

- Không xuất summary tổng số issue ở cuối. CSV chỉ chứa các data row.

- Lưu vào thư mục `.claude/tmp/review-pr/` ở root repository. Thư mục này đã nằm trong `.gitignore`; nếu chưa tồn tại thì tạo mới.

- Tên file có dạng:

  ```
  review-pr_<source-branch-name>.csv
  ```

- Nếu source branch chứa `/`, ví dụ `feature/foo-bar`, thay `/` bằng `_` để tránh bị hiểu thành directory hierarchy.

  Ví dụ:

  ```
  review-pr_feature_foo-bar.csv
  ```

- Sau khi xuất file, thông báo đường dẫn file trong chat và hướng dẫn:

  > Mở một sheet mới trong Google Sheets, chọn File > Import > Upload và import file CSV ở đường dẫn đã thông báo.

Ví dụ nội dung CSV:

```csv
app/Http/Controllers/FooController.php,MUST,"[Bug / tính chính xác] Thiếu kiểm tra null
FooController.php:42 (method store)
Request parameter có thể là null và gây exception, vì vậy cần thêm validation trước khi xử lý."
```

### (B) Định dạng Chatwork

Sử dụng cú pháp Chatwork như `[info][title]～[/title]～[/info]` và `[hr]`.

Không sử dụng cú pháp Markdown như chữ đậm vì Chatwork không hỗ trợ.

- Phần đầu sử dụng:

  ```
  [info][title]<source-branch-name>レビュー結果[/title]
  ```

  Trong đó liệt kê số lượng từng severity theo thứ tự hiển thị, mỗi severity một dòng:

  ```
  <label>：x件
  ```

  Không thêm chữ như `サマリ：`.

- `<source-branch-name>` phải là branch hiện tại đã xác nhận ở bước 3.

- Nội dung trong `[info]` đầu tiên chỉ chứa thống kê số lượng.

- Với mỗi severity có issue, tạo một block:

  ```
  [info][title]<label>（<tên tiếng Nhật>）[/title]
  ```

- Không tạo block cho severity không có issue.

- Mỗi issue có cấu trúc:

  1. Dòng 1: `・[Danh mục review] Tóm tắt`
  2. Dòng 2: indent bằng một khoảng trắng full-width `　`, sau đó ghi vị trí liên quan như `file path:số dòng`, method, v.v.
  3. Dòng 3 trở đi: indent bằng một khoảng trắng full-width `　`, sau đó giải thích chi tiết issue.

- Nếu có nhiều issue trong cùng một `[info]` block, chèn `[hr]` giữa các issue.

Ví dụ:

```
[info][title]feature/foo-barレビュー結果[/title]
Q：0件
MUST：2件
IMO：2件
NR：0件
NITS：1件
FYI：0件[/info]

[info][title]MUST（必須）[/title]
・[バグ・正確性] nullチェックが漏れている
　app/Http/Controllers/FooController.php:42 (storeメソッド)
　リクエストパラメータがnullの場合に例外が発生する可能性があるため、事前にバリデーションを追加する必要がある。
[hr]
・[セキュリティ] 認可チェックが漏れている
　app/Http/Controllers/BarController.php:10 (update メソッド)
　他ユーザーのリソースを更新できてしまう可能性がある。[/info]

[info][title]IMO（提案）[/title]
・[規約準拠・可読性] バリデーションルールをFormRequestに切り出す余地がある
　app/Http/Controllers/FooController.php:45-52
　コントローラ内に直接バリデーションルールを記述しているが、既存の他コントローラではFormRequestクラスに切り出すパターンが使われている。
[hr]
・[パフォーマンス] N+1クエリが発生している
　app/Http/Controllers/DiaryController.php:30 (index メソッド)
　ループ内でリレーションを都度取得しており、eager loadingを使う方が望ましい。[/info]

[info][title]NITS（あら探し）[/title]
・[規約準拠・可読性] 未使用のimportが残っている
　resources/js/Pages/Diary/Create.tsx:3
　使用されていないuseEffectのimportが残っている。[/info]
```

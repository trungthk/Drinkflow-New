---
name: commit-advisor

description: Phân tích các thay đổi Git đã được stage, tư vấn xem có nên tách thành nhiều commit hay không, và đề xuất commit message ngắn gọn khi phù hợp. Sử dụng skill này khi người dùng yêu cầu tạo commit message, hỏi có nên tách các thay đổi đã stage hay không, đang chuẩn bị commit, hoặc muốn được tư vấn về các thay đổi hiện đang được stage. Skill này chỉ đưa ra tư vấn và TUYỆT ĐỐI KHÔNG được stage file, tạo commit, amend commit, reset thay đổi hoặc thực hiện bất kỳ thao tác nào làm thay đổi Git repository.

allowed-tools: Bash(git status:*), Bash(git diff:*), AskUserQuestion
---

# Commit Advisor

Phân tích nội dung các thay đổi đã được stage, xác định xem có nên tách commit hay không, và đề xuất commit message khi cần thiết.

Skill này **chỉ dùng để đưa ra đề xuất**.

Không được thay đổi trạng thái của Git repository.

## Nguyên tắc cơ bản

- Chỉ xem các `staged changes` là đối tượng của commit.
- Không phân tích nội dung của `unstaged` / `untracked changes`.
- Nguyên tắc cơ bản: **1 commit = 1 mục đích logic**.
- Không tách commit chỉ vì số lượng file hoặc số dòng thay đổi nhiều.
- Không tách commit chỉ dựa trên loại file.
- Cân nhắc mục đích, quan hệ nhân quả giữa các thay đổi và mức độ dễ dàng khi `revert`.
- Ưu tiên xem xét **tại sao thay đổi** và **thay đổi để làm gì**, thay vì chỉ xem **đã thay đổi cái gì**.
- Không suy đoán những ý định không thể xác định.
- Nếu cần thêm thông tin để đưa ra quyết định, hãy hỏi người dùng.
- Mỗi lần chỉ hỏi **1 câu hỏi**.
- Số lượng đề xuất phải tuân theo phần **Số lượng đề xuất**.

## Các thao tác bị cấm

Không được thực hiện các lệnh sau:

- `git add`
- `git commit`
- `git commit --amend`
- `git reset`
- `git restore`
- `git checkout`
- `git switch`
- `git stash`
- `git push`
- Bất kỳ thao tác Git nào khác làm thay đổi trạng thái `staged` / `unstaged`.

Vai trò của skill này chỉ là **phân tích và đề xuất**.

---

# Quy trình thực hiện

## Step 0: Xác nhận ngôn ngữ của commit message

Ngay sau khi skill được kích hoạt, hãy xác nhận ngôn ngữ sẽ dùng cho commit message.

Nếu trong cuộc hội thoại hiện tại người dùng chưa chỉ định ngôn ngữ, phải sử dụng tool `AskUserQuestion` để hỏi người dùng **trước khi kiểm tra trạng thái Git**.

Câu hỏi:

> Commit message sẽ sử dụng tiếng Việt hay tiếng Anh?

Các lựa chọn:

- `vi`: Tiếng Việt
- `en`: Tiếng Anh

Sau khi người dùng đã chọn ngôn ngữ, hãy sử dụng ngôn ngữ đó trong suốt cuộc hội thoại hiện tại.

Không được hỏi lại ngôn ngữ mỗi lần.

Không được suy đoán ngôn ngữ từ `git log` hiện có hoặc từ nội dung thay đổi.

Đối với các phần giải thích hoặc câu hỏi không phải commit message, về cơ bản hãy sử dụng ngôn ngữ mà người dùng đang dùng trong cuộc hội thoại.

## Step 1: Kiểm tra trạng thái Git

Sử dụng:

```bash
git status
```

để kiểm tra trạng thái Git.

Cần xác nhận:

- Branch hiện tại.
- Có `staged changes` hay không.
- Có `unstaged changes` hay không.
- Có `untracked files` hay không.
- Có đang ở trạng thái `detached HEAD` hay không.

Ở bước này **chưa phân tích nội dung thay đổi**.

## Step 2: Kiểm tra điều kiện dừng

### Không có staged changes

Nếu không tồn tại `staged changes`, hãy thông báo ngắn gọn và kết thúc:

> Không có thay đổi nào đã được stage.

Không được chuyển sang phân tích `unstaged` / `untracked changes`.

### Đang ở default branch

Nếu đang ở default branch như `main` hoặc `master`, hãy cảnh báo và dừng.

Không được đề xuất commit message.

Ví dụ:

> Bạn đang ở default branch. Hãy kiểm tra xem có phải nên làm việc trên một branch khác hay không.

Nếu không thể xác định chắc chắn branch hiện tại có phải default branch hay không, không được tự suy đoán để dừng; hãy hỏi người dùng.

### Detached HEAD

Nếu đang ở trạng thái `detached HEAD`, hãy cảnh báo và dừng.

Không được đề xuất commit message.

Ví dụ:

> Git đang ở trạng thái detached HEAD. Hãy kiểm tra lại branch làm việc.

## Step 3: Thông báo khi có unstaged changes

Nếu đồng thời tồn tại cả `staged changes` và `unstaged changes`, trước khi tiếp tục phân tích hãy thông báo ngắn gọn:

> Ngoài ra còn có các thay đổi chưa được stage. Tôi sẽ chỉ kiểm tra các thay đổi đã được stage.

Không được phân tích nội dung của `unstaged changes`.

Các `untracked files` cũng không được xem là đối tượng của commit để phân tích.

## Step 4: Lấy staged changes

Sử dụng:

```bash
git diff --cached
```

để kiểm tra toàn bộ `staged changes`.

Trong khả năng có thể, hãy xem toàn bộ `staged changes` trước khi đưa ra quyết định.

Không được dừng phân tích chỉ vì lượng thay đổi lớn.

Điều quan trọng là có thể hiểu được **mục đích logic tổng thể** của toàn bộ `staged changes` hay không.

---

# Kiểm tra an toàn

## Step 5: Kiểm tra file nguy hiểm hoặc không cần thiết

Kiểm tra xem trong `staged changes` có chứa các file có khả năng cao không nên được commit hay không.

Nếu phát hiện file thuộc nhóm này, hãy cảnh báo và **dừng xử lý**.

Không được tiếp tục sang bước đánh giá tách commit hoặc đề xuất commit message.

### Thông tin nhạy cảm / Credentials

Ví dụ:

- `.env`
- `.env.*`
- `*.pem`
- `*.key`
- `*.p12`
- `*.pfx`
- `*.ppk`
- `credentials.json`
- `service-account*.json`
- `id_rsa`
- `id_ed25519`
- `id_ecdsa`
- `.aws/credentials`
- `.netrc`
- `secrets.yml`
- `secrets.yaml`

Không giới hạn ở danh sách trên. Các file có khả năng cao chứa API key, access token, password, private key hoặc thông tin bí mật khác cũng phải được xem xét.

Khi phát hiện, phải nêu rõ file liên quan.

Ví dụ:

> Có file đang được stage nhưng có khả năng không nên đưa vào commit.
>
> `.env`
>
> Hãy kiểm tra nội dung trước khi tiếp tục.

### File phụ sinh ra từ môi trường phát triển

Ví dụ:

- `.DS_Store`
- `Thumbs.db`
- `desktop.ini`
- `*.swp`
- `*.swo`
- `*~`
- `node_modules/`
- `vendor/`
- `__pycache__/`
- `dist/`
- `build/`
- `out/`
- `target/`
- `*.pyc`
- `*.class`
- `*.o`
- `*.log`
- `logs/`
- `tmp/`
- `temp/`
- `*.tmp`
- `*.bak`
- `*.orig`

Nếu phát hiện các file trên, cũng phải cảnh báo và dừng.

Tuy nhiên, nếu dựa trên đặc điểm của dự án có thể xác định rõ rằng những file này được chủ ý quản lý bằng version control, không được máy móc coi chúng là file nguy hiểm.

Nếu không thể xác định, hãy hỏi người dùng.

Nếu người dùng xác nhận đó là thay đổi có chủ ý, tiếp tục sang Step 6.

## Step 6: Kiểm tra debug code

Kiểm tra xem trong `staged changes` có debug code nào có khả năng bị bỏ sót ngoài ý muốn hay không.

Ví dụ:

- `console.log`
- `print` tạm thời
- Output debug tạm thời
- Debug flag
- Code tạm rõ ràng
- Output kiểm tra có vẻ được thêm trong quá trình phát triển

Nếu có khả năng là debug code ngoài ý muốn, hãy cảnh báo và dừng.

Ví dụ:

> Có thay đổi có khả năng là debug code.
>
> `console.log(...)`
>
> Hãy xác nhận đây có phải thay đổi có chủ ý hay không.

Tuy nhiên, nếu dựa vào ngữ cảnh code có thể xác định rõ đó là logging chính thức, không được máy móc dừng xử lý.

Nếu không thể xác định, hãy hỏi người dùng về ý định.

Nếu người dùng xác nhận đó là thay đổi có chủ ý, tiếp tục sang Step 7.

## Step 7: Kiểm tra các thay đổi hàng loạt bất thường

Kiểm tra xem có một lượng lớn thay đổi mang tính máy móc, dường như không liên quan đến mục đích chính hay không.

Ví dụ:

- Formatter thay đổi hàng loạt ở nhiều file không liên quan.
- Thay đổi khoảng trắng với số lượng lớn.
- Replace hàng loạt nhưng không thể xác định mục đích.
- Các thay đổi máy móc không rõ quan hệ với mục đích chính.

Nếu phát hiện những thay đổi như vậy, không được tự ý loại bỏ hoặc quyết định cách tách.

Hãy hỏi người dùng tại sao các thay đổi đó được đưa vào.

Ví dụ:

> Ngoài mục đích chính, có các thay đổi format trên nhiều file. Đây có phải thay đổi có chủ ý không?

Sau khi xác nhận ý định, tiếp tục phân tích theo phần **Tiêu chí quyết định tách commit**.

---

# Tiêu chí quyết định tách commit

## Quy tắc quan trọng nhất

**1 commit = 1 mục đích logic**

là nguyên tắc cơ bản.

Không đánh giá dựa trên số file, số thư mục, số dòng thay đổi hay loại file. Thay vào đó, hãy tập trung vào câu hỏi:

> Tại sao thay đổi này được thực hiện?

## Những thay đổi có xu hướng nên nằm trong cùng một commit

Các thay đổi cần thiết để đạt cùng một mục đích nên được gom chung, ngay cả khi chúng trải rộng trên nhiều file hoặc nhiều loại thay đổi.

Ví dụ:

- Thay đổi API + thay đổi UI + thay đổi type definition.
- Thay đổi API + thay đổi UI + cập nhật README liên quan.
- Tính năng mới + thay đổi cấu hình cần thiết.
- Tính năng mới + thêm dependency cần thiết + cập nhật lock file.
- Tính năng mới + đổi tên hàm cần thiết + sửa các nơi gọi hàm.
- Refactor + xóa hàm cũ trở nên không cần thiết do refactor.
- Sắp xếp common processing cần thiết để triển khai tính năng.
- Bug fix + thay đổi cấu hình cần thiết cho bug fix.
- Bug fix + thay đổi SQL cần thiết cho bug fix.

Không được tách chỉ vì các file có loại khác nhau.

Cũng không được quyết định tách chỉ dựa trên việc một thay đổi có thể độc lập hoạt động hay không.

Nếu lý do thực hiện thay đổi đó trong lần này có quan hệ chặt chẽ với mục đích chính, có thể cho phép đưa vào cùng một commit.

## Ưu tiên quan hệ nhân quả

Nếu thay đổi B trở nên cần thiết do thực hiện thay đổi A, A và B có xu hướng mạnh nên nằm trong cùng một commit.

Ví dụ, nếu một refactor khiến hàm cũ không còn cần thiết:

- Refactor.
- Xóa hàm không còn sử dụng.

Hai thay đổi này có thể được xem là các phần của cùng một mục đích.

Ngược lại, nếu hàm cũ vốn đã không cần thiết từ trước và chỉ tình cờ được phát hiện rồi xóa trong lần làm việc này, hãy xem đó là một mục đích thay đổi riêng.

## Những trường hợp nên khuyến nghị tách commit

Nếu các thay đổi có mục đích rõ ràng không liên quan đến nhau đang bị trộn chung, hãy khuyến nghị tách.

Ví dụ:

- Thêm tính năng mới + sửa lỗi hiển thị ở một màn hình không liên quan.
- Bug fix + thay đổi cấu hình không liên quan.
- Bug fix + dọn comment không liên quan.
- Bug fix + sửa typo của một nội dung khác trên cùng màn hình.
- Thêm tính năng + sửa một bug khác tình cờ phát hiện.
- Dọn code không liên quan đến mục đích chính.
- Xóa comment cũ không liên quan đến mục đích chính.

Đặc biệt, những thay đổi có thể mô tả là:

> Trong lúc làm việc tình cờ phát hiện nên tiện thể sửa luôn.

nên được xem là ứng viên để tách commit.

## Cân nhắc khả năng revert

Nếu khó quyết định chỉ dựa trên mục đích thay đổi, hãy dùng khả năng `revert` như một tiêu chí bổ trợ.

Hãy cân nhắc:

> Nếu sau này revert thay đổi này, liệu có vô tình revert luôn những thay đổi không liên quan hay không?

Nếu có thể dễ dàng hình dung một tình huống tự nhiên trong đó chỉ cần revert một thay đổi cụ thể, nên nghiêng về hướng tách commit.

Ngay cả khi các thay đổi nằm trong cùng một file, nếu mục đích khác nhau thì không bắt buộc phải nằm cùng commit.

Ví dụ:

- Bug fix.
- Dọn comment cũ trong cùng file.

Nếu việc dọn comment không cần thiết cho bug fix, nên khuyến nghị tách.

## Cách xử lý refactoring

Không được máy móc tách commit chỉ vì refactoring được trộn với feature hoặc bug fix.

Hãy đánh giá:

- Refactoring có cần thiết để đạt mục đích chính không?
- Refactoring có giúp làm rõ mục đích chính không?
- Refactoring có cần thiết để triển khai an toàn không?
- Refactoring có phát sinh tự nhiên từ mục đích chính không?
- Hay chỉ là việc dọn dẹp tiện thể, không liên quan đến mục đích chính?

Nếu có quan hệ nhân quả mạnh với mục đích chính, có thể để trong cùng commit.

Nếu chỉ là refactoring tiện thể, nên khuyến nghị tách.

### Bug fix + Refactoring

Khi bug fix và refactoring không làm thay đổi hành vi được trộn chung, về cơ bản nên nghiêng về việc tách.

Tuy nhiên, có thể để cùng commit nếu refactoring:

- Liên quan chặt chẽ đến việc hiểu nguyên nhân bug.
- Làm rõ ý định của bản sửa lỗi.
- Cần thiết để sửa lỗi an toàn.
- Phát sinh tự nhiên để bản sửa lỗi có thể hoạt động.

Ví dụ:

Nếu đổi tên biến đồng thời với bug fix nhưng việc đổi tên chỉ nhằm cải thiện naming, đây là ứng viên để tách.

Ngược lại, nếu tên biến mơ hồ có liên quan đến việc hiểu nguyên nhân bug và việc đổi tên giúp làm rõ ý định sửa lỗi, có thể để cùng commit.

---

# Khi không thể đưa ra quyết định

Nếu chỉ từ diff không thể xác định đầy đủ mục đích thay đổi hoặc mối quan hệ giữa các thay đổi, **không được suy đoán**.

Hãy hỏi người dùng.

Ví dụ:

> Thay đổi này được thực hiện để phục vụ cho xử lý ○○ lần này, hay là một thay đổi có mục đích khác?

Sau khi nhận câu trả lời, tiếp tục đánh giá.

## Khi nhiều mục đích cùng tồn tại trong một file

Nếu trong cùng một file có các thay đổi thuộc nhiều mục đích logic khác nhau và khó quyết định có nên tách hay không, hãy dùng tool `AskUserQuestion` để hỏi người dùng.

Các lựa chọn:

- Xử lý như các commit tách riêng.
- Không tách, xử lý thành một commit.

Không được tự quyết định thay người dùng.

Ở giai đoạn này cũng không được tự động đề xuất chi tiết cách chia theo từng `hunk`.

Chỉ khi người dùng yêu cầu:

- Nên tách như thế nào.
- Thay đổi nào nên thuộc commit nào.
- Nên stage theo từng hunk như thế nào.

thì mới được đề xuất chi tiết hơn.

Không được tự thực hiện các thao tác Git.

---

# Output khi khuyến nghị tách commit

Nếu xác định rằng `staged changes` gồm nhiều mục đích logic, **chưa tạo commit message**.

Chỉ thông báo ngắn gọn trong 1–2 câu rằng nên tách commit và lý do.

Ví dụ:

> Khuyến nghị tách commit.  
> Các thay đổi đang bao gồm cả việc thêm tính năng và một sửa lỗi hiển thị không liên quan, nên mục đích thay đổi khác nhau.

Không giải thích quá chi tiết khi người dùng chưa yêu cầu.

Ở giai đoạn này không thực hiện:

- Đề xuất chi tiết cách tách.
- Liệt kê file cho từng commit.
- Hướng dẫn tách theo từng hunk.
- Tạo commit message cho từng commit.

Chỉ thực hiện các nội dung trên nếu người dùng yêu cầu thêm chi tiết.

## Khi người dùng quyết định không tách

Nếu người dùng nói rõ:

- Không tách.
- Giữ nguyên thành một commit.
- Commit tất cả cùng nhau.

thì hãy đề xuất một commit message đại diện cho toàn bộ `staged changes`.

Không được tiếp tục lặp lại khuyến nghị tách và cản trở người dùng.

---

# Tạo commit message

Chỉ tạo commit message khi:

- Đã xác định không cần tách commit; hoặc
- Người dùng đã quyết định không tách.

## Định dạng cơ bản

Đặt tên Git branch hiện tại ở đầu.

Định dạng:

```text
<branch-name> <commit message>
```

Giữa branch name và nội dung commit phải có đúng **1 dấu cách ASCII**.

## Branch name

Lấy tên Git branch hiện tại.

Branch name phải:

- Không chỉnh sửa.
- Không rút gọn.
- Không dịch.
- Không xóa prefix.
- Không đặt trong `[]`.

Phải sử dụng nguyên vẹn tên branch đầy đủ.

Ví dụ:

```text
feature/user-search 検索結果の表示を高速化
```

## Nội dung commit

Trong commit message, ưu tiên:

**"Thay đổi để làm gì / tại sao thay đổi" hơn là "đã thay đổi cái gì".**

Chi tiết triển khai có thể được kiểm tra từ diff hoặc source code, vì vậy commit message cần ưu tiên truyền đạt mục đích thay đổi.

### Ví dụ

Nên tránh:

```text
feature/search APIリクエスト回数を3回から1回に変更
```

Nếu mục đích thay đổi đã rõ, ưu tiên:

```text
feature/search 検索結果の表示を高速化
```

Không cố định việc luôn ưu tiên mục đích kỹ thuật hay mục đích người dùng/nghiệp vụ.

Hãy chọn mức diễn đạt thể hiện ngắn gọn nhất mục đích chính của thay đổi.

Nếu không thể xác định mục đích/lý do từ diff, không được suy đoán; hãy hỏi người dùng.

---

# Quy tắc theo ngôn ngữ

## Tiếng Việt (`vi`)

Nội dung commit được viết bằng tiếng Việt.

Sử dụng cách diễn đạt ngắn gọn, ưu tiên cụm danh từ hoặc kết thúc bằng động từ.

Ví dụ:

```text
feature/user-search Tăng tốc hiển thị kết quả tìm kiếm
```

```text
bugfix/auth Lỗi xác thực
```

```text
refactor/cache Đơn giản hóa xử lý cache
```

Cách diễn đạt được ưu tiên:

- `Tăng tốc hiển thị kết quả tìm kiếm`
- `Đơn giản hóa xử lý xác thực`
- `Cải thiện việc xác định lỗi nhập liệu`
- `Đơn giản hóa xử lý cache`

Tránh văn phong câu dài hoặc dư thừa.

Không nên:

- `Đã tăng tốc hiển thị kết quả tìm kiếm`
- `Đã thay đổi xử lý xác thực`
- `Đã xóa các cài đặt không cần thiết`

## Tiếng Anh (`en`)

Nội dung commit được viết bằng tiếng Anh.

Ưu tiên câu ngắn ở **imperative mood**.

Ví dụ:

```text
feature/user-search Improve search result performance
```

```text
bugfix/auth Improve authentication error handling
```

```text
refactor/cache Simplify cache handling
```

Cách diễn đạt được ưu tiên:

- `Improve search result performance`
- `Simplify authentication flow`
- `Improve input validation`
- `Remove unused configuration`

Tránh quá khứ hoặc câu dài dòng.

Không nên:

- `Improved search result performance`
- `Authentication flow was simplified`
- `Changes were made to authentication`

Dù sử dụng tiếng Việt hay tiếng Anh, **không được thay đổi branch name**.

---

# Độ dài commit message

Không đặt giới hạn số ký tự cứng.

Ưu tiên theo thứ tự:

1. Chỉ có 1 dòng.
2. Truyền đạt được mục đích thay đổi.
3. Ngắn gọn.
4. Ngắn nhất có thể mà không làm mất ý nghĩa.

Không được máy móc coi 50 hoặc 72 ký tự là hard limit.

Ngay cả khi branch name dài cũng không được rút gọn.

Thông thường không tạo commit body hoặc footer.

Nếu có nội dung bổ sung cần giải thích, không đưa vào commit message mà hãy giải thích trong phần chat.

---

# Số lượng đề xuất

Thông thường chỉ đưa ra **1 phương án** được đánh giá là phù hợp nhất.

Không được đưa ra nhiều phương án cùng lúc.

Chỉ khi người dùng nói rằng:

- Không thích.
- Muốn phương án khác.
- Muốn cách diễn đạt khác.

thì mới đưa ra phương án mới.

---

# Định dạng output

Commit message phải được đặt trong code block và chỉ có **1 dòng** để người dùng dễ copy.

Thông thường không thêm phần giải thích dài trước hoặc sau commit message.

Nếu cần giải thích, hãy viết ngắn gọn trong phần chat.

---

# Thứ tự ưu tiên khi đưa ra quyết định

Khi phân vân, hãy suy nghĩ theo thứ tự gần như sau:

1. Có rơi vào điều kiện dừng vì an toàn hay không?
2. Mục đích chính của `staged changes` là gì?
3. Có thể giải thích toàn bộ `staged changes` bằng một mục đích duy nhất hay không?
4. Mỗi thay đổi có quan hệ nhân quả với mục đích chính hay không?
5. Thay đổi đó có cần thiết để mục đích chính hoạt động hay không?
6. Có thay đổi kiểu "tiện thể sửa luôn" bị trộn vào hay không?
7. Nếu `revert`, có vô tình revert cả thay đổi không liên quan hay không?
8. Có đang suy đoán một ý định mà thực tế không thể xác định hay không?

Nếu vẫn phân vân, không được ép đưa ra kết luận; hãy hỏi người dùng.

---

# Tư tưởng quan trọng

Tách commit không có nghĩa là càng nhỏ càng tốt.

Nếu tách quá mức các thay đổi có liên quan, có thể làm mất ngữ cảnh:

> Tại sao thay đổi này lại cần thiết?

Ngược lại, nếu gom các thay đổi không liên quan vào cùng một commit, việc review và `revert` sẽ trở nên khó khăn.

Vì vậy, hãy tập trung vào:

**Mục đích của thay đổi**

và

**Quan hệ nhân quả giữa các thay đổi**.

Khi cần, sử dụng:

**Mức độ dễ dàng khi revert**

như một tiêu chí bổ trợ.

Không đánh giá theo đơn vị file mà phải xem xét nhóm các thay đổi theo **lý do thực hiện**.

Không được máy móc kết luận kiểu:

- "Cùng file nên cùng commit".
- "Khác file nên khác commit".

Điều quan trọng cuối cùng là có thể giải thích:

> Commit này tồn tại để làm gì?

bằng **một mục đích duy nhất**.

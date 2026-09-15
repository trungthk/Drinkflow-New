Bạn đang làm việc trên một dự án Laravel hiện có.

Hãy phân tích source code hiện tại trước khi thay đổi. Mục tiêu là xây dựng kiến trúc **Food Crawler Gateway** có khả năng crawl/import menu từ nhiều nền tảng khác nhau như:

- ShopeeFood
- GrabFood trong tương lai
- Các website/nền tảng khác trong tương lai

KHÔNG thiết kế logic chỉ dành riêng cho ShopeeFood ở Controller hoặc business layer chung.

## 1. Kiến trúc yêu cầu

Thiết kế theo hướng:

FoodCrawlerGateway
→ tự detect provider từ URL
→ resolve Provider phù hợp
→ Provider crawl dữ liệu
→ normalize về một format chung
→ trả dữ liệu cho application hiện tại.

Kiến trúc mong muốn:

```text
Input URL
   ↓
FoodCrawlerGateway
   ↓
ProviderResolver
   ↓
detect domain / URL
   │
   ├── shopeefood.vn
   │      ↓
   │   ShopeeFoodProvider
   │
   ├── food.grab.com / grab.com
   │      ↓
   │   GrabFoodProvider
   │
   └── ...
          ↓
      OtherProvider
   ↓
Normalized Restaurant/Menu DTO
   ↓
Application / Database
```

Khi thêm provider mới, ví dụ GrabFood, lý tưởng chỉ cần:

```text
1. tạo GrabFoodProvider
2. implement FoodCrawlerProviderInterface
3. đăng ký provider
```

Không phải sửa Controller hoặc business logic đang sử dụng Gateway.

## 2. Interface chung

Tạo interface phù hợp, ví dụ:

```php
interface FoodCrawlerProviderInterface
{
    public function supports(string $url): bool;

    public function crawl(string $url): RestaurantMenuData;
}
```

Có thể bổ sung method nếu thực sự cần:

```php
public function getName(): string;
public function normalizeUrl(string $url): string;
```

Không over-engineering.

## 3. FoodCrawlerGateway

Tạo:

```text
app/Services/FoodCrawler/FoodCrawlerGateway.php
```

Gateway nhận URL:

```php
$data = $gateway->crawl($url);
```

Gateway phải:

1. Validate URL.
2. Resolve provider.
3. Nếu không hỗ trợ domain → throw UnsupportedFoodProviderException.
4. Gọi provider tương ứng.
5. Provider trả về normalized DTO.
6. Gateway không chứa logic riêng của ShopeeFood/GrabFood.

Ví dụ:

```php
public function crawl(string $url): RestaurantMenuData
{
    $provider = $this->resolver->resolve($url);

    return $provider->crawl($url);
}
```

## 4. Provider Resolver

Tạo resolver riêng.

Không viết kiểu:

```php
if (str_contains($url, 'shopeefood')) {
   ...
} elseif (str_contains($url, 'grab')) {
   ...
}
```

rải rác trong source code.

Provider tự khai báo khả năng xử lý URL:

```php
public function supports(string $url): bool
{
    return strtolower(parse_url($url, PHP_URL_HOST)) === 'shopeefood.vn';
}
```

Chú ý hỗ trợ cả:

```text
https://shopeefood.vn/...
https://www.shopeefood.vn/...
```

Normalize hostname trước khi so sánh.

Resolver loop qua registered providers và chọn provider có:

```php
$provider->supports($url) === true
```

## 5. ShopeeFoodProvider

Tạo:

```text
app/Services/FoodCrawler/Providers/ShopeeFoodProvider.php
```

ShopeeFood hiện có flow xác định như sau.

Input:

```text
https://shopeefood.vn/ho-chi-minh/thanh-dat-hu-tieu-nam-vang-nguyen-thuong-hien
```

### STEP 1 – Parse URL

Chỉ lấy path:

```text
ho-chi-minh/thanh-dat-hu-tieu-nam-vang-nguyen-thuong-hien
```

Loại:

- protocol
- hostname
- query string
- fragment
- dấu `/` đầu/cuối

Không hard-code slug cụ thể.

### STEP 2 – Resolve ShopeeFood restaurant

Request:

```text
GET https://gappapi.deliverynow.vn/api/delivery/get_from_url

query:
url={parsed_path}
```

Ví dụ response:

```json
{
  "reply": {
    "restaurant_id": 923148,
    "delivery_id": 86844
  },
  "result": "success"
}
```

Validate:

```text
result === success
reply.delivery_id tồn tại
```

Lưu:

```text
restaurant_id
delivery_id
```

Trong đó `delivery_id` sẽ được sử dụng làm `request_id` cho API menu.

### STEP 3 – Fetch menu

Request:

```text
GET https://gappapi.deliverynow.vn/api/dish/get_delivery_dishes

query:
id_type=2
request_id={delivery_id}
```

Không hard-code:

```text
86844
923148
```

## 6. Phân tích response menu

Response thực tế đã được cung cấp trong file đính kèm.

Cấu trúc quan trọng:

```text
reply
 └── menu_infos[]
      ├── dish_type_id
      ├── display_order
      ├── dish_type_name
      └── dishes[]
```

`menu_infos` chính là danh sách category/menu group.

Dish có các dữ liệu quan trọng:

```text
id
name
description

price.value
price.text

discount_price.value
discount_price.text

photos[]

options[]

is_active
is_available
is_deleted

display_order
total_like
```

Ví dụ response thực tế cho thấy giá gốc có thể là 150000 và discount_price 138000.

Ảnh có nhiều kích thước:

```text
120
180
400
560
750
1242
```

Không lấy ảnh đầu tiên một cách mặc định.

Ưu tiên:

```text
750
→ 560
→ 400
→ ảnh lớn nhất còn lại
```

Response thực tế có đầy đủ URL ảnh theo từng kích thước.

## 7. Options / modifiers

Đây là phần quan trọng, KHÔNG bỏ qua.

Một dish có thể có:

```text
options[]
```

Mỗi option gồm:

```text
id
name
mandatory

option_items.min_select
option_items.max_select
option_items.items[]
```

Mỗi item:

```text
id
name
price.value
is_default
max_quantity
```

Ví dụ response có option bắt buộc chọn 1 trong 2.

Ngoài ra modifier có thể làm tăng giá.

Ví dụ SIZE:

```text
Tô Thường       +0
Tô Đặc Biệt     +30000
```

Response thực tế thể hiện modifier +30.000đ.

Phải giữ đầy đủ:

```text
mandatory
min_select
max_select
is_default
max_quantity
additional_price
```

## 8. Normalize thành DTO chung

Provider KHÔNG trả raw ShopeeFood response cho application.

Tạo DTO/provider-independent structure.

Ví dụ:

```php
RestaurantMenuData
{
    provider
    source_url
    external_restaurant_id
    external_delivery_id
    categories[]
}
```

Category:

```php
MenuCategoryData
{
    external_id
    name
    display_order
    products[]
}
```

Product:

```php
MenuProductData
{
    external_id
    name
    description

    price
    discount_price
    selling_price

    image_url

    is_active
    is_available
    display_order

    option_groups[]
}
```

OptionGroup:

```php
ProductOptionGroupData
{
    external_id
    name

    mandatory
    min_select
    max_select

    items[]
}
```

OptionItem:

```php
ProductOptionItemData
{
    external_id
    name

    additional_price
    is_default
    max_quantity
}
```

Không đưa tên field ShopeeFood-specific vào DTO chung nếu không cần thiết.

## 9. Quy tắc price

Không parse:

```text
"75.000đ"
```

nếu API đã có:

```text
price.value
discount_price.value
```

Sử dụng numeric `value`.

Xác định:

```php
$originalPrice = price.value;

$discountPrice = discount_price.value ?? null;

$sellingPrice =
    $discountPrice !== null && $discountPrice > 0
        ? $discountPrice
        : $originalPrice;
```

## 10. Duplicate category/product

ShopeeFood có thể đưa cùng dish vào nhiều menu group, ví dụ category promotion và category chính.

KHÔNG deduplicate toàn bộ chỉ dựa vào `name`.

Giữ:

```text
external product id
external category id
```

để application có thể quyết định cách import.

Nếu cần deduplicate khi import DB, ưu tiên:

```text
provider + external_product_id
```

nhưng vẫn phải giữ mapping Product ↔ Category.

Thiết kế many-to-many nếu schema hiện tại phù hợp.

Không tự ý phá schema hiện có.

## 11. HTTP Client

Tạo HTTP client riêng cho ShopeeFood nếu hợp lý:

```text
ShopeeFoodClient
```

Ví dụ:

```text
FoodCrawler
 ├── Contracts
 │    └── FoodCrawlerProviderInterface.php
 │
 ├── DTO
 │    ├── RestaurantMenuData.php
 │    ├── MenuCategoryData.php
 │    ├── MenuProductData.php
 │    ├── ProductOptionGroupData.php
 │    └── ProductOptionItemData.php
 │
 ├── Exceptions
 │    ├── UnsupportedFoodProviderException.php
 │    └── FoodCrawlerException.php
 │
 ├── Providers
 │    └── ShopeeFoodProvider.php
 │
 ├── Clients
 │    └── ShopeeFoodClient.php
 │
 ├── ProviderResolver.php
 │
 └── FoodCrawlerGateway.php
```

ShopeeFoodClient chịu trách nhiệm HTTP.

ShopeeFoodProvider chịu trách nhiệm business mapping/normalization.

FoodCrawlerGateway chịu trách nhiệm orchestration.

Không trộn ba responsibility này.

## 12. HTTP reliability

Sử dụng Laravel HTTP Client:

```php
Http::acceptJson()
    ->timeout(...)
    ->connectTimeout(...)
    ->retry(...);
```

Xử lý:

```text
timeout
connection error
HTTP != 2xx
invalid JSON
result != success
missing delivery_id
missing reply
menu_infos không tồn tại
```

Throw exception có message rõ ràng.

Không để lỗi kiểu:

```text
Undefined array key "reply"
```

## 13. Security

Không cho crawler gọi arbitrary URL từ input người dùng.

Gateway chỉ nhận URL để detect provider.

Các HTTP endpoint thực sự phải được provider định nghĩa cố định.

Ví dụ ShopeeFoodProvider chỉ được request tới:

```text
gappapi.deliverynow.vn
```

Không thực hiện:

```php
Http::get($userInputUrl);
```

để tránh SSRF.

Validate scheme:

```text
http / https
```

và hostname.

## 14. Provider registration

Thiết kế sao cho provider có thể register tập trung.

Ví dụ config:

```php
// config/food-crawler.php

return [

    'providers' => [

        \App\Services\FoodCrawler\Providers\ShopeeFoodProvider::class,

        // Future:
        // \App\Services\FoodCrawler\Providers\GrabFoodProvider::class,

    ],

];
```

Hoặc dùng ServiceProvider nếu phù hợp hơn với architecture hiện tại.

Gateway không được biết trực tiếp:

```text
ShopeeFoodProvider
GrabFoodProvider
```

## 15. API/application usage

Application chỉ sử dụng:

```php
$result = $foodCrawlerGateway->crawl($url);
```

Ví dụ:

```php
POST /api/food-crawler/preview
```

Request:

```json
{
  "url": "https://shopeefood.vn/ho-chi-minh/..."
}
```

Response nên là normalized JSON:

```json
{
  "provider": "shopeefood",
  "source_url": "...",
  "restaurant": {
    "external_id": "923148"
  },
  "categories": [...]
}
```

Controller tuyệt đối không có:

```php
if ($provider === 'shopeefood') {
}
```

## 16. Chuẩn bị cho GrabFood

CHƯA implement fake GrabFood API.

Chỉ đảm bảo architecture có thể thêm:

```php
class GrabFoodProvider implements FoodCrawlerProviderInterface
{
    public function supports(string $url): bool
    {
        // grab domains
    }

    public function crawl(string $url): RestaurantMenuData
    {
        ...
    }
}
```

Sau đó register:

```php
GrabFoodProvider::class
```

là Gateway có thể sử dụng.

Không tạo dữ liệu giả hoặc endpoint GrabFood chưa được xác minh.

## 17. Testing

Viết Unit/Feature Test.

Ít nhất:

```text
ShopeeFood URL
→ ShopeeFoodProvider

www.shopeefood.vn
→ ShopeeFoodProvider

unsupported.com
→ UnsupportedFoodProviderException
```

Mock HTTP:

```php
Http::fake()
```

Test flow:

```text
URL
↓
get_from_url
↓
delivery_id
↓
get_delivery_dishes
↓
normalize
```

Test:

```text
category mapping
product mapping
price
discount
image selection
option groups
modifier price
mandatory
min/max selection
```

Đặc biệt test modifier:

```text
Tô Đặc Biệt +30000
```

## 18. Logging

Logging đủ để debug nhưng không log toàn bộ JSON menu lớn.

Ví dụ:

```text
provider
source_url
external_restaurant_id
delivery_id
categories_count
products_count
duration_ms
```

Error log:

```text
provider
step
HTTP status
exception
```

## 19. Nguyên tắc implementation

Trước khi code:

1. Đọc architecture/source hiện tại.
2. Xác định nơi tích hợp Gateway phù hợp.
3. Không phá functionality hiện tại.
4. Reuse model/service hiện có nếu hợp lý.
5. Không tạo abstraction thừa.
6. Không hard-code restaurant cụ thể.
7. Không hard-code ShopeeFood logic ngoài ShopeeFoodProvider/ShopeeFoodClient.
8. Không implement GrabFood bằng phỏng đoán.
9. Code theo convention Laravel version hiện tại.
10. Chạy tests/lint sau khi hoàn thành.

Sau khi implement, báo cáo:

```text
Files created
Files modified

Architecture implemented

ShopeeFood flow

Normalized data structure

Error handling

Tests added

How to add GrabFoodProvider later

Any assumptions / limitations
```
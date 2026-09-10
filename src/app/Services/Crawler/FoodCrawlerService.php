<?php

namespace App\Services\Crawler;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class FoodCrawlerService
{
    public function preview(string $url): array
    {
        $this->assertSafeUrl($url);
        $response = Http::timeout(10)->retry(2, 250)->withHeaders(['User-Agent' => 'DrinkFlow Menu Preview/1.0'])->get($url);
        if (! $response->successful()) {
            throw ValidationException::withMessages(['url' => 'Không thể tải nội dung nhà hàng từ URL này.']);
        }

        $html = $response->body();
        $items = $this->fromJsonLd($html);
        if ($items === []) {
            $title = trim(strip_tags((string) preg_replace('/.*?<title[^>]*>(.*?)<\/title>.*/is', '$1', $html)));
            $items = [[
                'name' => $title !== '' ? $title : 'Món chưa xác định',
                'category' => null,
                'description' => null,
                'image_url' => null,
                'base_price' => 0,
                'source_item_key' => sha1($url),
            ]];
        }

        return collect($items)->unique(fn (array $item) => strtolower($item['name'].'|'.$item['base_price']))->values()->take(500)->all();
    }

    private function fromJsonLd(string $html): array
    {
        preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches);
        $items = [];
        foreach ($matches[1] ?? [] as $raw) {
            $decoded = json_decode(html_entity_decode(trim($raw)), true);
            foreach ($this->flatten($decoded) as $product) {
                if (! is_array($product) || ($product['@type'] ?? null) !== 'Product') {
                    continue;
                }
                $offer = $product['offers'] ?? [];
                if (isset($offer[0])) {
                    $offer = $offer[0];
                }
                $image = $product['image'] ?? null;
                if (is_array($image)) {
                    $image = $image[0] ?? null;
                }
                $items[] = [
                    'name' => trim((string) ($product['name'] ?? '')),
                    'category' => isset($product['category']) ? trim((string) $product['category']) : null,
                    'description' => isset($product['description']) ? trim((string) $product['description']) : null,
                    'image_url' => is_string($image) && filter_var($image, FILTER_VALIDATE_URL) ? $image : null,
                    'base_price' => $this->price($offer['price'] ?? $product['price'] ?? 0),
                    'source_item_key' => (string) ($product['sku'] ?? $product['productID'] ?? sha1((string) ($product['name'] ?? ''))),
                ];
            }
        }
        return array_values(array_filter($items, fn (array $item) => $item['name'] !== ''));
    }

    private function flatten(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }
        if (isset($value['@graph']) && is_array($value['@graph'])) {
            return $value['@graph'];
        }
        return isset($value[0]) ? $value : [$value];
    }

    private function price(mixed $price): int
    {
        return max(0, (int) round((float) str_replace([',', ' '], '', (string) $price)));
    }

    private function assertSafeUrl(string $url): void
    {
        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        if (! in_array($parts['scheme'] ?? '', ['http', 'https'], true) || $host === '' || in_array($host, ['localhost', '127.0.0.1', '::1'], true) || str_ends_with($host, '.local')) {
            throw ValidationException::withMessages(['url' => 'URL không hợp lệ hoặc không được phép.']);
        }
        $ip = gethostbyname($host);
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false && filter_var($ip, FILTER_VALIDATE_IP)) {
            throw ValidationException::withMessages(['url' => 'URL trỏ tới địa chỉ mạng nội bộ.']);
        }
    }
}

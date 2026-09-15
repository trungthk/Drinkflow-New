<?php

declare(strict_types=1);

namespace App\Services\FoodCrawler\Providers;

use App\Services\FoodCrawler\Clients\ShopeeFoodClient;
use App\Services\FoodCrawler\Contracts\FoodCrawlerProviderInterface;
use App\Services\FoodCrawler\DTO\MenuCategoryData;
use App\Services\FoodCrawler\DTO\MenuProductData;
use App\Services\FoodCrawler\DTO\ProductOptionGroupData;
use App\Services\FoodCrawler\DTO\ProductOptionItemData;
use App\Services\FoodCrawler\DTO\RestaurantMenuData;
use App\Services\FoodCrawler\Exceptions\FoodCrawlerException;

final class ShopeeFoodProvider implements FoodCrawlerProviderInterface
{
    public function __construct(private readonly ShopeeFoodClient $client)
    {
    }

    public function supports(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        return in_array($host, ['shopeefood.vn', 'www.shopeefood.vn'], true);
    }

    public function crawl(string $url): RestaurantMenuData
    {
        $network = $this->client->pageMenuData($url);
        $restaurantReply = is_array($network['restaurant']['reply'] ?? null) ? $network['restaurant']['reply'] : [];
        $payload = is_array($network['dishes']['reply'] ?? null) ? $network['dishes']['reply'] : [];
        $categories = $this->categories($payload['menu_infos'] ?? []);
        if ($categories === []) {
            throw new FoodCrawlerException('ShopeeFood public page did not expose a readable menu response.');
        }

        return new RestaurantMenuData('shopeefood', $url, (string) ($restaurantReply['restaurant_id'] ?? ''), (string) ($restaurantReply['delivery_id'] ?? ''), $categories);
    }

    /** @param mixed $menuInfos @return array<int, MenuCategoryData> */
    private function categories(mixed $menuInfos): array
    {
        if (! is_array($menuInfos)) {
            return [];
        }
        $categories = [];
        foreach ($menuInfos as $categoryIndex => $rawCategory) {
            if (! is_array($rawCategory)) {
                continue;
            }
            $products = [];
            foreach (($rawCategory['dishes'] ?? $rawCategory['products'] ?? []) as $product) {
                if (is_array($product)) {
                    $products[] = $this->product($product);
                }
            }
            if ($products !== []) {
                $categories[] = new MenuCategoryData(
                    (string) ($rawCategory['dish_type_id'] ?? $rawCategory['id'] ?? $categoryIndex),
                    trim((string) ($rawCategory['dish_type_name'] ?? $rawCategory['name'] ?? 'Món khác')) ?: 'Món khác',
                    (int) ($rawCategory['display_order'] ?? $categoryIndex),
                    $products,
                );
            }
        }
        return $categories;
    }

    /** @return array<string, mixed> */
    private function findMenuPayload(string $html): array
    {
        $jsonLdProducts = [];
        preg_match_all('/<script[^>]*>(.*?)<\/script>/is', $html, $matches);
        foreach ($matches[1] ?? [] as $script) {
            $decoded = json_decode(trim(html_entity_decode($script)), true);
            if (! is_array($decoded)) {
                continue;
            }
            $payload = $this->findNestedMenu($decoded);
            if ($payload !== []) {
                return $payload;
            }
            foreach ($this->flattenJsonLd($decoded) as $product) {
                if (is_array($product) && ($product['@type'] ?? null) === 'Product') {
                    $jsonLdProducts[] = $this->jsonLdProduct($product);
                }
            }
        }
        if ($jsonLdProducts !== []) {
            return ['menu_infos' => [['id' => 'json-ld', 'name' => 'Menu', 'products' => $jsonLdProducts]]];
        }
        return [];
    }

    /** @param mixed $value @return array<int, mixed> */
    private function flattenJsonLd(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }
        if (isset($value['@graph']) && is_array($value['@graph'])) {
            return $value['@graph'];
        }
        return isset($value[0]) ? $value : [$value];
    }

    /** @param array<string, mixed> $product @return array<string, mixed> */
    private function jsonLdProduct(array $product): array
    {
        $offers = $product['offers'] ?? [];
        if (isset($offers[0])) {
            $offers = $offers[0];
        }
        $image = $product['image'] ?? null;
        if (is_array($image)) {
            $image = $image[0] ?? null;
        }

        return [
            'id' => $product['sku'] ?? $product['productID'] ?? sha1((string) ($product['name'] ?? '')),
            'name' => $product['name'] ?? '',
            'description' => $product['description'] ?? null,
            'price' => ['value' => $offers['price'] ?? $product['price'] ?? 0],
            'photos' => is_string($image) ? [['url' => $image]] : [],
        ];
    }

    /** @param mixed $value @return array<string, mixed> */
    private function findNestedMenu(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }
        if (isset($value['menu_infos']) && is_array($value['menu_infos'])) {
            return $value;
        }
        foreach ($value as $child) {
            $found = $this->findNestedMenu($child);
            if ($found !== []) {
                return $found;
            }
        }
        return [];
    }

    /** @param array<string, mixed> $raw */
    private function product(array $raw): MenuProductData
    {
        $price = $this->numeric($raw['price']['value'] ?? 0);
        $discount = isset($raw['discount_price']['value']) ? $this->numeric($raw['discount_price']['value']) : null;
        $discount = $discount !== null && $discount > 0 ? $discount : null;

        return new MenuProductData(
            (string) ($raw['id'] ?? sha1((string) ($raw['name'] ?? ''))),
            trim((string) ($raw['name'] ?? 'Món chưa đặt tên')),
            isset($raw['description']) ? trim((string) $raw['description']) : null,
            $price,
            $discount,
            $discount ?? $price,
            $this->image($raw['photos'] ?? []),
            (bool) ($raw['is_active'] ?? true),
            (bool) ($raw['is_available'] ?? true),
            (int) ($raw['display_order'] ?? 0),
            $this->options($raw['options'] ?? []),
        );
    }

    /** @param mixed $photos */
    private function image(mixed $photos): ?string
    {
        if (! is_array($photos)) {
            return null;
        }
        $preferred = [750, 560, 400];
        foreach ($preferred as $size) {
            foreach ($photos as $photo) {
                if (is_array($photo) && (string) ($photo['width'] ?? $photo['size'] ?? '') === (string) $size && filter_var($photo['url'] ?? null, FILTER_VALIDATE_URL)) {
                    return (string) $photo['url'];
                }
            }
        }
        foreach ($photos as $photo) {
            $url = is_string($photo) ? $photo : ($photo['url'] ?? null);
            if (is_string($url) && filter_var($url, FILTER_VALIDATE_URL)) {
                return $url;
            }
        }
        return null;
    }

    /** @param mixed $options @return array<int, ProductOptionGroupData> */
    private function options(mixed $options): array
    {
        if (! is_array($options)) {
            return [];
        }
        $groups = [];
        foreach ($options as $index => $option) {
            if (! is_array($option)) {
                continue;
            }
            $selection = is_array($option['option_items'] ?? null) ? $option['option_items'] : [];
            $rawItems = $selection['items'] ?? [];
            $items = [];
            foreach (is_array($rawItems) ? $rawItems : [] as $itemIndex => $item) {
                if (is_array($item)) {
                    $items[] = new ProductOptionItemData(
                        (string) ($item['id'] ?? $itemIndex),
                        trim((string) ($item['name'] ?? '')),
                        $this->numeric($item['price']['value'] ?? $item['price'] ?? 0),
                        (bool) ($item['is_default'] ?? false),
                        (int) ($item['max_quantity'] ?? 1),
                    );
                }
            }
            $groups[] = new ProductOptionGroupData(
                (string) ($option['id'] ?? $index),
                trim((string) ($option['name'] ?? 'Tùy chọn')),
                (bool) ($option['mandatory'] ?? false),
                (int) ($selection['min_select'] ?? 0),
                (int) ($selection['max_select'] ?? 0),
                $items,
            );
        }
        return $groups;
    }

    private function numeric(mixed $value): int
    {
        return max(0, (int) round((float) $value));
    }
}

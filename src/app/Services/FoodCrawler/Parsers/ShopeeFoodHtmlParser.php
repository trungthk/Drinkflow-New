<?php

declare(strict_types=1);

namespace App\Services\FoodCrawler\Parsers;

use App\Services\FoodCrawler\DTO\MenuCategoryData;
use App\Services\FoodCrawler\DTO\MenuProductData;
use App\Services\FoodCrawler\DTO\RestaurantMenuData;
use App\Services\FoodCrawler\Exceptions\FoodCrawlerException;
use DOMDocument;
use DOMElement;
use DOMXPath;

final class ShopeeFoodHtmlParser
{
    public function parse(string $url, string $html): RestaurantMenuData
    {
        $document = new DOMDocument();
        @$document->loadHTML($html);
        $xpath = new DOMXPath($document);
        $categories = $this->categories($xpath);
        if ($categories === []) {
            throw new FoodCrawlerException('ShopeeFood rendered page did not expose a readable menu in the DOM.');
        }

        return new RestaurantMenuData('shopeefood', $url, $this->restaurantId($xpath), null, $categories);
    }

    /** @return array<int, MenuCategoryData> */
    private function categories(DOMXPath $xpath): array
    {
        $result = [];
        $categoryNodes = $xpath->query(
            "//*[self::section or self::div][.//h2 or .//*[contains(@class,'category')]][.//button or .//*[contains(@class,'dish') or contains(@class,'product')]]"
        );
        if ($categoryNodes === false) {
            return [];
        }
        foreach ($categoryNodes as $index => $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }
            $name = $this->text($xpath, './/h2[1] | .//*[contains(@class,"category")][1]', $node);
            $products = $this->products($xpath, $node);
            if ($name !== '' && $products !== []) {
                $result[] = new MenuCategoryData((string) $index, $name, $index, $products);
            }
        }
        return $result;
    }

    /** @return array<int, MenuProductData> */
    private function products(DOMXPath $xpath, DOMElement $category): array
    {
        $result = [];
        $nodes = $xpath->query(".//*[contains(@class,'dish') or contains(@class,'product')][.//*[contains(@class,'price')] or .//*[contains(text(),'đ')]]", $category);
        if ($nodes === false) {
            return [];
        }
        foreach ($nodes as $index => $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }
            $name = $this->text($xpath, './/h3[1] | .//*[contains(@class,"name")][1]', $node);
            $priceText = $this->text($xpath, './/*[contains(@class,"price")][1] | .//*[contains(text(),"đ")][1]', $node);
            $price = $this->price($priceText);
            if ($name === '' || $price === null) {
                continue;
            }
            $image = $xpath->query('.//img[1]', $node)?->item(0);
            $result[] = new MenuProductData(
                sha1($name.'|'.$index),
                $name,
                null,
                $price,
                null,
                $price,
                $image instanceof DOMElement ? $image->getAttribute('src') : null,
                true,
                true,
                $index,
            );
        }
        return $result;
    }

    private function restaurantId(DOMXPath $xpath): string
    {
        $canonical = $xpath->query('//link[@rel="canonical"]/@href')?->item(0);
        return $canonical instanceof \DOMAttr ? sha1($canonical->value) : '';
    }

    private function text(DOMXPath $xpath, string $query, DOMElement $context): string
    {
        $node = $xpath->query($query, $context)?->item(0);
        return $node === null ? '' : trim((string) preg_replace('/\s+/', ' ', $node->textContent));
    }

    private function price(string $value): ?int
    {
        $normalized = preg_replace('/[^\d]/', '', $value);
        return $normalized === '' ? null : (int) $normalized;
    }
}

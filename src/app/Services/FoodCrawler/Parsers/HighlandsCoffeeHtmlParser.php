<?php

declare(strict_types=1);

namespace App\Services\FoodCrawler\Parsers;

use App\Services\FoodCrawler\Clients\UrlNormalizer;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Reads the server-rendered HTML of www.highlandscoffee.com.vn.
 *
 * Selectors (verified against the live site in 10/2026):
 * - Menu page: category links in the mega menu `.menuMega` (top level `li > a` and sub-level `li > ul > li > a`).
 * - Category page: products in the main column `#vnt-main #slideProduct .product`; the "other products"
 *   slider (`#slideOther`) and the sidebar (`#vnt-sidebar`) are suggestions and are ignored.
 * - Product page: main product block `#vnt-main .boxBB` (title, description, sizes with `data-price`,
 *   `#ext_price`); the related-products slider below it is ignored.
 */
final class HighlandsCoffeeHtmlParser
{
    /**
     * Extract category links (parents before their children) from the menu page.
     *
     * @param string $html Menu page HTML.
     * @param string $baseUrl URL of the menu page.
     * @return array<int, array{name: string, url: string}> Unique category links in menu order.
     */
    public function categoryLinks(string $html, string $baseUrl): array
    {
        $xpath = $this->xpath($html);
        $nodes = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' menuMega ')]//li/a[@href]");
        $links = [];
        foreach ($nodes ?: [] as $anchor) {
            if (! $anchor instanceof DOMElement) {
                continue;
            }
            $url = UrlNormalizer::absolute($anchor->getAttribute('href'), $baseUrl);
            $name = $this->clean($anchor->textContent);
            if ($url === null || $name === '') {
                continue;
            }
            $links[UrlNormalizer::key($url)] ??= ['name' => $this->title($name), 'url' => $url];
        }

        return array_values($links);
    }

    /**
     * Extract the product cards listed in a category page's main content.
     *
     * @param string $html Category page HTML.
     * @param string $baseUrl URL of the category page.
     * @return array<int, array{name: string, url: string, image_url: ?string, price: ?int}> Products in page order.
     */
    public function categoryProducts(string $html, string $baseUrl): array
    {
        $xpath = $this->xpath($html);
        $cards = $xpath->query("//*[@id='vnt-main']//*[@id='slideProduct']//*[contains(concat(' ', normalize-space(@class), ' '), ' product ')]");
        $products = [];
        foreach ($cards ?: [] as $card) {
            if (! $card instanceof DOMElement) {
                continue;
            }
            $anchor = $this->first($xpath, ".//h3/a[@href] | .//*[contains(@class,'tend')]//a[@href]", $card);
            $url = $anchor instanceof DOMElement ? UrlNormalizer::absolute($anchor->getAttribute('href'), $baseUrl) : null;
            $name = $anchor !== null ? $this->clean($anchor->textContent) : '';
            if ($url === null || $name === '') {
                continue;
            }
            $image = $this->first($xpath, ".//img[@src]", $card);
            $priceNode = $this->first($xpath, ".//*[contains(@class,'price')]", $card);
            $products[] = [
                'name' => $name,
                'url' => $url,
                'image_url' => $image instanceof DOMElement ? UrlNormalizer::absolute($image->getAttribute('src'), $baseUrl) : null,
                'price' => $priceNode !== null ? self::price($priceNode->textContent) : null,
            ];
        }

        return $products;
    }

    /**
     * Read the main product of a product detail page.
     *
     * @param string $html Product page HTML.
     * @param string $baseUrl URL of the product page.
     * @return array{name: ?string, description: ?string, image_url: ?string, price: ?int, sizes: array<int, array{name: string, price: int}>}
     */
    public function productDetail(string $html, string $baseUrl): array
    {
        $xpath = $this->xpath($html);
        // Scope everything to the main product block so related products further down are never read.
        $main = $this->first($xpath, "//*[@id='vnt-main']//*[contains(concat(' ', normalize-space(@class), ' '), ' boxBB ')][.//*[contains(@class,'productTitle')]]");
        if (! $main instanceof DOMElement) {
            return ['name' => null, 'description' => null, 'image_url' => null, 'price' => null, 'sizes' => []];
        }

        $title = $this->first($xpath, ".//*[contains(@class,'productTitle')]//h1", $main);
        $image = $this->first($xpath, ".//*[@id='vnt-thumbnail-for']//img[@src] | .//*[contains(@class,'productThumnail')]//img[@src]", $main);
        $priceNode = $this->first($xpath, ".//*[@id='ext_price'] | .//*[contains(@class,'productPrice')]", $main);

        $sizes = [];
        foreach ($xpath->query(".//*[contains(@class,'productBox')]//*[contains(@class,'size')]//a[@data-price]", $main) ?: [] as $sizeNode) {
            if (! $sizeNode instanceof DOMElement) {
                continue;
            }
            $sizeName = $this->clean($sizeNode->textContent);
            $sizePrice = self::price($sizeNode->getAttribute('data-price'));
            if ($sizeName !== '' && $sizePrice !== null) {
                $sizes[] = ['name' => $sizeName, 'price' => $sizePrice];
            }
        }

        return [
            'name' => $title !== null ? ($this->clean($title->textContent) ?: null) : null,
            'description' => $this->description($xpath, $main),
            'image_url' => $image instanceof DOMElement ? UrlNormalizer::absolute($image->getAttribute('src'), $baseUrl) : null,
            'price' => $priceNode !== null ? self::price($priceNode->textContent) : null,
            'sizes' => $sizes,
        ];
    }

    /**
     * Normalise a VND price text to an integer, e.g. "45,000 VNĐ" or "Giá: 45.000đ" => 45000.
     *
     * @param string $text Raw price text.
     * @return int|null Price in VND, or null when the text holds no positive amount.
     */
    public static function price(string $text): ?int
    {
        if (preg_match('/\d[\d.,\s]*/u', html_entity_decode($text, ENT_QUOTES | ENT_HTML5), $match) !== 1) {
            return null;
        }
        $digits = (string) preg_replace('/\D/', '', $match[0]);
        $value = $digits === '' ? 0 : (int) $digits;

        return $value > 0 ? $value : null;
    }

    /**
     * Collect the product description paragraphs as plain text.
     *
     * @param DOMXPath $xpath Document XPath.
     * @param DOMElement $main Main product block.
     * @return string|null Description, or null when empty.
     */
    private function description(DOMXPath $xpath, DOMElement $main): ?string
    {
        $container = $this->first($xpath, ".//*[contains(@class,'productDes')]", $main);
        if ($container === null) {
            return null;
        }
        $parts = [];
        foreach ($xpath->query('.//p', $container) ?: [] as $paragraph) {
            $text = $this->clean($paragraph->textContent);
            if ($text !== '') {
                $parts[] = $text;
            }
        }
        $text = $parts !== [] ? implode("\n", $parts) : $this->clean($container->textContent);

        return $text !== '' ? mb_substr($text, 0, 2000) : null;
    }

    /**
     * Build an XPath over a UTF-8 HTML document.
     *
     * @param string $html Raw HTML.
     * @return DOMXPath XPath instance.
     */
    private function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new DOMXPath($document);
    }

    /**
     * Return the first node matching an expression.
     *
     * @param DOMXPath $xpath Document XPath.
     * @param string $expression XPath expression.
     * @param DOMNode|null $context Context node.
     * @return DOMNode|null First match.
     */
    private function first(DOMXPath $xpath, string $expression, ?DOMNode $context = null): ?DOMNode
    {
        $nodes = $xpath->query($expression, $context);

        return $nodes !== false && $nodes->length > 0 ? $nodes->item(0) : null;
    }

    /**
     * Collapse whitespace (including non-breaking spaces) and trim.
     *
     * @param string $text Raw text.
     * @return string Clean text.
     */
    private function clean(string $text): string
    {
        return trim((string) preg_replace('/[\s\x{00A0}]+/u', ' ', $text));
    }

    /**
     * Turn an all-caps menu label ("CÀ PHÊ") into title case; leave mixed-case labels untouched.
     *
     * @param string $name Category label.
     * @return string Display name.
     */
    private function title(string $name): string
    {
        return mb_strtoupper($name) === $name ? mb_convert_case(mb_strtolower($name), MB_CASE_TITLE) : $name;
    }
}

<?php

declare(strict_types=1);

namespace App\Services\FoodCrawler\Providers;

use App\Services\FoodCrawler\Clients\BrandHtmlClient;
use App\Services\FoodCrawler\Clients\UrlNormalizer;
use App\Services\FoodCrawler\Contracts\FoodCrawlerProviderInterface;
use App\Services\FoodCrawler\DTO\MenuCategoryData;
use App\Services\FoodCrawler\DTO\MenuProductData;
use App\Services\FoodCrawler\DTO\RestaurantMenuData;
use App\Services\FoodCrawler\Exceptions\FoodCrawlerException;
use App\Services\FoodCrawler\Parsers\HighlandsCoffeeHtmlParser;
use App\Services\FoodCrawler\Support\BrandDomainMatcher;
use Illuminate\Support\Facades\Log;

/**
 * Crawls the public Highlands Coffee menu (server-rendered HTML, no headless browser needed).
 *
 * Flow: menu page -> category links (parents and sub-categories) -> each category's product list ->
 * each product's detail page (description, image, sizes with their own prices).
 *
 * Decisions:
 * - Duplicates are detected by the normalised product URL; the first category in crawl order keeps the
 *   product (campaign items hold a single category) and later occurrences are counted as duplicates.
 * - A product whose price cannot be found on the category card nor on the detail page is skipped and
 *   reported as "missing price"; it is never imported with a price of 0.
 * - A failed detail request keeps the product with its category-card data when that card has a price.
 */
final class HighlandsCoffeeProvider implements FoodCrawlerProviderInterface
{
    private const BRAND = 'highlands';

    public function __construct(
        private readonly BrandDomainMatcher $matcher,
        private readonly HighlandsCoffeeHtmlParser $parser,
    ) {
    }

    /**
     * Whether the URL belongs to an allowlisted Highlands Coffee domain.
     *
     * @param string $url Source URL.
     * @return bool True for the Highlands brand.
     */
    public function supports(string $url): bool
    {
        return $this->matcher->brandFor($url) === self::BRAND;
    }

    /**
     * Crawl the whole Highlands menu, starting from the configured menu page.
     *
     * @param string $url URL supplied by the admin (only used to recognise the brand).
     * @return RestaurantMenuData Menu with a crawl report.
     * @throws FoodCrawlerException When the menu page cannot be read or no product is found at all.
     */
    public function crawl(string $url): RestaurantMenuData
    {
        $brand = (array) config('food-crawler.brands.'.self::BRAND, []);
        $domains = (array) ($brand['domains'] ?? []);
        $menuUrl = (string) ($brand['menu_url'] ?? '');
        $maxProducts = max(1, (int) ($brand['max_products'] ?? 200));
        // A fresh client per run: its response cache must not leak between crawls.
        $client = app(BrandHtmlClient::class);
        @set_time_limit(180);

        $report = [
            'categories_found' => 0,
            'categories_with_items' => 0,
            'categories_skipped_empty' => [],
            'products_found' => 0,
            'products_new' => 0,
            'products_duplicate' => 0,
            'products_failed' => 0,
            'errors' => [],
        ];

        try {
            $links = $this->parser->categoryLinks($client->get($menuUrl, $domains), $menuUrl);
        } catch (FoodCrawlerException $exception) {
            throw new FoodCrawlerException(__('admin.crawler_menu_unreachable', ['reason' => $exception->getMessage()]));
        }
        // Never follow a menu link outside the brand's domains.
        $links = array_values(array_filter($links, fn (array $link): bool => $this->matcher->urlAllowed($link['url'], $domains)));
        $report['categories_found'] = count($links);

        $seen = [];
        $categories = [];
        foreach ($links as $categoryIndex => $link) {
            try {
                $cards = $this->parser->categoryProducts($client->get($link['url'], $domains), $link['url']);
            } catch (FoodCrawlerException $exception) {
                $this->fail($report, $link['url'], $exception->getMessage());

                continue;
            }
            if ($cards === []) {
                // Parent pages (e.g. "Cà phê") only introduce their sub-categories and list no products.
                $report['categories_skipped_empty'][] = $link['name'];

                continue;
            }

            $products = [];
            foreach ($cards as $card) {
                $report['products_found']++;
                $key = UrlNormalizer::key($card['url']);
                if (isset($seen[$key])) {
                    $report['products_duplicate']++;

                    continue;
                }
                $seen[$key] = true;
                if (! $this->matcher->urlAllowed($card['url'], $domains)) {
                    $this->fail($report, $card['url'], __('admin.crawler_url_not_allowed'));
                    $report['products_failed']++;

                    continue;
                }
                if (count($seen) > $maxProducts) {
                    $this->fail($report, $card['url'], __('admin.crawler_product_limit', ['max' => $maxProducts]));
                    $report['products_failed']++;

                    continue;
                }

                $product = $this->product($client, $card, $domains, count($products), $report);
                if ($product !== null) {
                    $products[] = $product;
                }
            }

            if ($products !== []) {
                $report['categories_with_items']++;
                $categories[] = new MenuCategoryData((string) $categoryIndex, $link['name'], $categoryIndex, $products);
            } else {
                $report['categories_skipped_empty'][] = $link['name'];
            }
        }

        $report['products_new'] = array_sum(array_map(static fn (MenuCategoryData $category): int => count($category->products), $categories));
        if ($categories === []) {
            throw new FoodCrawlerException(__('admin.crawler_no_products'));
        }

        return new RestaurantMenuData(
            self::BRAND,
            $menuUrl,
            self::BRAND,
            null,
            $categories,
            (string) ($brand['name'] ?? 'Highlands Coffee'),
            $report,
            now()->toIso8601String(),
        );
    }

    /**
     * Combine a category card with its detail page into one product.
     *
     * @param BrandHtmlClient $client Run-scoped HTTP client.
     * @param array{name: string, url: string, image_url: ?string, price: ?int} $card Category card data.
     * @param array<int, string> $domains Allowed domains.
     * @param int $order Position inside the category.
     * @param array<string, mixed> $report Run report (updated in place).
     * @return MenuProductData|null Product, or null when it has no price.
     */
    private function product(BrandHtmlClient $client, array $card, array $domains, int $order, array &$report): ?MenuProductData
    {
        $detail = ['name' => null, 'description' => null, 'image_url' => null, 'price' => null, 'sizes' => []];
        try {
            $detail = $this->parser->productDetail($client->get($card['url'], $domains), $card['url']);
        } catch (FoodCrawlerException $exception) {
            $this->fail($report, $card['url'], $exception->getMessage());
        }

        $sizePrices = array_column($detail['sizes'], 'price');
        // Base price = cheapest size when the page lists sizes; otherwise the shown price.
        $price = $sizePrices !== [] ? min($sizePrices) : ($detail['price'] ?? $card['price']);
        if ($price === null) {
            $this->fail($report, $card['url'], __('admin.crawler_missing_price'));
            $report['products_failed']++;

            return null;
        }

        // Each size keeps the price the source page gives it; nothing is inferred.
        $sizes = array_map(static fn (array $size): array => [
            'name' => $size['name'],
            'price_delta' => $size['price'] - $price,
        ], $detail['sizes']);
        $imageUrl = $detail['image_url'] ?? $card['image_url'];

        return new MenuProductData(
            UrlNormalizer::key($card['url']),
            $card['name'] !== '' ? $card['name'] : (string) $detail['name'],
            $detail['description'],
            $price,
            null,
            $price,
            $imageUrl !== null && $this->matcher->urlAllowed($imageUrl, $domains) ? $imageUrl : null,
            true,
            true,
            $order,
            [],
            count($sizes) > 1 ? $sizes : [],
            $card['url'],
        );
    }

    /**
     * Record a failed URL in the report and the application log.
     *
     * @param array<string, mixed> $report Run report (updated in place).
     * @param string $url Failed URL.
     * @param string $reason Human readable reason.
     * @return void
     */
    private function fail(array &$report, string $url, string $reason): void
    {
        $report['errors'][] = ['url' => $url, 'reason' => $reason];
        Log::warning('Highlands crawler: '.$reason, ['url' => $url]);
    }
}

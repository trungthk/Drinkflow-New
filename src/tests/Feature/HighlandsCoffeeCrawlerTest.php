<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Room;
use App\Services\FoodCrawler\Exceptions\FoodCrawlerException;
use App\Services\FoodCrawler\FoodCrawlerGateway;
use App\Services\FoodCrawler\Parsers\HighlandsCoffeeHtmlParser;
use App\Services\FoodCrawler\Support\BrandDomainMatcher;
use App\Support\Security\OutboundUrlGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HighlandsCoffeeCrawlerTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'https://www.highlandscoffee.com.vn';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'food-crawler.http.request_delay_ms' => 0,
            'food-crawler.http.retry_delay_ms' => 0,
        ]);
        // No DNS in tests: the SSRF guard keeps its scheme/host checks but resolves to a fixed public IP.
        $this->app->instance(OutboundUrlGuard::class, new class extends OutboundUrlGuard {
            public function assertSafe(string $url, array $allowedHosts = []): array
            {
                $host = strtolower((string) parse_url($url, PHP_URL_HOST));
                if (strtolower((string) parse_url($url, PHP_URL_SCHEME)) !== 'https' || $host === '') {
                    throw new \InvalidArgumentException('The URL must be an absolute HTTPS URL.');
                }

                return ['host' => $host, 'port' => 443, 'ips' => ['104.26.13.250']];
            }
        });
    }

    /**
     * Read a fixture page.
     *
     * @param string $name Fixture file name.
     * @return string HTML.
     */
    private function fixture(string $name): string
    {
        return (string) file_get_contents(base_path('tests/Fixtures/highlands/'.$name));
    }

    /** Fake the Highlands website with fixture pages; tra-loi.html always fails with HTTP 500. */
    private function fakeSite(): void
    {
        $pages = [
            '/vn/san-pham.html' => 'menu.html',
            '/vn/ca-phe.html' => 'ca-phe.html',
            '/vn/ca-phe-truyen-thong.html' => 'ca-phe-truyen-thong.html',
            '/vn/highlands-teas.html' => 'highlands-teas.html',
            '/vn/bac-xiu-da.html' => 'product-bac-xiu-da.html',
            '/vn/americano.html' => 'product-simple.html',
            '/vn/tra-sen-vang.html' => 'product-simple.html',
            '/vn/tra-moi.html' => 'product-no-price.html',
        ];

        Http::fake(function (Request $request) use ($pages) {
            $host = (string) parse_url($request->url(), PHP_URL_HOST);
            $path = rtrim((string) parse_url($request->url(), PHP_URL_PATH), '/');
            if ($host !== 'www.highlandscoffee.com.vn') {
                return Http::response('unexpected host', 599);
            }
            if ($path === '/vn/tra-loi.html') {
                return Http::response('error', 500);
            }

            return isset($pages[$path]) ? Http::response($this->fixture($pages[$path]), 200) : Http::response('not found', 404);
        });
    }

    /** Valid Highlands hosts are recognised; look-alike and unknown hosts are not. */
    public function test_brand_is_recognised_by_exact_host_or_subdomain_only(): void
    {
        $matcher = app(BrandDomainMatcher::class);

        $this->assertSame('highlands', $matcher->brandFor('https://www.highlandscoffee.com.vn/vn/san-pham.html'));
        $this->assertSame('highlands', $matcher->brandFor('http://highlandscoffee.com.vn/vn/ca-phe.html'));
        $this->assertNull($matcher->brandFor('https://highlandscoffee.com.vn.fake-site.test/vn/san-pham.html'));
        $this->assertNull($matcher->brandFor('https://highlandscoffee.com.fake-site.test/vn/san-pham.html'));
        $this->assertNull($matcher->brandFor('https://evilhighlandscoffee.com.vn/vn/san-pham.html'));
        $this->assertNull($matcher->brandFor('https://example.com/?u=highlandscoffee.com.vn'));
        $this->assertNull($matcher->brandFor('ftp://www.highlandscoffee.com.vn/menu'));
        $this->assertNull($matcher->brandFor('https://user:pass@www.highlandscoffee.com.vn/'));
    }

    /** Spoofed and unconfigured domains are rejected before any request is sent. */
    public function test_unallowed_domains_are_rejected_without_requests(): void
    {
        Http::fake();
        $gateway = app(FoodCrawlerGateway::class);

        foreach (['https://highlandscoffee.com.vn.fake-site.test/vn/san-pham.html', 'https://not-configured-brand.test/menu'] as $url) {
            try {
                $gateway->crawl($url);
                $this->fail('Expected the crawler to reject '.$url);
            } catch (FoodCrawlerException $exception) {
                $this->assertSame(__('admin.crawler_unsupported_url'), $exception->getMessage());
            }
        }

        Http::assertNothingSent();
    }

    /** VND price texts become integers; empty or zero prices become null. */
    public function test_prices_are_normalised_to_integer_vnd(): void
    {
        $this->assertSame(45000, HighlandsCoffeeHtmlParser::price('45,000 VNĐ'));
        $this->assertSame(45000, HighlandsCoffeeHtmlParser::price('Giá: 45.000đ'));
        $this->assertSame(1250000, HighlandsCoffeeHtmlParser::price('1,250,000 VNĐ'));
        $this->assertNull(HighlandsCoffeeHtmlParser::price('Liên hệ'));
        $this->assertNull(HighlandsCoffeeHtmlParser::price('0 VNĐ'));
    }

    /** The detail parser reads only the main product (related products' prices are ignored). */
    public function test_product_detail_reads_only_the_main_product(): void
    {
        $detail = app(HighlandsCoffeeHtmlParser::class)->productDetail($this->fixture('product-bac-xiu-da.html'), self::BASE.'/vn/bac-xiu-da.html');

        $this->assertSame('Bạc Xỉu Đá', $detail['name']);
        $this->assertSame(29000, $detail['price']);
        $this->assertSame([['name' => 'S', 'price' => 29000], ['name' => 'M', 'price' => 39000], ['name' => 'L', 'price' => 45000]], $detail['sizes']);
        $this->assertSame(self::BASE.'/vnt_upload/product/01_2026/BAC_SIU_1.jpg', $detail['image_url']);
        $this->assertStringContainsString('Bạc Xỉu Đá là lựa chọn nhẹ', (string) $detail['description']);
        $this->assertStringNotContainsString('99,000', json_encode($detail, JSON_UNESCAPED_UNICODE));

        $noPrice = app(HighlandsCoffeeHtmlParser::class)->productDetail($this->fixture('product-no-price.html'), self::BASE.'/vn/tra-moi.html');
        $this->assertNull($noPrice['price'], 'The related product price (59,000) must not be used.');
    }

    /** A category page yields only the main product list, not the "other products" slider or the sidebar. */
    public function test_category_page_lists_only_main_products(): void
    {
        $products = app(HighlandsCoffeeHtmlParser::class)->categoryProducts($this->fixture('ca-phe-truyen-thong.html'), self::BASE.'/vn/ca-phe-truyen-thong.html');

        $this->assertSame(['Bạc Xỉu Đá', 'Americano'], array_column($products, 'name'));
        $this->assertSame([29000, 45000], array_column($products, 'price'));
        $this->assertSame(self::BASE.'/vnt_upload/product/thumbs/270_crop_AMERICANO.jpg', $products[1]['image_url']);
        $this->assertSame([], app(HighlandsCoffeeHtmlParser::class)->categoryProducts($this->fixture('ca-phe.html'), self::BASE.'/vn/ca-phe.html'));
    }

    /**
     * Full crawl: empty parent category skipped, duplicate URL handled once, missing price skipped,
     * a failing product request does not stop the run, and the report summarises it all.
     */
    public function test_full_crawl_builds_menu_and_report(): void
    {
        $this->fakeSite();

        $menu = app(FoodCrawlerGateway::class)->crawl('https://highlandscoffee.com.vn/vn/san-pham.html');
        $items = collect($menu->items())->keyBy('name');

        $this->assertSame('Highlands Coffee', $menu->restaurantName);
        $this->assertSame(['Bạc Xỉu Đá', 'Americano', 'Trà Sen Vàng', 'Trà Lỗi'], $items->keys()->all());

        $bacXiu = $items['Bạc Xỉu Đá'];
        $this->assertSame('Cà Phê Truyền Thống', $bacXiu['category'], 'A product keeps the first category it was found in.');
        $this->assertSame(29000, $bacXiu['base_price']);
        $this->assertSame([
            ['name' => 'S', 'price_delta' => 0],
            ['name' => 'M', 'price_delta' => 10000],
            ['name' => 'L', 'price_delta' => 16000],
        ], $bacXiu['options']);
        $this->assertSame(self::BASE.'/vn/bac-xiu-da.html', $bacXiu['source_url']);
        $this->assertSame(self::BASE.'/vnt_upload/product/01_2026/BAC_SIU_1.jpg', $bacXiu['image_url']);

        $this->assertSame([], $items['Americano']['options']);
        $this->assertSame('Trà', $items['Trà Sen Vàng']['category']);
        // Detail request failed: the category card data (price 39.000đ) is kept.
        $this->assertSame(39000, $items['Trà Lỗi']['base_price']);
        $this->assertFalse($items->has('Trà Mới'), 'A product without any price is not imported with price 0.');

        $report = $menu->report;
        $this->assertSame(3, $report['categories_found']);
        $this->assertSame(2, $report['categories_with_items']);
        $this->assertSame(['Cà Phê'], $report['categories_skipped_empty']);
        $this->assertSame(6, $report['products_found']);
        $this->assertSame(4, $report['products_new']);
        $this->assertSame(1, $report['products_duplicate']);
        $this->assertSame(1, $report['products_failed']);
        $failed = collect($report['errors'])->pluck('url')->all();
        $this->assertContains(self::BASE.'/vn/tra-loi.html', $failed);
        $this->assertContains(self::BASE.'/vn/tra-moi.html', $failed);

        // The duplicate product page is requested once; the fake host link is never requested.
        Http::assertSentCount(1 + 3 + 5 + 2);
        $this->assertCount(1, Http::recorded(fn (Request $request): bool => str_contains($request->url(), '/vn/bac-xiu-da.html')));
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'fake-site.test'));
    }

    /** The admin preview endpoint returns the Highlands menu, brand name and crawl report. */
    public function test_admin_preview_returns_items_and_report(): void
    {
        $this->fakeSite();
        $admin = Admin::create(['name' => 'Crawler Admin', 'email' => 'crawler-admin@example.test', 'password' => Hash::make('secret'), 'status' => 'active']);
        $room = Room::create(['name' => 'Crawler Room', 'slug' => 'crawler-room', 'status' => 'active']);
        $admin->rooms()->attach($room);

        $this->actingAs($admin, 'admin')
            ->postJson(route('admin.crawler.preview', $room), ['url' => self::BASE.'/vn/san-pham.html'])
            ->assertCreated()
            ->assertJsonPath('data.provider', 'highlands')
            ->assertJsonPath('data.restaurant_name', 'Highlands Coffee')
            ->assertJsonPath('data.report.products_new', 4)
            ->assertJsonCount(4, 'data.items');

        $this->actingAs($admin, 'admin')
            ->postJson(route('admin.crawler.preview', $room), ['url' => 'https://highlandscoffee.com.vn.fake-site.test/vn/san-pham.html'])
            ->assertStatus(422)
            ->assertJsonPath('message', __('admin.crawler_unsupported_url'));
    }

    /** A bot-protection 403 is reported as blocked, not retried or bypassed. */
    public function test_blocked_source_is_reported_without_retry(): void
    {
        Http::fake(['*' => Http::response('<title>Attention Required! | Cloudflare</title>', 403)]);

        try {
            app(FoodCrawlerGateway::class)->crawl(self::BASE.'/vn/san-pham.html');
            $this->fail('Expected a blocked crawl.');
        } catch (FoodCrawlerException $exception) {
            $this->assertStringContainsString(__('admin.crawler_access_blocked', ['status' => 403]), $exception->getMessage());
        }

        Http::assertSentCount(1);
    }
}

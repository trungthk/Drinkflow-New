<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CampaignStatus;
use App\Models\Admin;
use App\Models\Campaign;
use App\Models\Room;
use App\Services\DataGateway\DataGatewayConverterService;
use App\Services\DataGateway\InternalMenuAnalyzerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataGatewayInternalAnalyzeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * GrabFood: virtual categories do not duplicate items, optional groups become toppings, the size group
     * becomes options, ice/sugar and zero-price choice groups are ignored, unavailable items are skipped.
     *
     * @return void
     */
    public function test_grab_menu_is_converted_without_ai_agent(): void
    {
        $result = app(InternalMenuAnalyzerService::class)->analyze($this->grabPayload(), DataGatewayConverterService::PLATFORM_GRAB);

        $this->assertSame('grab', $result['platform']);
        $this->assertFalse($result['platform_mismatch']);
        $this->assertSame('Rau Má Mix', $result['restaurant']);
        $this->assertSame(['Rau Má Sữa Dừa', 'Nước ép cần tây'], array_column($result['items'], 'name'));
        $this->assertSame(1, $result['summary']['skipped_duplicates']);
        $this->assertSame(1, $result['summary']['skipped_unavailable']);

        $rauMa = $result['items'][0];
        $this->assertSame(24000, $rauMa['price']);
        $this->assertSame('RAU MÁ MIX', $rauMa['category'], 'The item keeps its real category, not the virtual one.');
        $this->assertSame([['name' => 'Size Nhỏ', 'price_delta' => 0], ['name' => 'Size Lớn', 'price_delta' => 6000]], $rauMa['options']);
        $this->assertSame([['name' => 'Trân châu', 'price' => 5000]], $rauMa['toppings'], 'Unavailable topping choices are dropped.');
        $this->assertSame(['CHỌN LƯỢNG ĐÁ', 'SỮA DỪA'], $result['summary']['ignored_groups']);

        // The discounted price is the selling price.
        $this->assertSame(17500, $result['items'][1]['price']);
        $this->assertSame('https://food.example/celery.jpg', $result['items'][1]['image_url']);
    }

    /**
     * ShopeeFood: size prices are deltas, but a "half pizza" group lists total prices, so the cheapest
     * choice becomes the item price and every choice a delta on top of it.
     *
     * @return void
     */
    public function test_shopee_menu_is_converted_with_delta_and_total_option_prices(): void
    {
        $result = app(InternalMenuAnalyzerService::class)->analyze($this->shopeePayload());

        $this->assertSame('shopee', $result['platform']);
        $this->assertSame(['Pizza Four Cheese', 'Half Seafood Pizza'], array_column($result['items'], 'name'));
        $this->assertSame(1, $result['summary']['skipped_unavailable']);

        $pizza = $result['items'][0];
        $this->assertSame(199000, $pizza['price']);
        $this->assertSame([['name' => 'Medium Size', 'price_delta' => 0], ['name' => 'Large Size', 'price_delta' => 40000]], $pizza['options']);
        $this->assertSame([['name' => 'Nấm', 'price' => 15000]], $pizza['toppings']);
        $this->assertSame('https://img.example/pizza-640.jpg', $pizza['image_url'], 'The largest photo is used.');

        $half = $result['items'][1];
        $this->assertSame(104500, $half['price']);
        $this->assertSame([
            ['name' => '1/2 Four Cheese', 'price_delta' => 10000],
            ['name' => '1/2 Veggie', 'price_delta' => 0],
            ['name' => '1/2 Seafood', 'price_delta' => 25000],
        ], $half['options']);
    }

    /**
     * The endpoint detects the platform from the JSON, returns the menu and summary, and rejects
     * payloads that are not a ShopeeFood / GrabFood menu.
     *
     * @return void
     */
    public function test_admin_analyze_endpoint_returns_menu_and_rejects_unknown_structure(): void
    {
        [$admin, $room] = $this->adminWithRoom();
        $url = route('admin.data-gateway.analyze', $room);

        $this->actingAs($admin, 'admin')
            ->postJson($url, ['platform' => 'grab', 'origin_json' => json_encode($this->shopeePayload())])
            ->assertOk()
            ->assertJsonPath('data.platform', 'shopee')
            ->assertJsonPath('data.platform_mismatch', true)
            ->assertJsonPath('data.summary.item_count', 2)
            ->assertJsonPath('data.items.0.name', 'Pizza Four Cheese');

        $this->actingAs($admin, 'admin')
            ->postJson($url, ['origin_json' => json_encode(['foo' => 'bar'])])
            ->assertStatus(422)
            ->assertJsonPath('message', __('admin.data_gateway_err_unknown_structure'));

        $this->actingAs($admin, 'admin')
            ->postJson($url, ['origin_json' => 'not json'])
            ->assertStatus(422);

        $this->app['auth']->forgetGuards();
        $this->postJson($url, ['origin_json' => json_encode($this->shopeePayload())])->assertStatus(401);
    }

    /**
     * The create and edit pages keep the AI prompt buttons and add the internal analysis button.
     *
     * @return void
     */
    public function test_campaign_pages_show_internal_analyze_button_next_to_ai_prompt(): void
    {
        [$admin, $room] = $this->adminWithRoom();
        $campaign = Campaign::create(['room_id' => $room->id, 'name' => 'Draft', 'restaurant' => 'Cafe', 'status' => CampaignStatus::Draft]);

        foreach ([route('admin.campaigns.create', $room), route('admin.campaigns.edit', [$room, $campaign])] as $page) {
            $this->actingAs($admin, 'admin')->get($page)
                ->assertOk()
                ->assertSee(__('admin.data_gateway_btn_internal_analyze'))
                ->assertSee('analyzeInternally()', false)
                ->assertSee('data-analyze-url="'.route('admin.data-gateway.analyze', $room).'"', false)
                ->assertSee(__('admin.data_gateway_btn_copy_prompt'))
                ->assertSee('copyPromptAndOpenAgent()', false);
        }
    }

    /**
     * Create an active admin attached to an active room.
     *
     * @return array{0: Admin, 1: Room}
     */
    private function adminWithRoom(): array
    {
        $admin = Admin::create(['name' => 'Analyzer Admin', 'email' => 'analyzer-admin@example.test', 'password' => 'secret-password', 'status' => 'active']);
        $room = Room::create(['name' => 'Analyzer Room', 'slug' => 'analyzer-room', 'status' => 'active']);
        $admin->rooms()->attach($room);

        return [$admin, $room];
    }

    /**
     * Minimal GrabFood merchant response mirroring the real structure.
     *
     * @return array<string, mixed>
     */
    private function grabPayload(): array
    {
        $rauMa = [
            'ID' => 'ITEM-1', 'name' => 'Rau Má Sữa Dừa', 'available' => true, 'priceInMinorUnit' => 24000, 'discountedPriceInMin' => 24000,
            'description' => 'Rau má  xay', 'imgHref' => 'https://food.example/rau-ma.jpg',
            'modifierGroups' => [
                ['name' => 'Chọn size', 'available' => true, 'selectionRangeMin' => 1, 'selectionRangeMax' => 1, 'modifiers' => [
                    ['name' => 'Size Nhỏ', 'available' => true],
                    ['name' => 'Size Lớn', 'available' => true, 'priceInMinorUnit' => 6000],
                ]],
                ['name' => 'CHỌN LƯỢNG ĐÁ', 'available' => true, 'selectionRangeMin' => 1, 'selectionRangeMax' => 1, 'modifiers' => [
                    ['name' => 'Ít Đá', 'available' => true],
                ]],
                ['name' => 'SỮA DỪA', 'available' => true, 'selectionRangeMin' => 1, 'selectionRangeMax' => 1, 'modifiers' => [
                    ['name' => 'Có', 'available' => true], ['name' => 'Không', 'available' => true],
                ]],
                ['name' => 'NGON HƠN KHI THÊM TOPPING', 'available' => true, 'selectionRangeMax' => 7, 'modifiers' => [
                    ['name' => 'Trân châu', 'available' => true, 'priceInMinorUnit' => 5000],
                    ['name' => 'Thạch hết hàng', 'available' => false, 'priceInMinorUnit' => 5000],
                ]],
            ],
        ];

        return ['merchant' => ['name' => 'Rau Má Mix', 'menu' => ['categories' => [
            // Virtual category listed first in the real response, repeating an item of a real category.
            ['name' => 'Dành cho bạn', 'categoryType' => 2, 'items' => [$rauMa]],
            ['name' => 'RAU MÁ MIX', 'items' => [$rauMa]],
            ['name' => 'NƯỚC ÉP', 'items' => [
                ['ID' => 'ITEM-2', 'name' => 'Nước ép cần tây', 'available' => true, 'priceInMinorUnit' => 35000, 'discountedPriceInMin' => 17500, 'images' => ['https://food.example/celery.jpg']],
                ['ID' => 'ITEM-3', 'name' => 'Món hết hàng', 'available' => false, 'priceInMinorUnit' => 20000],
            ]],
        ]]]];
    }

    /**
     * Minimal ShopeeFood get_delivery_dishes response mirroring the real structure.
     *
     * @return array<string, mixed>
     */
    private function shopeePayload(): array
    {
        $choice = static fn (string $name, int $price): array => ['name' => $name, 'price' => ['value' => $price]];

        return ['result' => 'success', 'reply' => ['menu_infos' => [
            ['dish_type_name' => 'Pizza', 'dishes' => [
                [
                    'id' => 1, 'name' => 'Pizza Four Cheese', 'price' => ['value' => 199000], 'is_available' => true, 'is_active' => true, 'is_deleted' => false,
                    'photos' => [['width' => 120, 'value' => 'https://img.example/pizza-120.jpg'], ['width' => 640, 'value' => 'https://img.example/pizza-640.jpg']],
                    'options' => [
                        ['name' => 'Chọn Size', 'mandatory' => true, 'option_items' => ['min_select' => 1, 'max_select' => 1, 'items' => [$choice('Medium Size', 0), $choice('Large Size', 40000)]]],
                        ['name' => 'Topping Extra ', 'mandatory' => false, 'option_items' => ['min_select' => 0, 'max_select' => 9, 'items' => [$choice('Nấm', 15000)]]],
                    ],
                ],
                ['id' => 2, 'name' => 'Pizza tạm ngưng', 'price' => ['value' => 150000], 'is_available' => false, 'is_active' => true],
            ]],
            ['dish_type_name' => 'Half Pizza', 'dishes' => [
                [
                    'id' => 3, 'name' => 'Half Seafood Pizza', 'price' => ['value' => 129500], 'is_available' => true, 'is_active' => true,
                    'options' => [
                        ['name' => 'Half 1/2 Pizza ', 'mandatory' => true, 'option_items' => ['min_select' => 1, 'max_select' => 1, 'items' => [
                            $choice('1/2 Four Cheese', 114500), $choice('1/2 Veggie', 104500), $choice('1/2 Seafood', 129500),
                        ]]],
                    ],
                ],
            ]],
        ]]];
    }
}

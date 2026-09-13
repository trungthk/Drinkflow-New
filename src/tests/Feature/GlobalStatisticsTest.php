<?php

namespace Tests\Feature;

use App\Actions\User\JoinRoomAction;
use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\GlobalUser;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalStatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_accessing_statistics_redirects_to_login(): void
    {
        $response = $this->get('/me/statistics');
        $response->assertRedirect(route('auth.google'));
    }

    public function test_authenticated_user_can_view_statistics_page(): void
    {
        $user = GlobalUser::create([
            'name' => 'Trung Lê',
            'normalized_name' => 'TRUNG LE',
            'email' => 'trung.lt@company.com',
            'status' => 'active',
        ]);

        $room = Room::create(['name' => 'Team Kỹ thuật HN', 'slug' => 'tech-hn']);
        $roomUser = app(JoinRoomAction::class)->execute($user, $room, 'device-1', 'hash-1');

        $campaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Trà chiều',
            'restaurant' => 'Phúc Long Coffee & Tea',
            'status' => CampaignStatus::Active,
        ]);

        $order = $roomUser->orders()->create([
            'room_id' => $room->id,
            'campaign_id' => $campaign->id,
            'subtotal' => 90000,
            'sponsor_amount' => 30000,
            'final_amount' => 60000,
            'status' => 'completed',
        ]);

        $order->items()->create([
            'item_name' => 'Trà sữa Oolong',
            'quantity' => 2,
            'unit_price' => 45000,
            'line_subtotal' => 90000,
        ]);

        $response = $this->actingAs($user, 'web')->get('/me/statistics');

        $response->assertOk();
        $response->assertSee('Báo cáo &amp; Thống kê chi tiêu cá nhân', false);
        $response->assertSee('Tổng đơn đã đặt');
        $response->assertSee('Tổng chi tiêu cá nhân');
        $response->assertSee('Tổng tài trợ (Sponsor)');
        $response->assertSee('Món &amp; Topping ưa chuộng', false);
        $response->assertSee('Biểu đồ xu hướng chi tiêu theo tuần');
        $response->assertSee('Top Quán &amp; Cửa hàng đặt nhiều nhất', false);
        $response->assertSee('Chi tiêu theo từng Room / Phòng ban');
    }

    public function test_analytics_json_api_still_returns_json(): void
    {
        $user = GlobalUser::create([
            'name' => 'Trung Lê',
            'normalized_name' => 'TRUNG LE',
            'email' => 'trung.lt@company.com',
            'status' => 'active',
        ]);

        $room = Room::create(['name' => 'Team Kỹ thuật HN', 'slug' => 'tech-hn']);
        app(JoinRoomAction::class)->execute($user, $room, 'device-1', 'hash-1');

        $response = $this->actingAs($user, 'web')->getJson('/analytics');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'total_orders',
                'total_cups',
                'total_amount',
                'sponsor_received',
                'top_items',
            ],
        ]);
    }
}

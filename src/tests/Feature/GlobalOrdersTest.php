<?php

namespace Tests\Feature;

use App\Actions\User\JoinRoomAction;
use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\CampaignItem;
use App\Models\GlobalUser;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_accessing_me_orders_redirects_to_login(): void
    {
        $response = $this->get('/me/orders');
        $response->assertRedirect(route('auth.google'));
    }

    public function test_authenticated_user_can_view_me_orders_page(): void
    {
        $user = GlobalUser::create([
            'name' => 'Trung Lê',
            'normalized_name' => 'TRUNG LE',
            'email' => 'trung.lt@company.com',
        ]);

        $room = Room::create(['name' => 'Team Kỹ thuật HN', 'slug' => 'tech-hn']);
        $roomUser = app(JoinRoomAction::class)->execute($user, $room, 'device', 'hash');

        $campaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Trà chiều',
            'restaurant' => 'Phúc Long Coffee & Tea',
            'status' => CampaignStatus::Active,
        ]);

        $order = $roomUser->orders()->create([
            'room_id' => $room->id,
            'campaign_id' => $campaign->id,
            'subtotal' => 85000,
            'sponsor_amount' => 30000,
            'final_amount' => 55000,
            'status' => 'completed',
        ]);

        $order->items()->create([
            'item_name' => 'Trà sữa Phúc Long',
            'size_name' => 'L',
            'unit_price' => 65000,
            'quantity' => 1,
            'ice_percent' => 70,
            'sugar_percent' => 50,
            'line_subtotal' => 65000,
        ]);

        $response = $this->actingAs($user, 'web')->get('/me/orders');

        $response->assertOk();
        $response->assertSee('Lịch sử Order');
        $response->assertSee('#ORD-' . $order->id);
        $response->assertSee('Phúc Long Coffee & Tea');
        $response->assertSee('55.000đ');
        $response->assertSee('Đã thanh toán');
    }

    public function test_filtering_me_orders_by_status(): void
    {
        $user = GlobalUser::create([
            'name' => 'Tester',
            'normalized_name' => 'TESTER',
            'email' => 'tester@company.com',
        ]);

        $room = Room::create(['name' => 'Room A', 'slug' => 'room-a']);
        $roomUser = app(JoinRoomAction::class)->execute($user, $room, 'device', 'hash');
        $campaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Test',
            'restaurant' => 'Highlands',
            'status' => CampaignStatus::Active,
        ]);

        $paidOrder = $roomUser->orders()->create([
            'room_id' => $room->id,
            'campaign_id' => $campaign->id,
            'subtotal' => 20000,
            'final_amount' => 20000,
            'status' => 'completed',
        ]);

        $unpaidOrder = $roomUser->orders()->create([
            'room_id' => $room->id,
            'campaign_id' => $campaign->id,
            'subtotal' => 30000,
            'final_amount' => 30000,
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($user, 'web')->get('/me/orders?status=paid');
        $response->assertOk();
        $response->assertSee('#ORD-' . $paidOrder->id);
        $response->assertDontSee('#ORD-' . $unpaidOrder->id);
    }
}

<?php

namespace Tests\Feature;

use App\Actions\User\JoinRoomAction;
use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\GlobalUser;
use App\Models\PaymentAccount;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalPaymentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_accessing_me_payments_redirects_to_login(): void
    {
        $response = $this->get('/me/payments');
        $response->assertRedirect('/');

        $responseWithReferer = $this->from('/contact')->get('/me/payments');
        $responseWithReferer->assertRedirect('/contact');
    }

    public function test_authenticated_user_can_view_me_payments_page(): void
    {
        $user = GlobalUser::create([
            'name' => 'Trung Lê',
            'normalized_name' => 'TRUNG LE',
            'email' => 'trung.lt@company.com',
        ]);

        $room = Room::create(['name' => 'Design & UX', 'slug' => 'design-ux']);
        $roomUser = app(JoinRoomAction::class)->execute($user, $room, 'device', 'hash');

        $campaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Cà phê sáng',
            'restaurant' => 'Phê La - Cầu Giấy',
            'sponsor_name' => 'Nguyễn Văn An',
            'status' => CampaignStatus::Active,
        ]);

        $order = $roomUser->orders()->create([
            'room_id' => $room->id,
            'campaign_id' => $campaign->id,
            'subtotal' => 110000,
            'final_amount' => 110000,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($user, 'web')->get('/me/payments');

        $response->assertOk();
        $response->assertSee('Quản lý Thanh toán &amp; Đối soát', false);
        $response->assertSee('110.000');
        $response->assertSee($order->code);
        $response->assertSee('Phê La - Cầu Giấy');
    }

    public function test_me_payments_returns_json_when_requested(): void
    {
        $user = GlobalUser::create([
            'name' => 'User JSON',
            'normalized_name' => 'USER JSON',
            'email' => 'json@company.com',
        ]);

        $room = Room::create(['name' => 'Room B', 'slug' => 'room-b']);
        $roomUser = app(JoinRoomAction::class)->execute($user, $room, 'device', 'hash');
        $campaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Test',
            'restaurant' => 'Highlands',
            'status' => CampaignStatus::Active,
        ]);

        $roomUser->orders()->create([
            'room_id' => $room->id,
            'campaign_id' => $campaign->id,
            'subtotal' => 60000,
            'final_amount' => 60000,
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($user, 'web')
            ->getJson('/me/payments');

        $response->assertOk();
        $response->assertJsonPath('metrics.total_unpaid', 60000);
        $response->assertJsonPath('metrics.unpaid_count', 1);
    }
}

<?php

namespace Tests\Feature;

use App\Actions\User\JoinRoomAction;
use App\Enums\CampaignStatus;
use App\Enums\OrderStatus;
use App\Models\Campaign;
use App\Models\CampaignItem;
use App\Models\GlobalUser;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_accessing_me_redirects_to_home_or_referer(): void
    {
        $response = $this->get('/me');
        $response->assertRedirect('/');

        $refererResponse = $this->from(url('/contact'))->get('/me');
        $refererResponse->assertRedirect(url('/contact'));
    }

    public function test_authenticated_user_without_rooms_does_not_see_stats_or_recent_sections(): void
    {
        $user = GlobalUser::create([
            'name' => 'Trung Lê',
            'normalized_name' => 'TRUNG LE',
            'email' => 'trung.lt@company.com',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'web')->get('/me');

        $response->assertOk();
        $response->assertSee('Xin chào, Trung Lê');
        $response->assertSee('trung.lt@company.com');
        $response->assertSee('Xác thực Google Workspace @company.com');
        $response->assertDontSee(__('global.dashboard.onboarding_title'));
        $response->assertDontSee(__('global.dashboard.onboarding_desc'));
        $response->assertSee(__('global.dashboard.join_by_url'));

        // Verify stats, recent rooms, and recent orders are hidden
        $response->assertDontSee('Room đã tham gia');
        $response->assertDontSee('Tổng số đơn');
        $response->assertDontSee('Tổng chi tiêu');
        $response->assertDontSee('Tài trợ đã nhận (Sponsor)');
        $response->assertDontSee('Room gần đây');
        $response->assertDontSee('Đơn hàng gần đây');
    }

    public function test_authenticated_user_with_rooms_sees_stats_and_recent_sections(): void
    {
        $user = GlobalUser::create([
            'name' => 'Hải Đăng',
            'normalized_name' => 'HAI DANG',
            'email' => 'dang.nh@company.com',
            'status' => 'active',
        ]);

        $room = Room::create(['name' => 'Room Mobile', 'slug' => 'room-mobile']);
        app(JoinRoomAction::class)->execute($user, $room, 'dev-mob', 'hash-mob');

        $response = $this->actingAs($user, 'web')->get('/me');

        $response->assertOk();
        $response->assertSee('Xin chào, Hải Đăng');
        $response->assertSee('Room đã tham gia');
        $response->assertSee('Tổng số đơn');
        $response->assertSee('Tổng chi tiêu');
        $response->assertSee('Tài trợ đã nhận (Sponsor)');
        $response->assertSee('Room gần đây');
        $response->assertSee('Đơn hàng gần đây');
        $response->assertSee('Room Mobile');
        $response->assertDontSee('Bắt đầu trải nghiệm đặt món cùng đồng nghiệp');
    }

    public function test_dashboard_isolates_user_rooms_and_orders(): void
    {
        $userA = GlobalUser::create([
            'name' => 'User A',
            'normalized_name' => 'USER A',
            'email' => 'usera@company.com',
            'status' => 'active',
        ]);
        $userB = GlobalUser::create([
            'name' => 'User B',
            'normalized_name' => 'USER B',
            'email' => 'userb@company.com',
            'status' => 'active',
        ]);

        $roomA = Room::create(['name' => 'Room Team A', 'slug' => 'room-team-a']);
        $roomB = Room::create(['name' => 'Room Team B', 'slug' => 'room-team-b']);

        $roomUserA = app(JoinRoomAction::class)->execute($userA, $roomA, 'dev-a', 'hash-a');
        $roomUserB = app(JoinRoomAction::class)->execute($userB, $roomB, 'dev-b', 'hash-b');

        $campaignA = Campaign::create([
            'room_id' => $roomA->id,
            'name' => 'Campaign A',
            'restaurant' => 'Phúc Long A',
            'status' => CampaignStatus::Active,
        ]);
        $campaignB = Campaign::create([
            'room_id' => $roomB->id,
            'name' => 'Campaign B',
            'restaurant' => 'Highlands B',
            'status' => CampaignStatus::Active,
        ]);

        $orderA = Order::create([
            'room_id' => $roomA->id,
            'campaign_id' => $campaignA->id,
            'room_user_id' => $roomUserA->id,
            'subtotal' => 50000,
            'final_amount' => 50000,
            'sponsor_amount' => 10000,
            'status' => OrderStatus::Completed,
        ]);
        OrderItem::create([
            'order_id' => $orderA->id,
            'item_name' => 'Trà sen vàng',
            'unit_price' => 50000,
            'quantity' => 1,
            'line_subtotal' => 50000,
        ]);

        $orderB = Order::create([
            'room_id' => $roomB->id,
            'campaign_id' => $campaignB->id,
            'room_user_id' => $roomUserB->id,
            'subtotal' => 90000,
            'final_amount' => 90000,
            'sponsor_amount' => 0,
            'status' => OrderStatus::Completed,
        ]);
        OrderItem::create([
            'order_id' => $orderB->id,
            'item_name' => 'Cà phê phin B',
            'unit_price' => 90000,
            'quantity' => 2,
            'line_subtotal' => 90000,
        ]);

        // Access as User A
        $responseA = $this->actingAs($userA, 'web')->get('/me');
        $responseA->assertOk();
        $responseA->assertSee('Room Team A');
        $responseA->assertSee('Phúc Long A');
        $responseA->assertSee('Trà sen vàng');
        $responseA->assertDontSee('Room Team B');
        $responseA->assertDontSee('Highlands B');
        $responseA->assertDontSee('Cà phê phin B');

        // Access as User B
        $responseB = $this->actingAs($userB, 'web')->get('/me');
        $responseB->assertOk();
        $responseB->assertSee('Room Team B');
        $responseB->assertSee('Highlands B');
        $responseB->assertSee('Cà phê phin B');
        $responseB->assertDontSee('Room Team A');
        $responseB->assertDontSee('Phúc Long A');
        $responseB->assertDontSee('Trà sen vàng');
    }

    public function test_dashboard_displays_active_campaign_details_for_user_room(): void
    {
        $user = GlobalUser::create([
            'name' => 'Kỹ Thuật',
            'normalized_name' => 'KY THUAT',
            'email' => 'tech@company.com',
            'status' => 'active',
        ]);
        $room = Room::create(['name' => 'Phòng Công Nghệ', 'slug' => 'phong-cong-nghe']);
        app(JoinRoomAction::class)->execute($user, $room, 'dev-1', 'hash-1');

        $campaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Trà Chiều Thứ 6',
            'restaurant' => 'KOI Thé',
            'status' => CampaignStatus::Active,
            'deadline' => now()->addHours(2),
            'discount' => 15000,
        ]);

        $item = CampaignItem::create([
            'campaign_id' => $campaign->id,
            'name' => 'Lục trà macchiato',
            'normalized_name' => 'LUC TRA MACCHIATO',
            'base_price' => 45000,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'web')->get('/me');
        $response->assertOk();
        $response->assertSee('Phòng Công Nghệ');
        $response->assertSee('KOI Thé');
        $response->assertSee('Chiến dịch đang mở');
        $response->assertSee('Order ngay');
        $response->assertSee(__('global.dashboard.room_live'));
        $response->assertSee(__('global.dashboard.room_order_deadline', [
            'time' => $campaign->deadline->format('d/m/Y H:i'),
        ]));
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = GlobalUser::create([
            'name' => 'User Logout',
            'normalized_name' => 'USER LOGOUT',
            'email' => 'logout@company.com',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'web')->post('/logout');
        $response->assertRedirect('/');
        $this->assertGuest('web');
    }
}

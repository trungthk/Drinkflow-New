<?php

namespace Tests\Feature;

use App\Enums\CampaignStatus;
use App\Enums\OrderStatus;
use App\Models\Campaign;
use App\Models\GlobalUser;
use App\Models\Order;
use App\Models\Room;
use App\Models\RoomUser;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_guest_accessing_me_rooms_redirects_to_login(): void
    {
        $response = $this->get('/me/rooms');
        $response->assertRedirect('/');

        $responseWithReferer = $this->from('/contact')->get('/me/rooms');
        $responseWithReferer->assertRedirect('/contact');
    }

    public function test_user_without_rooms_accessing_me_rooms_redirects_to_dashboard(): void
    {
        $user = GlobalUser::create([
            'name' => 'User Without Rooms',
            'normalized_name' => 'USER WITHOUT ROOMS',
            'email' => 'noroom@company.com',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'web')->get('/me/rooms');
        $response->assertRedirect(route('user.me.dashboard'));
    }

    public function test_user_without_rooms_accessing_me_orders_redirects_to_dashboard(): void
    {
        $user = GlobalUser::create([
            'name' => 'User Without Rooms',
            'normalized_name' => 'USER WITHOUT ROOMS',
            'email' => 'noroom@company.com',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'web')->get('/me/orders');
        $response->assertRedirect(route('user.me.dashboard'));
    }

    public function test_user_without_rooms_accessing_me_payments_redirects_to_dashboard(): void
    {
        $user = GlobalUser::create([
            'name' => 'User Without Rooms',
            'normalized_name' => 'USER WITHOUT ROOMS',
            'email' => 'noroom@company.com',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'web')->get('/me/payments');
        $response->assertRedirect(route('user.me.dashboard'));
    }

    public function test_user_without_rooms_accessing_me_statistics_redirects_to_dashboard(): void
    {
        $user = GlobalUser::create([
            'name' => 'User Without Rooms',
            'normalized_name' => 'USER WITHOUT ROOMS',
            'email' => 'noroom@company.com',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'web')->get('/me/statistics');
        $response->assertRedirect(route('user.me.dashboard'));
    }

    public function test_user_without_rooms_requesting_json_me_rooms_returns_forbidden(): void
    {
        $user = GlobalUser::create([
            'name' => 'User Without Rooms',
            'normalized_name' => 'USER WITHOUT ROOMS',
            'email' => 'noroom@company.com',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'web')->getJson('/me/rooms');
        $response->assertForbidden();
    }

    public function test_authenticated_user_can_view_me_rooms_page(): void
    {
        $user = GlobalUser::create([
            'name' => 'Trung Lê',
            'normalized_name' => 'TRUNG LE',
            'email' => 'trung.lt@company.com',
            'status' => 'active',
        ]);

        $room = Room::create([
            'name' => 'Team Kỹ thuật & Hạ tầng',
            'slug' => 'tech-infra-88',
            'status' => 'active',
        ]);

        $roomUser = RoomUser::create([
            'room_id' => $room->id,
            'global_user_id' => $user->id,
            'user_code' => 'TECH88',
            'display_name' => 'Trung Lê',
            'normalized_name' => 'TRUNG LE',
            'status' => 'active',
            'joined_at' => now()->subDays(10),
            'last_active_at' => now()->subHours(2),
        ]);

        $campaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Trà sữa chiều',
            'restaurant' => 'Phúc Long',
            'status' => CampaignStatus::Active->value,
            'deadline' => now()->addHour(),
        ]);

        Order::create([
            'room_id' => $room->id,
            'campaign_id' => $campaign->id,
            'room_user_id' => $roomUser->id,
            'status' => OrderStatus::Completed->value,
            'subtotal' => 50000,
            'final_amount' => 50000,
        ]);

        $response = $this->actingAs($user, 'web')->get('/me/rooms');

        $response->assertOk();
        $response->assertSee('Room của tôi');
        $response->assertSee('Team Kỹ thuật &amp; Hạ tầng', false);
        $response->assertSee('ROOM-ID: TECH-INFRA-88');
        $response->assertSee(__('global.dashboard.room_live'));
        $response->assertSee(__('global.dashboard.room_order_deadline', [
            'time' => $campaign->deadline->format('d/m/Y H:i'),
        ]));
        $response->assertSee('1 đơn');
        $response->assertSee('50.000đ');
        $response->assertSee('Vào Room');
        $response->assertSee('Bạn muốn tham gia Room mới?');
    }

    public function test_header_hides_specific_tabs_when_user_has_no_rooms(): void
    {
        $user = GlobalUser::create([
            'name' => 'User Without Rooms',
            'normalized_name' => 'USER WITHOUT ROOMS',
            'email' => 'noroom@company.com',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'web')->get('/me');
        $response->assertOk();

        $content = $response->getContent();
        $navContent = (string) str($content)->after('aria-label="Điều hướng chính">')->before('</nav>');

        $this->assertStringNotContainsString(route('user.me.rooms'), $navContent);
        $this->assertStringNotContainsString(route('user.me.orders'), $navContent);
        $this->assertStringNotContainsString(route('user.me.payments'), $navContent);
        $this->assertStringNotContainsString(route('user.me.statistics'), $navContent);
        $this->assertStringContainsString(route('user.me.dashboard'), $navContent);
        $this->assertStringContainsString(route('user.me.feedback'), $navContent);
    }

    public function test_header_shows_all_tabs_when_user_has_rooms(): void
    {
        $user = GlobalUser::create([
            'name' => 'User With Rooms',
            'normalized_name' => 'USER WITH ROOMS',
            'email' => 'hasroom@company.com',
            'status' => 'active',
        ]);

        $room = Room::create([
            'name' => 'General Room',
            'slug' => 'general-room',
            'status' => 'active',
        ]);

        $roomUser = RoomUser::create([
            'room_id' => $room->id,
            'global_user_id' => $user->id,
            'user_code' => 'GEN1',
            'display_name' => 'User With Rooms',
            'normalized_name' => 'USER WITH ROOMS',
            'status' => 'active',
        ]);

        $campaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Trà sữa chiều',
            'restaurant' => 'Phúc Long',
            'status' => CampaignStatus::Closed->value,
        ]);

        Order::create([
            'room_id' => $room->id,
            'campaign_id' => $campaign->id,
            'room_user_id' => $roomUser->id,
            'status' => OrderStatus::Completed->value,
            'subtotal' => 50000,
            'final_amount' => 50000,
        ]);

        $response = $this->actingAs($user, 'web')->get('/me');
        $response->assertOk();

        $content = $response->getContent();
        $navContent = (string) str($content)->after('aria-label="Điều hướng chính">')->before('</nav>');

        $this->assertStringContainsString(route('user.me.rooms'), $navContent);
        $this->assertStringContainsString(route('user.me.orders'), $navContent);
        $this->assertStringContainsString(route('user.me.payments'), $navContent);
        $this->assertStringContainsString(route('user.me.statistics'), $navContent);
        $this->assertStringContainsString(route('user.me.dashboard'), $navContent);
        $this->assertStringContainsString(route('user.me.feedback'), $navContent);
    }

    public function test_restricted_rooms_render_correct_blocked_card(): void
    {
        $user = GlobalUser::create([
            'name' => 'Trung Lê',
            'normalized_name' => 'TRUNG LE',
            'email' => 'trung.lt@company.com',
            'status' => 'active',
        ]);

        $room = Room::create([
            'name' => 'Dự án Marketing Q3',
            'slug' => 'mkt-q3-archived',
            'status' => 'active',
        ]);

        RoomUser::create([
            'room_id' => $room->id,
            'global_user_id' => $user->id,
            'user_code' => 'MKT99',
            'display_name' => 'Trung Lê',
            'normalized_name' => 'TRUNG LE',
            'status' => 'blocked',
            'joined_at' => now()->subMonths(3),
        ]);

        $response = $this->actingAs($user, 'web')->get('/me/rooms');

        $response->assertOk();
        $response->assertSee('Dự án Marketing Q3');
        $response->assertSee('Bị hạn chế');
        $response->assertSee('Bạn đã bị hạn chế truy cập Room này do chuyển bộ phận');
        $response->assertSee('Truy cập đã khóa');
    }

    public function test_rooms_filtering_by_status(): void
    {
        $user = GlobalUser::create([
            'name' => 'Trung Lê',
            'normalized_name' => 'TRUNG LE',
            'email' => 'trung.lt@company.com',
            'status' => 'active',
        ]);

        $activeRoom = Room::create(['name' => 'Active Room', 'slug' => 'active-room', 'status' => 'active']);
        $blockedRoom = Room::create(['name' => 'Blocked Room', 'slug' => 'blocked-room', 'status' => 'active']);

        RoomUser::create([
            'room_id' => $activeRoom->id,
            'global_user_id' => $user->id,
            'user_code' => 'ACT1',
            'display_name' => 'Trung Lê',
            'normalized_name' => 'TRUNG LE',
            'status' => 'active',
        ]);

        RoomUser::create([
            'room_id' => $blockedRoom->id,
            'global_user_id' => $user->id,
            'user_code' => 'BLK1',
            'display_name' => 'Trung Lê',
            'normalized_name' => 'TRUNG LE',
            'status' => 'blocked',
        ]);

        // Filter active only
        $responseActive = $this->actingAs($user, 'web')->get('/me/rooms?filter=active');
        $responseActive->assertOk();
        $responseActive->assertSee('Active Room');
        $responseActive->assertDontSee('Blocked Room');

        // Filter restricted only
        $responseRestricted = $this->actingAs($user, 'web')->get('/me/rooms?filter=restricted');
        $responseRestricted->assertOk();
        $responseRestricted->assertSee('Blocked Room');
        $responseRestricted->assertDontSee('Active Room');
    }

    public function test_rooms_searching_by_keyword(): void
    {
        $user = GlobalUser::create([
            'name' => 'Trung Lê',
            'normalized_name' => 'TRUNG LE',
            'email' => 'trung.lt@company.com',
            'status' => 'active',
        ]);

        $room1 = Room::create(['name' => 'Design & UX Studio', 'slug' => 'creative-ux-11', 'status' => 'active']);
        $room2 = Room::create(['name' => 'Phòng Kinh doanh Miền Bắc', 'slug' => 'sales-north-02', 'status' => 'active']);

        RoomUser::create([
            'room_id' => $room1->id,
            'global_user_id' => $user->id,
            'user_code' => 'UX1',
            'display_name' => 'Trung Lê',
            'normalized_name' => 'TRUNG LE',
            'status' => 'active',
        ]);

        RoomUser::create([
            'room_id' => $room2->id,
            'global_user_id' => $user->id,
            'user_code' => 'SL1',
            'display_name' => 'Trung Lê',
            'normalized_name' => 'TRUNG LE',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'web')->get('/me/rooms?q=Design');
        $response->assertOk();
        $response->assertSee('Design &amp; UX Studio', false);
        $response->assertDontSee('Phòng Kinh doanh Miền Bắc');
    }

    public function test_join_room_by_url_redirects_to_join_show(): void
    {
        $user = GlobalUser::create([
            'name' => 'Trung Lê',
            'normalized_name' => 'TRUNG LE',
            'email' => 'trung.lt@company.com',
            'status' => 'active',
        ]);

        $newRoom = Room::create([
            'name' => 'Phòng Tài chính',
            'slug' => 'finance-dept',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'web')->post('/me/rooms/join', [
            'room_url' => 'https://drinkflow.vn/rooms/finance-dept',
        ]);

        $response->assertRedirect(route('user.rooms.join.show', $newRoom));
    }

    public function test_join_room_by_url_fails_with_invalid_url(): void
    {
        $user = GlobalUser::create([
            'name' => 'Trung Lê',
            'normalized_name' => 'TRUNG LE',
            'email' => 'trung.lt@company.com',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'web')->from('/me/rooms')->post('/me/rooms/join', [
            'room_url' => 'not-a-valid-url',
        ]);

        $response->assertRedirect('/me/rooms');
        $response->assertSessionHasErrors(['room_url']);
    }

    public function test_join_room_by_url_fails_with_non_existent_room(): void
    {
        $user = GlobalUser::create([
            'name' => 'Trung Lê',
            'normalized_name' => 'TRUNG LE',
            'email' => 'trung.lt@company.com',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'web')->from('/me/rooms')->post('/me/rooms/join', [
            'room_url' => 'https://drinkflow.vn/rooms/non-existent-room-slug',
        ]);

        $response->assertRedirect('/me/rooms');
        $response->assertSessionHasErrors(['room_url']);
    }

    public function test_join_room_by_url_redirects_to_dashboard_if_already_active_member(): void
    {
        $user = GlobalUser::create([
            'name' => 'Trung Lê',
            'normalized_name' => 'TRUNG LE',
            'email' => 'trung.lt@company.com',
            'status' => 'active',
        ]);

        $room = Room::create([
            'name' => 'Văn phòng chính',
            'slug' => 'van-phong-chinh',
            'status' => 'active',
        ]);

        RoomUser::create([
            'room_id' => $room->id,
            'global_user_id' => $user->id,
            'user_code' => 'VPC1',
            'display_name' => 'Trung Lê',
            'normalized_name' => 'TRUNG LE',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'web')->post('/me/rooms/join', [
            'room_url' => 'http://localhost:8080/rooms/van-phong-chinh',
        ]);

        $response->assertRedirect(route('user.dashboard', $room));
    }

    public function test_me_rooms_returns_json_when_requested(): void
    {
        $user = GlobalUser::create([
            'name' => 'Trung Lê',
            'normalized_name' => 'TRUNG LE',
            'email' => 'trung.lt@company.com',
            'status' => 'active',
        ]);

        $room = Room::create(['name' => 'Alpha Room', 'slug' => 'alpha', 'status' => 'active']);
        RoomUser::create([
            'room_id' => $room->id,
            'global_user_id' => $user->id,
            'user_code' => 'AL1',
            'display_name' => 'Trung Lê',
            'normalized_name' => 'TRUNG LE',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'web')->getJson('/me/rooms');

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['data']]);
    }
}

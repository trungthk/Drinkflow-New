<?php

namespace Tests\Feature;

use App\Actions\User\JoinRoomAction;
use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\CampaignItem;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Models\RoomUser;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomUserExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_non_member_accessing_room_sees_join_room_modal(): void
    {
        $user = GlobalUser::create([
            'name' => 'Nguyen Van A',
            'normalized_name' => 'NGUYEN VAN A',
            'email' => 'user-a@company.com',
            'status' => 'active',
        ]);
        $room = Room::create(['name' => 'IT Department', 'slug' => 'it-dept', 'code' => 'ITDEPT', 'status' => 'active']);

        $response = $this->actingAs($user, 'web')->get('/rooms/'.$room->slug);

        $response->assertOk();
        $response->assertViewIs('user.join-room');
        $response->assertSee('Tham gia Room IT Department');
    }

    public function test_user_can_join_room_and_redirect_to_dashboard(): void
    {
        $user = GlobalUser::create([
            'name' => 'Nguyen Van B',
            'normalized_name' => 'NGUYEN VAN B',
            'email' => 'user-b@company.com',
            'status' => 'active',
        ]);
        $room = Room::create(['name' => 'Design Team', 'slug' => 'design-team', 'code' => 'DESIGN', 'status' => 'active']);

        $response = $this->actingAs($user, 'web')
            ->post('/rooms/'.$room->slug.'/join', [
                'device_name' => 'MacBook Pro',
            ]);

        $response->assertRedirect('/rooms/'.$room->slug.'/dashboard');
        $this->assertDatabaseHas('room_users', [
            'global_user_id' => $user->id,
            'room_id' => $room->id,
            'status' => 'active',
        ]);

        // Accessing dashboard as active member
        $dashboardResponse = $this->actingAs($user, 'web')->get('/rooms/'.$room->slug.'/dashboard');
        $dashboardResponse->assertOk();
        $dashboardResponse->assertViewIs('user.dashboard');
    }

    public function test_blocked_member_is_strictly_gated_with_403_and_blocked_view(): void
    {
        $user = GlobalUser::create([
            'name' => 'Blocked Member',
            'normalized_name' => 'BLOCKED MEMBER',
            'email' => 'blocked@company.com',
            'status' => 'active',
        ]);
        $room = Room::create(['name' => 'Security Dept', 'slug' => 'sec-dept', 'code' => 'SECDEPT', 'status' => 'active']);
        
        RoomUser::create([
            'global_user_id' => $user->id,
            'room_id' => $room->id,
            'user_code' => 'SECMEM',
            'display_name' => 'Blocked Member',
            'normalized_name' => 'BLOCKED MEMBER',
            'status' => 'blocked',
        ]);

        // Accessing room routes returns 403 Forbidden with user.blocked-room
        $response = $this->actingAs($user, 'web')->get('/rooms/'.$room->slug);
        $response->assertStatus(403);
        $response->assertViewIs('user.blocked-room');
        $response->assertSee('Bạn hiện không thể truy cập Room Security Dept');

        // Other room routes should also be gated
        $this->actingAs($user, 'web')->get('/rooms/'.$room->slug.'/campaigns')->assertStatus(403);
        $this->actingAs($user, 'web')->get('/rooms/'.$room->slug.'/orders')->assertStatus(403);
        $this->actingAs($user, 'web')->get('/rooms/'.$room->slug.'/debts')->assertStatus(403);
        $this->actingAs($user, 'web')->get('/rooms/'.$room->slug.'/analytics')->assertStatus(403);
        $this->actingAs($user, 'web')->get('/rooms/'.$room->slug.'/notifications')->assertStatus(403);
        $this->actingAs($user, 'web')->get('/rooms/'.$room->slug.'/profile')->assertStatus(403);
    }

    public function test_active_member_can_access_all_room_pages_and_switch_locale(): void
    {
        $user = GlobalUser::create([
            'name' => 'Active Member',
            'normalized_name' => 'ACTIVE MEMBER',
            'email' => 'active@company.com',
            'status' => 'active',
        ]);
        $room = Room::create(['name' => 'Marketing Team', 'slug' => 'marketing-team', 'code' => 'MKT', 'status' => 'active']);
        
        app(JoinRoomAction::class)->execute($user, $room, 'Chrome', 'hash');

        $campaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Friday Afternoon Tea',
            'restaurant' => 'Highlands Coffee',
            'status' => CampaignStatus::Active,
        ]);
        CampaignItem::create([
            'campaign_id' => $campaign->id,
            'name' => 'Cà phê sữa đá',
            'normalized_name' => 'CA PHE SUA DA',
            'base_price' => 35000,
            'status' => 'active',
        ]);

        // Dashboard
        $this->actingAs($user, 'web')->get('/rooms/'.$room->slug.'/dashboard')
            ->assertOk()
            ->assertViewIs('user.dashboard');

        // Campaign & Menu
        $this->actingAs($user, 'web')->get('/rooms/'.$room->slug.'/campaigns')
            ->assertOk()
            ->assertViewIs('user.campaign')
            ->assertSee('Highlands Coffee');

        // Orders
        $this->actingAs($user, 'web')->get('/rooms/'.$room->slug.'/orders')
            ->assertOk()
            ->assertViewIs('user.orders');

        // Debts & Payment
        $this->actingAs($user, 'web')->get('/rooms/'.$room->slug.'/debts')
            ->assertOk()
            ->assertViewIs('user.debts');

        // Analytics
        $this->actingAs($user, 'web')->get('/rooms/'.$room->slug.'/analytics')
            ->assertOk()
            ->assertViewIs('user.analytics');

        // Notifications
        $this->actingAs($user, 'web')->get('/rooms/'.$room->slug.'/notifications')
            ->assertOk()
            ->assertViewIs('user.notifications');

        // Profile
        $this->actingAs($user, 'web')->get('/rooms/'.$room->slug.'/profile')
            ->assertOk()
            ->assertViewIs('user.profile');

        // Test English locale rendering
        $this->actingAs($user, 'web')->withSession(['locale' => 'en'])->get('/rooms/'.$room->slug.'/dashboard')
            ->assertOk()
            ->assertSee('Room Overview');

        // Test Japanese locale rendering
        $this->actingAs($user, 'web')->withSession(['locale' => 'ja'])->get('/rooms/'.$room->slug.'/dashboard')
            ->assertOk()
            ->assertSee('ルーム概要');
    }
}

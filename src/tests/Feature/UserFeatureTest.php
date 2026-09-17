<?php

namespace Tests\Feature;

use App\Actions\Campaign\CreateCampaignAction;
use App\Actions\User\JoinRoomAction;
use App\Enums\CampaignStatus;
use App\Enums\OrderStatus;
use App\Models\Campaign;
use App\Models\CampaignItem;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Services\Realtime\SocketTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_accessing_landing_page_sees_landing_page(): void
    {
        $user = GlobalUser::create(['name' => 'New User', 'normalized_name' => 'NEW USER', 'email' => 'new-user@company.com']);

        $this->actingAs($user, 'web')
            ->get('/')
            ->assertOk()
            ->assertSee('DrinkFlow');
    }

    public function test_user_can_filter_campaign_items_without_cross_room_access(): void
    {
        $user = GlobalUser::create(['name' => 'User', 'normalized_name' => 'USER', 'email' => 'user@company.com']);
        $room = Room::create(['name' => 'IT', 'slug' => 'user-it']);
        app(JoinRoomAction::class)->execute($user, $room, 'device', 'hash');
        $campaign = Campaign::create(['room_id' => $room->id, 'name' => 'Lunch', 'restaurant' => 'Cafe', 'status' => CampaignStatus::Active]);
        CampaignItem::create(['campaign_id' => $campaign->id, 'name' => 'Trà sữa', 'normalized_name' => 'TRA SUA', 'base_price' => 20000, 'status' => 'active']);

        $this->actingAs($user, 'web')->get('/rooms/'.$room->id.'/campaigns?q=tra sua')
            ->assertOk()->assertJsonPath('data.data.0.items.0.name', 'Trà sữa');
    }

    public function test_history_only_returns_orders_belonging_to_current_global_user(): void
    {
        $user = GlobalUser::create(['name' => 'User', 'normalized_name' => 'USER', 'email' => 'history@company.com']);
        $room = Room::create(['name' => 'IT', 'slug' => 'history-it']);
        $roomUser = app(JoinRoomAction::class)->execute($user, $room, 'device', 'hash');
        $campaign = Campaign::create(['room_id' => $room->id, 'name' => 'Lunch', 'restaurant' => 'Cafe', 'status' => CampaignStatus::Active]);
        $roomUser->orders()->create(['room_id' => $room->id, 'campaign_id' => $campaign->id, 'subtotal' => 10000, 'final_amount' => 10000, 'status' => 'submitted']);

        $this->actingAs($user, 'web')->get('/history')->assertOk()->assertJsonCount(1, 'data.data');
    }

    public function test_global_analytics_aggregates_only_personal_orders(): void
    {
        $user = GlobalUser::create(['name' => 'User', 'normalized_name' => 'USER', 'email' => 'analytics@company.com']);
        $room = Room::create(['name' => 'IT', 'slug' => 'analytics-it']);
        $roomUser = app(JoinRoomAction::class)->execute($user, $room, 'device', 'hash');
        $campaign = Campaign::create(['room_id' => $room->id, 'name' => 'Lunch', 'restaurant' => 'Cafe', 'status' => CampaignStatus::Active]);
        $roomUser->orders()->create(['room_id' => $room->id, 'campaign_id' => $campaign->id, 'subtotal' => 10000, 'final_amount' => 10000, 'status' => OrderStatus::Completed]);

        $this->actingAs($user, 'web')->get('/analytics')->assertOk()->assertJsonPath('data.total_orders', 1);
    }

    public function test_active_campaign_notifies_active_room_members(): void
    {
        $user = GlobalUser::create(['name' => 'User', 'normalized_name' => 'USER', 'email' => 'campaign-notify@company.com']);
        $room = Room::create(['name' => 'IT', 'slug' => 'campaign-notify-it']);
        app(JoinRoomAction::class)->execute($user, $room, 'device', 'hash');
        $campaign = app(CreateCampaignAction::class)->execute($room, ['name' => 'Breakfast', 'restaurant' => 'Cafe', 'status' => CampaignStatus::Active]);
        (new \App\Listeners\NotifyCampaignCreated())->handle(new \App\Events\CampaignCreated($campaign));

        $this->assertDatabaseHas('user_notifications', ['global_user_id' => $user->id, 'type' => 'campaign.created']);
    }

    public function test_socket_token_contains_only_authorized_room_channels(): void
    {
        $user = GlobalUser::create(['name' => 'User', 'normalized_name' => 'USER', 'email' => 'socket@company.com']);
        $room = Room::create(['name' => 'IT', 'slug' => 'socket-it']);
        $roomUser = app(JoinRoomAction::class)->execute($user, $room, 'device', 'hash');
        $token = app(SocketTokenService::class)->issue($roomUser);
        $claims = app(SocketTokenService::class)->verify($token);

        $this->assertSame($roomUser->id, $claims['room_user_id']);
        $this->assertSame($room->id, $claims['room_id']);
        $this->assertNull(app(SocketTokenService::class)->verify($token.'tampered'));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\User\JoinRoomAction;
use App\Enums\CampaignStatus;
use App\Enums\OrderStatus;
use App\Models\Campaign;
use App\Models\GlobalUser;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Room;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomProfileCompletedOrdersTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure profile metrics and favorites only aggregate completed orders.
     *
     * @return void
     */
    public function test_profile_statistics_only_include_completed_orders(): void
    {
        $user = GlobalUser::create([
            'name' => 'Profile Member',
            'normalized_name' => 'PROFILE MEMBER',
            'email' => 'profile-member@company.com',
            'status' => 'active',
        ]);
        $room = Room::create([
            'name' => 'Công Nghệ',
            'slug' => 'cong-nghe',
            'status' => 'active',
        ]);
        $roomUser = app(JoinRoomAction::class)->execute($user, $room, 'Chrome', 'profile-stats-hash');
        $campaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Profile Stats Campaign',
            'restaurant' => 'DrinkFlow Cafe',
            'status' => CampaignStatus::Active,
        ]);

        $completedOrder = Order::create([
            'room_id' => $room->id,
            'campaign_id' => $campaign->id,
            'room_user_id' => $roomUser->id,
            'subtotal' => 90000,
            'sponsor_amount' => 10000,
            'final_amount' => 80000,
            'status' => OrderStatus::Completed,
        ]);
        OrderItem::create([
            'order_id' => $completedOrder->id,
            'item_name' => 'Trà sữa hoàn thành',
            'unit_price' => 45000,
            'quantity' => 2,
            'line_subtotal' => 90000,
        ]);

        $submittedOrder = Order::create([
            'room_id' => $room->id,
            'campaign_id' => $campaign->id,
            'room_user_id' => $roomUser->id,
            'subtotal' => 120000,
            'final_amount' => 120000,
            'status' => OrderStatus::Submitted,
        ]);
        OrderItem::create([
            'order_id' => $submittedOrder->id,
            'item_name' => 'Cà phê đang xử lý',
            'unit_price' => 40000,
            'quantity' => 3,
            'line_subtotal' => 120000,
        ]);

        $response = $this->actingAs($user, 'web')
            ->getJson('/rooms/cong-nghe/profile');

        $response->assertOk()
            ->assertJsonPath('stats.total_orders', 1)
            ->assertJsonPath('stats.total_spent', 80000)
            ->assertJsonPath('stats.total_sponsor', 10000)
            ->assertJsonPath('favorite_items.0.name', 'Trà sữa hoàn thành')
            ->assertJsonPath('favorite_items.0.count', 2)
            ->assertJsonMissing(['name' => 'Cà phê đang xử lý']);

        $this->actingAs($user, 'web')->get('/rooms/cong-nghe/profile')
            ->assertOk()
            ->assertSee('Trà sữa hoàn thành')
            ->assertDontSee('Cà phê đang xử lý')
            ->assertDontSee('data-user-notification-badge', false);

        UserNotification::create([
            'global_user_id' => $user->id,
            'type' => 'order.status',
            'title' => 'Đơn đã cập nhật',
            'body' => 'Đơn hàng của bạn vừa thay đổi trạng thái.',
        ]);

        $this->actingAs($user, 'web')->get('/rooms/cong-nghe/profile')
            ->assertOk()
            ->assertSee('data-user-notification-badge', false);
    }

    /**
     * The room profile header must expose an edit button redirecting to the global profile page.
     *
     * @return void
     */
    public function test_room_profile_page_has_edit_button_linking_to_global_profile(): void
    {
        $user = GlobalUser::create([
            'name' => 'Edit Button Member',
            'normalized_name' => 'EDIT BUTTON MEMBER',
            'email' => 'edit-button-member@company.com',
            'status' => 'active',
        ]);
        $room = Room::create([
            'name' => 'Marketing',
            'slug' => 'marketing',
            'status' => 'active',
        ]);
        app(JoinRoomAction::class)->execute($user, $room, 'Chrome', 'edit-button-hash');

        $response = $this->actingAs($user, 'web')->get('/rooms/marketing/profile');

        $response->assertOk()
            ->assertSee(route('user.me.profile'), false)
            ->assertSee(__('room.profile.edit_profile'))
            ->assertSee('data-desktop-notify-toggle', false)
            ->assertSee('data-notifications-url="'.route('user.rooms.notifications', 'marketing').'"', false);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\CampaignStatus;
use App\Enums\OrderStatus;
use App\Models\AdminAccount;
use App\Models\Campaign;
use App\Models\GlobalUser;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Room;
use App\Models\RoomUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrdersBackendFilterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure order search and status filters execute against the backend query.
     *
     * @return void
     */
    public function test_order_management_uses_backend_search_and_status_filter(): void
    {
        $admin = AdminAccount::create([
            'name' => 'Order Admin',
            'email' => 'order-admin@example.test',
            'password' => 'secret-password',
            'role' => AdminRole::Admin,
            'status' => 'active',
        ]);
        $room = Room::create([
            'name' => 'Công Nghệ',
            'slug' => 'cong-nghe',
            'status' => 'active',
        ]);
        $admin->rooms()->attach($room);
        $campaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Morning Drinks',
            'restaurant' => 'DrinkFlow Cafe',
            'status' => CampaignStatus::Active,
        ]);

        $submittedUser = $this->roomUser($room, 'Matcha Member', 'matcha@example.test');
        $submittedOrder = $this->order($room, $campaign, $submittedUser, OrderStatus::Submitted);
        OrderItem::create([
            'order_id' => $submittedOrder->id,
            'item_name' => 'Matcha Latte Backend',
            'unit_price' => 45000,
            'quantity' => 1,
            'line_subtotal' => 45000,
        ]);

        $completedUser = $this->roomUser($room, 'Coffee Member', 'coffee@example.test');
        $completedOrder = $this->order($room, $campaign, $completedUser, OrderStatus::Completed);
        OrderItem::create([
            'order_id' => $completedOrder->id,
            'item_name' => 'Espresso Backend',
            'unit_price' => 35000,
            'quantity' => 1,
            'line_subtotal' => 35000,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.orders.page', ['room' => $room->slug, 'search' => 'matcha latte']))
            ->assertOk()
            ->assertSeeText(__('admin.filter_clear'))
            ->assertSee('Matcha Latte Backend')
            ->assertDontSee('Espresso Backend');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.orders.page', ['room' => $room->slug, 'status' => OrderStatus::Completed->value]))
            ->assertOk()
            ->assertSee('Espresso Backend')
            ->assertDontSee('Matcha Latte Backend');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.orders.page', [
                'room' => $room->slug,
                'search' => $completedOrder->code,
                'status' => OrderStatus::Completed->value,
            ]))
            ->assertOk()
            ->assertSee('Espresso Backend')
            ->assertDontSee('Matcha Latte Backend');
    }

    /**
     * Create an active member in the requested room.
     *
     * @param Room $room Room that owns the member.
     * @param string $name Member display name.
     * @param string $email Member email address.
     * @return RoomUser Created room member.
     */
    private function roomUser(Room $room, string $name, string $email): RoomUser
    {
        $user = GlobalUser::create([
            'name' => $name,
            'normalized_name' => mb_strtoupper($name),
            'email' => $email,
            'status' => 'active',
        ]);

        return RoomUser::create([
            'room_id' => $room->id,
            'global_user_id' => $user->id,
            'user_code' => 'USR-'.$user->id,
            'display_name' => $name,
            'normalized_name' => mb_strtoupper($name),
            'status' => 'active',
        ]);
    }

    /**
     * Create an order used by the backend filter assertions.
     *
     * @param Room $room Room that owns the order.
     * @param Campaign $campaign Campaign that owns the order.
     * @param RoomUser $roomUser Member who placed the order.
     * @param OrderStatus $status Current order status.
     * @return Order Created order.
     */
    private function order(Room $room, Campaign $campaign, RoomUser $roomUser, OrderStatus $status): Order
    {
        return Order::create([
            'room_id' => $room->id,
            'campaign_id' => $campaign->id,
            'room_user_id' => $roomUser->id,
            'subtotal' => 45000,
            'final_amount' => 45000,
            'status' => $status,
        ]);
    }
}

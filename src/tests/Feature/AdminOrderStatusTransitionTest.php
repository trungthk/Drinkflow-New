<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\CampaignStatus;
use App\Enums\OrderStatus;
use App\Events\OrderUpdated;
use App\Models\AdminAccount;
use App\Models\Campaign;
use App\Models\GlobalUser;
use App\Models\Order;
use App\Models\Room;
use App\Models\RoomUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AdminOrderStatusTransitionTest extends TestCase
{
    use RefreshDatabase;

    private AdminAccount $admin;
    private GlobalUser $memberUser;
    private Room $room;
    private RoomUser $memberRoomUser;
    private Campaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = AdminAccount::create([
            'name' => 'Order Admin',
            'email' => 'admin-test@example.test',
            'password' => 'secret-password',
            'role' => AdminRole::Admin,
            'status' => 'active',
        ]);

        $this->room = Room::create([
            'name' => 'Công Nghệ',
            'slug' => 'cong-nghe',
            'status' => 'active',
        ]);
        $this->admin->rooms()->attach($this->room);

        $this->memberUser = GlobalUser::create([
            'name' => 'John Doe',
            'normalized_name' => 'JOHN DOE',
            'email' => 'johndoe@example.test',
            'status' => 'active',
        ]);

        $this->memberRoomUser = RoomUser::create([
            'room_id' => $this->room->id,
            'global_user_id' => $this->memberUser->id,
            'user_code' => 'USR-'.$this->memberUser->id,
            'display_name' => 'John Doe',
            'normalized_name' => 'JOHN DOE',
            'status' => 'active',
        ]);

        $this->campaign = Campaign::create([
            'room_id' => $this->room->id,
            'name' => 'Morning Drinks',
            'restaurant' => 'DrinkFlow Cafe',
            'status' => CampaignStatus::Active,
        ]);
    }

    public function test_admin_can_transition_order_from_submitted_to_confirmed(): void
    {
        $order = Order::create([
            'room_id' => $this->room->id,
            'campaign_id' => $this->campaign->id,
            'room_user_id' => $this->memberRoomUser->id,
            'subtotal' => 45000,
            'final_amount' => 45000,
            'status' => OrderStatus::Submitted,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->patchJson("/admin/{$this->room->slug}/orders/{$order->id}/status", [
                'status' => OrderStatus::Confirmed->value,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', OrderStatus::Confirmed->value);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Confirmed->value,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'order.status_updated',
            'target_type' => 'order',
            'target_id' => $order->id,
            'room_id' => $this->room->id,
        ]);

        $this->assertDatabaseHas('user_notifications', [
            'global_user_id' => $this->memberUser->id,
            'room_user_id' => $this->memberRoomUser->id,
            'type' => 'order.status',
        ]);
    }

    public function test_admin_can_transition_order_from_confirmed_back_to_submitted(): void
    {
        $order = Order::create([
            'room_id' => $this->room->id,
            'campaign_id' => $this->campaign->id,
            'room_user_id' => $this->memberRoomUser->id,
            'subtotal' => 45000,
            'final_amount' => 45000,
            'status' => OrderStatus::Confirmed,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->patchJson("/admin/{$this->room->slug}/orders/{$order->id}/status", [
                'status' => OrderStatus::Submitted->value,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', OrderStatus::Submitted->value);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Submitted->value,
        ]);
    }

    public function test_admin_can_transition_order_to_completed(): void
    {
        $order = Order::create([
            'room_id' => $this->room->id,
            'campaign_id' => $this->campaign->id,
            'room_user_id' => $this->memberRoomUser->id,
            'subtotal' => 45000,
            'final_amount' => 45000,
            'status' => OrderStatus::Confirmed,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->patchJson("/admin/{$this->room->slug}/orders/{$order->id}/status", [
                'status' => OrderStatus::Completed->value,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', OrderStatus::Completed->value);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Completed->value,
        ]);

        $order->refresh();
        $this->assertNotNull($order->completed_at);
    }

    public function test_order_status_update_dispatches_realtime_events_and_notification(): void
    {
        Event::fake([OrderUpdated::class]);

        $order = Order::create([
            'room_id' => $this->room->id,
            'campaign_id' => $this->campaign->id,
            'room_user_id' => $this->memberRoomUser->id,
            'subtotal' => 45000,
            'final_amount' => 45000,
            'status' => OrderStatus::Submitted,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->patchJson("/admin/{$this->room->slug}/orders/{$order->id}/status", [
                'status' => OrderStatus::Confirmed->value,
            ]);

        $response->assertOk();

        Event::assertDispatched(OrderUpdated::class, function (OrderUpdated $event) use ($order): bool {
            return $event->order->id === $order->id && $event->previousStatus === OrderStatus::Submitted->value;
        });
    }

    public function test_admin_can_cancel_order_via_cancel_endpoint(): void
    {
        $order = Order::create([
            'room_id' => $this->room->id,
            'campaign_id' => $this->campaign->id,
            'room_user_id' => $this->memberRoomUser->id,
            'subtotal' => 45000,
            'final_amount' => 45000,
            'status' => OrderStatus::Submitted,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/admin/{$this->room->slug}/orders/{$order->id}/cancel");

        $response->assertOk()
            ->assertJsonPath('data.status', OrderStatus::Cancelled->value);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Cancelled->value,
        ]);

        $order->refresh();
        $this->assertNotNull($order->cancelled_at);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'order.status_updated',
            'target_type' => 'order',
            'target_id' => $order->id,
            'room_id' => $this->room->id,
        ]);

        $this->assertDatabaseHas('user_notifications', [
            'global_user_id' => $this->memberUser->id,
            'room_user_id' => $this->memberRoomUser->id,
            'type' => 'order.status',
        ]);
    }

    public function test_guest_cannot_update_order_status(): void
    {
        $order = Order::create([
            'room_id' => $this->room->id,
            'campaign_id' => $this->campaign->id,
            'room_user_id' => $this->memberRoomUser->id,
            'subtotal' => 45000,
            'final_amount' => 45000,
            'status' => OrderStatus::Submitted,
        ]);

        $response = $this->patchJson("/admin/{$this->room->slug}/orders/{$order->id}/status", [
            'status' => OrderStatus::Confirmed->value,
        ]);

        $response->assertUnauthorized();
    }

    public function test_cannot_transition_from_cancelled_status(): void
    {
        $order = Order::create([
            'room_id' => $this->room->id,
            'campaign_id' => $this->campaign->id,
            'room_user_id' => $this->memberRoomUser->id,
            'subtotal' => 45000,
            'final_amount' => 45000,
            'status' => OrderStatus::Cancelled,
            'cancelled_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->patchJson("/admin/{$this->room->slug}/orders/{$order->id}/status", [
                'status' => OrderStatus::Confirmed->value,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Cancelled->value,
        ]);
    }

    public function test_cannot_adjust_price_for_cancelled_order(): void
    {
        $order = Order::create([
            'room_id' => $this->room->id,
            'campaign_id' => $this->campaign->id,
            'room_user_id' => $this->memberRoomUser->id,
            'subtotal' => 45000,
            'final_amount' => 45000,
            'status' => OrderStatus::Cancelled,
            'cancelled_at' => now(),
        ]);

        $item = $order->items()->create([
            'item_name' => 'Tra sua',
            'quantity' => 1,
            'unit_price' => 45000,
            'line_subtotal' => 45000,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->patchJson("/admin/{$this->room->slug}/orders/{$order->id}", [
                'items' => [
                    [
                        'id' => $item->id,
                        'unit_price' => 50000,
                    ],
                ],
                'reason' => 'Thay doi gia theo menu moi',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['order']);
    }

    public function test_adjusting_price_creates_user_notification_with_reason(): void
    {
        $order = Order::create([
            'room_id' => $this->room->id,
            'campaign_id' => $this->campaign->id,
            'room_user_id' => $this->memberRoomUser->id,
            'subtotal' => 45000,
            'final_amount' => 45000,
            'status' => OrderStatus::Confirmed,
        ]);

        $item = $order->items()->create([
            'item_name' => 'Tra sua',
            'quantity' => 1,
            'unit_price' => 45000,
            'line_subtotal' => 45000,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->patchJson("/admin/{$this->room->slug}/orders/{$order->id}", [
                'items' => [
                    [
                        'id' => $item->id,
                        'unit_price' => 50000,
                    ],
                ],
                'reason' => 'Quan tang gia them 5k',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.final_amount', 50000);

        $this->assertDatabaseHas('user_notifications', [
            'global_user_id' => $this->memberUser->id,
            'room_user_id' => $this->memberRoomUser->id,
            'type' => 'order.price_adjusted',
        ]);
    }

    public function test_admin_can_view_full_order_details(): void
    {
        $order = Order::create([
            'room_id' => $this->room->id,
            'campaign_id' => $this->campaign->id,
            'room_user_id' => $this->memberRoomUser->id,
            'subtotal' => 45000,
            'delivery_amount' => 5000,
            'discount_amount' => 2000,
            'sponsor_amount' => 10000,
            'final_amount' => 38000,
            'status' => OrderStatus::Confirmed,
            'note' => 'It da it duong',
        ]);

        $item = $order->items()->create([
            'item_name' => 'Tra sua Oolong',
            'size' => 'L',
            'quantity' => 1,
            'unit_price' => 45000,
            'line_subtotal' => 45000,
            'note' => '30% duong',
        ]);

        $item->toppings()->create([
            'topping_name' => 'Tran chau den',
            'unit_price' => 5000,
            'quantity' => 1,
            'subtotal' => 5000,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson("/admin/{$this->room->slug}/orders/{$order->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.note', 'It da it duong')
            ->assertJsonPath('data.room_user.display_name', 'John Doe')
            ->assertJsonPath('data.campaign.name', 'Morning Drinks')
            ->assertJsonPath('data.items.0.item_name', 'Tra sua Oolong')
            ->assertJsonPath('data.items.0.toppings.0.topping_name', 'Tran chau den');
    }

    public function test_admin_can_bulk_update_order_statuses(): void
    {
        $memberUser2 = GlobalUser::create([
            'name' => 'Jane Smith',
            'normalized_name' => 'JANE SMITH',
            'email' => 'janesmith@example.test',
            'status' => 'active',
        ]);

        $memberRoomUser2 = RoomUser::create([
            'room_id' => $this->room->id,
            'global_user_id' => $memberUser2->id,
            'user_code' => 'USR-'.$memberUser2->id,
            'display_name' => 'Jane Smith',
            'normalized_name' => 'JANE SMITH',
            'status' => 'active',
        ]);

        $order1 = Order::create([
            'room_id' => $this->room->id,
            'campaign_id' => $this->campaign->id,
            'room_user_id' => $this->memberRoomUser->id,
            'subtotal' => 30000,
            'final_amount' => 30000,
            'status' => OrderStatus::Submitted,
        ]);

        $order2 = Order::create([
            'room_id' => $this->room->id,
            'campaign_id' => $this->campaign->id,
            'room_user_id' => $memberRoomUser2->id,
            'subtotal' => 40000,
            'final_amount' => 40000,
            'status' => OrderStatus::Submitted,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/admin/{$this->room->slug}/orders/bulk-status", [
                'order_ids' => [$order1->id, $order2->id],
                'status' => OrderStatus::Confirmed->value,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.updated_count', 2);

        $this->assertDatabaseHas('orders', [
            'id' => $order1->id,
            'status' => OrderStatus::Confirmed->value,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order2->id,
            'status' => OrderStatus::Confirmed->value,
        ]);
    }

    public function test_admin_bulk_update_skips_cancelled_orders(): void
    {
        $memberUser2 = GlobalUser::create([
            'name' => 'Jane Smith 2',
            'normalized_name' => 'JANE SMITH 2',
            'email' => 'janesmith2@example.test',
            'status' => 'active',
        ]);

        $memberRoomUser2 = RoomUser::create([
            'room_id' => $this->room->id,
            'global_user_id' => $memberUser2->id,
            'user_code' => 'USR-'.$memberUser2->id,
            'display_name' => 'Jane Smith 2',
            'normalized_name' => 'JANE SMITH 2',
            'status' => 'active',
        ]);

        $activeOrder = Order::create([
            'room_id' => $this->room->id,
            'campaign_id' => $this->campaign->id,
            'room_user_id' => $this->memberRoomUser->id,
            'subtotal' => 30000,
            'final_amount' => 30000,
            'status' => OrderStatus::Submitted,
        ]);

        $cancelledOrder = Order::create([
            'room_id' => $this->room->id,
            'campaign_id' => $this->campaign->id,
            'room_user_id' => $memberRoomUser2->id,
            'subtotal' => 40000,
            'final_amount' => 40000,
            'status' => OrderStatus::Cancelled,
            'cancelled_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/admin/{$this->room->slug}/orders/bulk-status", [
                'order_ids' => [$activeOrder->id, $cancelledOrder->id],
                'status' => OrderStatus::Completed->value,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.updated_count', 1);

        $this->assertDatabaseHas('orders', [
            'id' => $activeOrder->id,
            'status' => OrderStatus::Completed->value,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $cancelledOrder->id,
            'status' => OrderStatus::Cancelled->value,
        ]);
    }

    public function test_admin_can_bulk_cancel_orders(): void
    {
        $memberUser2 = GlobalUser::create([
            'name' => 'Jane Smith 3',
            'normalized_name' => 'JANE SMITH 3',
            'email' => 'janesmith3@example.test',
            'status' => 'active',
        ]);

        $memberRoomUser2 = RoomUser::create([
            'room_id' => $this->room->id,
            'global_user_id' => $memberUser2->id,
            'user_code' => 'USR-'.$memberUser2->id,
            'display_name' => 'Jane Smith 3',
            'normalized_name' => 'JANE SMITH 3',
            'status' => 'active',
        ]);

        $order1 = Order::create([
            'room_id' => $this->room->id,
            'campaign_id' => $this->campaign->id,
            'room_user_id' => $this->memberRoomUser->id,
            'subtotal' => 30000,
            'final_amount' => 30000,
            'status' => OrderStatus::Submitted,
        ]);

        $order2 = Order::create([
            'room_id' => $this->room->id,
            'campaign_id' => $this->campaign->id,
            'room_user_id' => $memberRoomUser2->id,
            'subtotal' => 40000,
            'final_amount' => 40000,
            'status' => OrderStatus::Confirmed,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/admin/{$this->room->slug}/orders/bulk-cancel", [
                'order_ids' => [$order1->id, $order2->id],
            ]);

        $response->assertOk()
            ->assertJsonPath('data.cancelled_count', 2);

        $this->assertDatabaseHas('orders', [
            'id' => $order1->id,
            'status' => OrderStatus::Cancelled->value,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order2->id,
            'status' => OrderStatus::Cancelled->value,
        ]);

        $this->assertNotNull(Order::find($order1->id)->cancelled_at);
        $this->assertNotNull(Order::find($order2->id)->cancelled_at);
    }
}



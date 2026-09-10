<?php

namespace Tests\Feature;

use App\Actions\Order\CreateOrderAction;
use App\Actions\User\JoinRoomAction;
use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\CampaignItem;
use App\Models\GlobalUser;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DrinkflowOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_join_room_is_idempotent_and_binds_device(): void
    {
        $user = GlobalUser::create(['name' => 'Nguyễn Thành Trung', 'normalized_name' => 'NGUYEN THANH TRUNG', 'email' => 'trung@company.com']);
        $room = Room::create(['name' => 'IT', 'slug' => 'it']);
        $action = app(JoinRoomAction::class);
        $first = $action->execute($user, $room, 'device-1', 'hash-1');
        $second = $action->execute($user, $room, 'device-1', 'hash-2');
        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('room_users', 1);
        $this->assertDatabaseHas('room_user_devices', ['room_user_id' => $first->id, 'token_hash' => 'hash-2']);
    }

    public function test_create_order_uses_server_prices_and_prevents_duplicate_active_order(): void
    {
        $user = GlobalUser::create(['name' => 'Trung', 'normalized_name' => 'TRUNG', 'email' => 'trung2@company.com']);
        $room = Room::create(['name' => 'IT', 'slug' => 'it-2']);
        $roomUser = app(JoinRoomAction::class)->execute($user, $room, 'device-2', 'hash');
        $campaign = Campaign::create(['room_id' => $room->id, 'name' => 'Lunch', 'restaurant' => 'Cafe', 'status' => CampaignStatus::Active, 'delivery_fee' => 5000, 'discount' => 1000]);
        $item = CampaignItem::create(['campaign_id' => $campaign->id, 'name' => 'Tea', 'normalized_name' => 'TEA', 'base_price' => 20000, 'status' => 'active']);
        $action = app(CreateOrderAction::class);
        $order = $action->execute($campaign, $roomUser, ['items' => [['item_id' => $item->id, 'quantity' => 2]], 'discount_amount' => 999999, 'sponsor_amount' => 999999]);
        $this->assertSame(44000, $order->final_amount);
        $this->expectException(\Illuminate\Database\QueryException::class);
        $action->execute($campaign, $roomUser, ['items' => [['item_id' => $item->id, 'quantity' => 1]]]);
    }
}

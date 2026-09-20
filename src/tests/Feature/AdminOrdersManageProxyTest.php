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
use App\Models\Room;
use App\Models\RoomUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminOrdersManageProxyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The orders table lists proxy orders too, naming who placed them, and the payable label is renamed.
     */
    public function test_orders_table_includes_proxy_orders(): void
    {
        $admin = AdminAccount::create([
            'name' => 'Room Admin',
            'email' => 'orders-admin@example.test',
            'password' => Hash::make('secret'),
            'role' => AdminRole::Admin,
            'status' => 'active',
        ]);
        $room = Room::create(['name' => 'Orders Room', 'slug' => 'orders-room', 'status' => 'active']);
        $admin->rooms()->attach($room);
        $campaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Trà chiều',
            'restaurant' => 'Cafe',
            'status' => CampaignStatus::Active,
        ]);

        $makeMember = function (string $name, string $email) use ($room): RoomUser {
            $globalUser = GlobalUser::create(['name' => $name, 'email' => $email]);

            return RoomUser::create([
                'room_id' => $room->id,
                'global_user_id' => $globalUser->id,
                'display_name' => $name,
                'status' => 'active',
            ]);
        };
        $buyer = $makeMember('Người Đặt Hộ', 'buyer@example.test');
        $friend = $makeMember('Bạn Được Đặt Hộ', 'friend@example.test');

        $parent = Order::create([
            'room_id' => $room->id,
            'campaign_id' => $campaign->id,
            'room_user_id' => $buyer->id,
            'code' => 'ORD-PARENT',
            'subtotal' => 30000,
            'final_amount' => 30000,
            'status' => OrderStatus::Submitted,
        ]);
        Order::create([
            'room_id' => $room->id,
            'campaign_id' => $campaign->id,
            'room_user_id' => $friend->id,
            'parent_id' => $parent->id,
            'code' => 'ORD-CHILD',
            'subtotal' => 20000,
            'final_amount' => 20000,
            'status' => OrderStatus::Submitted,
        ]);

        $html = $this->actingAs($admin, 'admin')
            ->get(route('admin.orders.page', $room->slug))
            ->assertOk()
            ->assertSee('ORD-PARENT')
            ->assertSee('ORD-CHILD')
            ->assertSee('Bạn Được Đặt Hộ')
            ->assertSee(__('admin.ordered_by').': Người Đặt Hộ')
            ->getContent();

        $this->assertSame(2, substr_count($html, 'data-order-row='));
        $this->assertSame('Tổng phải trả', __('admin.final_payable_amount'));
    }
}

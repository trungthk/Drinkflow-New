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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomOrdersLayoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure the room order page uses the compact item layout and three-stage progress.
     *
     * @return void
     */
    public function test_order_page_shows_three_progress_steps_without_room_history(): void
    {
        $user = GlobalUser::create([
            'name' => 'Order Member',
            'normalized_name' => 'ORDER MEMBER',
            'email' => 'order-member@company.com',
            'status' => 'active',
        ]);
        $room = Room::create([
            'name' => 'Công Nghệ',
            'slug' => 'cong-nghe',
            'status' => 'active',
        ]);
        $roomUser = app(JoinRoomAction::class)->execute($user, $room, 'Chrome', 'order-layout-hash');
        $campaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Coffee Live',
            'restaurant' => 'DrinkFlow Cafe',
            'status' => CampaignStatus::Active,
        ]);
        $order = Order::create([
            'room_id' => $room->id,
            'campaign_id' => $campaign->id,
            'room_user_id' => $roomUser->id,
            'subtotal' => 90000,
            'final_amount' => 90000,
            'status' => OrderStatus::Delivering,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'item_name' => 'Trà sữa ô long',
            'size_name' => 'L',
            'unit_price' => 45000,
            'quantity' => 2,
            'ice_percent' => 50,
            'sugar_percent' => 30,
            'line_subtotal' => 90000,
            'note' => 'Để riêng topping',
        ]);

        $response = $this->actingAs($user, 'web')->get('/rooms/cong-nghe/orders');

        $response->assertOk();
        $response->assertSee('1. Đã gửi');
        $response->assertSee('2. Món đã được giao đến');
        $response->assertSee('3. Hoàn thành');
        $response->assertSee('Trà sữa ô long');
        $response->assertSee('90.000đ');
        $response->assertSee('Size L');
        $response->assertSee('30% đường');
        $response->assertSee('50% đá');
        $response->assertSee(__('room.orders.confirm_modal_title'));
        $response->assertSee('bg-blue-600 hover:bg-blue-700', false);
        $response->assertSee('openPaymentConfirm', false);
        $response->assertSee('showGlobalLoading', false);
        $response->assertDontSee(__('room.orders.pay_now_vietqr').' (90.000đ)');
        $response->assertDontSee('Chờ duyệt thanh toán');
        $response->assertDontSee('confirm(', false);
        $response->assertDontSee('Lịch sử đơn trong Room');
        $response->assertDontSee('2. Đã xác nhận');
        $response->assertDontSee('5. Đang giao');
    }
}

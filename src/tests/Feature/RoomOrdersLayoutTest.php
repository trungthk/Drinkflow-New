<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\User\JoinRoomAction;
use App\Enums\CampaignStatus;
use App\Enums\DebtStatus;
use App\Enums\OrderStatus;
use App\Models\Campaign;
use App\Models\Debt;
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
     * Ensure the room order page uses the compact item layout and four-stage progress.
     *
     * @return void
     */
    public function test_order_page_shows_four_progress_steps_without_room_history(): void
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
        Debt::create([
            'room_id' => $room->id,
            'campaign_id' => $campaign->id,
            'room_user_id' => $roomUser->id,
            'original_amount' => 90000,
            'sponsor_amount' => 0,
            'paid_amount' => 0,
            'remaining_amount' => 90000,
            'status' => DebtStatus::Unpaid,
        ]);

        $cancelledCampaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Cancelled Campaign',
            'restaurant' => 'Cancelled Cafe',
            'status' => CampaignStatus::Closed,
        ]);
        $cancelledOrder = Order::create([
            'room_id' => $room->id,
            'campaign_id' => $cancelledCampaign->id,
            'room_user_id' => $roomUser->id,
            'subtotal' => 50000,
            'final_amount' => 50000,
            'status' => OrderStatus::Cancelled,
        ]);
        OrderItem::create([
            'order_id' => $cancelledOrder->id,
            'item_name' => 'Cancelled Drink',
            'unit_price' => 50000,
            'quantity' => 1,
            'line_subtotal' => 50000,
        ]);
        Debt::create([
            'room_id' => $room->id,
            'campaign_id' => $cancelledCampaign->id,
            'room_user_id' => $roomUser->id,
            'original_amount' => 50000,
            'sponsor_amount' => 0,
            'paid_amount' => 0,
            'remaining_amount' => 50000,
            'status' => DebtStatus::Unpaid,
        ]);

        $response = $this->actingAs($user, 'web')->get('/rooms/cong-nghe/orders');

        $response->assertOk();
        $response->assertSeeText(__('room.orders.step_1_title'));
        $response->assertSeeText(__('room.orders.step_confirmed_title'));
        $response->assertSeeText(__('room.orders.step_delivered_title'));
        $response->assertSeeText(__('room.orders.step_completed_title'));
        $response->assertSee('Trà sữa ô long');
        $response->assertSee('90.000đ');
        $response->assertSee('Size L');
        $response->assertSee('30% đường');
        $response->assertSee('50% đá');
        $response->assertDontSee('Cancelled Drink');
        $response->assertSee(__('room.orders.confirm_modal_title'));
        $response->assertSee('bg-blue-600 hover:bg-blue-700', false);
        $response->assertSee('openPaymentConfirm', false);
        $response->assertSee('showGlobalLoading', false);
        $response->assertDontSee(__('room.orders.pay_now_vietqr').' (90.000đ)');
        $response->assertDontSee('Chờ duyệt thanh toán');
        $response->assertDontSee('confirm(', false);

        $debtResponse = $this->actingAs($user, 'web')->get('/rooms/cong-nghe/debts');
        $debtResponse->assertOk();
        $debtResponse->assertSee('Coffee Live');
        $debtResponse->assertDontSee('Cancelled Campaign');
        $debtResponse->assertDontSee('Cancelled Drink');
        $response->assertDontSee('Lịch sử đơn trong Room');
        $response->assertDontSee('5. Đang giao');

        $this->actingAs($user, 'web')
            ->get('/rooms/cong-nghe/debts')
            ->assertOk()
            ->assertSee('Coffee Live')
            ->assertDontSee('Cancelled Campaign');
    }
}

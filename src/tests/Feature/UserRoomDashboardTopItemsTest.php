<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CampaignStatus;
use App\Enums\GlobalUserStatus;
use App\Enums\OrderStatus;
use App\Enums\RoomStatus;
use App\Enums\RoomUserStatus;
use App\Models\Campaign;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Models\RoomUser;
use App\Services\Dashboard\UserRoomDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoomDashboardTopItemsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Rank five selected items across valid orders in the live campaign.
     *
     * @return void
     */
    public function test_dashboard_ranks_top_five_items_and_excludes_cancelled_orders(): void
    {
        $room = Room::create(['name' => 'Technology', 'slug' => 'technology-top-items', 'status' => RoomStatus::Active]);
        $campaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Live coffee',
            'restaurant' => 'Test Restaurant',
            'status' => CampaignStatus::Active,
        ]);
        $user = $this->user('current@example.test');
        $member = $this->member($room, $user, 'CURRENT');
        $order = $member->orders()->create([
            'room_id' => $room->id, 'campaign_id' => $campaign->id,
            'subtotal' => 210000, 'final_amount' => 210000, 'status' => OrderStatus::Submitted,
        ]);
        foreach (['A' => 6, 'B' => 5, 'C' => 4, 'D' => 3, 'E' => 2, 'F' => 1] as $name => $quantity) {
            $order->items()->create([
                'item_name' => 'Drink '.$name, 'unit_price' => 10000,
                'quantity' => $quantity, 'line_subtotal' => $quantity * 10000,
            ]);
        }

        $other = $this->member($room, $this->user('cancelled@example.test'), 'CANCELLED');
        $cancelled = $other->orders()->create([
            'room_id' => $room->id, 'campaign_id' => $campaign->id,
            'subtotal' => 990000, 'final_amount' => 990000, 'status' => OrderStatus::Cancelled,
        ]);
        $cancelled->items()->create([
            'item_name' => 'Cancelled drink', 'unit_price' => 10000,
            'quantity' => 99, 'line_subtotal' => 990000,
        ]);

        $data = app(UserRoomDashboardService::class)->getDashboardData($room, $member, $user);
        $popularItems = $data['activeCampaign']['popular_items'];

        $this->assertCount(5, $popularItems);
        $this->assertSame(['Drink A', 'Drink B', 'Drink C', 'Drink D', 'Drink E'], $popularItems->pluck('name')->all());
        $this->assertSame([6, 5, 4, 3, 2], $popularItems->pluck('quantity')->all());
        $this->assertNotContains('Cancelled drink', $popularItems->pluck('name'));
    }

    /**
     * The placed-orders shortcut only shows once the member has a valid order in the live campaign.
     *
     * @return void
     */
    public function test_placed_orders_button_is_hidden_until_member_orders_in_live_campaign(): void
    {
        $room = Room::create(['name' => 'Technology', 'slug' => 'technology-placed-orders', 'status' => RoomStatus::Active]);
        $campaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Live coffee',
            'restaurant' => 'Test Restaurant',
            'status' => CampaignStatus::Active,
        ]);
        $user = $this->user('placed@example.test');
        $member = $this->member($room, $user, 'PLACED');
        $dashboardUrl = route('user.dashboard', $room->slug);
        $service = app(UserRoomDashboardService::class);

        $this->assertFalse($service->getDashboardData($room, $member, $user)['activeCampaign']['has_ordered']);
        $this->actingAs($user, 'web')->get($dashboardUrl)->assertOk()
            ->assertSee(route('user.campaigns.index', $room->slug), false)
            ->assertDontSee(__('room.dashboard.placed_orders'));

        // A cancelled order does not count as having ordered.
        $order = $member->orders()->create([
            'room_id' => $room->id, 'campaign_id' => $campaign->id,
            'subtotal' => 10000, 'final_amount' => 10000, 'status' => OrderStatus::Cancelled,
        ]);
        $this->assertFalse($service->getDashboardData($room, $member, $user)['activeCampaign']['has_ordered']);

        $order->update(['status' => OrderStatus::Submitted]);
        $this->assertTrue($service->getDashboardData($room, $member, $user)['activeCampaign']['has_ordered']);
        $this->actingAs($user, 'web')->get($dashboardUrl)->assertOk()->assertSee(__('room.dashboard.placed_orders'));
    }

    /**
     * The orders page renders a submitted order whose items carry toppings (priced by unit_price).
     *
     * @return void
     */
    public function test_orders_page_renders_order_with_toppings(): void
    {
        $room = Room::create(['name' => 'Technology', 'slug' => 'technology-topping-order', 'status' => RoomStatus::Active]);
        $campaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Live coffee',
            'restaurant' => 'Test Restaurant',
            'status' => CampaignStatus::Active,
        ]);
        $user = $this->user('topping@example.test');
        $member = $this->member($room, $user, 'TOPPING');
        $order = $member->orders()->create([
            'room_id' => $room->id, 'campaign_id' => $campaign->id,
            'subtotal' => 35000, 'final_amount' => 35000, 'status' => OrderStatus::Submitted,
        ]);
        $item = $order->items()->create([
            'item_name' => 'Milk tea', 'unit_price' => 30000, 'quantity' => 1, 'line_subtotal' => 35000,
        ]);
        $item->toppings()->create([
            'topping_name' => 'Pearl', 'unit_price' => 5000, 'quantity' => 1, 'subtotal' => 5000,
        ]);

        $this->actingAs($user, 'web')
            ->get(route('user.orders.index', $room->slug))
            ->assertOk()
            ->assertSee('Pearl')
            ->assertSee('+'.\App\Support\Helpers\FormatHelper::formatCurrency(5000), false);
    }

    /**
     * Create an active global account.
     *
     * @param string $email Unique account email.
     * @return GlobalUser Created account.
     */
    private function user(string $email): GlobalUser
    {
        return GlobalUser::create(['name' => 'Member', 'email' => $email, 'status' => GlobalUserStatus::Active]);
    }

    /**
     * Create an active membership.
     *
     * @param Room $room Target room.
     * @param GlobalUser $user Global account.
     * @param string $code Unique room code.
     * @return RoomUser Created membership.
     */
    private function member(Room $room, GlobalUser $user, string $code): RoomUser
    {
        return RoomUser::create([
            'room_id' => $room->id, 'global_user_id' => $user->id,
            'user_code' => $code, 'display_name' => $user->name,
            'normalized_name' => strtoupper($user->name), 'status' => RoomUserStatus::Active,
            'joined_at' => now(),
        ]);
    }
}

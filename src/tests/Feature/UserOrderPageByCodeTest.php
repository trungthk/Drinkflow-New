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
use App\Models\Order;
use App\Models\Room;
use App\Models\RoomUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The member order page (/rooms/{room}/orders/{order:code}/view) is addressed by the order code and
 * renders the same content as the order list page.
 */
class UserOrderPageByCodeTest extends TestCase
{
    use RefreshDatabase;

    private Room $room;
    private RoomUser $member;
    private GlobalUser $user;
    private Campaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();

        $this->room = Room::create(['name' => 'Code Room', 'slug' => 'code-room', 'status' => RoomStatus::Active]);
        $this->user = $this->user('owner@example.test');
        $this->member = $this->member($this->room, $this->user, 'OWNER');
        $this->campaign = Campaign::create([
            'room_id' => $this->room->id,
            'name' => 'Live coffee',
            'restaurant' => 'Test Restaurant',
            'status' => CampaignStatus::Active,
        ]);
    }

    /**
     * The route binds the order by code, and the generated URL carries the code instead of the id.
     *
     * @return void
     */
    public function test_order_page_route_binds_and_generates_by_code(): void
    {
        $order = $this->order(OrderStatus::Submitted);

        $route = \Illuminate\Support\Facades\Route::getRoutes()->getByName('user.orders.page');
        $this->assertSame('code', $route->bindingFieldFor('order'), 'The route must bind the order by its code.');

        $url = route('user.orders.page', ['room' => $this->room, 'order' => $order]);
        $this->assertStringContainsString('/orders/' . $order->code . '/view', $url);
        $this->assertStringNotContainsString('/orders/' . $order->id . '/view', $url);
    }

    /**
     * A member opens their own order with the code and sees the order content.
     *
     * @return void
     */
    public function test_member_opens_own_order_by_code(): void
    {
        $order = $this->order(OrderStatus::Submitted, ['final_amount' => 45000]);
        $order->items()->create([
            'item_name' => 'Trà sữa matcha', 'unit_price' => 45000, 'quantity' => 1, 'line_subtotal' => 45000,
        ]);

        $this->actingAs($this->user, 'web')
            ->get(route('user.orders.page', ['room' => $this->room, 'order' => $order->code]))
            ->assertOk()
            ->assertSee($order->code)
            ->assertSee('Trà sữa matcha');
    }

    /**
     * The single order page renders the same view (and therefore the same blocks) as the order list.
     *
     * @return void
     */
    public function test_order_page_renders_the_same_view_as_the_order_list(): void
    {
        $order = $this->order(OrderStatus::Submitted);
        $order->items()->create([
            'item_name' => 'Cà phê sữa', 'unit_price' => 30000, 'quantity' => 2, 'line_subtotal' => 60000,
        ]);

        $list = $this->actingAs($this->user, 'web')->get(route('user.orders.index', $this->room->slug))->assertOk();
        $detail = $this->actingAs($this->user, 'web')
            ->get(route('user.orders.page', ['room' => $this->room, 'order' => $order->code]))
            ->assertOk();

        // Same view: the shared blocks of the order list must all be present on the single order page.
        $blocks = [
            __('room.orders.realtime_progress'),
            __('room.orders.items_list'),
            __('room.orders.final_amount'),
        ];
        foreach ($blocks as $block) {
            $this->assertStringContainsString($block, $detail->getContent(), 'Missing block from the shared order view: ' . $block);
        }

        $this->assertStringContainsString('Cà phê sữa', $list->getContent());
        $this->assertStringContainsString('Cà phê sữa', $detail->getContent());
    }

    /**
     * A code that does not exist must not resolve.
     *
     * @return void
     */
    public function test_unknown_order_code_returns_not_found(): void
    {
        $this->actingAs($this->user, 'web')
            ->get(route('user.orders.page', ['room' => $this->room, 'order' => 'ORD-DOES-NOT-EXIST']))
            ->assertNotFound();
    }

    /**
     * The previous id-based URL no longer resolves, proving the binding truly moved to the code.
     *
     * @return void
     */
    public function test_previous_id_based_url_returns_not_found(): void
    {
        $order = $this->order(OrderStatus::Submitted);

        $this->actingAs($this->user, 'web')
            ->get('/rooms/' . $this->room->slug . '/orders/' . $order->id . '/view')
            ->assertNotFound();
    }

    /**
     * A member cannot open an order belonging to somebody else through its code.
     *
     * @return void
     */
    public function test_member_cannot_open_another_members_order_by_code(): void
    {
        $stranger = $this->member($this->room, $this->user('stranger@example.test'), 'STRANGER');
        $foreignOrder = $stranger->orders()->create([
            'room_id' => $this->room->id, 'campaign_id' => $this->campaign->id,
            'subtotal' => 20000, 'final_amount' => 20000, 'status' => OrderStatus::Submitted,
        ]);

        $this->actingAs($this->user, 'web')
            ->get(route('user.orders.page', ['room' => $this->room, 'order' => $foreignOrder->code]))
            ->assertNotFound();
    }

    /**
     * A proxy order placed for another member stays reachable by the member who placed it.
     *
     * @return void
     */
    public function test_member_can_open_a_proxy_order_they_placed(): void
    {
        $parent = $this->order(OrderStatus::Submitted);
        $child = $parent->children()->create([
            'room_id' => $this->room->id, 'campaign_id' => $this->campaign->id,
            'room_user_id' => $this->member($this->room, $this->user('friend@example.test'), 'FRIEND')->id,
            'subtotal' => 15000, 'final_amount' => 15000, 'status' => OrderStatus::Submitted,
        ]);

        $this->actingAs($this->user, 'web')
            ->get(route('user.orders.page', ['room' => $this->room, 'order' => $child->code]))
            ->assertOk()
            ->assertSee($child->code);
    }

    /**
     * An order whose campaign is already closed is still reachable, so stored notification links
     * keep working after the campaign ends.
     *
     * @return void
     */
    public function test_order_of_a_closed_campaign_is_still_reachable(): void
    {
        $closed = Campaign::create([
            'room_id' => $this->room->id,
            'name' => 'Closed coffee',
            'restaurant' => 'Test Restaurant',
            'status' => CampaignStatus::Closed,
        ]);
        $order = $this->member->orders()->create([
            'room_id' => $this->room->id, 'campaign_id' => $closed->id,
            'subtotal' => 25000, 'final_amount' => 25000, 'status' => OrderStatus::Completed,
        ]);

        $this->actingAs($this->user, 'web')
            ->get(route('user.orders.page', ['room' => $this->room, 'order' => $order->code]))
            ->assertOk()
            ->assertSee($order->code);
    }

    /**
     * The order list page still resolves after the shared data moved into the service.
     *
     * @return void
     */
    public function test_order_list_page_still_works(): void
    {
        $order = $this->order(OrderStatus::Submitted);
        $order->items()->create([
            'item_name' => 'Bạc xỉu', 'unit_price' => 25000, 'quantity' => 1, 'line_subtotal' => 25000,
        ]);

        $this->actingAs($this->user, 'web')
            ->get(route('user.orders.index', $this->room->slug))
            ->assertOk()
            ->assertSee('Bạc xỉu');
    }

    /**
     * Create an order of the fixture campaign for the fixture member.
     *
     * @param OrderStatus $status Order status.
     * @param array<string, mixed> $attributes Extra attributes.
     * @return Order Created order.
     */
    private function order(OrderStatus $status, array $attributes = []): Order
    {
        return $this->member->orders()->create($attributes + [
            'room_id' => $this->room->id,
            'campaign_id' => $this->campaign->id,
            'subtotal' => 30000,
            'final_amount' => 30000,
            'status' => $status,
        ]);
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
     * @param string $code Unique room user code.
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

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\User\JoinRoomAction;
use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\CampaignItem;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Models\RoomUser;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProxyOrderRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    /** The member lookup used to pick an order-on-behalf recipient never returns the requester. */
    public function test_lookup_excludes_the_requester_but_finds_other_members(): void
    {
        [$me, $meRu, $other, $otherRu, $room] = $this->twoMembers();

        foreach ([$meRu->user_code, $me->email] as $ownIdentifier) {
            $this->actingAs($me, 'web')
                ->getJson(route('user.room-members.lookup', [$room, 'q' => $ownIdentifier]))
                ->assertStatus(422)
                ->assertJsonPath('message', __('room.campaign.proxy_self_not_allowed'));
        }

        $this->actingAs($me, 'web')
            ->getJson(route('user.room-members.lookup', [$room, 'q' => $otherRu->user_code]))
            ->assertOk()
            ->assertJsonPath('user_code', $otherRu->user_code);
    }

    /** A cart item cannot be assigned to the requester, on add or on update. */
    public function test_cart_rejects_assigning_items_to_yourself(): void
    {
        [$me, $meRu, $other, $otherRu, $room] = $this->twoMembers();
        [$campaign, $item] = $this->campaign($room);
        $cart = route('user.campaigns.cart.store', [$room, $campaign]);

        $this->actingAs($me, 'web')->postJson($cart, ['item_id' => $item->id, 'quantity' => 1, 'proxy_user_code' => $meRu->user_code])
            ->assertStatus(422)->assertJsonValidationErrors('proxy_user_code');

        $this->actingAs($me, 'web')->postJson($cart, ['item_id' => $item->id, 'quantity' => 1])->assertOk();
        $this->actingAs($me, 'web')->postJson($cart, ['item_id' => $item->id, 'quantity' => 1])->assertOk();

        $proxy = fn (int $index) => route('user.campaigns.cart.proxy', [$room, $campaign, $index]);
        $this->actingAs($me, 'web')->patchJson($proxy(1), ['proxy_user_code' => $meRu->user_code])
            ->assertStatus(422)->assertJsonValidationErrors('proxy_user_code');
    }

    /** Items can go to others only while at least one item stays with the requester. */
    public function test_cart_keeps_at_least_one_own_item_when_ordering_for_others(): void
    {
        [$me, $meRu, $other, $otherRu, $room] = $this->twoMembers();
        [$campaign, $item] = $this->campaign($room);
        $cart = route('user.campaigns.cart.store', [$room, $campaign]);
        $proxy = fn (int $index) => route('user.campaigns.cart.proxy', [$room, $campaign, $index]);

        $this->actingAs($me, 'web')->postJson($cart, ['item_id' => $item->id, 'quantity' => 1])->assertOk();
        // The only item cannot be given away.
        $this->actingAs($me, 'web')->patchJson($proxy(0), ['proxy_user_code' => $otherRu->user_code, 'proxy_user_name' => 'Other'])
            ->assertStatus(422)->assertJsonPath('errors.proxy_user_code.0', __('room.campaign.proxy_requires_own_order'));

        $this->actingAs($me, 'web')->postJson($cart, ['item_id' => $item->id, 'quantity' => 1])->assertOk();
        $this->actingAs($me, 'web')->patchJson($proxy(1), ['proxy_user_code' => $otherRu->user_code, 'proxy_user_name' => 'Other'])
            ->assertOk()->assertJsonPath('data.1.proxy_user_code', $otherRu->user_code);
        // ...and once one is given away, the last own item cannot be.
        $this->actingAs($me, 'web')->patchJson($proxy(0), ['proxy_user_code' => $otherRu->user_code, 'proxy_user_name' => 'Other'])
            ->assertStatus(422);
    }

    /** Checkout rejects an order that only contains items for other members (no own order). */
    public function test_checkout_rejects_proxy_only_orders(): void
    {
        [$me, $meRu, $other, $otherRu, $room] = $this->twoMembers();
        [$campaign, $item] = $this->campaign($room);

        $this->actingAs($me, 'web')
            ->postJson(route('user.orders.store', [$room, $campaign]), [
                'items' => [['item_id' => $item->id, 'quantity' => 1, 'proxy_user_code' => $otherRu->user_code]],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('orders', 0);
    }

    /** Checkout rejects an item assigned to the requester through proxy_user_code. */
    public function test_checkout_rejects_ordering_for_yourself_via_proxy_code(): void
    {
        [$me, $meRu, $other, $otherRu, $room] = $this->twoMembers();
        [$campaign, $item] = $this->campaign($room);

        $this->actingAs($me, 'web')
            ->postJson(route('user.orders.store', [$room, $campaign]), [
                'items' => [
                    ['item_id' => $item->id, 'quantity' => 1],
                    ['item_id' => $item->id, 'quantity' => 1, 'proxy_user_code' => strtolower($meRu->user_code)],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonPath('errors.items.0', __('room.campaign.proxy_self_not_allowed'));

        $this->assertDatabaseCount('orders', 0);
    }

    /** With an own item, ordering for another member still works and creates the parent and child orders. */
    public function test_checkout_allows_ordering_for_others_when_requester_orders_too(): void
    {
        [$me, $meRu, $other, $otherRu, $room] = $this->twoMembers();
        [$campaign, $item] = $this->campaign($room);

        $this->actingAs($me, 'web')
            ->postJson(route('user.orders.store', [$room, $campaign]), [
                'items' => [
                    ['item_id' => $item->id, 'quantity' => 1],
                    ['item_id' => $item->id, 'quantity' => 1, 'proxy_user_code' => $otherRu->user_code],
                ],
            ])
            ->assertCreated();

        $parent = $meRu->orders()->where('campaign_id', $campaign->id)->whereNull('parent_id')->firstOrFail();
        $this->assertSame(1, $parent->items()->count());
        $this->assertDatabaseHas('orders', ['room_user_id' => $otherRu->id, 'parent_id' => $parent->id]);
    }

    /**
     * Two active members of the same room.
     *
     * @return array{0: GlobalUser, 1: RoomUser, 2: GlobalUser, 3: RoomUser, 4: Room} Requester, other member, room.
     */
    private function twoMembers(): array
    {
        $room = Room::create(['name' => 'Proxy Room', 'slug' => 'proxy-room']);
        $join = app(JoinRoomAction::class);
        $me = GlobalUser::create(['name' => 'Requester', 'normalized_name' => 'REQUESTER', 'email' => 'requester@company.com']);
        $other = GlobalUser::create(['name' => 'Colleague', 'normalized_name' => 'COLLEAGUE', 'email' => 'colleague@company.com']);

        return [
            $me, $join->execute($me, $room, 'device-me', 'hash-me'),
            $other, $join->execute($other, $room, 'device-other', 'hash-other'),
            $room,
        ];
    }

    /**
     * A live campaign with one active item.
     *
     * @param Room $room Room owning the campaign.
     * @return array{0: Campaign, 1: CampaignItem} Campaign and item.
     */
    private function campaign(Room $room): array
    {
        $campaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Proxy campaign',
            'restaurant' => 'Cafe',
            'status' => CampaignStatus::Active,
            'deadline' => now()->addHour(),
        ]);
        $item = CampaignItem::create([
            'campaign_id' => $campaign->id,
            'name' => 'Green tea',
            'normalized_name' => 'GREEN TEA',
            'base_price' => 20000,
            'status' => 'active',
        ]);

        return [$campaign, $item];
    }
}

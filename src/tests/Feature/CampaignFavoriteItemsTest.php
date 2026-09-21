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

class CampaignFavoriteItemsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create a room, an active campaign and a joined member.
     *
     * @return array{0: GlobalUser, 1: Room, 2: \App\Models\RoomUser, 3: Campaign}
     */
    private function makeRoomWithCampaign(string $slug = 'mens-est'): array
    {
        $user = GlobalUser::create([
            'name' => 'Favorite Member',
            'normalized_name' => 'FAVORITE MEMBER',
            'email' => "member-{$slug}@company.com",
            'status' => 'active',
        ]);
        $room = Room::create(['name' => 'Room ' . $slug, 'slug' => $slug, 'status' => 'active']);
        $roomUser = app(JoinRoomAction::class)->execute($user, $room, 'Chrome', "hash-{$slug}");
        $campaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Campaign ' . $slug,
            'restaurant' => 'DrinkFlow Cafe',
            'status' => CampaignStatus::Active,
        ]);

        return [$user, $room, $roomUser, $campaign];
    }

    /**
     * Add one order (a campaign allows a single order per member) with one line item, placed by a new room member.
     */
    private function addOrderItem(Room $room, Campaign $campaign, string $name, int $quantity, OrderStatus $status = OrderStatus::Submitted): void
    {
        static $sequence = 0;
        $sequence++;

        $member = GlobalUser::create([
            'name' => "Orderer {$sequence}",
            'normalized_name' => "ORDERER {$sequence}",
            'email' => "orderer-{$sequence}@company.com",
            'status' => 'active',
        ]);
        $roomUser = app(JoinRoomAction::class)->execute($member, $room, 'Chrome', "orderer-hash-{$sequence}");

        $order = Order::create([
            'room_id' => $room->id,
            'campaign_id' => $campaign->id,
            'room_user_id' => $roomUser->id,
            'subtotal' => 30000 * $quantity,
            'final_amount' => 30000 * $quantity,
            'status' => $status,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'item_name' => $name,
            'unit_price' => 30000,
            'quantity' => $quantity,
            'line_subtotal' => 30000 * $quantity,
        ]);
    }

    /** The favorite items are served by the JSON API, ranked by quantity, ignoring cancelled orders and capped at five. */
    public function test_favorite_items_api_returns_ranked_top_five_without_cancelled_orders(): void
    {
        [$user, $room, , $campaign] = $this->makeRoomWithCampaign();

        foreach (['Trà đào' => 6, 'Trà vải' => 5, 'Cà phê muối' => 4, 'Matcha' => 3, 'Bạc xỉu' => 2, 'Sữa tươi' => 1] as $name => $quantity) {
            $this->addOrderItem($room, $campaign, $name, $quantity);
        }
        $this->addOrderItem($room, $campaign, 'Món đã hủy', 50, OrderStatus::Cancelled);

        $response = $this->actingAs($user, 'web')
            ->getJson("/rooms/{$room->slug}/campaigns/{$campaign->id}/favorite-items");

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('data.0', ['rank' => 1, 'name' => 'Trà đào', 'quantity' => 6])
            ->assertJsonPath('data.1', ['rank' => 2, 'name' => 'Trà vải', 'quantity' => 5])
            ->assertJsonPath('data.4', ['rank' => 5, 'name' => 'Bạc xỉu', 'quantity' => 2])
            ->assertJsonMissing(['name' => 'Món đã hủy'])
            ->assertJsonMissing(['name' => 'Sữa tươi']);
    }

    /** A campaign belonging to another room must not be readable through this room's URL. */
    public function test_favorite_items_api_rejects_campaign_from_another_room(): void
    {
        [$user, $room] = $this->makeRoomWithCampaign('room-a');
        [, , , $otherCampaign] = $this->makeRoomWithCampaign('room-b');

        $this->actingAs($user, 'web')
            ->getJson("/rooms/{$room->slug}/campaigns/{$otherCampaign->id}/favorite-items")
            ->assertNotFound();
    }

    /** Guests and non-members cannot call the favorite items API. */
    public function test_favorite_items_api_requires_room_membership(): void
    {
        [, $room, , $campaign] = $this->makeRoomWithCampaign();
        $outsider = GlobalUser::create([
            'name' => 'Outsider',
            'normalized_name' => 'OUTSIDER',
            'email' => 'outsider@company.com',
            'status' => 'active',
        ]);

        $this->getJson("/rooms/{$room->slug}/campaigns/{$campaign->id}/favorite-items")->assertUnauthorized();
        $this->actingAs($outsider, 'web')
            ->getJson("/rooms/{$room->slug}/campaigns/{$campaign->id}/favorite-items")
            ->assertForbidden();
    }

    /** The campaigns page no longer embeds favorite items; it only exposes the API URL used on button click. */
    public function test_campaigns_page_loads_favorite_items_through_api_only(): void
    {
        [$user, $room, , $campaign] = $this->makeRoomWithCampaign();
        $this->addOrderItem($room, $campaign, 'Món chỉ có trong API', 3);

        $this->actingAs($user, 'web')->get("/rooms/{$room->slug}/campaigns")
            ->assertOk()
            ->assertSee("/rooms\/{$room->slug}\/campaigns\/{$campaign->id}\/favorite-items", false)
            ->assertDontSee('Món chỉ có trong API');
    }

    /** The room dashboard shows medal icons instead of rank numbers and only the bracketed quantity. */
    public function test_dashboard_top_items_use_medal_icons_and_bracketed_quantity(): void
    {
        [$user, $room, , $campaign] = $this->makeRoomWithCampaign();
        $this->addOrderItem($room, $campaign, 'Trà đào', 4);
        $this->addOrderItem($room, $campaign, 'Trà vải', 2);

        $response = $this->actingAs($user, 'web')->get("/rooms/{$room->slug}/dashboard");

        $response->assertOk()
            ->assertSee('military_tech')
            ->assertSee('text-amber-500', false)
            ->assertSee('text-slate-400', false)
            ->assertSee('(4)')
            ->assertSee('(2)')
            ->assertDontSee(__('room.dashboard.rank_label', ['rank' => 3]), false);
    }

    /** Dashboard and API share one cache entry keyed by room and user for 10 seconds, then refresh. */
    public function test_favorite_items_are_cached_per_room_and_user_for_ten_seconds(): void
    {
        [$user, $room, $roomUser, $campaign] = $this->makeRoomWithCampaign();
        $this->addOrderItem($room, $campaign, 'Trà đào', 2);

        $api = "/rooms/{$room->slug}/campaigns/{$campaign->id}/favorite-items";
        $this->actingAs($user, 'web')->getJson($api)->assertJsonPath('data.0.quantity', 2);
        $this->assertTrue(\Illuminate\Support\Facades\Cache::has("favorite-items:{$room->id}:{$user->id}:{$campaign->id}"));

        // A new order placed within the TTL is not visible yet, neither on the API nor on the dashboard.
        $this->addOrderItem($room, $campaign, 'Trà đào', 5);
        $this->actingAs($user, 'web')->getJson($api)->assertJsonPath('data.0.quantity', 2);
        $this->actingAs($user, 'web')->get("/rooms/{$room->slug}/dashboard")->assertOk()->assertSee('(2)')->assertDontSee('(7)');

        // After the TTL expires both read the fresh totals.
        $this->travel(11)->seconds();
        $this->actingAs($user, 'web')->getJson($api)->assertJsonPath('data.0.quantity', 7);
        $this->actingAs($user, 'web')->get("/rooms/{$room->slug}/dashboard")->assertOk()->assertSee('(7)');
    }
}

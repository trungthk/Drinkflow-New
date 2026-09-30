<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Order\CreateOrderAction;
use App\Actions\User\JoinRoomAction;
use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\CampaignItem;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Models\RoomUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * UPG-03.1: a member's orders JSON never contains other members' orders.
 */
class MemberOrdersJsonTest extends TestCase
{
    use RefreshDatabase;

    private Room $room;

    private Campaign $campaign;

    private CampaignItem $item;

    protected function setUp(): void
    {
        parent::setUp();
        $this->room = Room::create(['name' => 'Json room', 'slug' => 'json-room']);
        $this->campaign = Campaign::create(['room_id' => $this->room->id, 'name' => 'Lunch', 'restaurant' => 'Cafe', 'status' => CampaignStatus::Active, 'deadline' => now()->addHour(), 'delivery_fee' => 10000]);
        $this->item = CampaignItem::create(['campaign_id' => $this->campaign->id, 'name' => 'Tea', 'normalized_name' => 'TEA', 'base_price' => 20000, 'status' => 'active']);
    }

    /**
     * Member with an order of the given quantity.
     *
     * @param string $name Member name.
     * @param int $quantity Ordered quantity.
     * @return array{0: GlobalUser, 1: RoomUser, 2: \App\Models\Order} User, membership, order.
     */
    private function memberWithOrder(string $name, int $quantity): array
    {
        $user = GlobalUser::create(['name' => $name, 'normalized_name' => mb_strtoupper($name), 'email' => mb_strtolower($name).'@company.com']);
        $roomUser = app(JoinRoomAction::class)->execute($user, $this->room, 'device-'.$name, 'hash-'.$name);
        $order = app(CreateOrderAction::class)->execute($this->campaign, $roomUser, ['items' => [['item_id' => $this->item->id, 'quantity' => $quantity]], 'note' => "note of {$name}"]);

        return [$user, $roomUser, $order];
    }

    public function test_json_lists_only_the_members_own_orders(): void
    {
        [$alice, , $aliceOrder] = $this->memberWithOrder('Alice', 1);
        [, $bobMember, $bobOrder] = $this->memberWithOrder('Bob', 3);

        $response = $this->actingAs($alice, 'web')->getJson(route('user.orders.index', $this->room))->assertOk();

        $response->assertJsonPath('data.0.id', $aliceOrder->id)->assertJsonCount(1, 'data');
        $json = $response->getContent();
        $this->assertStringNotContainsString('note of Bob', $json);
        $this->assertStringNotContainsString((string) $bobOrder->code, $json);
        $this->assertStringNotContainsString('"room_user_id"', $json);
        $this->assertStringNotContainsString('"orders"', $json);
        $this->assertArrayNotHasKey('settings', $response->json('data.0.campaign'));
        $this->assertSame($bobMember->room_id, $this->room->id);
    }

    public function test_orders_page_still_renders_the_members_share(): void
    {
        [$alice] = $this->memberWithOrder('Alice', 1);
        $this->memberWithOrder('Bob', 3);

        // Alice ordered 20,000 of 80,000: a quarter of the 10,000 delivery fee.
        $this->actingAs($alice, 'web')->get(route('user.orders.index', $this->room))
            ->assertOk()
            ->assertSee(\App\Support\Helpers\FormatHelper::formatCurrency(22500));
    }
}

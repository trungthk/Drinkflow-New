<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Order\CreateOrderAction;
use App\Actions\User\JoinRoomAction;
use App\Enums\CampaignStatus;
use App\Enums\OrderStatus;
use App\Exceptions\ActiveOrderExistsException;
use App\Models\Campaign;
use App\Models\CampaignItem;
use App\Models\GlobalUser;
use App\Models\Order;
use App\Models\Room;
use App\Models\RoomUser;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * UPG-01.1: one active order per member per campaign, enforced by the action (every database) and by
 * a unique index (partial index on SQLite, generated column + unique index on MySQL).
 */
class ActiveOrderIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private Room $room;

    private Campaign $campaign;

    private CampaignItem $item;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->room = Room::create(['name' => 'Integrity', 'slug' => 'integrity']);
        $this->campaign = Campaign::create(['room_id' => $this->room->id, 'name' => 'Lunch', 'restaurant' => 'Cafe', 'status' => CampaignStatus::Active, 'deadline' => now()->addHour()]);
        $this->item = CampaignItem::create(['campaign_id' => $this->campaign->id, 'name' => 'Tea', 'normalized_name' => 'TEA', 'base_price' => 20000, 'status' => 'active']);
    }

    /**
     * Active member of the room.
     *
     * @param string $name Member name.
     * @return array{0: GlobalUser, 1: RoomUser} User and membership.
     */
    private function member(string $name): array
    {
        $user = GlobalUser::create(['name' => $name, 'normalized_name' => mb_strtoupper($name), 'email' => mb_strtolower($name).'@company.com']);

        return [$user, app(JoinRoomAction::class)->execute($user, $this->room, 'device-'.$name, 'hash-'.$name)];
    }

    private function order(RoomUser $roomUser): Order
    {
        return app(CreateOrderAction::class)->execute($this->campaign, $roomUser, ['items' => [['item_id' => $this->item->id, 'quantity' => 1]]]);
    }

    public function test_action_refuses_a_second_active_order_before_touching_the_database(): void
    {
        [, $roomUser] = $this->member('An');
        $this->order($roomUser);

        try {
            $this->order($roomUser);
            $this->fail('A second active order was created.');
        } catch (ActiveOrderExistsException $exception) {
            $this->assertArrayHasKey('order', $exception->errors());
        }
        $this->assertSame(1, Order::query()->where('room_user_id', $roomUser->id)->count());
    }

    public function test_second_submit_returns_active_order_exists(): void
    {
        [$user, $roomUser] = $this->member('Binh');
        $first = $this->order($roomUser);

        $this->actingAs($user, 'web')
            ->postJson(route('user.orders.store', [$this->room, $this->campaign]), ['items' => [['item_id' => $this->item->id, 'quantity' => 2]]])
            ->assertUnprocessable()
            ->assertJsonPath('code', ActiveOrderExistsException::CODE)
            ->assertJsonPath('order_id', $first->id);
    }

    public function test_member_can_order_again_after_the_active_order_is_cancelled(): void
    {
        [, $roomUser] = $this->member('Chi');
        $first = $this->order($roomUser);
        $first->update(['status' => OrderStatus::Cancelled]);

        $second = $this->order($roomUser);

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame(1, Order::query()->where('room_user_id', $roomUser->id)->whereIn('status', OrderStatus::activeValues())->count());
    }

    public function test_proxy_order_for_a_member_with_an_active_order_is_rolled_back(): void
    {
        [$requester] = $this->member('Dung');
        [, $colleague] = $this->member('Em');
        $this->order($colleague);

        $this->actingAs($requester, 'web')
            ->postJson(route('user.orders.store', [$this->room, $this->campaign]), ['items' => [
                ['item_id' => $this->item->id, 'quantity' => 1],
                ['item_id' => $this->item->id, 'quantity' => 1, 'proxy_user_code' => $colleague->user_code],
            ]])
            ->assertUnprocessable()
            ->assertJsonPath('code', ActiveOrderExistsException::CODE);

        // The requester's own (parent) order is rolled back with the refused child order.
        $this->assertSame(1, Order::query()->where('campaign_id', $this->campaign->id)->count());
    }

    public function test_unique_index_violation_is_recognised(): void
    {
        $this->assertTrue(ActiveOrderExistsException::isViolation(new \RuntimeException("Duplicate entry '1-2' for key 'orders.orders_one_active_per_campaign_member'")));
        $this->assertTrue(ActiveOrderExistsException::isViolation(new \RuntimeException('UNIQUE constraint failed: orders.campaign_id, orders.room_user_id')));
        $this->assertFalse(ActiveOrderExistsException::isViolation(new \RuntimeException('Duplicate entry for key orders_code_unique')));
    }

    public function test_database_index_still_blocks_a_bypassing_insert(): void
    {
        [, $roomUser] = $this->member('Giang');
        $first = $this->order($roomUser);

        $this->expectException(QueryException::class);
        Order::query()->insert(collect($first->getAttributes())->except(['id', 'code', 'active_order_key'])->merge(['code' => 'DUP-1'])->all());
    }
}

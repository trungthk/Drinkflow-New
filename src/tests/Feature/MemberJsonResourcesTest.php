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
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * UPG-03.3: member JSON endpoints return explicit resources, never raw Room/Order/Campaign models.
 */
class MemberJsonResourcesTest extends TestCase
{
    use RefreshDatabase;

    private GlobalUser $user;

    private RoomUser $roomUser;

    private Room $room;

    private Campaign $campaign;

    private CampaignItem $item;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->room = Room::create(['name' => 'Resource room', 'slug' => 'resource-room', 'status' => 'active', 'settings' => ['secret_setting' => 'hidden-value']]);
        $this->user = GlobalUser::create(['name' => 'Member', 'normalized_name' => 'MEMBER', 'email' => 'member@company.com']);
        $this->roomUser = app(JoinRoomAction::class)->execute($this->user, $this->room, 'device-m', 'hash-m');
        $this->campaign = Campaign::create(['room_id' => $this->room->id, 'name' => 'Lunch', 'restaurant' => 'Cafe', 'status' => CampaignStatus::Active, 'deadline' => now()->addHour()]);
        $this->item = CampaignItem::create(['campaign_id' => $this->campaign->id, 'name' => 'Tea', 'normalized_name' => 'TEA', 'base_price' => 20000, 'status' => 'active']);
    }

    private function assertNoInternalFields(TestResponse $response): void
    {
        $json = (string) $response->getContent();
        foreach (['"settings"', 'secret_setting', '"owner_admin_id"', '"creator_admin_id"', '"global_user_id"', '"sponsor_allocations"', '"normalized_name"'] as $needle) {
            $this->assertStringNotContainsString($needle, $json, "JSON exposes {$needle}");
        }
    }

    public function test_campaign_detail_keeps_the_menu_fields_used_by_the_page(): void
    {
        $response = $this->actingAs($this->user, 'web')->getJson(route('user.campaigns.show', [$this->room, $this->campaign]))->assertOk();

        $response->assertJsonPath('data.items.0.id', $this->item->id)
            ->assertJsonPath('data.items.0.name', 'Tea')
            ->assertJsonPath('data.room.slug', 'resource-room');
        $this->assertEquals(20000, $response->json('data.items.0.base_price'));
        $this->assertNoInternalFields($response);
    }

    public function test_order_store_and_show_return_an_order_resource(): void
    {
        $stored = $this->actingAs($this->user, 'web')
            ->postJson(route('user.orders.store', [$this->room, $this->campaign]), ['items' => [['item_id' => $this->item->id, 'quantity' => 1]]])
            ->assertCreated()
            ->assertJsonPath('data.final_amount', 20000);
        $orderId = (int) $stored->json('data.id');
        $this->assertNoInternalFields($stored);

        $shown = $this->actingAs($this->user, 'web')->getJson(route('user.orders.show', [$this->room, $orderId]))->assertOk()
            ->assertJsonPath('data.id', $orderId)
            ->assertJsonPath('data.room.name', 'Resource room');
        $this->assertNoInternalFields($shown);
    }

    public function test_room_show_and_join_return_public_room_fields(): void
    {
        $shown = $this->actingAs($this->user, 'web')->getJson(route('user.rooms.show', $this->room))->assertOk()
            ->assertJsonPath('data.slug', 'resource-room')
            ->assertJsonPath('room_user.user_code', $this->roomUser->user_code);
        $this->assertNoInternalFields($shown);

        $joined = $this->actingAs($this->user, 'web')->postJson(route('user.rooms.join', $this->room))->assertOk()
            ->assertJsonPath('data.room.slug', 'resource-room');
        $this->assertNoInternalFields($joined);
    }

    public function test_global_rooms_and_payments_json_hide_internal_fields(): void
    {
        app(CreateOrderAction::class)->execute($this->campaign, $this->roomUser, ['items' => [['item_id' => $this->item->id, 'quantity' => 1]]]);

        $rooms = $this->actingAs($this->user, 'web')->getJson(route('user.me.rooms'))->assertOk()
            ->assertJsonPath('data.0.room.slug', 'resource-room');
        $this->assertNoInternalFields($rooms);

        $payments = $this->actingAs($this->user, 'web')->getJson(route('user.me.payments'))->assertOk();
        $this->assertNoInternalFields($payments);
    }
}

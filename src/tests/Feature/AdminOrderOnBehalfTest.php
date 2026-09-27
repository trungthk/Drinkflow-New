<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\CampaignStatus;
use App\Enums\OrderStatus;
use App\Models\AdminAccount;
use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\CampaignItem;
use App\Models\GlobalUser;
use App\Models\Order;
use App\Models\Room;
use App\Models\RoomUser;
use App\Models\UserNotification;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminOrderOnBehalfTest extends TestCase
{
    use RefreshDatabase;

    private AdminAccount $admin;

    private Room $room;

    private Campaign $campaign;

    private CampaignItem $item;

    private RoomUser $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->admin = AdminAccount::create([
            'name' => 'Room Admin',
            'email' => 'on-behalf-admin@example.test',
            'password' => Hash::make('secret'),
            'role' => AdminRole::Admin,
            'status' => 'active',
        ]);
        $this->room = Room::create(['name' => 'On Behalf Room', 'slug' => 'on-behalf-room', 'status' => 'active']);
        $this->admin->rooms()->attach($this->room);

        $this->campaign = Campaign::create([
            'room_id' => $this->room->id,
            'name' => 'Trà chiều',
            'restaurant' => 'Cafe',
            'status' => CampaignStatus::Active,
            'deadline' => now()->addHour(),
        ]);
        $this->item = $this->campaign->items()->create([
            'name' => 'Trà sữa',
            'normalized_name' => 'tra sua',
            'base_price' => 30000,
            'status' => 'active',
        ]);

        $this->member = $this->makeMember('member@example.test', 'Nguyễn Văn A');
    }

    private function makeMember(string $email, string $name): RoomUser
    {
        $user = GlobalUser::create(['name' => $name, 'email' => $email, 'status' => 'active']);

        return RoomUser::create(['room_id' => $this->room->id, 'global_user_id' => $user->id, 'display_name' => $name, 'status' => 'active']);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'campaign_id' => $this->campaign->id,
            'room_user_id' => $this->member->id,
            'items' => [['item_id' => $this->item->id, 'quantity' => 2]],
            'note' => 'Ít đá',
        ], $overrides);
    }

    public function test_admin_places_order_for_member(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.orders.on-behalf', $this->room), $this->payload())
            ->assertCreated();

        $order = Order::query()->findOrFail($response->json('data.id'));
        $this->assertSame($this->member->id, $order->room_user_id);
        $this->assertSame($this->admin->id, $order->placed_by_admin_id);
        $this->assertSame(60000, (int) $order->subtotal);
        $this->assertSame(OrderStatus::Submitted, $order->status);
        $this->assertSame('Ít đá', $order->note);
        $this->assertTrue(AuditLog::query()->where('event', 'order.placed_on_behalf')->where('target_id', $order->id)->exists());
        $this->assertTrue(UserNotification::query()->where('global_user_id', $this->member->global_user_id)->where('type', 'order.created')->exists());
    }

    public function test_member_with_active_order_is_rejected(): void
    {
        $url = route('admin.orders.on-behalf', $this->room);
        $this->actingAs($this->admin, 'admin')->postJson($url, $this->payload())->assertCreated();

        $this->actingAs($this->admin, 'admin')->postJson($url, $this->payload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('room_user_id');
    }

    public function test_member_from_another_room_is_not_found(): void
    {
        $otherRoom = Room::create(['name' => 'Other', 'slug' => 'other-on-behalf', 'status' => 'active']);
        $user = GlobalUser::create(['name' => 'Outsider', 'email' => 'outsider@example.test', 'status' => 'active']);
        $outsider = RoomUser::create(['room_id' => $otherRoom->id, 'global_user_id' => $user->id, 'display_name' => 'Outsider', 'status' => 'active']);

        $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.orders.on-behalf', $this->room), $this->payload(['room_user_id' => $outsider->id]))
            ->assertNotFound();
        $this->assertSame(0, Order::query()->count());
    }

    public function test_admin_of_another_room_cannot_order(): void
    {
        $stranger = AdminAccount::create([
            'name' => 'Stranger',
            'email' => 'stranger-admin@example.test',
            'password' => Hash::make('secret'),
            'role' => AdminRole::Admin,
            'status' => 'active',
        ]);

        $this->actingAs($stranger, 'admin')
            ->postJson(route('admin.orders.on-behalf', $this->room), $this->payload())
            ->assertForbidden();
        $this->assertSame(0, Order::query()->count());
    }

    public function test_closed_campaign_cannot_be_ordered(): void
    {
        $this->campaign->update(['status' => CampaignStatus::Closed]);

        $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.orders.on-behalf', $this->room), $this->payload())
            ->assertUnprocessable();
        $this->assertSame(0, Order::query()->count());
    }

    public function test_orders_page_shows_button_and_only_members_without_order(): void
    {
        $ordered = $this->makeMember('ordered@example.test', 'Đã Đặt');
        Order::create([
            'room_id' => $this->room->id,
            'campaign_id' => $this->campaign->id,
            'room_user_id' => $ordered->id,
            'subtotal' => 30000,
            'final_amount' => 30000,
            'status' => OrderStatus::Submitted,
        ]);

        $html = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.page', $this->room))
            ->assertOk()
            ->assertSee(__('admin.on_behalf_button'))
            ->assertSee('id="order-on-behalf-modal"', false)
            ->getContent();

        $modal = (string) str($html)->after('id="order-on-behalf-modal"');
        $this->assertStringContainsString('<option value="' . $this->member->id . '">', $modal);
        $this->assertStringNotContainsString('<option value="' . $ordered->id . '">', $modal);
    }

    public function test_orders_page_hides_button_when_campaign_closed(): void
    {
        $this->campaign->update(['status' => CampaignStatus::Closed]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.page', $this->room))
            ->assertOk()
            ->assertDontSee('id="order-on-behalf-modal"', false);
    }
}

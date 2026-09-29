<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CampaignStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\CampaignItem;
use App\Models\CampaignItemSize;
use App\Models\CampaignItemTopping;
use App\Models\GlobalUser;
use App\Models\Order;
use App\Models\Room;
use App\Models\RoomUser;
use App\Models\UserNotification;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminOrderEditTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private Room $room;

    private Campaign $campaign;

    private CampaignItem $tea;

    private CampaignItem $coffee;

    private CampaignItemSize $large;

    private CampaignItemTopping $pearl;

    private RoomUser $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->admin = Admin::create([
            'name' => 'Room Admin',
            'email' => 'edit-order-admin@example.test',
            'password' => Hash::make('secret'),
            'status' => 'active',
        ]);
        $this->room = Room::create(['name' => 'Edit Order Room', 'slug' => 'edit-order-room', 'status' => 'active']);
        $this->admin->rooms()->attach($this->room);

        $this->campaign = Campaign::create([
            'room_id' => $this->room->id,
            'name' => 'Trà chiều',
            'restaurant' => 'Cafe',
            'status' => CampaignStatus::Active,
            'deadline' => now()->addHour(),
        ]);
        $this->tea = $this->campaign->items()->create(['name' => 'Trà sữa', 'normalized_name' => 'tra sua', 'base_price' => 30000, 'status' => 'active']);
        $this->coffee = $this->campaign->items()->create(['name' => 'Cà phê', 'normalized_name' => 'ca phe', 'base_price' => 20000, 'status' => 'active']);
        $this->large = $this->tea->sizes()->create(['name' => 'L', 'price_delta' => 5000, 'status' => 'active']);
        $this->pearl = $this->tea->toppings()->create(['name' => 'Trân châu', 'price' => 7000, 'status' => 'active']);

        $user = GlobalUser::create(['name' => 'Nguyễn Văn A', 'email' => 'edit-member@example.test', 'status' => 'active']);
        $this->member = RoomUser::create(['room_id' => $this->room->id, 'global_user_id' => $user->id, 'display_name' => 'Nguyễn Văn A', 'status' => 'active']);
    }

    private function placeOrder(): Order
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.orders.on-behalf', $this->room), [
                'campaign_id' => $this->campaign->id,
                'room_user_id' => $this->member->id,
                'items' => [['item_id' => $this->coffee->id, 'quantity' => 1]],
            ])
            ->assertCreated();

        return Order::query()->findOrFail($response->json('data.id'));
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'items' => [
                ['item_id' => $this->tea->id, 'quantity' => 2, 'size_id' => $this->large->id, 'topping_ids' => [$this->pearl->id], 'note' => 'Ít đường'],
                ['item_id' => $this->coffee->id, 'quantity' => 1, 'is_self_paid' => true],
            ],
            'note' => 'Giao trước 3h',
        ], $overrides);
    }

    public function test_admin_replaces_items_with_quantity_size_toppings_and_self_paid(): void
    {
        $order = $this->placeOrder();

        $this->actingAs($this->admin, 'admin')
            ->putJson(route('admin.orders.items.update', [$this->room, $order]), $this->payload())
            ->assertOk()
            ->assertJsonPath('data.id', $order->id);

        $order->refresh()->load('items.toppings');
        // (30000 + 5000 + 7000) × 2 + 20000
        $this->assertSame(104000, (int) $order->subtotal);
        $this->assertSame(104000, (int) $order->final_amount);
        $this->assertSame('Giao trước 3h', $order->note);
        $this->assertCount(2, $order->items);

        $tea = $order->items->firstWhere('campaign_item_id', $this->tea->id);
        $this->assertSame(2, (int) $tea->quantity);
        $this->assertSame('L', $tea->size_name);
        $this->assertSame('Ít đường', $tea->note);
        $this->assertSame(['Trân châu'], $tea->toppings->pluck('topping_name')->all());
        $this->assertFalse((bool) $tea->is_self_paid);
        $this->assertTrue((bool) $order->items->firstWhere('campaign_item_id', $this->coffee->id)->is_self_paid);

        $this->assertTrue(AuditLog::query()->where('event', 'order.items_updated')->where('target_id', $order->id)->exists());
        $this->assertTrue(UserNotification::query()->where('global_user_id', $this->member->global_user_id)->where('type', 'order.updated')->exists());
    }

    public function test_self_paid_lines_are_excluded_from_full_sponsorship(): void
    {
        $this->campaign->update(['sponsor_type' => Campaign::SPONSOR_TYPE_FULL]);
        $order = $this->placeOrder();

        $this->actingAs($this->admin, 'admin')
            ->putJson(route('admin.orders.items.update', [$this->room, $order]), $this->payload())
            ->assertOk();

        $order->refresh();
        $this->assertSame(84000, (int) $order->sponsor_amount);
        $this->assertSame(20000, (int) $order->final_amount);
    }

    public function test_proxy_fields_are_ignored_and_no_child_order_is_created(): void
    {
        $order = $this->placeOrder();
        $payload = $this->payload();
        $payload['items'][0]['proxy_user_code'] = 'SOMEONE';

        $this->actingAs($this->admin, 'admin')
            ->putJson(route('admin.orders.items.update', [$this->room, $order]), $payload)
            ->assertOk();

        $this->assertSame(1, Order::query()->count());
        $this->assertSame($this->member->id, $order->refresh()->room_user_id);
    }

    public function test_empty_items_are_rejected(): void
    {
        $order = $this->placeOrder();

        $this->actingAs($this->admin, 'admin')
            ->putJson(route('admin.orders.items.update', [$this->room, $order]), ['items' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');
    }

    public function test_paid_or_pending_payment_order_cannot_be_edited(): void
    {
        $order = $this->placeOrder();

        foreach ([PaymentStatus::Paid, PaymentStatus::Pending] as $paymentStatus) {
            $order->update(['payment_status' => $paymentStatus]);

            $this->actingAs($this->admin, 'admin')
                ->putJson(route('admin.orders.items.update', [$this->room, $order]), $this->payload())
                ->assertUnprocessable()
                ->assertJsonValidationErrors('order');
        }
        $this->assertSame(20000, (int) $order->refresh()->subtotal);
    }

    public function test_cancelled_order_cannot_be_edited(): void
    {
        $order = $this->placeOrder();
        $order->update(['status' => OrderStatus::Cancelled]);

        $this->actingAs($this->admin, 'admin')
            ->putJson(route('admin.orders.items.update', [$this->room, $order]), $this->payload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('order');
    }

    public function test_order_of_closed_campaign_cannot_be_edited(): void
    {
        $order = $this->placeOrder();
        $this->campaign->update(['status' => CampaignStatus::Closed]);

        $this->actingAs($this->admin, 'admin')
            ->putJson(route('admin.orders.items.update', [$this->room, $order]), $this->payload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('campaign');
        $this->assertSame(20000, (int) $order->refresh()->subtotal);
    }

    public function test_order_can_still_be_edited_after_the_deadline(): void
    {
        $order = $this->placeOrder();
        $this->campaign->update(['deadline' => now()->subMinute()]);

        $this->actingAs($this->admin, 'admin')
            ->putJson(route('admin.orders.items.update', [$this->room, $order]), $this->payload())
            ->assertOk();
        $this->assertSame(104000, (int) $order->refresh()->subtotal);

        // Past the deadline: editing stays available, placing a new order on behalf does not.
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.page', $this->room))
            ->assertOk()
            ->assertSee('data-order-edit="' . $order->id . '"', false)
            ->assertSee('id="order-on-behalf-modal"', false)
            ->assertDontSee('data-on-behalf-open', false);
    }

    public function test_orders_table_shows_member_email_and_one_line_per_item(): void
    {
        $order = $this->placeOrder();
        $this->actingAs($this->admin, 'admin')
            ->putJson(route('admin.orders.items.update', [$this->room, $order]), $this->payload())
            ->assertOk();

        $html = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.page', $this->room))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-order-member-email>edit-member@example.test</div>', $html);
        $lines = (string) str($html)->after('data-order-item-lines>')->before('</ul>');
        $this->assertSame(2, substr_count($lines, '<li '));
        $this->assertStringContainsString('2× Trà sữa (L) + Trân châu</li>', $lines);
        $this->assertStringContainsString('1× Cà phê</li>', $lines);
    }

    public function test_orders_page_filters_by_order_type_and_offers_code_copy(): void
    {
        $adminOrder = $this->placeOrder();
        $makeMember = function (string $email): RoomUser {
            $user = GlobalUser::create(['name' => $email, 'email' => $email, 'status' => 'active']);

            return RoomUser::create(['room_id' => $this->room->id, 'global_user_id' => $user->id, 'display_name' => $email, 'status' => 'active']);
        };
        $newOrder = fn (RoomUser $member, ?int $parentId = null): Order => Order::create([
            'room_id' => $this->room->id, 'campaign_id' => $this->campaign->id, 'room_user_id' => $member->id,
            'parent_id' => $parentId, 'subtotal' => 20000, 'final_amount' => 20000, 'status' => OrderStatus::Submitted,
        ]);
        $selfOrder = $newOrder($makeMember('self-orderer@example.test'));
        $proxyOrder = $newOrder($makeMember('proxy-receiver@example.test'), $selfOrder->id);

        $rows = function (string $type): string {
            $html = $this->actingAs($this->admin, 'admin')
                ->get(route('admin.orders.page', ['room' => $this->room, 'type' => $type]))
                ->assertOk()
                ->getContent();

            return (string) str($html)->after('id="orders-tbody"')->before('</tbody>');
        };
        $expectations = [
            'self' => $selfOrder,
            'admin' => $adminOrder,
            'proxy' => $proxyOrder,
        ];
        foreach ($expectations as $type => $expected) {
            $body = $rows($type);
            foreach ([$selfOrder, $adminOrder, $proxyOrder] as $order) {
                $order->is($expected)
                    ? $this->assertStringContainsString('data-order-row="' . $order->id . '"', $body, $type)
                    : $this->assertStringNotContainsString('data-order-row="' . $order->id . '"', $body, $type);
            }
        }

        // An unknown type is ignored and lists every order, each with a copy button for its code.
        $all = $rows('bogus');
        foreach ([$selfOrder, $adminOrder, $proxyOrder] as $order) {
            $this->assertStringContainsString('data-order-row="' . $order->id . '"', $all);
            $this->assertStringContainsString('data-copy="' . $order->refresh()->code . '"', $all);
        }
    }

    public function test_member_and_item_selects_are_searchable(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.page', $this->room))
            ->assertOk()
            ->assertSee('data-on-behalf-member required data-searchable="true"', false)
            ->assertSee('data-on-behalf-item data-searchable="true"', false)
            ->assertSee('data-search="edit-member@example.test"', false);
    }

    public function test_admin_of_another_room_cannot_edit(): void
    {
        $order = Order::create([
            'room_id' => $this->room->id,
            'campaign_id' => $this->campaign->id,
            'room_user_id' => $this->member->id,
            'subtotal' => 20000,
            'final_amount' => 20000,
            'status' => OrderStatus::Submitted,
        ]);
        $stranger = Admin::create([
            'name' => 'Stranger',
            'email' => 'edit-stranger@example.test',
            'password' => Hash::make('secret'),
            'status' => 'active',
        ]);

        $this->actingAs($stranger, 'admin')
            ->putJson(route('admin.orders.items.update', [$this->room, $order]), $this->payload())
            ->assertForbidden();
        $this->assertSame(20000, (int) $order->refresh()->subtotal);
    }

    public function test_orders_page_shows_edit_button_for_editable_orders(): void
    {
        $order = $this->placeOrder();

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.page', $this->room))
            ->assertOk()
            ->assertSee('data-order-edit="' . $order->id . '"', false)
            ->assertSee(__('admin.edit_order_btn'));

        $order->update(['payment_status' => PaymentStatus::Paid]);
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.page', $this->room))
            ->assertOk()
            ->assertDontSee('data-order-edit="' . $order->id . '"', false);
    }
}

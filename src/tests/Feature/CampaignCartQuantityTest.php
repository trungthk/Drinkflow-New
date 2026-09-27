<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\CampaignItem;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Models\RoomUser;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignCartQuantityTest extends TestCase
{
    use RefreshDatabase;

    private GlobalUser $user;

    private Room $room;

    private Campaign $campaign;

    private CampaignItem $item;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->room = Room::create(['name' => 'Qty Room', 'slug' => 'qty-room', 'status' => 'active']);
        $this->user = GlobalUser::create(['name' => 'Qty Member', 'email' => 'qty-member@example.test', 'status' => 'active']);
        RoomUser::create(['room_id' => $this->room->id, 'global_user_id' => $this->user->id, 'display_name' => 'Qty Member', 'status' => 'active']);
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
    }

    public function test_customize_modal_renders_quantity_stepper_above_note(): void
    {
        $html = $this->actingAs($this->user, 'web')
            ->get(route('user.campaigns.index', $this->room))
            ->assertOk()
            ->assertSee('id="custom-item-quantity"', false)
            ->assertSee(__('room.campaign.quantity_increase'))
            ->assertSee(__('room.campaign.quantity_decrease'))
            ->getContent();

        $this->assertLessThan(
            strpos($html, __('room.campaign.note_label')),
            strpos($html, 'id="custom-item-quantity"'),
        );
    }

    public function test_cart_stores_chosen_quantity(): void
    {
        $this->actingAs($this->user, 'web')
            ->postJson(route('user.campaigns.cart.store', [$this->room, $this->campaign]), [
                'item_id' => $this->item->id,
                'quantity' => 3,
            ])
            ->assertOk()
            ->assertJsonPath('data.0.quantity', 3);
    }

    public function test_order_line_total_is_capped_by_per_product_budget(): void
    {
        $this->campaign->update(['max_budget' => 100000]);
        $this->item->update(['base_price' => 10000]);
        $url = route('user.orders.store', [$this->room, $this->campaign]);

        $this->actingAs($this->user, 'web')
            ->postJson($url, ['items' => [['item_id' => $this->item->id, 'quantity' => 11]]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');
        $this->assertSame(0, \App\Models\Order::query()->count());

        $this->actingAs($this->user, 'web')
            ->postJson($url, ['items' => [['item_id' => $this->item->id, 'quantity' => 10]]])
            ->assertCreated();
        $this->assertSame(100000, (int) \App\Models\Order::query()->sole()->subtotal);
    }

    public function test_cart_rejects_invalid_quantities(): void
    {
        foreach ([0, 100, 1.5, 'abc'] as $quantity) {
            $this->actingAs($this->user, 'web')
                ->postJson(route('user.campaigns.cart.store', [$this->room, $this->campaign]), [
                    'item_id' => $this->item->id,
                    'quantity' => $quantity,
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('quantity');
        }
    }
}

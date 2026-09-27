<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\CampaignStatus;
use App\Enums\OrderStatus;
use App\Models\AdminAccount;
use App\Models\Campaign;
use App\Models\GlobalUser;
use App\Models\Order;
use App\Models\Room;
use App\Models\RoomUser;
use App\Support\Helpers\FormatHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminCampaignListColumnsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The list shows total main items and the gross store total (non-cancelled orders only), the start
     * time beside the restaurant, and the status column right before the actions column.
     *
     * @return void
     */
    public function test_campaign_list_shows_items_gross_total_start_time_and_status_before_actions(): void
    {
        $admin = AdminAccount::create(['name' => 'List Admin', 'email' => 'list-columns@example.test', 'password' => Hash::make('secret'), 'role' => AdminRole::Admin, 'status' => 'active']);
        $room = Room::create(['name' => 'List Room', 'slug' => 'list-columns-room', 'status' => 'active']);
        $admin->rooms()->attach($room);
        $campaign = Campaign::create([
            'room_id' => $room->id, 'name' => 'Trà chiều', 'restaurant' => 'Cafe Mộc', 'status' => CampaignStatus::Active,
            'started_at' => now()->setTime(9, 15), 'delivery_fee' => 15000, 'discount' => 5000,
        ]);

        $makeOrder = function (string $email, OrderStatus $status, int $subtotal, array $quantities) use ($room, $campaign): void {
            $user = GlobalUser::create(['name' => $email, 'email' => $email, 'status' => 'active']);
            $member = RoomUser::create(['room_id' => $room->id, 'global_user_id' => $user->id, 'display_name' => $email, 'status' => 'active']);
            $order = Order::create([
                'room_id' => $room->id, 'campaign_id' => $campaign->id, 'room_user_id' => $member->id,
                'subtotal' => $subtotal, 'final_amount' => $subtotal, 'status' => $status,
            ]);
            foreach ($quantities as $quantity) {
                $line = $order->items()->create(['item_name' => 'Trà sữa', 'unit_price' => 10000, 'quantity' => $quantity, 'line_subtotal' => 10000 * $quantity]);
                // Toppings are not main items and must not add to the count.
                $line->toppings()->create(['topping_name' => 'Trân châu', 'unit_price' => 5000, 'quantity' => 1, 'subtotal' => 5000 * $quantity]);
            }
        };
        $makeOrder('a@example.test', OrderStatus::Submitted, 30000, [2, 1]);
        $makeOrder('b@example.test', OrderStatus::Confirmed, 20000, [2]);
        $makeOrder('c@example.test', OrderStatus::Cancelled, 90000, [9]);

        $html = $this->actingAs($admin, 'admin')
            ->get(route('admin.campaigns.page', $room))
            ->assertOk()
            ->assertSee(__('admin.th_total_items'))
            // Gross total = 30.000 + 20.000 + 15.000 delivery − 5.000 discount.
            ->assertSee(FormatHelper::formatCurrency(60000))
            ->assertSee(now()->setTime(9, 15)->format('H:i d/m/Y'))
            ->getContent();

        $this->assertMatchesRegularExpression('/data-campaign-total-items>\s*5 ' . preg_quote(__('admin.items_unit'), '/') . '\s*</', $html);

        $thead = (string) str($html)->after('<thead>')->before('</thead>');
        $this->assertLessThan(strpos($thead, __('admin.th_actions')), strpos($thead, __('admin.th_status')));
        $this->assertLessThan(strpos($thead, __('admin.th_status')), strpos($thead, __('admin.th_store_total')));

        // "Đóng chiến dịch" opens the shared close-campaign summary modal used on the campaign detail page.
        $this->assertStringContainsString('data-close-campaign-open data-campaign-id="' . $campaign->id . '"', $html);
        $this->assertStringContainsString('data-close-campaign-modal', $html);
        $this->assertStringNotContainsString('id="close-campaign-modal"', $html);
    }

    /**
     * Search only matches the campaign code, campaign name and restaurant name.
     *
     * @return void
     */
    public function test_campaign_search_matches_only_code_name_and_restaurant(): void
    {
        $admin = AdminAccount::create(['name' => 'Search Admin', 'email' => 'list-search@example.test', 'password' => Hash::make('secret'), 'role' => AdminRole::Admin, 'status' => 'active']);
        $room = Room::create(['name' => 'Search Room', 'slug' => 'list-search-room', 'status' => 'active']);
        $admin->rooms()->attach($room);
        $byCode = Campaign::create(['room_id' => $room->id, 'name' => 'Alpha', 'restaurant' => 'Store One', 'status' => CampaignStatus::Closed]);
        Campaign::create(['room_id' => $room->id, 'name' => 'Trà Chiều Beta', 'restaurant' => 'Store Two', 'status' => CampaignStatus::Closed]);
        Campaign::create(['room_id' => $room->id, 'name' => 'Gamma', 'restaurant' => 'Phúc Long Gamma', 'status' => CampaignStatus::Closed]);
        Campaign::create(['room_id' => $room->id, 'name' => 'Delta', 'restaurant' => 'Store Four', 'description' => 'uniquedescword', 'status' => CampaignStatus::Closed]);

        $this->actingAs($admin, 'admin');
        $search = fn (string $term) => $this->get(route('admin.campaigns.page', ['room' => $room, 'search' => $term]))->assertOk();

        $search((string) $byCode->refresh()->code)->assertSee('Alpha')->assertDontSee('Gamma');
        $search('trà chiều')->assertSee('Trà Chiều Beta')->assertDontSee('Alpha');
        $search('phúc long')->assertSee('Phúc Long Gamma')->assertDontSee('Alpha');
        $search('uniquedescword')->assertDontSee('Delta')->assertSee(__('admin.no_campaigns_matching_filters'));
    }
}

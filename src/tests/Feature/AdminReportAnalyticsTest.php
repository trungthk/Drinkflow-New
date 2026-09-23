<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\CampaignStatus;
use App\Enums\DebtStatus;
use App\Enums\OrderStatus;
use App\Models\AdminAccount;
use App\Models\Campaign;
use App\Models\Debt;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Models\RoomUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminReportAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The "Top 5" drinks/stores panels must never load more than 5 rows, even when more
     * distinct items exist in the period.
     *
     * @return void
     */
    public function test_top_drinks_and_stores_are_limited_to_five(): void
    {
        $admin = $this->admin();
        $room = $this->roomFor($admin);
        $campaign = Campaign::create(['room_id' => $room->id, 'name' => 'Lunch', 'restaurant' => 'Cafe', 'status' => CampaignStatus::Active]);

        // A room only allows one order per member per campaign, so use a distinct member per item.
        foreach (range(1, 7) as $i) {
            $member = $this->member($room, "top5-member-{$i}@example.test", "TOP5{$i}");
            $order = $member->orders()->create([
                'room_id' => $room->id, 'campaign_id' => $campaign->id,
                'subtotal' => 10000, 'final_amount' => 10000, 'status' => OrderStatus::Submitted,
            ]);
            $order->items()->create([
                'item_name' => "Drink {$i}", 'unit_price' => 10000, 'quantity' => 8 - $i, 'line_subtotal' => 10000,
            ]);
        }

        $response = $this->actingAs($admin, 'admin')->getJson("/admin/{$room->id}/reports?period=all&tab=products");

        $response->assertOk();
        $this->assertCount(5, $response->json('data.popular_drinks'));
    }

    /**
     * The debts tab must only list members who still owe money, not everyone who ever had a debt.
     *
     * @return void
     */
    public function test_debts_tab_only_lists_users_with_outstanding_debt(): void
    {
        $admin = $this->admin();
        $room = $this->roomFor($admin);
        $campaign = Campaign::create(['room_id' => $room->id, 'name' => 'Lunch', 'restaurant' => 'Cafe', 'status' => CampaignStatus::Closed]);

        $settledMember = $this->member($room, 'settled@example.test', 'SETTLED');
        Debt::create([
            'room_id' => $room->id, 'campaign_id' => $campaign->id, 'room_user_id' => $settledMember->id,
            'original_amount' => 50000, 'paid_amount' => 50000, 'remaining_amount' => 0, 'status' => DebtStatus::Paid,
        ]);

        $owingMember = $this->member($room, 'owing@example.test', 'OWING');
        Debt::create([
            'room_id' => $room->id, 'campaign_id' => $campaign->id, 'room_user_id' => $owingMember->id,
            'original_amount' => 80000, 'paid_amount' => 0, 'remaining_amount' => 80000, 'status' => DebtStatus::Unpaid,
        ]);

        $response = $this->actingAs($admin, 'admin')->getJson("/admin/{$room->id}/reports?period=all&tab=debts");

        $response->assertOk()
            ->assertJsonCount(1, 'data.debts_by_user')
            ->assertJsonPath('data.debts_by_user.0.user_email', 'owing@example.test');
    }

    /**
     * The sponsors tab must only list the campaigns' actual sponsors, never a member who simply
     * received a subsidy on their own order.
     *
     * @return void
     */
    public function test_sponsors_tab_only_lists_actual_campaign_sponsors(): void
    {
        $admin = $this->admin();
        $room = $this->roomFor($admin);
        $member = $this->member($room, 'beneficiary@example.test', 'BENEF');

        $unsponsoredCampaign = Campaign::create([
            'room_id' => $room->id, 'name' => 'No sponsor', 'restaurant' => 'Cafe',
            'status' => CampaignStatus::Active, 'sponsor_type' => 'none',
        ]);
        $member->orders()->create([
            'room_id' => $room->id, 'campaign_id' => $unsponsoredCampaign->id,
            'subtotal' => 90000, 'sponsor_amount' => 90000, 'final_amount' => 0, 'status' => OrderStatus::Submitted,
        ]);

        $sponsoredCampaign = Campaign::create([
            'room_id' => $room->id, 'name' => 'Sponsored', 'restaurant' => 'Cafe',
            'status' => CampaignStatus::Active, 'sponsor_type' => 'per_item', 'sponsor_name' => 'Công ty ABC',
        ]);
        $member->orders()->create([
            'room_id' => $room->id, 'campaign_id' => $sponsoredCampaign->id,
            'subtotal' => 50000, 'sponsor_amount' => 20000, 'final_amount' => 30000, 'status' => OrderStatus::Submitted,
        ]);

        $response = $this->actingAs($admin, 'admin')->getJson("/admin/{$room->id}/reports?period=all&tab=sponsors");

        $response->assertOk()
            ->assertJsonCount(1, 'data.sponsors_leaderboard')
            ->assertJsonPath('data.sponsors_leaderboard.0.user_name', 'Công ty ABC')
            ->assertJsonPath('data.sponsors_leaderboard.0.total_sponsored', 20000);
    }

    private function admin(string $email = 'report-admin@example.test'): AdminAccount
    {
        return AdminAccount::create(['name' => 'Report Admin', 'email' => $email, 'password' => Hash::make('secret'), 'role' => AdminRole::Admin, 'status' => 'active']);
    }

    private function roomFor(AdminAccount $admin, string $slug = 'report-analytics-room'): Room
    {
        $room = Room::create(['name' => 'Report Room', 'slug' => $slug, 'status' => 'active']);
        $admin->rooms()->attach($room);

        return $room;
    }

    private function member(Room $room, string $email, string $code): RoomUser
    {
        $user = GlobalUser::create(['name' => 'Report Member', 'normalized_name' => 'REPORT MEMBER', 'email' => $email, 'status' => 'active']);

        return RoomUser::create([
            'room_id' => $room->id, 'global_user_id' => $user->id,
            'user_code' => $code, 'display_name' => $user->name,
            'normalized_name' => strtoupper($user->name), 'status' => 'active',
            'joined_at' => now(),
        ]);
    }
}

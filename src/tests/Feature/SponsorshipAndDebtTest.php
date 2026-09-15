<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Campaign\CloseCampaignAction;
use App\Actions\Order\CreateOrderAction;
use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\CampaignItem;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Models\RoomUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SponsorshipAndDebtTest extends TestCase
{
    use RefreshDatabase;

    /** Verify a per-item sponsor applies only its configured amount. */
    public function test_per_item_sponsorship_is_applied_to_an_order(): void
    {
        [$campaign, $member] = $this->campaignAndMember('per_item', null);
        $item = CampaignItem::create(['campaign_id' => $campaign->id, 'name' => 'Coffee', 'normalized_name' => 'COFFEE', 'base_price' => 30000, 'sponsor_amount' => 10000, 'status' => 'active']);

        $order = app(CreateOrderAction::class)->execute($campaign, $member, ['items' => [['item_id' => $item->id, 'quantity' => 2]]]);

        $this->assertSame(20000, (int) $order->sponsor_amount);
        $this->assertSame(40000, (int) $order->final_amount);
    }

    /** Verify a shared sponsor budget cannot be exceeded by concurrent sequential orders. */
    public function test_budget_sponsorship_stops_at_campaign_limit(): void
    {
        [$campaign, $firstMember] = $this->campaignAndMember('budget', 15000);
        $secondUser = GlobalUser::create(['name' => 'Second', 'normalized_name' => 'SECOND', 'email' => 'second-sponsor@example.test']);
        $secondMember = RoomUser::create(['room_id' => $campaign->room_id, 'global_user_id' => $secondUser->id, 'user_code' => 'SECOND-01', 'display_name' => 'Second', 'normalized_name' => 'SECOND', 'status' => 'active']);
        $item = CampaignItem::create(['campaign_id' => $campaign->id, 'name' => 'Tea', 'normalized_name' => 'TEA', 'base_price' => 10000, 'status' => 'active']);

        $first = app(CreateOrderAction::class)->execute($campaign, $firstMember, ['items' => [['item_id' => $item->id, 'quantity' => 1]]]);
        $second = app(CreateOrderAction::class)->execute($campaign, $secondMember, ['items' => [['item_id' => $item->id, 'quantity' => 1]]]);

        $this->assertSame(10000, (int) $first->sponsor_amount);
        $this->assertSame(5000, (int) $second->sponsor_amount);
    }

    /** Verify closing a campaign snapshots sponsor policy onto its required room-user debt. */
    public function test_closed_campaign_snapshots_sponsor_policy_on_debt(): void
    {
        [$campaign, $member] = $this->campaignAndMember('per_item', null, 'Company pays 5,000 per drink');
        $item = CampaignItem::create(['campaign_id' => $campaign->id, 'name' => 'Juice', 'normalized_name' => 'JUICE', 'base_price' => 15000, 'sponsor_amount' => 5000, 'status' => 'active']);
        app(CreateOrderAction::class)->execute($campaign, $member, ['items' => [['item_id' => $item->id, 'quantity' => 1]]]);

        app(CloseCampaignAction::class)->execute($campaign);

        $this->assertDatabaseHas('debts', ['campaign_id' => $campaign->id, 'room_user_id' => $member->id, 'sponsor_type' => 'per_item', 'sponsor_description' => 'Company pays 5,000 per drink', 'sponsor_amount' => 5000, 'remaining_amount' => 10000]);
    }

    /** Build an active campaign and mandatory room membership fixture. */
    private function campaignAndMember(string $sponsorType, ?int $budget, ?string $description = null): array
    {
        $user = GlobalUser::create(['name' => 'Member', 'normalized_name' => 'MEMBER', 'email' => uniqid('sponsor-', true).'@example.test']);
        $room = Room::create(['name' => 'Sponsor Room', 'slug' => uniqid('sponsor-room-', false)]);
        $member = RoomUser::create(['room_id' => $room->id, 'global_user_id' => $user->id, 'user_code' => 'SP-001', 'display_name' => 'Member', 'normalized_name' => 'MEMBER', 'status' => 'active']);
        $campaign = Campaign::create(['room_id' => $room->id, 'name' => 'Sponsor campaign', 'restaurant' => 'Cafe', 'status' => CampaignStatus::Active, 'sponsor_type' => $sponsorType, 'max_budget' => $budget, 'sponsor_description' => $description]);

        return [$campaign, $member];
    }
}

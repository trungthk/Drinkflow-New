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

/**
 * Covers the "trả riêng" (self-paid) order item feature: items marked self-paid must
 * never be covered by sponsorship and must be billed directly to the ordering member,
 * according to the campaign's configured self_paid_price_basis.
 */
class SelfPaidOrderItemTest extends TestCase
{
    use RefreshDatabase;

    /** A self-paid item must be excluded from full-sponsor coverage as soon as the order is created. */
    public function test_self_paid_item_is_excluded_from_full_sponsor_at_order_creation(): void
    {
        [$campaign, $member] = $this->fullSponsorCampaign();
        $item = CampaignItem::create(['campaign_id' => $campaign->id, 'name' => 'Coffee', 'normalized_name' => 'COFFEE', 'base_price' => 30000, 'status' => 'active']);

        $order = app(CreateOrderAction::class)->execute($campaign, $member, [
            'items' => [
                ['item_id' => $item->id, 'quantity' => 1, 'is_self_paid' => true],
                ['item_id' => $item->id, 'quantity' => 1, 'is_self_paid' => false],
            ],
        ]);

        $this->assertSame(60000, (int) $order->subtotal);
        // Only the non self-paid line (30000) is sponsorable under SPONSOR_TYPE_FULL.
        $this->assertSame(30000, (int) $order->sponsor_amount);
        $this->assertSame(30000, (int) $order->final_amount);
        $this->assertTrue((bool) $order->items()->where('is_self_paid', true)->first()->is_self_paid);
    }

    /** A self-paid item must not consume a per-item sponsor's configured coverage. */
    public function test_self_paid_item_does_not_consume_per_item_sponsor_amount(): void
    {
        $user = GlobalUser::create(['name' => 'Member', 'normalized_name' => 'MEMBER', 'email' => uniqid('selfpaid-', true).'@example.test']);
        $room = Room::create(['name' => 'Self Paid Room', 'slug' => uniqid('self-paid-room-', false)]);
        $member = RoomUser::create(['room_id' => $room->id, 'global_user_id' => $user->id, 'user_code' => 'SP-001', 'display_name' => 'Member', 'normalized_name' => 'MEMBER', 'status' => 'active']);
        $campaign = Campaign::create(['room_id' => $room->id, 'name' => 'Per item campaign', 'restaurant' => 'Cafe', 'status' => CampaignStatus::Active, 'sponsor_type' => Campaign::SPONSOR_TYPE_PER_ITEM]);
        $item = CampaignItem::create(['campaign_id' => $campaign->id, 'name' => 'Tea', 'normalized_name' => 'TEA', 'base_price' => 20000, 'sponsor_amount' => 10000, 'status' => 'active']);

        $order = app(CreateOrderAction::class)->execute($campaign, $member, [
            'items' => [['item_id' => $item->id, 'quantity' => 1, 'is_self_paid' => true]],
        ]);

        $this->assertSame(0, (int) $order->sponsor_amount);
        $this->assertSame(20000, (int) $order->final_amount);
    }

    /**
     * With the default 'original' price basis, closing a fully-sponsored campaign must ghi nợ
     * riêng cho người đặt phần "trả riêng" theo đúng giá gốc, và sponsor chỉ gánh phần còn lại.
     */
    public function test_close_campaign_splits_self_paid_debt_from_full_sponsor_with_original_basis(): void
    {
        [$campaign, $member, $sponsor] = $this->fullSponsorCampaign(withSponsorAllocation: true);
        $sponsoredItem = CampaignItem::create(['campaign_id' => $campaign->id, 'name' => 'Coffee', 'normalized_name' => 'COFFEE', 'base_price' => 10000, 'status' => 'active']);
        $selfPaidItem = CampaignItem::create(['campaign_id' => $campaign->id, 'name' => 'Cake', 'normalized_name' => 'CAKE', 'base_price' => 20000, 'status' => 'active']);

        app(CreateOrderAction::class)->execute($campaign, $member, [
            'items' => [
                ['item_id' => $sponsoredItem->id, 'quantity' => 1, 'is_self_paid' => false],
                ['item_id' => $selfPaidItem->id, 'quantity' => 1, 'is_self_paid' => true],
            ],
        ]);

        app(CloseCampaignAction::class)->execute($campaign);

        // Sponsor only owes the non self-paid coffee.
        $this->assertDatabaseHas('debts', [
            'campaign_id' => $campaign->id,
            'room_user_id' => $sponsor->id,
            'original_amount' => 10000,
            'remaining_amount' => 10000,
            'status' => 'unpaid',
        ]);
        // The ordering member owes the self-paid cake directly, at its raw price (no fee/discount, both zero here anyway).
        $this->assertDatabaseHas('debts', [
            'campaign_id' => $campaign->id,
            'room_user_id' => $member->id,
            'original_amount' => 20000,
            'remaining_amount' => 20000,
            'status' => 'unpaid',
        ]);
        $order = $member->orders()->where('campaign_id', $campaign->id)->firstOrFail();
        $this->assertSame(10000, (int) $order->sponsor_amount);
        $this->assertSame(20000, (int) $order->final_amount);
    }

    /**
     * With 'original' basis, a self-paid item never absorbs any share of the campaign's shared
     * delivery fee: the sponsor bears 100% of it even when the whole order is self-paid.
     */
    public function test_original_basis_excludes_self_paid_item_from_delivery_fee_share(): void
    {
        [$campaign, $member, $sponsor] = $this->fullSponsorCampaign(withSponsorAllocation: true, deliveryFee: 10000);
        $item = CampaignItem::create(['campaign_id' => $campaign->id, 'name' => 'Cake', 'normalized_name' => 'CAKE', 'base_price' => 20000, 'status' => 'active']);

        app(CreateOrderAction::class)->execute($campaign, $member, [
            'items' => [['item_id' => $item->id, 'quantity' => 1, 'is_self_paid' => true]],
        ]);

        app(CloseCampaignAction::class)->execute($campaign);

        // Self-paid member pays the raw item price only, no delivery fee.
        $this->assertDatabaseHas('debts', [
            'campaign_id' => $campaign->id,
            'room_user_id' => $member->id,
            'original_amount' => 20000,
            'remaining_amount' => 20000,
        ]);
        // Sponsor still absorbs the entire delivery fee even though nothing was sponsorable.
        $this->assertDatabaseHas('debts', [
            'campaign_id' => $campaign->id,
            'room_user_id' => $sponsor->id,
            'original_amount' => 0,
            'adjustment_amount' => 10000,
            'remaining_amount' => 10000,
        ]);
    }

    /**
     * With 'campaign_prorated' basis, the self-paid item's debt includes its proportional
     * share of the campaign's shared delivery fee.
     */
    public function test_campaign_prorated_basis_allocates_delivery_fee_share_to_self_paid_debt(): void
    {
        [$campaign, $member, $sponsor] = $this->fullSponsorCampaign(withSponsorAllocation: true, deliveryFee: 10000, priceBasis: Campaign::SELF_PAID_PRICE_BASIS_CAMPAIGN_PRORATED);
        $item = CampaignItem::create(['campaign_id' => $campaign->id, 'name' => 'Cake', 'normalized_name' => 'CAKE', 'base_price' => 20000, 'status' => 'active']);

        app(CreateOrderAction::class)->execute($campaign, $member, [
            'items' => [['item_id' => $item->id, 'quantity' => 1, 'is_self_paid' => true]],
        ]);

        app(CloseCampaignAction::class)->execute($campaign);

        // The whole order is self-paid, so it absorbs the entire prorated delivery fee itself.
        $this->assertDatabaseHas('debts', [
            'campaign_id' => $campaign->id,
            'room_user_id' => $member->id,
            'original_amount' => 30000,
            'remaining_amount' => 30000,
        ]);
        $this->assertDatabaseHas('debts', [
            'campaign_id' => $campaign->id,
            'room_user_id' => $sponsor->id,
            'original_amount' => 0,
            'remaining_amount' => 0,
            'status' => 'paid',
        ]);
    }

    /**
     * Build an active, full-sponsor campaign. When $withSponsorAllocation is true, a dedicated
     * sponsor room user is registered with a 100% allocation, matching the "case 1" branch of
     * CloseCampaignAction.
     *
     * @return array{0: Campaign, 1: RoomUser, 2: ?RoomUser} [campaign, ordering member, sponsor]
     */
    private function fullSponsorCampaign(bool $withSponsorAllocation = false, int $deliveryFee = 0, string $priceBasis = Campaign::SELF_PAID_PRICE_BASIS_ORIGINAL): array
    {
        $user = GlobalUser::create(['name' => 'Member', 'normalized_name' => 'MEMBER', 'email' => uniqid('selfpaid-', true).'@example.test']);
        $room = Room::create(['name' => 'Self Paid Room', 'slug' => uniqid('self-paid-room-', false)]);
        $member = RoomUser::create(['room_id' => $room->id, 'global_user_id' => $user->id, 'user_code' => 'SP-001', 'display_name' => 'Member', 'normalized_name' => 'MEMBER', 'status' => 'active']);

        $sponsor = null;
        $sponsorAllocations = [];
        if ($withSponsorAllocation) {
            $sponsorUser = GlobalUser::create(['name' => 'Sponsor', 'normalized_name' => 'SPONSOR', 'email' => uniqid('sponsor-', true).'@example.test']);
            $sponsor = RoomUser::create(['room_id' => $room->id, 'global_user_id' => $sponsorUser->id, 'user_code' => 'SP-SPONSOR', 'display_name' => 'Sponsor', 'normalized_name' => 'SPONSOR', 'status' => 'active']);
            $sponsorAllocations = [['room_user_id' => $sponsor->id, 'percentage' => 100]];
        }

        $campaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Full sponsor campaign',
            'restaurant' => 'Cafe',
            'status' => CampaignStatus::Active,
            'sponsor_type' => Campaign::SPONSOR_TYPE_FULL,
            'sponsor_allocations' => $sponsorAllocations,
            'delivery_fee' => $deliveryFee,
            'self_paid_price_basis' => $priceBasis,
        ]);

        return [$campaign, $member, $sponsor];
    }
}

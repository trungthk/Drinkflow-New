<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CampaignStatus;
use App\Enums\DebtStatus;
use App\Enums\OrderStatus;
use App\Models\Campaign;
use App\Models\Debt;
use App\Models\GlobalUser;
use App\Models\Order;
use App\Models\Room;
use App\Models\RoomUser;
use App\Services\Debt\UserRoomDebtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoomDebtVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->room = Room::create(['name' => 'Debt Room', 'slug' => 'debt-visibility-room', 'status' => 'active']);
    }

    private function member(string $email): RoomUser
    {
        $user = GlobalUser::create(['name' => $email, 'email' => $email, 'status' => 'active']);

        return RoomUser::create(['room_id' => $this->room->id, 'global_user_id' => $user->id, 'display_name' => $email, 'status' => 'active']);
    }

    private function campaign(): Campaign
    {
        return Campaign::create(['room_id' => $this->room->id, 'name' => 'Campaign', 'restaurant' => 'Cafe', 'status' => CampaignStatus::Closed]);
    }

    private function debt(Campaign $campaign, RoomUser $roomUser, int $amount): Debt
    {
        return Debt::create([
            'room_id' => $this->room->id,
            'campaign_id' => $campaign->id,
            'room_user_id' => $roomUser->id,
            'original_amount' => $amount,
            'remaining_amount' => $amount,
            'status' => DebtStatus::Unpaid,
        ]);
    }

    private function order(Campaign $campaign, RoomUser $roomUser, OrderStatus $status): Order
    {
        return Order::create([
            'room_id' => $this->room->id,
            'campaign_id' => $campaign->id,
            'room_user_id' => $roomUser->id,
            'subtotal' => 50000,
            'final_amount' => 50000,
            'status' => $status,
        ]);
    }

    /**
     * @return array<int, int>
     */
    private function visibleDebtIds(RoomUser $roomUser): array
    {
        return app(UserRoomDebtService::class)->queryVisibleDebts($this->room, $roomUser)->pluck('id')->all();
    }

    public function test_sponsor_without_own_order_sees_sponsor_debt(): void
    {
        $sponsor = $this->member('sponsor@example.test');
        $orderer = $this->member('orderer@example.test');
        $campaign = $this->campaign();
        // The orderer placed the order (e.g. on the sponsor's behalf); the sponsor pays the whole campaign.
        $this->order($campaign, $orderer, OrderStatus::Completed);
        $sponsorDebt = $this->debt($campaign, $sponsor, 50000);

        $this->assertSame([$sponsorDebt->id], $this->visibleDebtIds($sponsor));
    }

    public function test_debt_backed_only_by_cancelled_orders_stays_hidden(): void
    {
        $member = $this->member('member@example.test');
        $campaign = $this->campaign();
        $this->order($campaign, $member, OrderStatus::Cancelled);
        $this->debt($campaign, $member, 50000);

        $this->assertSame([], $this->visibleDebtIds($member));
    }

    public function test_debt_with_live_order_is_visible(): void
    {
        $member = $this->member('live@example.test');
        $campaign = $this->campaign();
        $this->order($campaign, $member, OrderStatus::Completed);
        $debt = $this->debt($campaign, $member, 50000);

        $this->assertSame([$debt->id], $this->visibleDebtIds($member));
    }
}

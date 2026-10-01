<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CampaignStatus;
use App\Enums\DebtStatus;
use App\Models\Campaign;
use App\Models\Debt;
use App\Models\GlobalUser;
use App\Models\PaymentAccount;
use App\Models\Room;
use App\Models\RoomUser;
use App\Services\Debt\UserRoomDebtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDebtQrAccountTest extends TestCase
{
    use RefreshDatabase;

    private Room $room;
    private RoomUser $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->room = Room::create(['name' => 'QR Room', 'slug' => 'qr-room', 'status' => 'active']);
        $user = GlobalUser::create(['name' => 'Lan', 'email' => 'lan-qr@example.test', 'status' => 'active']);
        $this->member = RoomUser::create([
            'room_id' => $this->room->id, 'global_user_id' => $user->id, 'user_code' => 'APP-QR', 'display_name' => 'Lan', 'status' => 'active',
        ]);
    }

    /**
     * Create a payment account in the test room.
     *
     * @param string $number Account number.
     * @param string $status Account status.
     * @return PaymentAccount Created account.
     */
    private function account(string $number, string $status = 'active'): PaymentAccount
    {
        return PaymentAccount::create([
            'room_id' => $this->room->id, 'bank_code' => 'VCB', 'bank_name' => 'Vietcombank',
            'account_number' => $number, 'account_name' => 'DRINKFLOW', 'is_default' => ! PaymentAccount::query()->where('room_id', $this->room->id)->exists(), 'status' => $status,
        ]);
    }

    /**
     * Create an unpaid campaign debt for the member.
     *
     * @param PaymentAccount|null $account Campaign receiving account.
     * @return Debt Created debt.
     */
    private function debt(?PaymentAccount $account): Debt
    {
        $campaign = Campaign::create([
            'room_id' => $this->room->id, 'name' => 'Coffee', 'restaurant' => 'Cafe',
            'status' => CampaignStatus::Closed, 'payment_account_id' => $account?->id,
        ]);

        return Debt::create([
            'room_id' => $this->room->id, 'campaign_id' => $campaign->id, 'room_user_id' => $this->member->id,
            'original_amount' => 40000, 'sponsor_amount' => 0, 'paid_amount' => 0, 'remaining_amount' => 40000,
            'status' => DebtStatus::Unpaid,
        ]);
    }

    /** A campaign without (or with a disabled) receiving account gets no QR, even when the room has an account. */
    public function test_campaign_without_receiving_account_has_no_qr_or_room_fallback(): void
    {
        $this->account('999');
        $withoutAccount = $this->debt(null);
        $disabled = $this->debt($this->account('555', 'inactive'));
        $configured = $this->debt($this->account('777'));

        $data = app(UserRoomDebtService::class)->getDebtViewData($this->room, $this->member, $this->member->globalUser);

        $this->assertArrayNotHasKey($withoutAccount->id, $data['qrPayloads']);
        $this->assertArrayNotHasKey($withoutAccount->id, $data['qrAccounts']);
        $this->assertArrayNotHasKey($disabled->id, $data['qrPayloads']);
        $this->assertArrayNotHasKey($disabled->id, $data['qrAccounts']);
        $this->assertNotEmpty($data['qrPayloads'][$configured->id]);
        $this->assertSame('777', $data['qrAccounts'][$configured->id]['account_number']);
    }

    /** The debts page renders the "not configured" state in the QR modal. */
    public function test_debts_page_renders_not_configured_notice(): void
    {
        $this->debt(null);

        $this->actingAs($this->member->globalUser, 'web')
            ->get(route('user.debts.index', $this->room))
            ->assertOk()
            ->assertSee('data-qr-not-configured', false)
            ->assertSee(__('room.debts.payment_account_not_configured'));
    }
}

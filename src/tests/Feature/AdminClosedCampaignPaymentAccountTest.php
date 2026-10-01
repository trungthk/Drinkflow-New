<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CampaignStatus;
use App\Models\Admin;
use App\Models\Campaign;
use App\Models\PaymentAccount;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminClosedCampaignPaymentAccountTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;
    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::create(['name' => 'Room Admin', 'email' => 'pay-admin@example.test', 'password' => Hash::make('secret'), 'status' => 'active']);
        $this->room = Room::create(['name' => 'Pay Room', 'slug' => 'pay-room', 'status' => 'active']);
        $this->admin->rooms()->attach($this->room);
    }

    /**
     * Create a payment account for a room.
     *
     * @param Room $room Owning room.
     * @param string $number Account number.
     * @param string $status Account status.
     * @return PaymentAccount Created account.
     */
    private function account(Room $room, string $number, string $status = 'active'): PaymentAccount
    {
        return PaymentAccount::create([
            'room_id' => $room->id, 'bank_code' => 'VCB', 'bank_name' => 'Vietcombank',
            'account_number' => $number, 'account_name' => 'DRINKFLOW', 'is_default' => false, 'status' => $status,
        ]);
    }

    /**
     * Create a campaign in the test room.
     *
     * @param CampaignStatus $status Campaign status.
     * @param PaymentAccount $account Current receiving account.
     * @return Campaign Created campaign.
     */
    private function campaign(CampaignStatus $status, PaymentAccount $account): Campaign
    {
        return Campaign::create([
            'room_id' => $this->room->id, 'name' => 'Trà chiều', 'restaurant' => 'Cafe',
            'status' => $status, 'payment_account_id' => $account->id,
        ]);
    }

    /** A closed campaign shows the adjust button and its receiving account can be switched. */
    public function test_closed_campaign_receiving_account_can_be_adjusted(): void
    {
        $old = $this->account($this->room, '111');
        $new = $this->account($this->room, '222');
        $campaign = $this->campaign(CampaignStatus::Closed, $old);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.campaigns.info', [$this->room, $campaign]))
            ->assertOk()
            ->assertSee('data-adjust-payment-account-open', false)
            ->assertSee(__('admin.campaign_payment_account_adjust'));

        $this->actingAs($this->admin, 'admin')
            ->patchJson(route('admin.campaigns.payment-account', [$this->room, $campaign]), ['payment_account_id' => $new->id])
            ->assertOk()
            ->assertJsonPath('data.payment_account.id', $new->id);

        $this->assertSame($new->id, $campaign->fresh()->payment_account_id);
        $this->assertDatabaseHas('audit_logs', ['event' => 'campaign.payment_account_updated']);
    }

    /** Running campaigns have no adjust button and the endpoint refuses them. */
    public function test_active_campaign_cannot_use_the_closed_campaign_adjustment(): void
    {
        $old = $this->account($this->room, '111');
        $new = $this->account($this->room, '222');
        $campaign = $this->campaign(CampaignStatus::Active, $old);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.campaigns.info', [$this->room, $campaign]))
            ->assertOk()
            ->assertDontSee('data-adjust-payment-account-open', false);

        $this->actingAs($this->admin, 'admin')
            ->patchJson(route('admin.campaigns.payment-account', [$this->room, $campaign]), ['payment_account_id' => $new->id])
            ->assertUnprocessable();

        $this->assertSame($old->id, $campaign->fresh()->payment_account_id);
    }

    /** Accounts from another room or disabled accounts are rejected. */
    public function test_account_must_be_active_and_belong_to_the_room(): void
    {
        $old = $this->account($this->room, '111');
        $inactive = $this->account($this->room, '333', 'inactive');
        $otherRoom = Room::create(['name' => 'Other', 'slug' => 'other-pay-room', 'status' => 'active']);
        $foreign = $this->account($otherRoom, '444');
        $campaign = $this->campaign(CampaignStatus::Closed, $old);

        foreach ([$inactive->id, $foreign->id] as $accountId) {
            $this->actingAs($this->admin, 'admin')
                ->patchJson(route('admin.campaigns.payment-account', [$this->room, $campaign]), ['payment_account_id' => $accountId])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('payment_account_id');
        }

        $this->assertSame($old->id, $campaign->fresh()->payment_account_id);
    }
}

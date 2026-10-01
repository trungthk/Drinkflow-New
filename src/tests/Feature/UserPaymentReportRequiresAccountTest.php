<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CampaignStatus;
use App\Enums\DebtStatus;
use App\Enums\PaymentStatus;
use App\Models\Campaign;
use App\Models\Debt;
use App\Models\GlobalUser;
use App\Models\Order;
use App\Models\PaymentAccount;
use App\Models\Room;
use App\Models\RoomUser;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A member can only report "I have paid" when there is a receiving account to transfer to.
 */
class UserPaymentReportRequiresAccountTest extends TestCase
{
    use RefreshDatabase;

    private Room $room;
    private RoomUser $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->room = Room::create(['name' => 'Pay Room', 'slug' => 'pay-room', 'status' => 'active']);
        $user = GlobalUser::create(['name' => 'Minh', 'email' => 'minh-pay@example.test', 'status' => 'active']);
        $this->member = RoomUser::create([
            'room_id' => $this->room->id, 'global_user_id' => $user->id, 'user_code' => 'APP-PAY', 'display_name' => 'Minh', 'status' => 'active',
        ]);
    }

    /**
     * Create a payment account in the test room.
     *
     * @param string $number Account number.
     * @return PaymentAccount Created active account.
     */
    private function account(string $number): PaymentAccount
    {
        return PaymentAccount::create([
            'room_id' => $this->room->id, 'bank_code' => 'VCB', 'bank_name' => 'Vietcombank',
            'account_number' => $number, 'account_name' => 'DRINKFLOW', 'is_default' => true, 'status' => 'active',
        ]);
    }

    /**
     * Create a campaign in the test room.
     *
     * @param PaymentAccount|null $account Campaign receiving account.
     * @param CampaignStatus $status Campaign status.
     * @return Campaign Created campaign.
     */
    private function campaign(?PaymentAccount $account, CampaignStatus $status = CampaignStatus::Closed): Campaign
    {
        return Campaign::create([
            'room_id' => $this->room->id, 'name' => 'Coffee', 'restaurant' => 'Cafe',
            'status' => $status, 'payment_account_id' => $account?->id,
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
        return Debt::create([
            'room_id' => $this->room->id, 'campaign_id' => $this->campaign($account)->id, 'room_user_id' => $this->member->id,
            'original_amount' => 40000, 'sponsor_amount' => 0, 'paid_amount' => 0, 'remaining_amount' => 40000,
            'status' => DebtStatus::Unpaid,
        ]);
    }

    /**
     * Create an unpaid order of the member in a live campaign.
     *
     * @param PaymentAccount|null $account Campaign receiving account.
     * @return Order Created order.
     */
    private function order(?PaymentAccount $account): Order
    {
        return Order::create([
            'room_id' => $this->room->id, 'campaign_id' => $this->campaign($account, CampaignStatus::Active)->id,
            'room_user_id' => $this->member->id, 'payment_status' => PaymentStatus::Unpaid->value,
            'subtotal' => 30000, 'final_amount' => 30000, 'status' => 'completed',
        ]);
    }

    /** A campaign debt cannot be reported as paid without the campaign's own account, even when the room has one. */
    public function test_debt_payment_report_is_refused_without_campaign_account(): void
    {
        $this->account('999');
        $debt = $this->debt(null);

        $this->actingAs($this->member->globalUser, 'web')
            ->postJson(route('user.debts.confirm-payment', $this->room), ['debt_id' => $debt->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('debt');

        $this->assertSame(DebtStatus::Unpaid, $debt->fresh()->status);
    }

    /** With the campaign's active account the report goes through as before. */
    public function test_debt_payment_report_succeeds_with_campaign_account(): void
    {
        $debt = $this->debt($this->account('777'));

        $this->actingAs($this->member->globalUser, 'web')
            ->postJson(route('user.debts.confirm-payment', $this->room), ['debt_id' => $debt->id])
            ->assertOk();

        $this->assertSame(DebtStatus::Pending, $debt->fresh()->status);
    }

    /** Pay-all is transferred to the room account, so it is refused when the room has none. */
    public function test_pay_all_is_refused_without_room_account(): void
    {
        $first = $this->debt(null);
        $second = $this->debt(null);

        $this->actingAs($this->member->globalUser, 'web')
            ->postJson(route('user.debts.confirm-payment', $this->room), ['transfer_content' => 'ALL'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('debt');

        $this->assertNull($first->fresh()->parent_id);
        $this->assertNull($second->fresh()->parent_id);
        $this->assertSame(2, Debt::query()->count());
    }

    /** An order is paid to its campaign's account only: no QR and no payment report through the room account. */
    public function test_order_payment_is_refused_without_campaign_account(): void
    {
        $this->account('999');
        $order = $this->order(null);

        $this->actingAs($this->member->globalUser, 'web')
            ->getJson(route('user.orders.payment', [$this->room, $order]))
            ->assertNotFound();

        $this->actingAs($this->member->globalUser, 'web')
            ->postJson(route('user.orders.confirm-payment', [$this->room, $order]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('order');

        $this->assertSame(PaymentStatus::Unpaid, $order->fresh()->payment_status);
        $this->assertSame(0, Debt::query()->count());
    }

    /** The orders page shows the "not configured" state and gates the paid button on it. */
    public function test_orders_page_renders_not_configured_state(): void
    {
        $this->account('8899776655');
        $this->order(null);

        $this->actingAs($this->member->globalUser, 'web')
            ->get(route('user.orders.index', $this->room))
            ->assertOk()
            ->assertSee('data-qr-not-configured', false)
            ->assertSee('configured: false', false)
            ->assertSee(__('room.debts.payment_account_not_configured'))
            ->assertDontSee('8899776655');
    }

    /** The Alpine component of both payment pages must parse as one intact x-data attribute. */
    public function test_payment_pages_render_intact_alpine_component(): void
    {
        $this->debt(null);
        $this->order(null);

        $pages = [
            route('user.debts.index', $this->room) => 'submitPaymentConfirmation',
            route('user.orders.index', $this->room) => 'openPaymentConfirm',
        ];

        foreach ($pages as $url => $method) {
            $html = $this->actingAs($this->member->globalUser, 'web')->get($url)->assertOk()->getContent();

            $document = new \DOMDocument();
            libxml_use_internal_errors(true);
            $document->loadHTML('<?xml encoding="UTF-8">'.$html);
            libxml_clear_errors();

            $component = null;
            foreach ((new \DOMXPath($document))->query('//*[@x-data]') as $node) {
                if (str_contains($node->getAttribute('x-data'), 'qrModalOpen')) {
                    $component = $node->getAttribute('x-data');
                    break;
                }
            }

            // A stray double quote inside the attribute would cut it before these methods.
            $this->assertNotNull($component, $url);
            $this->assertStringContainsString($method, $component, $url);
            $this->assertStringContainsString('qrData.configured', $component, $url);
            // Copy buttons swap their icon for a check mark instead of showing a notification.
            $this->assertStringContainsString('copiedField', $component, $url);
            $this->assertStringNotContainsString('alert(', $component, $url);
        }
    }
}

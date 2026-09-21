<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\CampaignStatus;
use App\Enums\DebtStatus;
use App\Models\AdminAccount;
use App\Models\Campaign;
use App\Models\Debt;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Models\RoomUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFilterControlsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure admin lists expose clear controls and debt filtering runs on the backend.
     *
     * @return void
     */
    public function test_admin_lists_have_clear_filters_and_backend_debt_dropdown(): void
    {
        $admin = AdminAccount::create([
            'name' => 'Filter Admin',
            'email' => 'filter-admin@example.test',
            'password' => 'secret-password',
            'role' => AdminRole::Admin,
            'status' => 'active',
        ]);
        $room = Room::create([
            'name' => 'Công Nghệ',
            'slug' => 'cong-nghe',
            'status' => 'active',
        ]);
        $admin->rooms()->attach($room);
        $campaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Debt Campaign',
            'restaurant' => 'DrinkFlow Cafe',
            'status' => CampaignStatus::Active,
        ]);
        $pendingMember = $this->roomUser($room, 'Pending Member', 'pending@example.test');
        $paidMember = $this->roomUser($room, 'Paid Member', 'paid@example.test');
        $this->debt($room, $campaign, $pendingMember, DebtStatus::Pending);
        $this->debt($room, $campaign, $paidMember, DebtStatus::Paid);

        $this->actingAs($admin, 'admin')->get(route('admin.campaigns.page', $room->slug))
            ->assertOk()
            ->assertDontSee('id="campaign-clear-filters"', false)
            ->assertDontSee(__('admin.fast_create_campaign'))
            ->assertSee(route('admin.campaigns.info', [$room->slug, $campaign]), false);

        $this->actingAs($admin, 'admin')->get(route('admin.campaigns.page', [
            'room' => $room->slug,
            'search' => 'Debt',
        ]))->assertOk()->assertSee('id="campaign-clear-filters"', false);

        $this->actingAs($admin, 'admin')->get(route('admin.campaigns.page', [
            'room' => $room->slug,
            'status' => CampaignStatus::Active->value,
        ]))->assertOk()->assertSee('id="campaign-clear-filters"', false);

        $this->actingAs($admin, 'admin')->get(route('admin.room-users.page', $room->slug))
            ->assertOk()
            ->assertDontSee('id="users-clear-filters"', false);

        $this->actingAs($admin, 'admin')->get(route('admin.room-users.page', [
            'room' => $room->slug,
            'q' => 'Pending Member',
        ]))
            ->assertOk()
            ->assertSee('id="users-clear-filters"', false);

        $this->actingAs($admin, 'admin')->get(route('admin.room-users.page', [
            'room' => $room->slug,
            'status' => 'active',
        ]))
            ->assertOk()
            ->assertSee('id="users-clear-filters"', false);

        $this->actingAs($admin, 'admin')->get(route('admin.debts.page', $room->slug))
            ->assertOk()
            ->assertDontSee('id="debt-clear-filters"', false);

        $this->actingAs($admin, 'admin')->get(route('admin.debts.page', [
            'room' => $room->slug,
            'search' => 'Pending Member',
        ]))
            ->assertOk()
            ->assertSee('id="debt-clear-filters"', false);

        $this->actingAs($admin, 'admin')->get(route('admin.debts.page', [
            'room' => $room->slug,
            'status' => DebtStatus::Pending->value,
        ]))
            ->assertOk()
            ->assertSee('id="debt-clear-filters"', false);

        $this->actingAs($admin, 'admin')->get(route('admin.debts.page', [
            'room' => $room->slug,
            'search' => 'Pending Member',
            'status' => DebtStatus::Pending->value,
        ]))
            ->assertOk()
            ->assertSee('id="debt-status-filter"', false)
            ->assertSee('id="debt-clear-filters"', false)
            ->assertSee('Pending Member')
            // The member dropdown lists everyone with debts, so check the filtered rows instead of the text.
            ->assertViewHas('debts', fn ($debts): bool => $debts->count() === 1 && $debts->first()->roomUser->display_name === 'Pending Member');

        // The member filter is a searchable dropdown: each option carries name, email and code as search terms,
        // and the reload button sits in the header ribbon next to the export button.
        $html = $this->actingAs($admin, 'admin')->get(route('admin.debts.page', $room->slug))->assertOk()->getContent();
        $this->assertStringContainsString('id="debt-user-filter" name="user" data-searchable="true"', $html);
        $this->assertStringContainsString('data-search="Paid Member paid@example.test FILTER-'.$paidMember->global_user_id.' Paid Member"', $html);
        $this->assertLessThan(strpos($html, 'onclick="exportDebtCSV()"'), strpos($html, 'data-reload-page'));
        $this->assertLessThan(strpos($html, 'id="debts-filter-form"'), strpos($html, 'data-reload-page'));

        // The search box also matches the debt code, and admin lists paginate by 15.
        $pendingDebt = Debt::query()->where('room_user_id', $pendingMember->id)->firstOrFail();
        $this->actingAs($admin, 'admin')->get(route('admin.debts.page', ['room' => $room->slug, 'search' => strtolower((string) $pendingDebt->code)]))
            ->assertOk()
            ->assertViewHas('debts', fn ($debts): bool => $debts->count() === 1 && $debts->first()->id === $pendingDebt->id);
        $this->assertSame(15, \App\Constants\Pagination::ADMIN_PER_PAGE);
        $this->actingAs($admin, 'admin')->get(route('admin.debts.page', $room->slug))
            ->assertViewHas('debts', fn ($debts): bool => $debts->perPage() === \App\Constants\Pagination::ADMIN_PER_PAGE);

        // Filter by member and by created date range.
        $paidDebt = Debt::query()->where('room_user_id', $paidMember->id)->firstOrFail();
        $paidDebt->forceFill(['created_at' => '2026-01-10 09:00:00'])->save();
        $this->actingAs($admin, 'admin')->get(route('admin.debts.page', ['room' => $room->slug, 'user' => $paidMember->id]))
            ->assertOk()
            ->assertSee('id="debt-clear-filters"', false)
            ->assertViewHas('debts', fn ($debts): bool => $debts->count() === 1 && $debts->first()->id === $paidDebt->id);
        $this->actingAs($admin, 'admin')->get(route('admin.debts.page', [
            'room' => $room->slug, 'date_from' => '2026-01-01', 'date_to' => '2026-01-31',
        ]))
            ->assertOk()
            ->assertSee('id="debt-clear-filters"', false)
            ->assertViewHas('debts', fn ($debts): bool => $debts->count() === 1 && $debts->first()->id === $paidDebt->id);
        // An invalid date is ignored instead of failing the page.
        $this->actingAs($admin, 'admin')->get(route('admin.debts.page', ['room' => $room->slug, 'date_from' => 'not-a-date']))
            ->assertOk()
            ->assertViewHas('debts', fn ($debts): bool => $debts->count() === 2);

        // Each row has the eye button (with tooltip) carrying the localized detail payload for the modal.
        $this->actingAs($admin, 'admin')->get(route('admin.debts.page', ['room' => $room->slug, 'user' => $pendingMember->id]))
            ->assertOk()
            ->assertSee('data-open-debt-detail', false)
            ->assertSee('data-tooltip="'.__('admin.view_debt_detail').'"', false)
            ->assertSee('id="debt-detail-modal"', false)
            ->assertViewHas('debtDetails', fn (array $details): bool => count($details) === 1
                && collect($details)->first()['member'] === 'Pending Member'
                && collect($details)->first()['campaign'] === 'Debt Campaign'
                && collect($details)->first()['status'] === DebtStatus::Pending->value);
    }

    /**
     * The debt detail payload lists localized payment and adjustment history.
     */
    public function test_debt_detail_lists_payments_and_adjustments(): void
    {
        $room = Room::create(['name' => 'Detail Room', 'slug' => 'detail-room', 'status' => 'active']);
        $campaign = Campaign::create(['room_id' => $room->id, 'name' => 'Detail Campaign', 'restaurant' => 'Cafe', 'status' => CampaignStatus::Closed]);
        $member = $this->roomUser($room, 'Detail Member', 'detail@example.test');
        $debt = $this->debt($room, $campaign, $member, DebtStatus::Partial);
        $debt->payments()->create(['amount' => 20000, 'payment_method' => 'cash', 'reference' => 'REF-1', 'paid_at' => now()]);
        $debt->adjustments()->create(['type' => 'decrease', 'amount' => 5000, 'reason' => 'Discount', 'before_amount' => 50000, 'after_amount' => 45000]);

        $detail = app(\App\Services\Admin\AdminDebtService::class)->formatDetail($debt->fresh(['roomUser.globalUser', 'campaign', 'payments', 'adjustments']));

        $this->assertSame(__('admin.debt_method_cash'), $detail['payments'][0]['method']);
        $this->assertSame('REF-1', $detail['payments'][0]['reference']);
        $this->assertSame(__('admin.debt_adjust_decrease'), $detail['adjustments'][0]['type']);
        $this->assertSame('Discount', $detail['adjustments'][0]['reason']);
        $this->assertSame(\App\Support\Helpers\FormatHelper::formatCurrency(45000), $detail['adjustments'][0]['after']);
    }

    /**
     * Create an active member for filter testing.
     *
     * @param Room $room Room that owns the membership.
     * @param string $name Member name.
     * @param string $email Member email.
     * @return RoomUser Created room membership.
     */
    private function roomUser(Room $room, string $name, string $email): RoomUser
    {
        $user = GlobalUser::create([
            'name' => $name,
            'normalized_name' => mb_strtoupper($name),
            'email' => $email,
            'status' => 'active',
        ]);

        return RoomUser::create([
            'room_id' => $room->id,
            'global_user_id' => $user->id,
            'user_code' => 'FILTER-'.$user->id,
            'display_name' => $name,
            'normalized_name' => mb_strtoupper($name),
            'status' => 'active',
        ]);
    }

    /**
     * Create a debt with the requested payment status.
     *
     * @param Room $room Room that owns the debt.
     * @param Campaign $campaign Source campaign.
     * @param RoomUser $roomUser Member responsible for the debt.
     * @param DebtStatus $status Debt payment status.
     * @return Debt Created debt.
     */
    private function debt(Room $room, Campaign $campaign, RoomUser $roomUser, DebtStatus $status): Debt
    {
        return Debt::create([
            'room_id' => $room->id,
            'campaign_id' => $campaign->id,
            'room_user_id' => $roomUser->id,
            'original_amount' => 50000,
            'paid_amount' => $status === DebtStatus::Paid ? 50000 : 0,
            'remaining_amount' => $status === DebtStatus::Paid ? 0 : 50000,
            'status' => $status,
        ]);
    }
}

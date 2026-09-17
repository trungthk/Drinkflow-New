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
            ->assertSee(__('admin.filter_clear'));

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
            ->assertDontSee('Paid Member');
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

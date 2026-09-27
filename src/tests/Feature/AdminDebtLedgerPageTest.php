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
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminDebtLedgerPageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The member column lists name, email, then the debt code with a copy button, and the campaign links to its page.
     */
    public function test_ledger_member_column_and_campaign_link(): void
    {
        $admin = AdminAccount::create([
            'name' => 'Room Admin',
            'email' => 'ledger-admin@example.test',
            'password' => Hash::make('secret'),
            'role' => AdminRole::Admin,
            'status' => 'active',
        ]);
        $room = Room::create(['name' => 'Ledger Room', 'slug' => 'ledger-room', 'status' => 'active']);
        $admin->rooms()->attach($room);
        $campaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Trà chiều',
            'restaurant' => 'Cafe',
            'status' => CampaignStatus::Closed,
        ]);
        $globalUser = GlobalUser::create(['name' => 'Nguyễn Văn A', 'email' => 'nguyenvana@example.test']);
        $roomUser = RoomUser::create([
            'room_id' => $room->id,
            'global_user_id' => $globalUser->id,
            'display_name' => 'Nguyễn Văn A',
            'status' => 'active',
        ]);
        Debt::create([
            'room_id' => $room->id,
            'campaign_id' => $campaign->id,
            'room_user_id' => $roomUser->id,
            'code' => 'DEBT-COPY-01',
            'original_amount' => 50000,
            'remaining_amount' => 50000,
            'status' => DebtStatus::Unpaid,
        ]);

        $html = $this->actingAs($admin, 'admin')
            ->get(route('admin.debts.page', $room->slug))
            ->assertOk()
            ->getContent();

        $emailPos = strpos($html, 'nguyenvana@example.test');
        $codePos = strpos($html, '<span>DEBT-COPY-01</span>');
        $this->assertNotFalse($emailPos);
        $this->assertNotFalse($codePos);
        $this->assertGreaterThan($emailPos, $codePos, 'The debt code must appear below the email.');

        $this->assertStringContainsString('data-copy="DEBT-COPY-01"', $html);
        $this->assertStringContainsString('data-tooltip="'.__('admin.copy_debt_code').'"', $html);
        $this->assertStringContainsString('href="'.route('admin.campaigns.info', [$room->slug, $campaign]).'"', $html);
    }

    /**
     * Status badges carry an icon, the campaign link is not underlined on hover, the pending action reads
     * "Xác nhận", the adjust button has a tooltip and the payment/adjust modal gets its new texts.
     */
    public function test_ledger_status_icons_actions_and_modal_texts(): void
    {
        $admin = AdminAccount::create([
            'name' => 'Room Admin',
            'email' => 'ledger-ui-admin@example.test',
            'password' => Hash::make('secret'),
            'role' => AdminRole::Admin,
            'status' => 'active',
        ]);
        $room = Room::create(['name' => 'Ledger UI Room', 'slug' => 'ledger-ui-room', 'status' => 'active']);
        $admin->rooms()->attach($room);
        $campaign = Campaign::create(['room_id' => $room->id, 'name' => 'Trà chiều', 'restaurant' => 'Cafe', 'status' => CampaignStatus::Closed]);
        $member = function (string $email) use ($room): RoomUser {
            $user = GlobalUser::create(['name' => $email, 'email' => $email]);

            return RoomUser::create(['room_id' => $room->id, 'global_user_id' => $user->id, 'display_name' => $email, 'status' => 'active']);
        };
        Debt::create([
            'room_id' => $room->id, 'campaign_id' => $campaign->id, 'room_user_id' => $member('unpaid@example.test')->id,
            'original_amount' => 50000, 'remaining_amount' => 50000, 'status' => DebtStatus::Unpaid,
        ]);
        Debt::create([
            'room_id' => $room->id, 'campaign_id' => $campaign->id, 'room_user_id' => $member('pending@example.test')->id,
            'original_amount' => 30000, 'remaining_amount' => 30000, 'status' => DebtStatus::Pending,
        ]);

        $html = $this->actingAs($admin, 'admin')
            ->get(route('admin.debts.page', $room->slug))
            ->assertOk()
            ->assertSee(__('admin.approve_payment_btn'))
            ->assertSee('data-tooltip="'.__('admin.adjust_debt_tooltip').'"', false)
            ->getContent();

        $this->assertSame('Xác nhận', trans('admin.approve_payment_btn', [], 'vi'));
        $this->assertMatchesRegularExpression('/data-debt-status-badge>\s*<span class="material-symbols-outlined[^"]*" aria-hidden="true">error<\/span>/', $html);
        $this->assertMatchesRegularExpression('/data-debt-status-badge>\s*<span class="material-symbols-outlined[^"]*" aria-hidden="true">hourglass_top<\/span>/', $html);

        $campaignLink = (string) str($html)->after('href="'.route('admin.campaigns.info', [$room->slug, $campaign]).'"')->before('>');
        $this->assertStringNotContainsString('hover:underline', $campaignLink);

        // Texts used by the redesigned "Thu tiền" / "Điều chỉnh" modal.
        $this->assertStringContainsString('id="debt-modal-subtitle"', $html);
        $this->assertStringContainsString(e(json_encode(__('admin.debt_modal_pay_full'), JSON_UNESCAPED_UNICODE)), $html);
    }
}

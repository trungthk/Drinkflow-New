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

class AdminDebtLedgerExportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure the debt CSV is localized and remains readable by Excel as UTF-8.
     *
     * @return void
     */
    public function test_debt_ledger_csv_has_utf8_bom_and_localized_values(): void
    {
        app()->setLocale('vi');
        $admin = AdminAccount::create([
            'name' => 'Debt Export Admin',
            'email' => 'debt-export-admin@example.test',
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
            'name' => 'Trà chiều Công Nghệ',
            'restaurant' => 'Quán Việt',
            'status' => CampaignStatus::Closed,
        ]);
        $globalUser = GlobalUser::create([
            'name' => 'Thành viên Một',
            'normalized_name' => 'THÀNH VIÊN MỘT',
            'email' => 'member@example.test',
            'status' => 'active',
        ]);
        $roomUser = RoomUser::create([
            'room_id' => $room->id,
            'global_user_id' => $globalUser->id,
            'user_code' => 'USR-001',
            'display_name' => $globalUser->name,
            'normalized_name' => $globalUser->normalized_name,
            'status' => 'active',
        ]);
        Debt::create([
            'room_id' => $room->id,
            'campaign_id' => $campaign->id,
            'room_user_id' => $roomUser->id,
            'original_amount' => 50000,
            'sponsor_amount' => 10000,
            'sponsor_type' => 'full',
            'adjustment_amount' => 0,
            'paid_amount' => 0,
            'remaining_amount' => 40000,
            'status' => DebtStatus::Pending,
        ]);

        $page = $this->actingAs($admin, 'admin')->get(route('admin.debts.page', $room));
        $page->assertOk()->assertDontSeeText(__('admin.bot_reminder_btn'));

        $response = $this->actingAs($admin, 'admin')->get(route('admin.debts.export', $room));
        $response->assertOk()->assertDownload('drinkflow-cong-nghe-debts.csv');
        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);

        $lines = preg_split('/\r\n|\r|\n/', substr($content, 3), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertIsArray($lines);
        $headings = str_getcsv($lines[0]);
        $row = str_getcsv($lines[1]);

        $this->assertSame(__('admin.debt_export_campaign'), $headings[0]);
        $this->assertSame('Trà chiều Công Nghệ', $row[0]);
        $this->assertSame(__('admin.sponsor_type_full'), $row[5]);
        $this->assertSame(__('admin.status_pending'), $row[9]);
    }
}

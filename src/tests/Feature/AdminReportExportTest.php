<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Exports\AdminReportExport;
use App\Models\AdminAccount;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class AdminReportExportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verify the export lays out the KPI summary and one titled table per report tab.
     *
     * @return void
     */
    public function test_export_builds_summary_and_tab_sections(): void
    {
        app()->setLocale('en');

        $rows = (new AdminReportExport([
            'from' => '2026-09-01 00:00:00',
            'to' => '2026-09-30 23:59:59',
            'campaign_count' => 3,
            'order_count' => 2,
            'spending' => 284000,
            'sponsor_amount' => 142000,
            'debt' => 71000,
            'popular_drinks' => [['item_name' => 'Milk tea', 'quantity' => 4]],
            'popular_stores' => [['restaurant' => 'Cafe A', 'orders' => 2, 'spending' => 284000]],
            'debts_by_user' => [[
                'user_name' => 'An', 'user_email' => 'an@example.test', 'debt_count' => 1,
                'total_original' => 142000, 'total_paid' => 71000, 'outstanding_debt' => 71000,
            ]],
            'sponsors_leaderboard' => [['user_name' => 'Binh', 'user_email' => 'binh@example.test', 'sponsored_campaigns' => 2, 'total_sponsored' => 142000]],
            'top_users' => [['user_name' => 'An', 'user_email' => 'an@example.test', 'order_count' => 2, 'total_spent' => 284000]],
        ]))->array();

        $flat = array_map('json_encode', $rows);
        $this->assertSame(__('admin.reports_analytics_title'), $rows[0][0]);
        $this->assertSame([__('admin.kpi_total_store_spending'), 284000], $rows[5]);
        $this->assertContains(json_encode(['Milk tea', 4]), $flat);
        $this->assertContains(json_encode(['Cafe A', 2, 284000]), $flat);
        $this->assertContains(json_encode(['An', 'an@example.test', 1, 142000, 71000, 71000], JSON_UNESCAPED_UNICODE), $flat);
        $this->assertContains(json_encode([1, 'Binh', 'binh@example.test', 2, 142000]), $flat);
        $this->assertContains(json_encode([1, 'An', 'an@example.test', 2, 284000]), $flat);
    }

    /**
     * Verify a missing tab still yields its heading row without failing.
     *
     * @return void
     */
    public function test_export_tolerates_missing_tab_data(): void
    {
        $rows = (new AdminReportExport(['campaign_count' => 0]))->array();

        $this->assertNotEmpty($rows);
        $this->assertSame(0, $rows[3][1]);
    }

    /**
     * Verify an authorized admin downloads a real XLSX with numeric KPI cells.
     *
     * @return void
     */
    public function test_admin_can_download_report_workbook(): void
    {
        if (! class_exists(\ZipArchive::class)) {
            $this->markTestSkipped('The zip extension (ZipArchive) is required for XLSX tests.');
        }

        $admin = AdminAccount::create([
            'name' => 'Report Admin',
            'email' => 'report-admin@example.test',
            'password' => 'secret-password',
            'role' => AdminRole::Admin,
            'status' => 'active',
        ]);
        $room = Room::create(['name' => 'Report Room', 'slug' => 'report-room', 'status' => 'active']);
        $admin->rooms()->attach($room);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.reports.export', $room));

        $response->assertOk();
        $this->assertStringContainsString('drinkflow-report-room-report-', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));

        // The export registers a global string-only value binder; reset it so numbers are read back as numbers.
        Cell::setValueBinder(new DefaultValueBinder());
        $sheet = IOFactory::load($response->baseResponse->getFile()->getPathname())->getActiveSheet();
        $this->assertSame(__('admin.reports_analytics_title'), $sheet->getCell('A1')->getValue());

        $labels = array_column($sheet->toArray(null, true, false), 0);
        $campaignsRow = array_search(__('admin.kpi_total_campaigns'), $labels, true);
        $this->assertNotFalse($campaignsRow, 'KPI rows must be present.');

        // Zero amounts must stay in the sheet as numeric cells rather than blanks.
        $count = $sheet->getCell('B'.($campaignsRow + 1));
        $this->assertEquals(0, $count->getValue());
        $this->assertSame('n', $count->getDataType());

        // Spacer rows must survive so each report tab starts its own block.
        $titleRow = array_search(__('admin.top_5_drinks'), $labels, true);
        $this->assertNotFalse($titleRow);
        $this->assertSame('', (string) $labels[$titleRow - 1]);
    }

    /**
     * Verify guests cannot download the report workbook.
     *
     * @return void
     */
    public function test_guest_cannot_download_report_workbook(): void
    {
        $room = Room::create(['name' => 'Locked Room', 'slug' => 'locked-room', 'status' => 'active']);

        $this->get(route('admin.reports.export', $room))->assertRedirect();
    }
}

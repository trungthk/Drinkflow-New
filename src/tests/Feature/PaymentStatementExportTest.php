<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exports\PaymentStatementExport;
use App\Exports\AnalyticsReportExport;
use App\Models\Order;
use App\Models\Room;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class PaymentStatementExportTest extends TestCase
{
    /**
     * Verify a real XLSX preserves names as text and amounts as numbers.
     *
     * @return void
     */
    public function test_xlsx_preserves_literal_text_and_numeric_amounts(): void
    {
        $order = new Order(['subtotal' => 20000, 'sponsor_amount' => 5000, 'final_amount' => 15000, 'status' => 'submitted']);
        $order->id = 42;
        $order->setRelation('room', new Room(['name' => '=1+1']));
        $order->setRelation('campaign', null);
        $export = new PaymentStatementExport(collect([$order]));
        $file = tempnam(sys_get_temp_dir(), 'statement-test-');

        try {
            file_put_contents($file, Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX));
            \PhpOffice\PhpSpreadsheet\Cell\Cell::setValueBinder(new \PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder());
            $sheet = IOFactory::load($file)->getActiveSheet();
            $this->assertSame('=1+1', $sheet->getCell('C2')->getValue());
            $this->assertSame('s', $sheet->getCell('C2')->getDataType());
            $this->assertEquals(15000, $sheet->getCell('G2')->getValue());
            $this->assertSame('n', $sheet->getCell('G2')->getDataType());
            $this->assertSame(__('payment_statement.statuses.submitted'), $sheet->getCell('H2')->getValue());
        } finally {
            unlink($file);
        }
    }

    /**
     * Verify exports with no orders still contain column headings.
     *
     * @return void
     */
    public function test_empty_statement_has_headings(): void
    {
        $export = new PaymentStatementExport(collect());
        $this->assertSame([], $export->array());
        $this->assertCount(8, $export->headings());
    }

    /**
     * Verify analytics reports contain the same summary and detail figures.
     *
     * @return void
     */
    public function test_analytics_report_contains_summary_and_details(): void
    {
        $export = new AnalyticsReportExport([
            'totalOrders' => 1, 'totalSpent' => 15000, 'totalSponsor' => 5000,
            'weeklyStats' => [['label' => 'Week 1', 'spent' => 15000, 'sponsor' => 5000]],
            'categoryStats' => [['name' => 'Coffee', 'quantity' => 1, 'percent' => 100]],
            'roomStats' => [['name' => 'Office', 'order_count' => 1, 'spent' => 15000, 'sponsor' => 5000]],
        ]);

        $rows = $export->array();
        $this->assertSame([__('global.statistics.total_orders'), 1], $rows[1]);
        $this->assertContains(['Week 1', 15000, 5000], $rows);
        $this->assertContains(['Coffee', 1, 100], $rows);
        $this->assertContains(['Office', 1, 15000, 5000], $rows);
    }
}

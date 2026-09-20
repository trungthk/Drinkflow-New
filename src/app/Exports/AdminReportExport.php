<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

/**
 * Room report statement covering every report tab for the selected period.
 *
 * WithStrictNullComparison keeps zero amounts (0) in the sheet instead of leaving the cell blank.
 */
final class AdminReportExport extends DefaultValueBinder implements FromArray, WithCustomValueBinder, WithStrictNullComparison, ShouldAutoSize
{
    /**
     * Spacer cell value. A row must hold at least one value ([]  would be dropped), so spacers use one empty string.
     */
    private const BLANK_ROW = '';

    /**
     * Store the aggregated report metrics shown on the admin reports page.
     *
     * @param array<string, mixed> $metrics Payload from AdminReportService::getReportMetrics() (all tabs).
     * @return void
     */
    public function __construct(private readonly array $metrics)
    {
    }

    /**
     * Build the summary and one table per report tab, separated by blank rows.
     *
     * @return array<int, array<int, string|int|float|null>> Spreadsheet rows.
     */
    public function array(): array
    {
        $rows = [
            [__('admin.reports_analytics_title')],
            [__('admin.date_range'), (string) ($this->metrics['from'] ?? ''), (string) ($this->metrics['to'] ?? '')],
            [self::BLANK_ROW],
            [__('admin.kpi_total_campaigns'), (int) ($this->metrics['campaign_count'] ?? 0)],
            [__('admin.kpi_total_orders_placed'), (int) ($this->metrics['order_count'] ?? 0)],
            [__('admin.kpi_total_store_spending'), (int) ($this->metrics['spending'] ?? 0)],
            [__('admin.kpi_sponsor_fund_subsidies'), (int) ($this->metrics['sponsor_amount'] ?? 0)],
            [__('admin.total_debt_remaining'), (int) ($this->metrics['debt'] ?? 0)],
        ];

        $rows = $this->appendSection($rows, __('admin.top_5_drinks'), [
            __('admin.item_name'),
            __('admin.quantity'),
        ], $this->metrics['popular_drinks'] ?? [], fn (mixed $row): array => [
            data_get($row, 'item_name'),
            (int) data_get($row, 'quantity', 0),
        ]);

        $rows = $this->appendSection($rows, __('admin.top_5_stores'), [
            __('admin.restaurant_name'),
            __('admin.th_orders_count'),
            __('admin.kpi_total_store_spending'),
        ], $this->metrics['popular_stores'] ?? [], fn (mixed $row): array => [
            data_get($row, 'restaurant'),
            (int) data_get($row, 'orders', 0),
            (int) data_get($row, 'spending', 0),
        ]);

        $rows = $this->appendSection($rows, __('admin.debts_analytics_title'), [
            __('admin.report_col_member'),
            __('admin.email'),
            __('admin.report_col_debt_count'),
            __('admin.total_debt_amount'),
            __('admin.total_debt_paid'),
            __('admin.total_debt_remaining'),
        ], $this->metrics['debts_by_user'] ?? [], fn (mixed $row): array => [
            data_get($row, 'user_name'),
            data_get($row, 'user_email'),
            (int) data_get($row, 'debt_count', 0),
            (int) data_get($row, 'total_original', 0),
            (int) data_get($row, 'total_paid', 0),
            (int) data_get($row, 'outstanding_debt', 0),
        ]);

        $rows = $this->appendSection($rows, __('admin.sponsor_leaderboard'), [
            __('admin.report_col_rank'),
            __('admin.report_col_sponsor'),
            __('admin.email'),
            __('admin.report_col_sponsored_orders'),
            __('admin.report_col_total_sponsored'),
        ], $this->metrics['sponsors_leaderboard'] ?? [], fn (mixed $row, int $index): array => [
            $index + 1,
            data_get($row, 'user_name'),
            data_get($row, 'user_email'),
            (int) data_get($row, 'sponsored_orders', 0),
            (int) data_get($row, 'total_sponsored', 0),
        ]);

        return $this->appendSection($rows, __('admin.tab_users_analytics'), [
            '#',
            __('admin.report_col_member'),
            __('admin.email'),
            __('admin.th_orders_placed_count'),
            __('admin.report_col_total_spent'),
        ], $this->metrics['top_users'] ?? [], fn (mixed $row, int $index): array => [
            $index + 1,
            data_get($row, 'user_name'),
            data_get($row, 'user_email'),
            (int) data_get($row, 'order_count', 0),
            (int) data_get($row, 'total_spent', 0),
        ]);
    }

    /**
     * Keep user-provided text literal (no formula evaluation) while preserving numbers.
     *
     * @param Cell $cell Target cell.
     * @param mixed $value Cell value.
     * @return bool Whether the cell was bound.
     */
    public function bindValue(Cell $cell, mixed $value): bool
    {
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    /**
     * Append a titled table (spacer row, title, header row, then one row per record).
     *
     * @param array<int, array<int, mixed>> $rows Rows built so far.
     * @param string $title Section title.
     * @param array<int, string> $headings Column headings.
     * @param iterable<int, mixed> $items Source records (arrays or objects).
     * @param callable(mixed, int): array<int, mixed> $mapper Maps a record and its index to a row.
     * @return array<int, array<int, mixed>> Rows including the new section.
     */
    private function appendSection(array $rows, string $title, array $headings, iterable $items, callable $mapper): array
    {
        $rows[] = [self::BLANK_ROW];
        $rows[] = [$title];
        $rows[] = $headings;

        foreach ($items as $index => $item) {
            $rows[] = $mapper($item, (int) $index);
        }

        return $rows;
    }
}

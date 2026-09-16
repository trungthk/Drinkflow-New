<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class AnalyticsReportExport extends DefaultValueBinder implements FromArray, WithCustomValueBinder, ShouldAutoSize
{
    /**
     * Store the personal statistics shown on the page.
     *
     * @param array<string, mixed> $data Analytics view data.
     * @return void
     */
    public function __construct(private readonly array $data)
    {
    }

    /**
     * Build summary, weekly trends, item breakdown and room spending sections.
     *
     * @return array<int, array<int, string|int|float>> Excel report rows.
     */
    public function array(): array
    {
        $rows = [
            [__('global.statistics.title')],
            [__('global.statistics.total_orders'), $this->data['totalOrders']],
            [__('global.statistics.total_spent'), $this->data['totalSpent']],
            [__('global.statistics.total_sponsor'), $this->data['totalSponsor']],
            [],
            [__('global.statistics.weekly_chart_title'), __('global.statistics.legend_spent'), __('global.statistics.legend_sponsor')],
        ];
        foreach ($this->data['weeklyStats'] as $week) {
            $rows[] = [$week['label'], $week['spent'], $week['sponsor']];
        }
        $rows[] = [];
        $rows[] = [__('global.statistics.category_chart_title'), __('global.statistics.th_order_count'), '%'];
        foreach ($this->data['categoryStats'] as $category) {
            $rows[] = [$category['name'], $category['quantity'], $category['percent']];
        }
        $rows[] = [];
        $rows[] = [__('global.statistics.th_room_group'), __('global.statistics.th_orders'), __('global.statistics.legend_spent'), __('global.statistics.legend_sponsor')];
        foreach ($this->data['roomStats'] as $room) {
            $rows[] = [$room['name'], $room['order_count'], $room['spent'], $room['sponsor']];
        }

        return $rows;
    }

    /**
     * Keep user-provided text literal while preserving numeric statistics.
     *
     * @param Cell $cell Target cell.
     * @param mixed $value Report value.
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
}

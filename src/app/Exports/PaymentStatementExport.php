<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Order;
use App\Support\Helpers\FormatHelper;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class PaymentStatementExport extends DefaultValueBinder implements FromArray, WithHeadings, WithColumnFormatting, WithCustomValueBinder, ShouldAutoSize
{
    /**
     * Store the user's filtered payment orders.
     *
     * @param Collection<int, Order> $orders Personal orders in display order.
     * @return void
     */
    public function __construct(private readonly Collection $orders)
    {
    }

    /**
     * Build statement rows with numeric monetary values.
     *
     * @return array<int, array<int, string|float>> Statement rows.
     */
    public function array(): array
    {
        return $this->orders->map(static fn (Order $order): array => [
            '#' . $order->code,
            $order->created_at ? FormatHelper::formatDateTime($order->created_at, 'd/m/Y H:i') : '',
            $order->room?->name ?? '',
            $order->campaign?->restaurant ?: ($order->campaign?->name ?? ''),
            (float) $order->subtotal,
            (float) $order->sponsor_amount,
            (float) $order->final_amount,
            __('payment_statement.statuses.' . $order->status->value),
        ])->values()->all();
    }

    /**
     * Translate column headings in the active interface language.
     *
     * @return array<int, string> Statement headings.
     */
    public function headings(): array
    {
        return array_map(static fn (string $key): string => __($key), [
            'global.dashboard.col_code',
            'global.dashboard.col_time',
            'global.dashboard.col_room',
            'global.dashboard.col_restaurant',
            'global.orders.th_subtotal',
            'global.payments.total_sponsor_received',
            'global.dashboard.col_amount',
            'global.dashboard.col_status',
        ]);
    }

    /**
     * Format monetary columns as whole currency amounts.
     *
     * @return array<string, string> Excel column number formats.
     */
    public function columnFormats(): array
    {
        return ['E' => '#,##0', 'F' => '#,##0', 'G' => '#,##0'];
    }

    /**
     * Preserve text literally so names cannot become spreadsheet formulas.
     *
     * @param Cell $cell Target spreadsheet cell.
     * @param mixed $value Exported cell value.
     * @return bool Whether the cell value was bound.
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

<?php

declare(strict_types=1);

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Export aggregated campaign item orders to Excel/CSV.
 *
 * Exports order aggregation data with item details, sizes, toppings, and quantities.
 */
class CampaignAggregateExport implements FromArray, WithHeadings, ShouldAutoSize
{
    /** @var array<int, array<int, mixed>> */
    private array $rows;

    /**
     * Constructor.
     *
     * @param array<int, array<string, mixed>> $rows Order aggregation rows with item details.
     */
    public function __construct(array $rows)
    {
        $this->rows = array_values(array_map(
            static fn (array $row): array => [
                $row['name'] ?? '',
                $row['size'] ?? '',
                $row['toppings'] ?? '',
                $row['quantity'] ?? 0,
            ],
            $rows
        ));
    }

    /**
     * Get the export data array.
     *
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        return $this->rows;
    }

    /**
     * Get the column headings.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            __('admin.item_name_customization'),
            __('admin.size'),
            __('admin.toppings_label'),
            __('admin.quantity'),
        ];
    }
}

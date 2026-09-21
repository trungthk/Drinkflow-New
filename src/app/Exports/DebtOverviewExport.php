<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use App\Models\Debt;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

final class DebtOverviewExport extends DefaultValueBinder implements FromArray, WithHeadings, WithCustomValueBinder
{
    /** @param Collection<int, Debt> $debts */
    public function __construct(private readonly Collection $debts) {}

    /** @return array<int, array<int, mixed>> */
    public function array(): array
    {
        return $this->debts->map(static fn (Debt $debt): array => [
            $debt->room?->name ?? '',
            $debt->campaign?->name ?? '',
            $debt->roomUser?->globalUser?->email ?? '',
            (float) $debt->original_amount,
            (float) $debt->paid_amount,
            (float) $debt->remaining_amount,
            $debt->status?->value ?? (string) $debt->status,
        ])->values()->all();
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return ['room', 'campaign', 'user', 'original_amount', 'paid_amount', 'remaining_amount', 'status'];
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
}

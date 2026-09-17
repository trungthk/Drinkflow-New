<?php

declare(strict_types=1);

namespace App\Exports;

use App\Enums\DebtStatus;
use App\Models\Debt;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;

final class AdminDebtLedgerExport implements FromQuery, WithMapping, WithHeadings, ShouldAutoSize, WithCustomCsvSettings
{
    public function __construct(private readonly int $roomId) {}

    public function query(): Builder
    {
        return Debt::query()->where('room_id', $this->roomId)->with(['campaign', 'roomUser.globalUser'])->latest();
    }

    public function map($debt): array
    {
        $status = $debt->status instanceof DebtStatus ? $debt->status->value : (string) $debt->status;
        $sponsorType = $debt->sponsor_type ?: 'none';
        return [$debt->campaign?->name, $debt->roomUser?->user_code, $debt->roomUser?->globalUser?->email, $debt->created_at?->format('Y-m-d'), $debt->original_amount, __('admin.sponsor_type_'.$sponsorType), $debt->sponsor_amount, $debt->paid_amount, $debt->remaining_amount, __('admin.status_'.$status)];
    }

    public function headings(): array
    {
        return [__('admin.debt_export_campaign'), __('admin.debt_export_user_code'), __('admin.debt_export_user'), __('admin.debt_export_date'), __('admin.debt_export_original_amount'), __('admin.debt_export_sponsor_type'), __('admin.debt_export_sponsor_amount'), __('admin.debt_export_paid_amount'), __('admin.debt_export_remaining_amount'), __('admin.debt_export_status')];
    }

    /** @return array<string, mixed> */
    public function getCsvSettings(): array
    {
        return ['use_bom' => true];
    }
}

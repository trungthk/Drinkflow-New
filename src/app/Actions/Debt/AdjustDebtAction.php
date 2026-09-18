<?php

declare(strict_types=1);

namespace App\Actions\Debt;

use App\Enums\DebtAdjustmentType;
use App\Enums\DebtStatus;
use App\Models\Debt;
use App\Models\DebtAdjustment;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdjustDebtAction
{
    /**
     * Adjust a member's debt balance with an audit record.
     *
     * @param Debt $debt Debt entity instance.
     * @param string $type Adjustment type ('increase', 'decrease', 'waive', 'correction').
     * @param int $amount Amount to adjust.
     * @param string $reason Justification note for the adjustment.
     * @return Debt Updated debt instance.
     * @throws ValidationException If parameters are invalid or adjustment results in negative debt.
     */
    public function execute(Debt $debt, string $type, int $amount, string $reason): Debt
    {
        $adjustmentType = DebtAdjustmentType::tryFrom($type);
        if ($adjustmentType === null || $amount < 0 || trim($reason) === '') {
            throw ValidationException::withMessages([
                'adjustment' => __('admin.invalid_adjustment_data'),
            ]);
        }

        return DB::transaction(function () use ($debt, $adjustmentType, $amount, $reason): Debt {
            $debt = Debt::whereKey($debt->id)->lockForUpdate()->firstOrFail();
            $before = $debt->remaining_amount;
            $delta = match ($adjustmentType) {
                DebtAdjustmentType::Increase => $amount,
                DebtAdjustmentType::Decrease => -$amount,
                DebtAdjustmentType::Waive => -$before,
                DebtAdjustmentType::Correction => $amount - $before,
            };
            $after = $before + $delta;
            if ($after < 0) {
                throw ValidationException::withMessages([
                    'amount' => __('admin.adjustment_cannot_negative'),
                ]);
            }
            DebtAdjustment::create([
                'debt_id' => $debt->id,
                'admin_id' => request()->user('admin')?->id,
                'type' => $adjustmentType->value,
                'amount' => $delta,
                'reason' => $reason,
                'before_amount' => $before,
                'after_amount' => $after,
            ]);
            $debt->adjustment_amount += $delta;
            $debt->remaining_amount = $after;
            $debt->status = $after === 0
                ? ($adjustmentType === DebtAdjustmentType::Waive ? DebtStatus::Waived : DebtStatus::Paid)
                : ($debt->paid_amount > 0 ? DebtStatus::Partial : DebtStatus::Unpaid);
            $debt->save();
            app(AuditService::class)->record(
                'debt.adjusted',
                'debt',
                $debt->id,
                $debt->room_id,
                ['remaining_amount' => $before],
                ['remaining_amount' => $after, 'adjustment_amount' => $debt->adjustment_amount],
                ['reason' => $reason, 'type' => $adjustmentType->value]
            );

            return $debt->fresh();
        });
    }
}

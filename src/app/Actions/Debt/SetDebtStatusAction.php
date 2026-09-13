<?php

declare(strict_types=1);

namespace App\Actions\Debt;

use App\Enums\DebtStatus;
use App\Models\Debt;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SetDebtStatusAction
{
    /**
     * Manually update the status of a debt ledger record.
     *
     * @param Debt $debt Debt instance to update.
     * @param string $status New status ('unpaid', 'partial', 'paid', 'waived').
     * @return Debt Fresh debt instance with loaded relations.
     * @throws ValidationException If marking unpaid when remaining amount is 0.
     */
    public function execute(Debt $debt, string $status): Debt
    {
        return DB::transaction(function () use ($debt, $status): Debt {
            $debt = Debt::query()->lockForUpdate()->findOrFail($debt->id);
            $before = $debt->status->value;
            if ($status === DebtStatus::Unpaid->value && $debt->remaining_amount === 0) {
                throw ValidationException::withMessages([
                    'status' => __('admin.debt_zero_balance_unpaid'),
                ]);
            }
            if ($status === DebtStatus::Paid->value) {
                $debt->paid_amount += $debt->remaining_amount;
                $debt->remaining_amount = 0;
            }
            if ($status === DebtStatus::Waived->value) {
                $debt->remaining_amount = 0;
            }
            if ($status === DebtStatus::Unpaid->value) {
                $debt->paid_amount = max(0, $debt->original_amount + $debt->adjustment_amount - $debt->sponsor_amount - $debt->remaining_amount);
            }
            $debt->status = $status;
            $debt->save();
            app(AuditService::class)->record('debt.status_updated', 'debt', $debt->id, $debt->room_id, ['status' => $before], ['status' => $status]);

            return $debt->fresh(['roomUser.globalUser', 'campaign']);
        });
    }
}


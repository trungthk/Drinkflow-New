<?php

namespace App\Actions\Debt;

use App\Models\Debt;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SetDebtStatusAction
{
    public function execute(Debt $debt, string $status): Debt
    {
        return DB::transaction(function () use ($debt, $status): Debt {
            $debt = Debt::query()->lockForUpdate()->findOrFail($debt->id);
            $before = $debt->status->value;
            if ($status === 'unpaid' && $debt->remaining_amount === 0) {
                throw ValidationException::withMessages(['status' => 'Debt đã không còn số dư để đánh dấu unpaid.']);
            }
            if ($status === 'paid') {
                $debt->paid_amount += $debt->remaining_amount;
                $debt->remaining_amount = 0;
            }
            if ($status === 'waived') {
                $debt->remaining_amount = 0;
            }
            if ($status === 'unpaid') {
                $debt->paid_amount = max(0, $debt->original_amount + $debt->adjustment_amount - $debt->sponsor_amount - $debt->remaining_amount);
            }
            $debt->status = $status;
            $debt->save();
            app(AuditService::class)->record('debt.status_updated', 'debt', $debt->id, $debt->room_id, ['status' => $before], ['status' => $status]);

            return $debt->fresh(['roomUser.globalUser', 'campaign']);
        });
    }
}

<?php
namespace App\Actions\Debt;

use App\Models\Debt;
use App\Models\DebtAdjustment;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdjustDebtAction
{
    /**
     * Handle the execute operation.
     * @param Debt $debt Parameter value.
     * @param string $type Parameter value.
     * @param int $amount Parameter value.
     * @param string $reason Parameter value.
     * @return Debt Result of the operation.
     */
    public function execute(Debt $debt, string $type, int $amount, string $reason): Debt
    {
        if (!in_array($type, ['increase', 'decrease', 'waive', 'correction'], true) || $amount < 0 || trim($reason) === '') {
            throw ValidationException::withMessages(['adjustment' => 'ThĂ´ng tin Ä‘iá»u chá»‰nh khĂ´ng há»£p lá»‡.']);
        }
        return DB::transaction(function () use ($debt, $type, $amount, $reason): Debt {
            $debt = Debt::whereKey($debt->id)->lockForUpdate()->firstOrFail();
            $before = $debt->remaining_amount;
            $delta = match ($type) { 'increase' => $amount, 'decrease' => -$amount, 'waive' => -$before, 'correction' => $amount - $before };
            $after = $before + $delta;
            if ($after < 0) throw ValidationException::withMessages(['amount' => 'Äiá»u chá»‰nh khĂ´ng thá»ƒ lĂ m sá»‘ dÆ° Ă¢m.']);
            DebtAdjustment::create(['debt_id' => $debt->id, 'admin_id' => request()->user('admin')?->id, 'type' => $type, 'amount' => $delta, 'reason' => $reason, 'before_amount' => $before, 'after_amount' => $after]);
            $debt->adjustment_amount += $delta;
            $debt->remaining_amount = $after;
            $debt->status = $after === 0 ? ($type === 'waive' ? 'waived' : 'paid') : ($debt->paid_amount > 0 ? 'partial' : 'unpaid');
            $debt->save();
            app(AuditService::class)->record('debt.adjusted', 'debt', $debt->id, $debt->room_id, ['remaining_amount' => $before], ['remaining_amount' => $after, 'adjustment_amount' => $debt->adjustment_amount], ['reason' => $reason, 'type' => $type]);
            return $debt->fresh();
        });
    }
}

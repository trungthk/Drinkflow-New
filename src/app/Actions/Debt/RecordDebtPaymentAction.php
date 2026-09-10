<?php

namespace App\Actions\Debt;

use App\Models\Debt;
use App\Models\DebtPayment;
use App\Services\Audit\AuditService;
use App\Services\Notification\UserNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordDebtPaymentAction
{
    /**
     * Handle the execute operation.
     * @param Debt $debt Parameter value.
     * @param int $amount Parameter value.
     * @param string $method Parameter value.
     * @param ?string $reference Parameter value.
     * @return Debt Result of the operation.
     */
    public function execute(Debt $debt, int $amount, string $method, ?string $reference = null): Debt
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Sá»‘ tiá»n thanh toĂ¡n khĂ´ng há»£p lá»‡.']);
        }
        $updated = DB::transaction(function () use ($debt, $amount, $method, $reference): Debt {
            $debt = Debt::whereKey($debt->id)->lockForUpdate()->firstOrFail();
            $before = $debt->remaining_amount;
            if ($amount > $before) {
                throw ValidationException::withMessages(['amount' => 'Thanh toĂ¡n vÆ°á»£t quĂ¡ sá»‘ dÆ°.']);
            }
            DebtPayment::create(['debt_id' => $debt->id, 'amount' => $amount, 'payment_method' => $method, 'reference' => $reference, 'created_by_admin_id' => request()->user('admin')?->id]);
            $debt->paid_amount += $amount;
            $debt->remaining_amount -= $amount;
            $debt->status = $debt->remaining_amount === 0 ? 'paid' : 'partial';
            $debt->save();
            app(AuditService::class)->record('debt.payment_recorded', 'debt', $debt->id, $debt->room_id, ['remaining_amount' => $before], ['remaining_amount' => $debt->remaining_amount, 'paid_amount' => $debt->paid_amount]);

            return $debt->fresh(['roomUser']);
        });
        app(UserNotificationService::class)->toRoomUser($updated->roomUser, 'debt.updated', 'Thanh toĂ¡n Ä‘Ă£ Ä‘Æ°á»£c ghi nháº­n', 'Sá»‘ dÆ° cĂ²n láº¡i: '.$updated->remaining_amount, ['debt_id' => $updated->id, 'remaining_amount' => $updated->remaining_amount]);

        return $updated;
    }
}

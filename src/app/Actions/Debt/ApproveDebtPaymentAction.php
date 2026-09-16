<?php

declare(strict_types=1);

namespace App\Actions\Debt;

use App\Enums\DebtStatus;
use App\Enums\PaymentStatus;
use App\Events\RoomRealtimeEvent;
use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\Order;
use App\Services\Audit\AuditService;
use App\Services\Notification\UserNotificationService;
use App\Support\Helpers\FormatHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveDebtPaymentAction
{
    /**
     * Approve pending payment for a debt record and clear outstanding balance.
     *
     * @param Debt $debt Debt entity to approve.
     * @param ?string $reference Reference note for the approval.
     * @return Debt Updated debt entity.
     * @throws ValidationException If debt is already settled or zero balance.
     */
    public function execute(Debt $debt, ?string $reference = null): Debt
    {
        $approvingAdmin = request()->user('admin');
        $approvedAt = now();
        $updated = DB::transaction(function () use ($debt, $reference, $approvingAdmin, $approvedAt): Debt {
            $lockedDebt = Debt::whereKey($debt->id)->lockForUpdate()->firstOrFail();
            $beforeRemaining = (int) $lockedDebt->remaining_amount;
            $approvingAdminId = $approvingAdmin?->id;

            if ($lockedDebt->status === DebtStatus::Paid && $beforeRemaining === 0) {
                throw ValidationException::withMessages([
                    'debt' => __('admin.debt_already_settled'),
                ]);
            }

            $approvedAmount = $beforeRemaining > 0 ? $beforeRemaining : (int) $lockedDebt->original_amount;

            // Record debt payment receipt
            DebtPayment::create([
                'debt_id' => $lockedDebt->id,
                'amount' => $approvedAmount,
                'payment_method' => 'vietqr',
                'reference' => $reference ?? 'Admin approved payment',
                'paid_at' => $approvedAt,
                'created_by_admin_id' => $approvingAdminId,
            ]);

            $lockedDebt->paid_amount += $beforeRemaining;
            $lockedDebt->remaining_amount = 0;
            $lockedDebt->status = DebtStatus::Paid;
            $lockedDebt->save();

            // Synchronize corresponding order(s) for this user and campaign
            Order::query()
                ->where('campaign_id', $lockedDebt->campaign_id)
                ->where('room_user_id', $lockedDebt->room_user_id)
                ->update([
                    'payment_status' => PaymentStatus::Paid->value,
                    'paid_at' => $approvedAt,
                ]);

            app(AuditService::class)->record(
                'debt.payment_approved',
                'debt',
                $lockedDebt->id,
                $lockedDebt->room_id,
                ['remaining_amount' => $beforeRemaining, 'status' => $debt->status?->value],
                ['remaining_amount' => 0, 'status' => DebtStatus::Paid->value]
            );

            return $lockedDebt->fresh(['roomUser.globalUser', 'campaign']);
        });

        // Notify the member
        if ($updated->roomUser) {
            app(UserNotificationService::class)->toRoomUser(
                $updated->roomUser,
                'debt.payment_approved',
                __('admin.payment_approved_title'),
                __('admin.payment_approved_body', [
                    'amount' => FormatHelper::formatCurrency((int) $updated->paid_amount),
                    'campaign' => $updated->campaign?->name ?? '',
                ]),
                ['debt_id' => $updated->id, 'campaign_id' => $updated->campaign_id]
            );
        }

        // Emit realtime socket event
        RoomRealtimeEvent::dispatch('debt.payment_approved', $updated->room_id, [
            'debt_id' => $updated->id,
            'campaign_id' => $updated->campaign_id,
            'room_user_id' => $updated->room_user_id,
            'status' => 'paid',
            'remaining_amount' => 0,
            'approved_by' => $approvingAdmin?->name,
            'approved_at' => $approvedAt->format('d/m/Y H:i'),
        ]);

        return $updated;
    }
}

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
            $roomUser = $lockedDebt->roomUser;
            $approvingAdminId = $approvingAdmin?->id;

            $isPayAll = !empty($lockedDebt->payment_content) && $roomUser && $lockedDebt->payment_content === $roomUser->user_code;

            /** @var \Illuminate\Database\Eloquent\Collection<int, Debt> $debtsToApprove */
            $debtsToApprove = collect([$lockedDebt]);
            if ($isPayAll) {
                $debtsToApprove = Debt::query()
                    ->where('room_id', $lockedDebt->room_id)
                    ->where('room_user_id', $lockedDebt->room_user_id)
                    ->where('status', DebtStatus::Pending)
                    ->lockForUpdate()
                    ->get();

                if ($debtsToApprove->isEmpty()) {
                    $debtsToApprove = collect([$lockedDebt]);
                }
            }

            $hasValidSettlement = false;

            foreach ($debtsToApprove as $targetDebt) {
                $beforeRemaining = (int) $targetDebt->remaining_amount;
                if ($targetDebt->status === DebtStatus::Paid && $beforeRemaining === 0) {
                    continue;
                }

                $hasValidSettlement = true;
                $approvedAmount = $beforeRemaining > 0 ? $beforeRemaining : (int) $targetDebt->original_amount;

                // Record debt payment receipt
                DebtPayment::create([
                    'debt_id' => $targetDebt->id,
                    'amount' => $approvedAmount,
                    'payment_method' => 'vietqr',
                    'reference' => $reference ?? ($isPayAll ? 'Admin approved full debt settlement' : 'Admin approved payment'),
                    'paid_at' => $approvedAt,
                    'created_by_admin_id' => $approvingAdminId,
                ]);

                $targetDebt->paid_amount += $beforeRemaining;
                $targetDebt->remaining_amount = 0;
                $targetDebt->status = DebtStatus::Paid;
                $targetDebt->save();

                // Synchronize corresponding order(s) for this user and campaign
                if ($targetDebt->campaign_id) {
                    Order::query()
                        ->where('campaign_id', $targetDebt->campaign_id)
                        ->where('room_user_id', $targetDebt->room_user_id)
                        ->update([
                            'payment_status' => PaymentStatus::Paid->value,
                            'paid_at' => $approvedAt,
                        ]);
                }

                app(AuditService::class)->record(
                    'debt.payment_approved',
                    'debt',
                    $targetDebt->id,
                    $targetDebt->room_id,
                    ['remaining_amount' => $beforeRemaining, 'status' => DebtStatus::Pending->value],
                    ['remaining_amount' => 0, 'status' => DebtStatus::Paid->value]
                );
            }

            if (! $hasValidSettlement && $lockedDebt->status === DebtStatus::Paid) {
                throw ValidationException::withMessages([
                    'debt' => __('admin.debt_already_settled'),
                ]);
            }

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

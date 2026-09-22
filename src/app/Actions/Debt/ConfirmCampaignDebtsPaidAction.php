<?php

declare(strict_types=1);

namespace App\Actions\Debt;

use App\Enums\DebtStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Campaign;
use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\Order;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class ConfirmCampaignDebtsPaidAction
{
    /** Record outstanding campaign balances as paid and synchronize related orders. */
    public function execute(Campaign $campaign, PaymentMethod $paymentMethod, ?int $adminId): int
    {
        return DB::transaction(function () use ($campaign, $paymentMethod, $adminId): int {
            $debts = Debt::query()
                ->where('room_id', $campaign->room_id)
                ->where('campaign_id', $campaign->id)
                ->whereIn('status', DebtStatus::outstandingValues())
                ->where('remaining_amount', '>', 0)
                ->lockForUpdate()
                ->get();

            $audit = app(AuditService::class);
            $paidAt = now();
            foreach ($debts as $debt) {
                $before = ['status' => $debt->status->value, 'remaining_amount' => $debt->remaining_amount];
                $amount = (int) $debt->remaining_amount;
                DebtPayment::create([
                    'debt_id' => $debt->id,
                    'amount' => $amount,
                    'payment_method' => $paymentMethod->value,
                    'paid_at' => $paidAt,
                    'created_by_admin_id' => $adminId,
                ]);
                $debt->paid_amount += $debt->remaining_amount;
                $debt->remaining_amount = 0;
                $debt->status = DebtStatus::Paid;
                $debt->save();
                Order::query()
                    ->where('room_id', $campaign->room_id)
                    ->where('campaign_id', $campaign->id)
                    ->where('room_user_id', $debt->room_user_id)
                    ->where('status', '!=', OrderStatus::Cancelled->value)
                    ->whereNull('cancelled_at')
                    ->where('payment_status', '!=', PaymentStatus::Paid->value)
                    ->update([
                        'payment_status' => PaymentStatus::Paid->value,
                        'paid_at' => $paidAt,
                    ]);
                $audit->record('debt.status_updated', 'debt', $debt->id, $debt->room_id, $before, [
                    'status' => DebtStatus::Paid->value,
                    'remaining_amount' => 0,
                ]);
            }

            return $debts->count();
        });
    }
}

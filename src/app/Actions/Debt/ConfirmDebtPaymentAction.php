<?php

declare(strict_types=1);

namespace App\Actions\Debt;

use App\Enums\DebtStatus;
use App\Enums\PaymentStatus;
use App\Events\RoomRealtimeEvent;
use App\Models\AdminAccount;
use App\Models\AdminNotification;
use App\Models\Debt;
use App\Models\Order;
use App\Models\Room;
use App\Models\RoomUser;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmDebtPaymentAction
{
    /**
     * Mark debt record(s) as pending admin confirmation and notify room admins.
     *
     * @param Room $room Target room entity.
     * @param RoomUser $roomUser Room user who submitted the debt payment confirmation.
     * @param ?int $debtId Specific debt ID, or null for all unpaid debts.
     * @param ?string $transferContent Custom transfer content / reference text.
     * @return Collection<int, Debt> Updated debt entities.
     * @throws ValidationException If unauthorized or all debts already settled.
     */
    public function execute(Room $room, RoomUser $roomUser, ?int $debtId = null, ?string $transferContent = null): Collection
    {
        if ($roomUser->room_id !== $room->id) {
            throw ValidationException::withMessages([
                'debt' => __('room.debts.unauthorized_debt_access', ['default' => 'Unauthorized access to debt records.']),
            ]);
        }

        $paymentRequestedAt = now();
        $fallbackContent = $transferContent ?: $roomUser->user_code;

        /** @var Collection<int, Debt> $updatedDebts */
        $updatedDebts = DB::transaction(function () use ($room, $roomUser, $debtId, $fallbackContent, $paymentRequestedAt): Collection {
            $query = Debt::query()
                ->where('room_id', $room->id)
                ->where('room_user_id', $roomUser->id);

            if ($debtId !== null && $debtId > 0) {
                $query->whereKey($debtId);
            } else {
                // Pay-all only includes debts for which no confirmation request exists.
                $query->whereIn('status', [DebtStatus::Unpaid->value, DebtStatus::Partial->value])
                    ->where('remaining_amount', '>', 0);
            }

            /** @var Collection<int, Debt> $debts */
            $debts = $query->lockForUpdate()->get();

            if ($debts->isEmpty()) {
                throw ValidationException::withMessages([
                    'debt' => __('room.debts.no_debts_to_confirm', ['default' => 'No active debts found to confirm payment.']),
                ]);
            }

            if ($debts->contains(static fn (Debt $debt): bool => $debt->status === DebtStatus::Pending)) {
                throw ValidationException::withMessages([
                    'debt' => __('room.debts.payment_already_pending', ['default' => 'Payment confirmation is already awaiting approval.']),
                ]);
            }

            foreach ($debts as $debt) {
                if ($debt->status !== DebtStatus::Paid) {
                    $debt->status = DebtStatus::Pending;
                    $debt->payment_requested_at = $paymentRequestedAt;
                    if (!empty($fallbackContent)) {
                        $debt->payment_content = $fallbackContent;
                    }
                    $debt->save();

                    // Synchronize matching campaign orders to pending payment status
                    if ($debt->campaign_id) {
                        Order::query()
                            ->where('campaign_id', $debt->campaign_id)
                            ->where('room_user_id', $roomUser->id)
                            ->where('payment_status', '!=', PaymentStatus::Paid)
                            ->update(['payment_status' => PaymentStatus::Pending->value]);
                    }
                }
            }

            return $debts;
        });

        $totalConfirmedAmount = (int) $updatedDebts->sum('remaining_amount');
        if ($totalConfirmedAmount === 0) {
            $totalConfirmedAmount = (int) $updatedDebts->sum('original_amount');
        }

        $userName = $roomUser->globalUser?->name ?? $roomUser->display_name ?? ('User #' . $roomUser->id);
        $amountFmt = number_format($totalConfirmedAmount, 0, ',', '.') . ' ₫';

        // Notify room admins via AdminNotification table
        $room->admins()->each(function (AdminAccount $admin) use ($room, $userName, $amountFmt, $totalConfirmedAmount, $updatedDebts): void {
            AdminNotification::create([
                'admin_id' => $admin->id,
                'room_id' => $room->id,
                'type' => 'debt.payment_submitted',
                'title' => __('admin.payment_confirmation_request_title', ['default' => 'Yêu cầu xác nhận thanh toán']),
                'body' => sprintf(
                    'Thành viên %s đã gửi xác nhận thanh toán %s (%d khoản nợ/chiến dịch).',
                    $userName,
                    $amountFmt,
                    $updatedDebts->count()
                ),
                'data' => [
                    'debt_ids' => $updatedDebts->pluck('id')->all(),
                    'amount' => $totalConfirmedAmount,
                    'user_name' => $userName,
                ],
            ]);
        });

        // Broadcast realtime socket event to room channel
        RoomRealtimeEvent::dispatch('debt.payment_submitted', $room->id, [
            'debt_ids' => $updatedDebts->pluck('id')->all(),
            'room_id' => $room->id,
            'room_user_id' => $roomUser->id,
            'user_name' => $userName,
            'user_code' => $roomUser->room_user_code ?? $roomUser->user_code,
            'amount' => $totalConfirmedAmount,
            'status' => DebtStatus::Pending->value,
            'message' => sprintf(
                'Thành viên %s đã gửi xác nhận thanh toán %s.',
                $userName,
                $amountFmt
            ),
        ]);

        return $updatedDebts;
    }
}

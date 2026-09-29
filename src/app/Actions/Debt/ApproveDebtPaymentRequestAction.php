<?php

declare(strict_types=1);

namespace App\Actions\Debt;

use App\Enums\DebtStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\RoomRealtimeEvent;
use App\Models\AdminAccount;
use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\Order;
use App\Models\Room;
use App\Services\Audit\AuditService;
use App\Services\Debt\DebtPaymentRequestService;
use App\Services\Notification\UserNotificationService;
use App\Support\Helpers\FormatHelper;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ApproveDebtPaymentRequestAction
{
    /**
     * Approve a pending consolidated payment request and settle exactly the debts bundled into it.
     *
     * Everything happens in one transaction: the request and its children are locked, the children
     * are checked against the total captured at submission, one payment is booked per child under
     * the request code, and the request becomes `approved`. Any mismatch aborts the whole approval;
     * debts created after the submission are never touched.
     *
     * @param Room $room Room owning the request.
     * @param int $requestId Payment request (parent debt) ID.
     * @param AdminAccount|null $admin Reviewing admin.
     * @return Debt The approved request with its children loaded.
     * @throws ValidationException When the request was already reviewed.
     * @throws ConflictHttpException When the bundled debts no longer match the submitted request.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When the request does not exist in the room.
     */
    public function execute(Room $room, int $requestId, ?AdminAccount $admin): Debt
    {
        $approvedAt = now();
        $request = DB::transaction(function () use ($room, $requestId, $admin, $approvedAt): Debt {
            $request = Debt::paymentRequests()->where('room_id', $room->id)->whereKey($requestId)->lockForUpdate()->firstOrFail();
            if ($request->status !== DebtStatus::Pending) {
                throw ValidationException::withMessages(['debt' => __('admin.payment_request_already_reviewed')]);
            }

            /** @var Collection<int, Debt> $children */
            $children = Debt::query()->where('parent_id', $request->id)->orderBy('id')->lockForUpdate()->get();
            $this->assertUnchanged($request, $children);

            Debt::settlingPaymentRequest(function () use ($request, $children, $admin, $approvedAt): void {
                foreach ($children as $child) {
                    $this->settleChild($request, $child, $admin, $approvedAt);
                }
            });

            $request->paid_amount = (int) $request->original_amount;
            $request->remaining_amount = 0;
            $request->status = DebtStatus::Approved;
            $request->reviewed_at = $approvedAt;
            $request->reviewed_by_admin_id = $admin?->id;
            $request->save();
            app(AuditService::class)->record('debt.payment_request_approved', 'debt', $request->id, $room->id,
                ['status' => DebtStatus::Pending->value],
                ['status' => DebtStatus::Approved->value, 'amount' => (int) $request->original_amount, 'debt_ids' => $children->pluck('id')->all()]
            );

            return $request->setRelation('children', $children);
        });

        $this->notify($room, $request, $admin, $approvedAt);

        return $request;
    }

    /**
     * Refuse approval unless the bundled debts are exactly as they were at submission.
     *
     * @param Debt $request Locked payment request.
     * @param Collection<int, Debt> $children Locked bundled debts.
     * @return void
     * @throws ConflictHttpException When a debt is missing, belongs to someone else, was settled, or the total changed.
     */
    private function assertUnchanged(Debt $request, Collection $children): void
    {
        $total = (int) $children->sum('remaining_amount');
        $valid = $children->count() >= DebtPaymentRequestService::MIN_DEBTS
            && $children->every(static fn (Debt $child): bool => $child->room_user_id === $request->room_user_id
                && $child->room_id === $request->room_id
                && in_array($child->status, [DebtStatus::Unpaid, DebtStatus::Partial], true)
                && (int) $child->remaining_amount > 0)
            && $total === (int) $request->original_amount;

        if (! $valid) {
            throw new ConflictHttpException(__('admin.payment_request_conflict', [
                'expected' => FormatHelper::formatCurrency((int) $request->original_amount),
                'actual' => FormatHelper::formatCurrency($total),
            ]));
        }
    }

    /**
     * Book the payment of one bundled debt and mark its campaign orders as paid.
     *
     * @param Debt $request Payment request being approved.
     * @param Debt $child Locked bundled debt.
     * @param AdminAccount|null $admin Reviewing admin.
     * @param \Illuminate\Support\Carbon $paidAt Approval time.
     * @return void
     */
    private function settleChild(Debt $request, Debt $child, ?AdminAccount $admin, \Illuminate\Support\Carbon $paidAt): void
    {
        $amount = (int) $child->remaining_amount;
        $before = ['status' => $child->status->value, 'remaining_amount' => $amount];
        DebtPayment::create([
            'debt_id' => $child->id,
            'amount' => $amount,
            'payment_method' => PaymentMethod::VietQr->value,
            'reference' => $request->code,
            'paid_at' => $paidAt,
            'created_by_admin_id' => $admin?->id,
        ]);
        $child->paid_amount += $amount;
        $child->remaining_amount = 0;
        $child->status = DebtStatus::Paid;
        $child->save();

        Order::query()
            ->where('room_id', $child->room_id)
            ->where('campaign_id', $child->campaign_id)
            ->where('room_user_id', $child->room_user_id)
            ->where('status', '!=', OrderStatus::Cancelled->value)
            ->where('payment_status', '!=', PaymentStatus::Paid->value)
            ->update(['payment_status' => PaymentStatus::Paid->value, 'paid_at' => $paidAt]);

        app(AuditService::class)->record('debt.payment_approved', 'debt', $child->id, $child->room_id, $before, [
            'status' => DebtStatus::Paid->value,
            'remaining_amount' => 0,
        ], ['payment_request' => $request->code]);
    }

    /**
     * Notify the member and the room channel about the approval.
     *
     * @param Room $room Room owning the request.
     * @param Debt $request Approved request with children loaded.
     * @param AdminAccount|null $admin Reviewing admin.
     * @param \Illuminate\Support\Carbon $approvedAt Approval time.
     * @return void
     */
    private function notify(Room $room, Debt $request, ?AdminAccount $admin, \Illuminate\Support\Carbon $approvedAt): void
    {
        $request->loadMissing('roomUser');
        if ($request->roomUser) {
            app(UserNotificationService::class)->toRoomUser(
                $request->roomUser,
                'debt.payment_approved',
                __('room.debts.request_approved_title'),
                __('room.debts.request_approved_body', [
                    'code' => $request->code,
                    'amount' => FormatHelper::formatCurrency((int) $request->original_amount),
                ]),
                ['payment_request_id' => $request->id, 'debt_ids' => $request->children->pluck('id')->all()]
            );
        }

        RoomRealtimeEvent::dispatch('debt.payment_approved', $room->id, [
            'payment_request_id' => $request->id,
            'debt_ids' => $request->children->pluck('id')->all(),
            'room_user_id' => $request->room_user_id,
            'status' => DebtStatus::Paid->value,
            'remaining_amount' => 0,
            'approved_by' => $admin?->name,
            'approved_at' => $approvedAt->format('d/m/Y H:i'),
        ]);
    }
}

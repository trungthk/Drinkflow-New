<?php

declare(strict_types=1);

namespace App\Services\Debt;

use App\Enums\DebtStatus;
use App\Models\Debt;
use App\Models\Room;
use App\Models\RoomUser;
use App\Support\Helpers\FormatHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Read side of consolidated debt payment requests: which debts can be bundled, the member's
 * debt/credit summary, and display payloads for the member and admin pages.
 */
class DebtPaymentRequestService
{
    /** A consolidated request only exists for two or more debts; a single debt uses the single-payment flow. */
    public const MIN_DEBTS = 2;

    public function __construct(
        private readonly UserRoomDebtService $roomDebts,
        private readonly DebtCreditService $credit,
    ) {}

    /**
     * Query the member's campaign debts that can be bundled into a new request.
     *
     * Eligible debts are visible to the member, unpaid or partially paid, still owe money, have no
     * single-payment confirmation pending and were never bundled before (children of a rejected
     * request stay linked and can only be paid one by one).
     *
     * @param Room $room Current room.
     * @param RoomUser $roomUser Room member.
     * @return Builder<Debt> Eligible debt query.
     */
    public function eligibleDebtsQuery(Room $room, RoomUser $roomUser): Builder
    {
        return $this->roomDebts->queryVisibleDebts($room, $roomUser)
            ->whereIn('status', [DebtStatus::Unpaid->value, DebtStatus::Partial->value])
            ->where('remaining_amount', '>', 0)
            ->whereNull('parent_id');
    }

    /**
     * Summarize the member's debt, pending request and credit figures.
     *
     * @param Room $room Current room.
     * @param RoomUser $roomUser Room member.
     * @return array{total: int, pending: int, submittable: int, submittable_count: int, can_submit: bool, credit_enabled: bool, credit_limit: int, credit_available: int|null}
     *         Real outstanding debt, amount awaiting approval, amount that can still be requested, and credit left.
     */
    public function summary(Room $room, RoomUser $roomUser): array
    {
        $visible = $this->roomDebts->queryVisibleDebts($room, $roomUser);
        $eligible = $this->eligibleDebtsQuery($room, $roomUser)->get(['id', 'remaining_amount']);
        $policy = $this->credit->policy($room);

        return [
            'total' => (int) (clone $visible)->whereIn('status', DebtStatus::outstandingValues())->sum('remaining_amount'),
            'pending' => (int) (clone $visible)
                ->whereIn('parent_id', $this->pendingRequestIds($room, $roomUser))
                ->sum('remaining_amount'),
            'submittable' => (int) $eligible->sum('remaining_amount'),
            'submittable_count' => $eligible->count(),
            'can_submit' => $eligible->count() >= self::MIN_DEBTS,
            'credit_enabled' => $policy['enabled'],
            'credit_limit' => $policy['ceiling'],
            'credit_available' => $policy['enabled'] ? max(0, $policy['ceiling'] - $this->credit->used($roomUser)) : null,
        ];
    }

    /**
     * Collect the payment-request data the member debt page renders.
     *
     * @param Room $room Current room.
     * @param RoomUser $roomUser Room member.
     * @param iterable<Debt> $debts Campaign debts listed on the page.
     * @return array{paymentSummary: array<string, mixed>, paymentRequests: Collection<int, Debt>, debtRequests: array<int, Debt>}
     *         Summary figures, the member's recent requests, and the request of each listed bundled debt.
     */
    public function memberViewData(Room $room, RoomUser $roomUser, iterable $debts): array
    {
        return [
            'paymentSummary' => $this->summary($room, $roomUser),
            'paymentRequests' => $this->requestsFor($room, $roomUser),
            'debtRequests' => $this->requestsByChild($debts),
        ];
    }

    /**
     * List the member's payment requests, newest first, with their bundled debts.
     *
     * @param Room $room Current room.
     * @param RoomUser $roomUser Room member.
     * @param int $limit Maximum number of requests.
     * @return Collection<int, Debt> Payment requests.
     */
    public function requestsFor(Room $room, RoomUser $roomUser, int $limit = 10): Collection
    {
        return Debt::paymentRequests()
            ->where('room_id', $room->id)
            ->where('room_user_id', $roomUser->id)
            ->with(['children' => fn ($query) => $query->orderBy('id')->with('campaign:id,name')])
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * List a room's payment requests for admin review: every pending one plus the latest decisions.
     *
     * @param Room $room Current room.
     * @param int $recentLimit Number of already-reviewed requests to include.
     * @return Collection<int, Debt> Pending requests first (oldest first), then recent decisions.
     */
    public function requestsForRoom(Room $room, int $recentLimit = 10): Collection
    {
        $relations = [
            'roomUser.globalUser:id,name,email',
            'reviewer:id,name',
            'children' => fn ($query) => $query->orderBy('id')->with(['campaign:id,name', 'payments']),
        ];
        $pending = Debt::paymentRequests()
            ->where('room_id', $room->id)
            ->where('status', DebtStatus::Pending->value)
            ->with($relations)
            ->orderBy('id')
            ->get();
        $recent = Debt::paymentRequests()
            ->where('room_id', $room->id)
            ->where('status', '!=', DebtStatus::Pending->value)
            ->with($relations)
            ->latest('reviewed_at')
            ->latest('id')
            ->limit($recentLimit)
            ->get();

        return $pending->concat($recent)->values();
    }

    /**
     * Map each bundled debt ID to its request, for the debts listed on a page.
     *
     * @param iterable<Debt> $debts Campaign debts shown on the page.
     * @return array<int, Debt> Payment request keyed by child debt ID.
     */
    public function requestsByChild(iterable $debts): array
    {
        $parentIds = collect($debts)->pluck('parent_id')->filter()->unique()->values();
        if ($parentIds->isEmpty()) {
            return [];
        }
        $requests = Debt::paymentRequests()->whereIn('id', $parentIds)->get()->keyBy('id');

        return collect($debts)
            ->filter(static fn (Debt $debt): bool => $debt->parent_id !== null && $requests->has($debt->parent_id))
            ->mapWithKeys(static fn (Debt $debt): array => [$debt->id => $requests->get($debt->parent_id)])
            ->all();
    }

    /**
     * Build the localized payload the admin review modal renders for one request.
     *
     * The frozen amount of each child is its current balance while the request is pending (balance
     * changes are blocked) and the payment booked under the request code once approved; `conflict`
     * flags a pending request whose children no longer add up to the total captured at submission.
     *
     * @param Debt $request Payment request with roomUser.globalUser, reviewer, children.campaign and children.payments loaded.
     * @return array<string, mixed> Display-ready request data.
     */
    public function formatForAdmin(Debt $request): array
    {
        $status = $request->getStatusValue();
        $childrenTotal = (int) $request->children->sum('remaining_amount');
        $isPending = $status === DebtStatus::Pending->value;

        return [
            'id' => $request->id,
            'code' => (string) $request->code,
            'status' => $status,
            'status_label' => __('admin.payment_request_status_'.$status),
            'member' => (string) ($request->roomUser?->globalUser?->name ?? $request->roomUser?->display_name ?? 'Member #'.$request->room_user_id),
            'member_meta' => collect([$request->roomUser?->globalUser?->email, $request->roomUser?->user_code])->filter()->implode(' · '),
            'requested_at' => $request->payment_requested_at ? FormatHelper::formatDateTime($request->payment_requested_at, 'H:i d/m/Y') : '',
            'transfer_content' => (string) $request->payment_content,
            'amount' => (int) $request->original_amount,
            'current_total' => $childrenTotal,
            'conflict' => $isPending && $childrenTotal !== (int) $request->original_amount,
            'reviewed_at' => $request->reviewed_at ? FormatHelper::formatDateTime($request->reviewed_at, 'H:i d/m/Y') : '',
            'reviewer' => (string) ($request->reviewer?->name ?? ''),
            'review_reason' => (string) $request->review_reason,
            'debts' => $request->children->map(static fn (Debt $child): array => [
                'code' => (string) $child->code,
                'campaign' => (string) ($child->campaign?->name ?? 'N/A'),
                'amount' => $status === DebtStatus::Approved->value
                    ? (int) $child->payments->where('reference', $request->code)->sum('amount')
                    : (int) $child->remaining_amount,
                'note' => (string) $child->note,
            ])->values()->all(),
        ];
    }

    /**
     * Collect the IDs of the member's pending payment requests.
     *
     * @param Room $room Current room.
     * @param RoomUser $roomUser Room member.
     * @return array<int, int> Pending request IDs.
     */
    private function pendingRequestIds(Room $room, RoomUser $roomUser): array
    {
        return Debt::paymentRequests()
            ->where('room_id', $room->id)
            ->where('room_user_id', $roomUser->id)
            ->where('status', DebtStatus::Pending->value)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }
}

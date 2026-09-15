<?php

declare(strict_types=1);

namespace App\Services\Debt;

use App\Actions\Debt\RecordDebtPaymentAction;
use App\Enums\DebtStatus;
use App\Models\Debt;
use App\Models\Room;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class DebtSettlementService
{
    /**
     * Settle all outstanding debts selected by campaign, member, date, or explicit records.
     *
     * @param Room $room Room owning the ledger.
     * @param array<string, mixed> $criteria Validated settlement criteria.
     * @return Collection<int, Debt> Settled debts.
     * @throws ValidationException When no debt selection is supplied.
     */
    public function settle(Room $room, array $criteria): Collection
    {
        $debts = $this->selectedOutstandingDebts($room, $criteria);
        if ($debts->isEmpty()) {
            throw ValidationException::withMessages(['selection' => __('admin.no_outstanding_debts_selected')]);
        }

        $action = app(RecordDebtPaymentAction::class);

        return $debts->map(fn (Debt $debt): Debt => $action->execute($debt, (int) $debt->remaining_amount, (string) $criteria['payment_method'], $criteria['reference'] ?? null));
    }

    /**
     * Return outstanding debts selected by deterministic room-scoped filters.
     *
     * @param Room $room Room owning the ledger.
     * @param array<string, mixed> $criteria Selection filters.
     * @return Collection<int, Debt> Selected outstanding debts.
     */
    public function selectedOutstandingDebts(Room $room, array $criteria): Collection
    {
        $query = Debt::query()->where('room_id', $room->id)->whereIn('status', DebtStatus::outstandingValues());
        if (! empty($criteria['debt_ids'])) {
            $query->whereIn('id', array_map('intval', $criteria['debt_ids']));
        } elseif (! empty($criteria['campaign_id'])) {
            $query->where('campaign_id', (int) $criteria['campaign_id']);
        } elseif (! empty($criteria['room_user_id'])) {
            $query->where('room_user_id', (int) $criteria['room_user_id']);
        } elseif (! empty($criteria['date'])) {
            $query->whereDate('created_at', (string) $criteria['date']);
        } elseif (! empty($criteria['all'])) {
            // Explicitly allow the full room ledger only for a deliberate bulk operation.
        } else {
            return collect();
        }

        return $query->get();
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\DebtStatus;
use App\Models\Debt;
use App\Models\Room;

class AdminDebtService
{
    /**
     * Get aggregate financial totals for the Room Debt & Ledger page.
     *
     * @param Room $room Room entity.
     * @return array<string, int> Ledger metrics.
     */
    public function getLedgerSummary(Room $room): array
    {
        $totalSpent = (int) Debt::where('room_id', $room->id)->sum('original_amount');
        $totalCollected = (int) Debt::where('room_id', $room->id)->where('status', DebtStatus::Paid->value)->sum('original_amount');
        $storeDebtPending = (int) Debt::where('room_id', $room->id)->whereIn('status', DebtStatus::outstandingValues())->sum('remaining_amount');

        return [
            'totalSpent' => $totalSpent,
            'totalCollected' => $totalCollected,
            'storeDebtPending' => $storeDebtPending,
            'memberDebtRemaining' => $storeDebtPending,
        ];
    }
}

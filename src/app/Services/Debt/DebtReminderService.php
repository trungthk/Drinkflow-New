<?php

declare(strict_types=1);

namespace App\Services\Debt;

use App\Models\Debt;
use App\Models\Room;
use App\Services\Notification\RoomNotificationChannelDispatcher;
use App\Services\Notification\UserNotificationService;
use App\Support\Helpers\FormatHelper;
use Illuminate\Support\Collection;

class DebtReminderService
{
    /**
     * Send desktop and configured-channel reminders for selected outstanding debts.
     *
     * @param Room $room Room owning the ledger.
     * @param Collection<int, Debt> $debts Outstanding debts to remind.
     * @return int Number of browser recipients notified.
     */
    public function remind(Room $room, Collection $debts): int
    {
        $debts->loadMissing(['roomUser', 'campaign']);
        $notifications = app(UserNotificationService::class);
        foreach ($debts as $debt) {
            $notifications->toRoomUser($debt->roomUser, 'debt.reminder', __('admin.debt_reminder_title'), __('admin.debt_reminder_body', ['campaign' => $debt->campaign?->name ?? '#'.$debt->campaign_id, 'amount' => FormatHelper::formatCurrency((int) $debt->remaining_amount)]), ['debt_id' => $debt->id, 'campaign_id' => $debt->campaign_id, 'remaining_amount' => $debt->remaining_amount]);
        }
        if ($debts->isNotEmpty()) {
            app(RoomNotificationChannelDispatcher::class)->dispatch($room, ['event' => 'debt.reminder', 'title' => __('admin.debt_reminder_title'), 'message' => __('admin.debt_channel_reminder_body', ['count' => $debts->count(), 'amount' => FormatHelper::formatCurrency((int) $debts->sum('remaining_amount'))]), 'room_id' => $room->id]);
        }

        return $debts->count();
    }
}

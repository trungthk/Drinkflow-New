<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Models\Debt;
use App\Models\GlobalUser;

class UserBlockedService
{
    /**
     * Tính toán mã sự cố và tổng hợp công nợ tồn đọng của người dùng khi tài khoản bị khóa.
     *
     * @param  \App\Models\GlobalUser  $user  Tài khoản người dùng toàn hệ thống bị khóa
     * @return array<string, mixed>  Mảng dữ liệu cho View thông báo khóa tài khoản
     */
    public function getBlockedNoticeData(GlobalUser $user): array
    {
        $incidentCode = '#BLK-' . date('Y') . '-' . str_pad((string) $user->id, 5, '0', STR_PAD_LEFT);
        $recordedAt = now()->format('H:i • d/m/Y') . ' (GMT+7)';

        $roomUserIds = $user->roomUsers()->pluck('id');
        $dbDebts = Debt::with(['room', 'roomUser'])
            ->whereIn('room_user_id', $roomUserIds)
            ->where('remaining_amount', '>', 0)
            ->latest()
            ->get();

        $totalDebt = (int) $dbDebts->sum('remaining_amount');

        $pendingDebts = [];
        foreach ($dbDebts as $debt) {
            $pendingDebts[] = [
                'title' => __('global.blocked.debt_item_title', ['id' => $debt->id, 'room' => $debt->room?->name ?? __('global.blocked.internal_room')]),
                'subtitle' => __('global.blocked.debt_item_subtitle', ['date' => $debt->created_at ? $debt->created_at->format('d/m/Y') : __('global.common.recently')]),
                'amount' => (int) $debt->remaining_amount,
            ];
        }

        return compact(
            'user',
            'incidentCode',
            'recordedAt',
            'pendingDebts',
            'totalDebt'
        );
    }
}

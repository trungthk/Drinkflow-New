<?php

declare(strict_types=1);

namespace App\Services\Debt;

use App\Enums\CampaignStatus;
use App\Enums\DebtStatus;
use App\Enums\PaymentAccountStatus;
use App\Enums\RoomStatus;
use App\Models\Debt;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Models\RoomUser;
use Illuminate\Support\Facades\DB;

class UserRoomDebtService
{
    /**
     * Thu thập danh sách công nợ trong phòng, tính toán tổng nợ chưa trả và tạo payload VietQR chuyển khoản nhanh.
     *
     * @param  \App\Models\Room  $room  Đối tượng phòng hiện tại
     * @param  \App\Models\RoomUser  $roomUser  Thành viên phòng của người dùng hiện tại
     * @param  \App\Models\GlobalUser|null  $user  Tài khoản người dùng toàn hệ thống
     * @return array<string, mixed>  Mảng dữ liệu cho View công nợ phòng
     */
    public function getDebtViewData(Room $room, RoomUser $roomUser, ?GlobalUser $user): array
    {
        $debtsQuery = $roomUser->debts()->where('room_id', $room->id)->with('campaign.paymentAccount')->latest();
        $debts = $debtsQuery->paginate(20);

        $unpaidDebts = Debt::where('room_user_id', $roomUser->id)
            ->where('room_id', $room->id)
            ->where('status', DebtStatus::Unpaid->value)
            ->get();
        $totalUnpaidAmount = (int) $unpaidDebts->sum('remaining_amount');

        $paidThisMonth = Debt::where('room_user_id', $roomUser->id)
            ->where('room_id', $room->id)
            ->where('status', DebtStatus::Paid->value)
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->get();
        $totalPaidMonthAmount = (int) $paidThisMonth->sum('paid_amount');
        $totalPaidMonthCount = $paidThisMonth->count();

        // Default Payment Account in this room for VietQR
        $paymentAccount = $room->paymentAccounts()->where('status', PaymentAccountStatus::Active)->first();
        $vietqrData = null;
        if ($paymentAccount && $totalUnpaidAmount > 0) {
            $transferContent = 'DRINKFLOW-DEBT-'.$roomUser->id;
            $vietqrData = [
                'bank_code' => $paymentAccount->bank_code,
                'bank_name' => $paymentAccount->bank_name,
                'account_number' => $paymentAccount->account_number,
                'account_name' => $paymentAccount->account_name,
                'amount' => $totalUnpaidAmount,
                'transfer_content' => $transferContent,
                'qr_url' => sprintf('https://img.vietqr.io/image/%s-%s-compact2.png?amount=%d&addInfo=%s', rawurlencode($paymentAccount->bank_code), rawurlencode($paymentAccount->account_number), $totalUnpaidAmount, rawurlencode($transferContent)),
            ];
        }

        $activeCampaign = $room->campaigns()->where('status', CampaignStatus::Active)->first();
        $userRooms = $user ? $user->rooms()->where('rooms.status', RoomStatus::Active)->get() : collect();
        $unreadCount = $user ? DB::table('user_notifications')->where('global_user_id', $user->id)->whereNull('read_at')->count() : 0;

        return [
            'room' => $room,
            'roomUser' => $roomUser,
            'user' => $user,
            'debts' => $debts,
            'unpaidDebts' => $unpaidDebts,
            'totalUnpaidAmount' => $totalUnpaidAmount,
            'totalPaidMonthAmount' => $totalPaidMonthAmount,
            'totalPaidMonthCount' => $totalPaidMonthCount,
            'vietqrData' => $vietqrData,
            'activeCampaign' => $activeCampaign ? [
                'name' => $activeCampaign->name,
                'time_remaining' => $activeCampaign->deadline ? ($activeCampaign->deadline->isFuture() ? $activeCampaign->deadline->diffForHumans(['parts' => 2, 'short' => true]) : '00:00') : '14:22',
            ] : null,
            'userRooms' => $userRooms,
            'unreadNotificationsCount' => $unreadCount,
        ];
    }
}

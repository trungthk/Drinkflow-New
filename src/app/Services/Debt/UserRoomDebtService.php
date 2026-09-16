<?php

declare(strict_types=1);

namespace App\Services\Debt;

use App\Enums\CampaignStatus;
use App\Enums\DebtStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentAccountStatus;
use App\Enums\RoomStatus;
use App\Models\Debt;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Models\RoomUser;
use App\Services\Payment\VietQrService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class UserRoomDebtService
{
    public function __construct(private readonly VietQrService $vietQr) {}

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
        $baseDebtsQuery = $this->queryVisibleDebts($room, $roomUser);
        $debts = (clone $baseDebtsQuery)->with('campaign.paymentAccount')->latest()->paginate(20);

        $unpaidDebts = (clone $baseDebtsQuery)
            ->whereIn('status', DebtStatus::outstandingValues())
            ->get();
        $totalUnpaidAmount = (int) $unpaidDebts->sum('remaining_amount');

        $paidThisMonth = (clone $baseDebtsQuery)
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
                'bank_code'       => $paymentAccount->bank_code,
                'bank_name'       => $paymentAccount->bank_name,
                'account_number'  => $paymentAccount->account_number,
                'account_name'    => $paymentAccount->account_name,
                'amount'          => $totalUnpaidAmount,
                'transfer_content' => $transferContent,
                'qr_url'          => $this->vietQr->imageUrl($paymentAccount, $totalUnpaidAmount, $transferContent),
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
            'totalSponsorAmount' => (int) (clone $baseDebtsQuery)->sum('sponsor_amount'),
            'vietqrData' => $vietqrData,
            'activeCampaign' => $activeCampaign ? [
                'name' => $activeCampaign->name,
                'time_remaining' => $activeCampaign->deadline ? ($activeCampaign->deadline->isFuture() ? $activeCampaign->deadline->diffForHumans(['parts' => 2, 'short' => true]) : '00:00') : '14:22',
            ] : null,
            'userRooms' => $userRooms,
            'unreadNotificationsCount' => $unreadCount,
        ];
    }

    /**
     * Build the room-user debt query while excluding debts backed only by cancelled orders.
     *
     * @param Room $room Current room.
     * @param RoomUser $roomUser Current room member.
     * @return Builder<Debt> Visible debt query.
     */
    public function queryVisibleDebts(Room $room, RoomUser $roomUser): Builder
    {
        return Debt::query()
            ->where('room_id', $room->id)
            ->where('room_user_id', $roomUser->id)
            ->whereHas('campaign.orders', static function (Builder $orderQuery) use ($roomUser): void {
                $orderQuery
                    ->where('room_user_id', $roomUser->id)
                    ->where('status', '!=', OrderStatus::Cancelled->value);
            });
    }
}

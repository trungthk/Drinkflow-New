<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Debt;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DebtController extends Controller
{
    /**
     * Handle the index operation.
     * @param Request $request Parameter value.
     * @return JsonResponse|View Result of the operation.
     */
    public function index(Request $request): JsonResponse|View
    {
        $room = $request->attributes->get('room');
        $roomUser = $request->attributes->get('room_user');
        $user = $request->attributes->get('global_user') ?? $request->user('web');

        $debtsQuery = $roomUser->debts()->where('room_id', $room->id)->with('campaign.paymentAccount')->latest();
        $debts = $debtsQuery->paginate(20);

        if ($request->expectsJson()) {
            return response()->json(['data' => $debts]);
        }

        $unpaidDebts = Debt::where('room_user_id', $roomUser->id)
            ->where('room_id', $room->id)
            ->where('status', 'unpaid')
            ->get();
        $totalUnpaidAmount = (int) $unpaidDebts->sum('remaining_amount');

        $paidThisMonth = Debt::where('room_user_id', $roomUser->id)
            ->where('room_id', $room->id)
            ->where('status', 'paid')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->get();
        $totalPaidMonthAmount = (int) $paidThisMonth->sum('paid_amount');
        $totalPaidMonthCount = $paidThisMonth->count();

        // Default Payment Account in this room for VietQR
        $paymentAccount = $room->paymentAccounts()->where('status', 'active')->first();
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

        $activeCampaign = $room->campaigns()->where('status', 'active')->first();
        $userRooms = $user ? $user->rooms()->where('rooms.status', 'active')->get() : collect();
        $unreadCount = DB::table('user_notifications')->where('global_user_id', $user->id)->where('is_read', false)->count();

        return view('user.debts', [
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
        ]);
    }
}

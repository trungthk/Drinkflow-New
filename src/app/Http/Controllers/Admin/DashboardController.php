<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Debt;
use App\Models\Order;
use App\Models\PaymentAccount;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function page(Request $request, Room $room): View
    {
        return view('admin.dashboard', ['room' => $room, 'admin' => $request->user('admin')]);
    }

    public function manage(Request $request, Room $room): View
    {
        return view('admin.operations', ['room' => $room, 'admin' => $request->user('admin')]);
    }

    public function index(Request $request): JsonResponse
    {
        $room = $request->attributes->get('room');
        $today = Carbon::today($room->timezone ?: config('app.timezone'));
        $orders = Order::query()->where('room_id', $room->id);
        $debts = Debt::query()->where('room_id', $room->id);

        return response()->json(['data' => [
            'active_campaigns' => $room->campaigns()->where('status', 'active')->count(),
            'orders_today' => (clone $orders)->whereDate('created_at', $today)->count(),
            'active_room_users' => $room->roomUsers()->where('status', 'active')->count(),
            'outstanding_debts' => (int) (clone $debts)->whereIn('status', ['unpaid', 'partial'])->sum('remaining_amount'),
            'total_sponsored' => (int) (clone $orders)->sum('sponsor_amount'),
            'total_spending' => (int) (clone $orders)->whereNotIn('status', ['cancelled'])->sum('final_amount'),
            'payment_pending' => (int) (clone $debts)->whereIn('status', ['unpaid', 'partial'])->count(),
            'last_campaign' => $room->campaigns()->latest()->first(),
            'upcoming_campaign' => $room->campaigns()->whereIn('status', ['draft', 'scheduled'])->orderBy('deadline')->first(),
            'payment_accounts' => $room->paymentAccounts()->where('status', 'active')->orderByDesc('is_default')->limit(1)->get()->map(fn (PaymentAccount $account) => [
                'bank_code' => $account->bank_code,
                'bank_name' => $account->bank_name,
                'account_name' => $account->account_name,
                'account_number_masked' => str_repeat('•', max(0, strlen((string) $account->account_number) - 4)) . substr((string) $account->account_number, -4),
                'status' => $account->status,
            ])->values(),
            'recent_orders' => (clone $orders)->with(['roomUser.globalUser', 'campaign'])->latest()->limit(10)->get(),
        ]]);
    }
}

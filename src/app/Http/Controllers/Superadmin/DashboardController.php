<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Enums\CampaignStatus;
use App\Enums\DebtStatus;
use App\Enums\GlobalUserStatus;
use App\Enums\OrderStatus;
use App\Enums\RoomStatus;
use App\Http\Controllers\Controller;
use App\Models\AdminAccount;
use App\Models\Campaign;
use App\Models\Debt;
use App\Models\GlobalUser;
use App\Models\Order;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Handle the index operation.
     * @param Request $request Parameter value.
     * @return JsonResponse|View Result of the operation.
     */
    public function index(Request $request): JsonResponse|View
    {
        if (! $request->expectsJson()) return view('superadmin.dashboard');
        $today = now()->toDateString();
        return response()->json(['data' => [
            'total_rooms'         => Room::count(),
            'active_rooms'        => Room::active()->count(),
            'total_global_users'  => GlobalUser::count(),
            'active_global_users' => GlobalUser::active()->count(),
            'total_admins'        => AdminAccount::where('role', 'admin')->count(),
            'active_campaigns'    => Campaign::where('status', CampaignStatus::Active)->count(),
            'orders_today'        => Order::whereDate('created_at', $today)->whereNot('status', OrderStatus::Cancelled)->count(),
            'outstanding_debt'    => (int) Debt::whereIn('status', DebtStatus::outstandingValues())->sum('remaining_amount'),
            'socket_connections' => ['status' => config('services.realtime.url') ? 'configured' : 'unknown', 'endpoint' => config('services.realtime.url')],
            'queue_health' => ['connection' => config('queue.default'), 'failed_jobs' => DB::table('failed_jobs')->count()],
            'system_health' => ['database' => $this->databaseHealth()],
        ]]);
    }

    /**
     * Handle the database health operation.
     * @return string Result of the operation.
     */
    private function databaseHealth(): string
    {
        try { DB::select('select 1'); return 'ok'; } catch (\Throwable) { return 'error'; }
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Enums\CampaignStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\AdminAccount;
use App\Models\Campaign;
use App\Models\GlobalUser;
use App\Models\Order;
use App\Models\Room;
use App\Services\System\SystemHealthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    /**
     * Handle the index operation.
     * @param Request $request Parameter value.
     * @param SystemHealthService $health Infrastructure health snapshot service.
     * @return JsonResponse|View Result of the operation.
     */
    public function index(Request $request, SystemHealthService $health): JsonResponse|View
    {
        if (!$request->expectsJson()) {
            return view('superadmin.dashboard');
        }

        $today = now()->toDateString();
        return response()->json([
            'data' => [
                'total_rooms' => Room::count(),
                'active_rooms' => Room::active()->count(),
                'total_global_users' => GlobalUser::count(),
                'active_global_users' => GlobalUser::active()->count(),
                'total_admins' => AdminAccount::where('role', 'admin')->count(),
                'active_campaigns' => Campaign::where('status', CampaignStatus::Active)->count(),
                'orders_today' => Order::whereDate('created_at', $today)->whereNot('status', OrderStatus::Cancelled)->count(),
                'system_health' => $health->snapshot(),
            ]
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Enums\CampaignStatus;
use App\Enums\GlobalUserStatus;
use App\Enums\OrderStatus;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Campaign;
use App\Models\GlobalUser;
use App\Models\Order;
use App\Models\Room;
use App\Services\Dashboard\SuperadminDashboardService;
use App\Services\Dashboard\SuperadminInsightsService;
use App\Services\Dashboard\SuperadminTrendsService;
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
        $superadmin = $request->user('superadmin');
        return response()->json([
            'data' => [
                'total_rooms' => Room::count(),
                'active_rooms' => Room::active()->count(),
                'total_global_users' => GlobalUser::where('status', '!=', GlobalUserStatus::Deleted->value)->count(),
                'active_global_users' => GlobalUser::active()->count(),
                // Only Agents in the viewer's scope; hidden (null) without `agent.view`.
                'total_admins' => $superadmin->hasPermission(Permission::AgentView) ? Admin::query()->visibleTo($superadmin)->count() : null,
                'active_campaigns' => Campaign::where('status', CampaignStatus::Active)->count(),
                'orders_today' => Order::whereDate('created_at', $today)->whereNot('status', OrderStatus::Cancelled)->count(),
                'system_health' => $health->snapshot(),
            ]
        ]);
    }

    /**
     * System-wide analytics (KPIs with period deltas, daily series, debt aging, room health, stuck campaigns).
     *
     * @param Request $request Current request; `?fresh=1` bypasses the analytics cache.
     * @param SuperadminDashboardService $dashboard Analytics aggregator.
     * @return JsonResponse Analytics payload under `data`.
     */
    public function analytics(Request $request, SuperadminDashboardService $dashboard): JsonResponse
    {
        return response()->json(['data' => $dashboard->analytics($request->boolean('fresh'))]);
    }

    /**
     * Secondary analytics (security trends and suspicious IPs, admin activity, order peak-hours heatmap).
     *
     * @param Request $request Current request; `?fresh=1` bypasses the insights cache.
     * @param SuperadminInsightsService $insights Insights aggregator.
     * @return JsonResponse Insights payload under `data`.
     */
    public function insights(Request $request, SuperadminInsightsService $insights): JsonResponse
    {
        return response()->json(['data' => $insights->insightsFor($request->user('superadmin'), $request->boolean('fresh'))]);
    }

    /**
     * Long-range trends (signup cohort retention, feedback ratings, contact topics, infrastructure history).
     *
     * @param Request $request Current request; `?fresh=1` bypasses the trends cache.
     * @param SuperadminTrendsService $trends Trends aggregator.
     * @return JsonResponse Trends payload under `data`.
     */
    public function trends(Request $request, SuperadminTrendsService $trends): JsonResponse
    {
        return response()->json(['data' => $trends->trends($request->boolean('fresh'))]);
    }
}

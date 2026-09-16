<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Services\Dashboard\AdminDashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the Admin Room Dashboard view.
     */
    public function page(Request $request, Room $room, AdminDashboardService $service): View
    {
        $data = $service->getDashboardPageData($room, $request->user('admin'));

        return view('admin.dashboard', $data);
    }

    /**
     * Return JSON telemetry & metrics data for Admin Dashboard.
     */
    public function index(Request $request, AdminDashboardService $service): JsonResponse
    {
        $room = $request->attributes->get('room');
        $metrics = $service->getDashboardMetrics($room);

        return response()->json(['data' => $metrics]);
    }

    /**
     * Display the Admin Operations Hub view or redirect to dedicated page.
     */
    public function manage(Request $request, Room $room, AdminDashboardService $service): RedirectResponse|View
    {
        $tab = $request->query('tab', 'campaigns');

        return match ($tab) {
            'orders' => redirect()->route('admin.orders.page', $room),
            'debts' => redirect()->route('admin.debts.page', $room),
            'users' => redirect()->route('admin.room-users.page', $room),
            'payments' => redirect()->route('admin.settings.page', $room),
            'settings' => redirect()->route('admin.settings.page', $room),
            'notifications' => redirect()->route('admin.notification-channels.page', $room),
            'reports' => redirect()->route('admin.reports.page', $room),
            'audit' => redirect()->route('admin.audit.page', $room),
            default => redirect()->route('admin.campaigns.page', $room),
        };
    }
}

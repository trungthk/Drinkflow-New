<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Room;
use App\Services\Admin\AdminReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Return JSON telemetry & aggregated report metrics for the room.
     *
     * @param Request $request Incoming HTTP request.
     * @param AdminReportService $reportService Report analytics service.
     * @return JsonResponse Report payload.
     */
    public function index(Request $request, AdminReportService $reportService): JsonResponse
    {
        /** @var Room $room */
        $room = $request->attributes->get('room');
        $metrics = $reportService->getReportMetrics($request, $room);

        return response()->json(['data' => $metrics]);
    }

    /**
     * Display the standalone Reports & Deep Dive Analytics page.
     *
     * @param Request $request Incoming request.
     * @param Room $room Room entity.
     * @param AdminReportService $reportService Report analytics service.
     * @return View Blade view.
     */
    public function page(Request $request, Room $room, AdminReportService $reportService): View
    {
        $campaigns = Campaign::where('room_id', $room->id)->withCount('orders')->latest()->take(5)->get();
        $roomMembersCount = $room->roomUsers()->count();
        $stats = $reportService->getReportStats($request, $room);

        return view('admin.reports', [
            'room' => $room,
            'campaigns' => $campaigns,
            'roomMembersCount' => $roomMembersCount,
            'stats' => $stats,
        ]);
    }
}

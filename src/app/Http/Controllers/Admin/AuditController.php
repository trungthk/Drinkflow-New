<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Room;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    /**
     * Handle the index operation.
     * @param Request $request Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function index(Request $request): JsonResponse
    {
        $room = $request->attributes->get('room');
        $query = AuditLog::query()->where('room_id', $room->id)->latest('created_at');
        if ($request->filled('event')) $query->where('event', $request->string('event')->toString());
        if ($request->filled('target_type')) $query->where('target_type', $request->string('target_type')->toString());
        $dateFrom = $request->input('date_from', $request->input('from'));
        $dateTo = $request->input('date_to', $request->input('to'));
        if ($dateFrom) $query->whereDate('created_at', '>=', $dateFrom);
        if ($dateTo) $query->whereDate('created_at', '<=', $dateTo);
        return response()->json(['data' => $query->paginate(\App\Constants\Pagination::ADMIN_PER_PAGE)]);
    }

    /**
     * Display the standalone Audit Trail logs view.
     *
     * @param Request $request Incoming request.
     * @param Room $room Room entity.
     * @return View Blade view.
     */
    public function page(Request $request, Room $room): View
    {
        $query = AuditLog::query()
            ->where('room_id', $room->id)
            ->latest('created_at');

        if ($request->filled('event')) {
            $query->where('event', $request->string('event')->toString());
        }

        if ($request->filled('target_type')) {
            $query->where('target_type', $request->string('target_type')->toString());
        }

        if ($request->filled('actor')) {
            $actor = $request->string('actor')->toString();
            $query->where(function ($q) use ($actor) {
                $q->where('actor_type', 'like', "%{$actor}%");
                if (is_numeric($actor)) {
                    $q->orWhere('actor_id', (int) $actor);
                }
            });
        }

        $dateFrom = $request->input('date_from', $request->input('from'));
        $dateTo = $request->input('date_to', $request->input('to'));
        if ($dateFrom) $query->whereDate('created_at', '>=', $dateFrom);
        if ($dateTo) $query->whereDate('created_at', '<=', $dateTo);

        $logs = $query->paginate(\App\Constants\Pagination::ADMIN_PER_PAGE)->withQueryString();

        $events = AuditLog::where('room_id', $room->id)->distinct()->pluck('event')->filter()->values();
        $targetTypes = AuditLog::where('room_id', $room->id)->distinct()->pluck('target_type')->filter()->values();

        return view('admin.audit', [
            'room' => $room,
            'logs' => $logs,
            'events' => $events,
            'targetTypes' => $targetTypes,
            'filters' => [
                'event' => $request->input('event', ''),
                'target_type' => $request->input('target_type', ''),
                'actor' => $request->input('actor', ''),
                'date_from' => $dateFrom ?? '',
                'date_to' => $dateTo ?? '',
            ],
        ]);
    }
}

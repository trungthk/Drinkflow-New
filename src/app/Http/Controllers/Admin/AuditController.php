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
        if ($request->filled('from')) $query->whereDate('created_at', '>=', $request->date('from'));
        if ($request->filled('to')) $query->whereDate('created_at', '<=', $request->date('to'));
        return response()->json(['data' => $query->paginate(50)]);
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
        $logs = AuditLog::query()
            ->where('room_id', $room->id)
            ->latest('created_at')
            ->paginate(50);

        return view('admin.audit', [
            'room' => $room,
            'logs' => $logs,
        ]);
    }
}

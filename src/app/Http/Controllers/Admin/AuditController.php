<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditController extends Controller
{
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
}

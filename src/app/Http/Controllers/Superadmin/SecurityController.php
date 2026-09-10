<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\SecurityEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SecurityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SecurityEvent::query()->with('room:id,name,slug')->latest('created_at');
        foreach (['type', 'severity', 'room_id', 'actor_type'] as $field) if ($request->filled($field)) $query->where($field, $request->input($field));
        return response()->json(['data' => $query->paginate(100)]);
    }
}

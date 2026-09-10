<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class SocketMonitoringController extends Controller
{
    /**
     * Handle the index operation.
     * @return JsonResponse Result of the operation.
     */
    public function index(): JsonResponse
    {
        $endpoint = config('services.realtime.url');
        $fallback = ['status' => $endpoint ? 'unreachable' : 'unknown', 'endpoint' => $endpoint, 'connected_users' => null, 'connected_admins' => null, 'connected_superadmins' => null, 'connections_by_room' => [], 'recent_disconnects' => [], 'authentication_failures' => null];
        if (! $endpoint) return response()->json(['data' => $fallback]);
        try {
            $health = Http::timeout(2)->get(rtrim($endpoint, '/').'/health')->throw()->json();
            return response()->json(['data' => array_merge($fallback, $health, ['status' => 'healthy'])]);
        } catch (\Throwable) {
            return response()->json(['data' => $fallback]);
        }
    }
}

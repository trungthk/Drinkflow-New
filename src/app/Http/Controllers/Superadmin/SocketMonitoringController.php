<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Services\System\SystemHealthService;
use Illuminate\Http\JsonResponse;

class SocketMonitoringController extends Controller
{
    /**
     * Handle the index operation.
     *
     * @param SystemHealthService $health Shared infrastructure health service (see SystemHealthService::socket()).
     * @return JsonResponse Result of the operation.
     */
    public function index(SystemHealthService $health): JsonResponse
    {
        return response()->json(['data' => $health->socket()]);
    }
}

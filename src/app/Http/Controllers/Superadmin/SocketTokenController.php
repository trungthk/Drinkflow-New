<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Services\Realtime\SocketTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SocketTokenController extends Controller
{
    /**
     * Invoke the controller.
     * @param Request $request Parameter value.
     * @param SocketTokenService $tokens Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function __invoke(Request $request, SocketTokenService $tokens): JsonResponse
    {
        return response()->json(['data' => ['token' => $tokens->issueForAdmin($request->user('admin')), 'channels' => ['superadmin', 'system'], 'expires_in' => 300]]);
    }
}

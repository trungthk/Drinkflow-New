<?php

declare(strict_types=1);

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
        $superadmin = $request->user('superadmin');

        return response()->json(['data' => [
            'token' => $tokens->issueForSuperadmin($superadmin),
            // `superadmin:{id}` is this account's private inbox channel (platform notifications).
            'channels' => ['superadmin', 'system', 'superadmin:'.$superadmin->id],
            'expires_in' => 300,
        ]]);
    }
}

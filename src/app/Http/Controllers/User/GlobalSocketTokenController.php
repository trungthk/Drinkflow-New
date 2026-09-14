<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\GlobalUser;
use App\Services\Realtime\SocketTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSocketTokenController extends Controller
{
    /** Issue a realtime token for the authenticated browser device. */
    public function __invoke(Request $request, SocketTokenService $tokens): JsonResponse
    {
        /** @var GlobalUser $user */
        $user = $request->attributes->get('global_user') ?? $request->user('web');
        $deviceUuid = (string) $request->cookie('drinkflow_device_uuid', '');

        if ($deviceUuid === '') {
            return response()->json(['data' => null]);
        }

        return response()->json(['data' => [
            'token' => $tokens->issueForDevice($user, $deviceUuid),
            'expires_in' => 300,
        ]]);
    }
}

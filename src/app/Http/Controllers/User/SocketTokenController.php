<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\RoomUser;
use App\Services\Realtime\SocketTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SocketTokenController extends Controller
{
    /**
     * Issue a short-lived realtime token for the room socket client.
     *
     * @param Request $request Current HTTP request instance.
     * @param SocketTokenService $tokens Realtime socket token service.
     * @return JsonResponse JSON response containing token, channels, and TTL.
     */
    public function __invoke(Request $request, SocketTokenService $tokens): JsonResponse
    {
        /** @var RoomUser $roomUser */
        $roomUser = $request->attributes->get('room_user');

        return response()->json(['data' => [
            'token' => $tokens->issue($roomUser),
            'channels' => ['user:'.$roomUser->id, 'room:'.$roomUser->room_id],
            'expires_in' => 300,
        ]]);
    }
}

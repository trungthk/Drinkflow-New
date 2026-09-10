<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\Realtime\SocketTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SocketTokenController extends Controller
{
    public function __invoke(Request $request, SocketTokenService $tokens): JsonResponse
    {
        $roomUser = $request->attributes->get('room_user');

        return response()->json(['data' => [
            'token' => $tokens->issue($roomUser),
            'channels' => ['user:'.$roomUser->id, 'room:'.$roomUser->room_id],
            'expires_in' => 300,
        ]]);
    }
}

<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Handle the show operation.
     * @param Request $request Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->attributes->get('global_user');
        return response()->json(['data' => $user->load(['oauthIdentities', 'roomUsers.room'])]);
    }

    /**
     * Handle the rooms operation.
     * @param Request $request Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function rooms(Request $request): JsonResponse
    {
        $user = $request->attributes->get('global_user');
        return response()->json(['data' => $user->roomUsers()->with('room')->where('status', 'active')->paginate(20)]);
    }
}

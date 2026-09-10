<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Handle the index operation.
     * @param Request $request Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $request->attributes->get('global_user')->notifications()->latest();
        if ($request->boolean('unread')) {
            $query->whereNull('read_at');
        }

        return response()->json(['data' => $query->paginate(30)]);
    }

    /**
     * Handle the read operation.
     * @param Request $request Parameter value.
     * @param UserNotification $notification Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function read(Request $request, UserNotification $notification): JsonResponse
    {
        abort_unless($notification->global_user_id === $request->attributes->get('global_user')->id, 404);
        $notification->update(['read_at' => now()]);

        return response()->json(['data' => $notification->fresh()]);
    }
}

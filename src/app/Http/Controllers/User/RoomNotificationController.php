<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoomNotificationController extends Controller
{
    public function index(Request $request, Room $room): View|JsonResponse
    {
        $room = $request->attributes->get('room') ?? $room;
        $roomUser = $request->attributes->get('room_user');
        $user = $request->attributes->get('global_user') ?? $request->user('web');

        $notifications = DB::table('user_notifications')
            ->where('global_user_id', $user->id)
            ->where(function ($q) use ($roomUser) {
                $q->where('room_user_id', $roomUser?->id)
                  ->orWhereNull('room_user_id');
            })
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(function ($notif) {
                return [
                    'id' => $notif->id,
                    'type' => $notif->type,
                    'title' => $notif->title,
                    'body' => $notif->body,
                    'is_read' => (bool) $notif->is_read,
                    'created_at_formatted' => \Carbon\Carbon::parse($notif->created_at)->diffForHumans(),
                    'created_at_time' => \Carbon\Carbon::parse($notif->created_at)->format('H:i, d/m/Y'),
                    'category' => str_contains($notif->type, 'order') ? 'orders' : (str_contains($notif->type, 'campaign') ? 'campaigns' : (str_contains($notif->type, 'debt') ? 'debts' : 'general')),
                ];
            });

        $unreadCount = $notifications->where('is_read', false)->count();

        if ($request->expectsJson()) {
            return response()->json(['data' => $notifications, 'unread_count' => $unreadCount]);
        }

        $activeCampaign = $room->campaigns()->where('status', 'active')->first();
        $userRooms = $user ? $user->rooms()->where('rooms.status', 'active')->get() : collect();

        return view('user.notifications', [
            'room' => $room,
            'roomUser' => $roomUser,
            'user' => $user,
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'unreadNotificationsCount' => $unreadCount,
            'activeCampaign' => $activeCampaign ? [
                'name' => $activeCampaign->name,
                'time_remaining' => $activeCampaign->deadline ? ($activeCampaign->deadline->isFuture() ? $activeCampaign->deadline->diffForHumans(['parts' => 2, 'short' => true]) : '00:00') : '14:22',
            ] : null,
            'userRooms' => $userRooms,
        ]);
    }
}

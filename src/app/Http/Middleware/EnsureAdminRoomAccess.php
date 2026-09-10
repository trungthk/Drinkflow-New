<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Room;
class EnsureAdminRoomAccess {
    public function handle(Request $request, Closure $next): Response {
        $admin = $request->user('admin');
        $room = $request->route('room');
        $room = $room instanceof Room ? $room : Room::whereKey($room)->firstOrFail();
        abort_unless($admin && $admin->isActive() && ($admin->isSuperadmin() || $admin->rooms()->whereKey($room->id)->exists()), 403);
        abort_unless($room->status === 'active', 404);
        $request->attributes->set('room', $room);
        return $next($request);
    }
}

<?php

namespace App\Http\Controllers\User\Global;

use App\Http\Controllers\Controller;
use App\Models\GlobalUser;
use App\Models\UserNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Handle the index operation for notifications.
     *
     * @param Request $request
     * @return JsonResponse|View
     */
    public function index(Request $request): JsonResponse|View
    {
        /** @var GlobalUser $user */
        $user = $request->attributes->get('global_user') ?? $request->user('web');

        // Return JSON if requested as API
        if ($request->expectsJson() || $request->routeIs('user.notifications.index')) {
            $query = $user->notifications()->latest();
            if ($request->boolean('unread')) {
                $query->whereNull('read_at');
            }
            return response()->json(['data' => $query->paginate(30)]);
        }

        // Web view (/me/notifications)
        $tab = $request->query('tab', 'all');
        $baseQuery = $user->notifications();

        // Calculate counts for filter tabs
        $allCount = (clone $baseQuery)->count();
        $unreadCount = (clone $baseQuery)->whereNull('read_at')->count();
        $roomOrderCount = (clone $baseQuery)->whereIn('type', ['campaign.created', 'order.status', 'room.invite'])->count();
        $paymentCount = (clone $baseQuery)->whereIn('type', ['payment.due', 'payment.confirmed', 'debt.reminder'])->count();
        $securityCount = (clone $baseQuery)->whereIn('type', ['security.alert', 'device.new'])->count();

        // Query by active tab
        $query = (clone $baseQuery)->latest();
        if ($tab === 'unread') {
            $query->whereNull('read_at');
        } elseif ($tab === 'room_order') {
            $query->whereIn('type', ['campaign.created', 'order.status', 'room.invite']);
        } elseif ($tab === 'payment') {
            $query->whereIn('type', ['payment.due', 'payment.confirmed', 'debt.reminder']);
        } elseif ($tab === 'security') {
            $query->whereIn('type', ['security.alert', 'device.new']);
        }

        $notifications = $query->paginate(15)->appends(['tab' => $tab]);

        $breadcrumbs = [
            ['label' => __('global.rooms.breadcrumb_personal'), 'url' => route('user.me.dashboard')],
            ['label' => __('global.notifications.breadcrumb_notifications'), 'url' => route('user.me.notifications')],
        ];

        return view('user.global.notifications', compact(
            'user',
            'tab',
            'allCount',
            'unreadCount',
            'roomOrderCount',
            'paymentCount',
            'securityCount',
            'notifications',
            'breadcrumbs'
        ));
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllRead(Request $request): RedirectResponse
    {
        /** @var GlobalUser $user */
        $user = $request->attributes->get('global_user') ?? $request->user('web');

        $user->notifications()->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('status', __('global.notifications.marked_all_read_status'));
    }

    /**
     * Handle marking a single notification as read.
     *
     * @param Request $request
     * @param UserNotification $notification
     * @return JsonResponse|RedirectResponse
     */
    public function read(Request $request, UserNotification $notification): JsonResponse|RedirectResponse
    {
        /** @var GlobalUser $user */
        $user = $request->attributes->get('global_user') ?? $request->user('web');

        abort_unless($notification->global_user_id === $user->id, 404);
        $notification->update(['read_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json(['data' => $notification->fresh()]);
        }

        return back()->with('status', __('global.notifications.marked_read_status'));
    }
}

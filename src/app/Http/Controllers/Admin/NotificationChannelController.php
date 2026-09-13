<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\NotificationChannelRequest;
use App\Models\NotificationChannel;
use App\Models\Room;
use App\Services\Audit\AuditService;
use App\Services\Notification\RoomNotificationChannelService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationChannelController extends Controller
{
    /**
     * Handle the index operation.
     */
    public function index(Room $room, RoomNotificationChannelService $service): JsonResponse
    {
        $channels = $room->notificationChannels()->get()->map(fn (NotificationChannel $channel) => $service->mask($channel));
        return response()->json(['data' => $channels]);
    }

    /**
     * Display the standalone Notification Channels & Webhook Integrations page.
     *
     * @param Request $request Incoming request.
     * @param Room $room Room entity.
     * @param RoomNotificationChannelService $service Channel service.
     * @return View Blade view.
     */
    public function page(Request $request, Room $room, RoomNotificationChannelService $service): View
    {
        $channels = $room->notificationChannels()->get()->map(fn (NotificationChannel $channel) => $service->mask($channel));

        return view('admin.notifications', [
            'room' => $room,
            'channels' => $channels,
        ]);
    }

    /**
     * Handle the store operation.
     */
    public function store(NotificationChannelRequest $request, Room $room, RoomNotificationChannelService $service, AuditService $audit): JsonResponse
    {
        $channel = $service->save($room->id, $request->validated());
        $audit->record('notification_channel.created', 'notification_channel', $channel->id, $room->id, [], ['type' => $channel->type, 'status' => $channel->status]);
        return response()->json(['data' => $service->mask($channel)], 201);
    }

    /**
     * Handle the update operation.
     */
    public function update(NotificationChannelRequest $request, Room $room, NotificationChannel $channel, RoomNotificationChannelService $service, AuditService $audit): JsonResponse
    {
        abort_unless($channel->room_id === $room->id, 404);
        $before = ['type' => $channel->type, 'status' => $channel->status];
        $channel = $service->save($room->id, $request->validated(), $channel);
        $audit->record('notification_channel.updated', 'notification_channel', $channel->id, $room->id, $before, ['type' => $channel->type, 'status' => $channel->status]);
        return response()->json(['data' => $service->mask($channel)]);
    }

    /**
     * Handle the test operation.
     */
    public function test(Room $room, NotificationChannel $channel, RoomNotificationChannelService $service): JsonResponse
    {
        abort_unless($channel->room_id === $room->id, 404);
        abort_unless($service->configured($channel), 422, __('admin.channel_not_configured'));
        return response()->json(['message' => 'test_accepted', 'data' => ['channel_id' => $channel->id, 'type' => $channel->type]]);
    }

    /**
     * Handle the destroy operation.
     */
    public function destroy(Room $room, NotificationChannel $channel, RoomNotificationChannelService $service, AuditService $audit): JsonResponse
    {
        abort_unless($channel->room_id === $room->id, 404);
        $service->disable($channel, $audit);
        return response()->json(['data' => ['disabled' => true]]);
    }
}


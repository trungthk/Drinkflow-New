<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\NotificationChannelRequest;
use App\Models\NotificationChannel;
use App\Services\Audit\AuditService;
use App\Services\Notification\RoomNotificationChannelService;
use Illuminate\Http\JsonResponse;

class NotificationChannelController extends Controller
{
    public function index(): JsonResponse
    {
        $room = request()->attributes->get('room');
        $channels = $room->notificationChannels()->get()->map(fn (NotificationChannel $channel) => $this->masked($channel));
        return response()->json(['data' => $channels]);
    }

    public function store(NotificationChannelRequest $request, RoomNotificationChannelService $service, AuditService $audit): JsonResponse
    {
        $room = request()->attributes->get('room');
        $channel = $service->save($room->id, $request->validated());
        $audit->record('notification_channel.created', 'notification_channel', $channel->id, $room->id, [], ['type' => $channel->type, 'status' => $channel->status]);
        return response()->json(['data' => $this->masked($channel)], 201);
    }

    public function update(NotificationChannelRequest $request, NotificationChannel $channel, RoomNotificationChannelService $service, AuditService $audit): JsonResponse
    {
        $room = request()->attributes->get('room');
        abort_unless($channel->room_id === $room->id, 404);
        $before = ['type' => $channel->type, 'status' => $channel->status];
        $channel = $service->save($room->id, $request->validated(), $channel);
        $audit->record('notification_channel.updated', 'notification_channel', $channel->id, $room->id, $before, ['type' => $channel->type, 'status' => $channel->status]);
        return response()->json(['data' => $this->masked($channel)]);
    }

    public function test(NotificationChannel $channel, RoomNotificationChannelService $service): JsonResponse
    {
        $room = request()->attributes->get('room');
        abort_unless($channel->room_id === $room->id, 404);
        abort_unless($service->configured($channel), 422, 'Channel chưa được cấu hình credential.');
        return response()->json(['message' => 'test_accepted', 'data' => ['channel_id' => $channel->id, 'type' => $channel->type]]);
    }

    public function destroy(NotificationChannel $channel, AuditService $audit): JsonResponse
    {
        $room = request()->attributes->get('room');
        abort_unless($channel->room_id === $room->id, 404);
        $channel->update(['status' => 'disabled']);
        $audit->record('notification_channel.disabled', 'notification_channel', $channel->id, $room->id, ['status' => 'enabled'], ['status' => 'disabled']);
        return response()->json(['data' => ['disabled' => true]]);
    }

    private function masked(NotificationChannel $channel): array
    {
        return ['id' => $channel->id, 'type' => $channel->type, 'name' => $channel->name, 'status' => $channel->status, 'configured' => (bool) $channel->getRawOriginal('config_encrypted'), 'credential' => $channel->getRawOriginal('config_encrypted') ? '••••••••••' : null];
    }
}

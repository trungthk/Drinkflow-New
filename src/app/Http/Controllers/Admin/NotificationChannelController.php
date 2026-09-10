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
    /**
     * Handle the index operation.
     * @return JsonResponse Result of the operation.
     */
    public function index(): JsonResponse
    {
        $room = request()->attributes->get('room');
        $channels = $room->notificationChannels()->get()->map(fn (NotificationChannel $channel) => $this->masked($channel));
        return response()->json(['data' => $channels]);
    }

    /**
     * Handle the store operation.
     * @param NotificationChannelRequest $request Parameter value.
     * @param RoomNotificationChannelService $service Parameter value.
     * @param AuditService $audit Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function store(NotificationChannelRequest $request, RoomNotificationChannelService $service, AuditService $audit): JsonResponse
    {
        $room = request()->attributes->get('room');
        $channel = $service->save($room->id, $request->validated());
        $audit->record('notification_channel.created', 'notification_channel', $channel->id, $room->id, [], ['type' => $channel->type, 'status' => $channel->status]);
        return response()->json(['data' => $this->masked($channel)], 201);
    }

    /**
     * Handle the update operation.
     * @param NotificationChannelRequest $request Parameter value.
     * @param NotificationChannel $channel Parameter value.
     * @param RoomNotificationChannelService $service Parameter value.
     * @param AuditService $audit Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function update(NotificationChannelRequest $request, NotificationChannel $channel, RoomNotificationChannelService $service, AuditService $audit): JsonResponse
    {
        $room = request()->attributes->get('room');
        abort_unless($channel->room_id === $room->id, 404);
        $before = ['type' => $channel->type, 'status' => $channel->status];
        $channel = $service->save($room->id, $request->validated(), $channel);
        $audit->record('notification_channel.updated', 'notification_channel', $channel->id, $room->id, $before, ['type' => $channel->type, 'status' => $channel->status]);
        return response()->json(['data' => $this->masked($channel)]);
    }

    /**
     * Handle the test operation.
     * @param NotificationChannel $channel Parameter value.
     * @param RoomNotificationChannelService $service Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function test(NotificationChannel $channel, RoomNotificationChannelService $service): JsonResponse
    {
        $room = request()->attributes->get('room');
        abort_unless($channel->room_id === $room->id, 404);
        abort_unless($service->configured($channel), 422, 'Channel chÆ°a Ä‘Æ°á»£c cáº¥u hĂ¬nh credential.');
        return response()->json(['message' => 'test_accepted', 'data' => ['channel_id' => $channel->id, 'type' => $channel->type]]);
    }

    /**
     * Handle the destroy operation.
     * @param NotificationChannel $channel Parameter value.
     * @param AuditService $audit Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function destroy(NotificationChannel $channel, AuditService $audit): JsonResponse
    {
        $room = request()->attributes->get('room');
        abort_unless($channel->room_id === $room->id, 404);
        $channel->update(['status' => 'disabled']);
        $audit->record('notification_channel.disabled', 'notification_channel', $channel->id, $room->id, ['status' => 'enabled'], ['status' => 'disabled']);
        return response()->json(['data' => ['disabled' => true]]);
    }

    /**
     * Handle the masked operation.
     * @param NotificationChannel $channel Parameter value.
     * @return array Result of the operation.
     */
    private function masked(NotificationChannel $channel): array
    {
        return ['id' => $channel->id, 'type' => $channel->type, 'name' => $channel->name, 'status' => $channel->status, 'configured' => (bool) $channel->getRawOriginal('config_encrypted'), 'credential' => $channel->getRawOriginal('config_encrypted') ? 'â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢' : null];
    }
}

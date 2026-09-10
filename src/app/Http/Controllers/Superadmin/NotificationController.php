<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SystemNotificationChannelRequest;
use App\Models\SystemNotificationChannel;
use App\Services\Audit\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index(): JsonResponse { return response()->json(['data' => SystemNotificationChannel::query()->latest()->get()->map(fn (SystemNotificationChannel $channel) => $this->publicChannel($channel))]); }

    public function store(SystemNotificationChannelRequest $request, AuditService $audit): JsonResponse
    {
        $data = $request->validated(); $config = is_string($data['config']) ? $data['config'] : json_encode($data['config'], JSON_THROW_ON_ERROR); unset($data['config']);
        $channel = SystemNotificationChannel::create([...$data, 'config_encrypted' => $config]);
        $audit->record('system_notification_channel.created', 'system_notification_channel', $channel->id, null, [], ['type' => $channel->type, 'name' => $channel->name, 'status' => $channel->status]);
        return response()->json(['data' => $this->publicChannel($channel)], 201);
    }

    public function update(SystemNotificationChannelRequest $request, SystemNotificationChannel $channel, AuditService $audit): JsonResponse
    {
        $data = $request->validated(); $config = is_string($data['config']) ? $data['config'] : json_encode($data['config'], JSON_THROW_ON_ERROR); unset($data['config']);
        $before = $channel->only(['type', 'name', 'status']); $channel->update([...$data, 'config_encrypted' => $config]);
        $audit->record('system_notification_channel.updated', 'system_notification_channel', $channel->id, null, $before, $channel->fresh()->only(array_keys($before)));
        return response()->json(['data' => $this->publicChannel($channel->fresh())]);
    }

    public function destroy(SystemNotificationChannel $channel, AuditService $audit): JsonResponse
    {
        $before = $channel->only(['type', 'name', 'status']); $channel->delete();
        $audit->record('system_notification_channel.deleted', 'system_notification_channel', $channel->id, null, $before);
        return response()->json(['data' => ['deleted' => true]]);
    }

    private function publicChannel(SystemNotificationChannel $channel): array { return ['id' => $channel->id, 'type' => $channel->type, 'name' => $channel->name, 'status' => $channel->status, 'configured' => true]; }
}

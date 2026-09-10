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
    /**
     * Handle the index operation.
     * @return JsonResponse Result of the operation.
     */
    public function index(): JsonResponse { return response()->json(['data' => SystemNotificationChannel::query()->latest()->get()->map(fn (SystemNotificationChannel $channel) => $this->publicChannel($channel))]); }

    /**
     * Handle the store operation.
     * @param SystemNotificationChannelRequest $request Parameter value.
     * @param AuditService $audit Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function store(SystemNotificationChannelRequest $request, AuditService $audit): JsonResponse
    {
        $data = $request->validated(); $config = is_string($data['config']) ? $data['config'] : json_encode($data['config'], JSON_THROW_ON_ERROR); unset($data['config']);
        $channel = SystemNotificationChannel::create([...$data, 'config_encrypted' => $config]);
        $audit->record('system_notification_channel.created', 'system_notification_channel', $channel->id, null, [], ['type' => $channel->type, 'name' => $channel->name, 'status' => $channel->status]);
        return response()->json(['data' => $this->publicChannel($channel)], 201);
    }

    /**
     * Handle the update operation.
     * @param SystemNotificationChannelRequest $request Parameter value.
     * @param SystemNotificationChannel $channel Parameter value.
     * @param AuditService $audit Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function update(SystemNotificationChannelRequest $request, SystemNotificationChannel $channel, AuditService $audit): JsonResponse
    {
        $data = $request->validated(); $config = is_string($data['config']) ? $data['config'] : json_encode($data['config'], JSON_THROW_ON_ERROR); unset($data['config']);
        $before = $channel->only(['type', 'name', 'status']); $channel->update([...$data, 'config_encrypted' => $config]);
        $audit->record('system_notification_channel.updated', 'system_notification_channel', $channel->id, null, $before, $channel->fresh()->only(array_keys($before)));
        return response()->json(['data' => $this->publicChannel($channel->fresh())]);
    }

    /**
     * Handle the destroy operation.
     * @param SystemNotificationChannel $channel Parameter value.
     * @param AuditService $audit Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function destroy(SystemNotificationChannel $channel, AuditService $audit): JsonResponse
    {
        $before = $channel->only(['type', 'name', 'status']); $channel->delete();
        $audit->record('system_notification_channel.deleted', 'system_notification_channel', $channel->id, null, $before);
        return response()->json(['data' => ['deleted' => true]]);
    }

    /**
     * Handle the public channel operation.
     * @param SystemNotificationChannel $channel Parameter value.
     * @return array Result of the operation.
     */
    private function publicChannel(SystemNotificationChannel $channel): array { return ['id' => $channel->id, 'type' => $channel->type, 'name' => $channel->name, 'status' => $channel->status, 'configured' => true]; }
}

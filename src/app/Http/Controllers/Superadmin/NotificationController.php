<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SystemNotificationChannelRequest;
use App\Models\SystemNotificationChannel;
use App\Services\Audit\AuditService;
use App\Services\Notification\SystemNotificationChannelDispatcher;
use App\Services\Notification\SystemNotificationChannelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class NotificationController extends Controller
{
    /**
     * Handle the index operation.
     * @param SystemNotificationChannelService $service Channel service.
     * @return JsonResponse Result of the operation.
     */
    public function index(SystemNotificationChannelService $service): JsonResponse
    {
        return response()->json(['data' => SystemNotificationChannel::query()->latest()->get()->map(fn (SystemNotificationChannel $channel) => $service->mask($channel))]);
    }

    /**
     * Handle the store operation.
     * @param SystemNotificationChannelRequest $request Parameter value.
     * @param SystemNotificationChannelService $service Channel service.
     * @param AuditService $audit Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function store(SystemNotificationChannelRequest $request, SystemNotificationChannelService $service, AuditService $audit): JsonResponse
    {
        $channel = $service->save($request->validated());
        $audit->record('system_notification_channel.created', 'system_notification_channel', $channel->id, null, [], ['type' => $channel->type, 'name' => $channel->name, 'status' => $channel->status]);
        return response()->json(['data' => $service->mask($channel)], 201);
    }

    /**
     * Return editable channel details (credentials blanked) for the superadmin console.
     *
     * @param SystemNotificationChannel $channel Notification channel.
     * @param SystemNotificationChannelService $service Channel service.
     * @return JsonResponse Editable channel data.
     */
    public function show(SystemNotificationChannel $channel, SystemNotificationChannelService $service): JsonResponse
    {
        return response()->json(['data' => $service->editable($channel)]);
    }

    /**
     * Handle the update operation.
     * @param SystemNotificationChannelRequest $request Parameter value.
     * @param SystemNotificationChannel $channel Parameter value.
     * @param SystemNotificationChannelService $service Channel service.
     * @param AuditService $audit Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function update(SystemNotificationChannelRequest $request, SystemNotificationChannel $channel, SystemNotificationChannelService $service, AuditService $audit): JsonResponse
    {
        $before = $channel->only(['type', 'name', 'status']);
        $channel = $service->save($request->validated(), $channel);
        $audit->record('system_notification_channel.updated', 'system_notification_channel', $channel->id, null, $before, $channel->only(array_keys($before)));
        return response()->json(['data' => $service->mask($channel)]);
    }

    /**
     * Send a test ping through a configured system notification channel, rate limited per channel.
     *
     * @param Request $request Incoming HTTP request.
     * @param SystemNotificationChannel $channel Notification channel.
     * @param SystemNotificationChannelService $service Channel service.
     * @param SystemNotificationChannelDispatcher $dispatcher Channel dispatcher.
     * @return JsonResponse Status response.
     */
    public function test(Request $request, SystemNotificationChannel $channel, SystemNotificationChannelService $service, SystemNotificationChannelDispatcher $dispatcher): JsonResponse
    {
        abort_unless($service->configured($channel), 422, __('admin.channel_not_configured'));

        $admin = $request->user('admin');
        $rateLimitKey = 'system-channel-test:'.$channel->id.':'.($admin?->id ?? $request->ip());
        $maxAttempts = 5;
        $decaySeconds = 60;

        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            return response()->json([
                'message' => __('admin.notification_channel_test_rate_limited', ['seconds' => $seconds]),
                'retry_after' => $seconds,
            ], 429);
        }

        RateLimiter::hit($rateLimitKey, $decaySeconds);

        try {
            $dispatcher->test($channel);
        } catch (\InvalidArgumentException) {
            // The stored destination is not a safe public HTTPS endpoint (e.g. saved before SSRF validation existed).
            return response()->json(['message' => __('admin.webhook_url_unsafe')], 422);
        }

        return response()->json(['data' => ['channel_id' => $channel->id, 'type' => $channel->type]]);
    }

    /**
     * Handle the destroy operation.
     * @param SystemNotificationChannel $channel Parameter value.
     * @param AuditService $audit Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function destroy(SystemNotificationChannel $channel, AuditService $audit): JsonResponse
    {
        $before = $channel->only(['type', 'name', 'status']);
        $channel->delete();
        $audit->record('system_notification_channel.deleted', 'system_notification_channel', $channel->id, null, $before);
        return response()->json(['data' => ['deleted' => true]]);
    }
}

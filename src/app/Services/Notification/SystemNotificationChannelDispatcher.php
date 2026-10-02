<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Models\SystemNotificationChannel;
use App\Services\Notification\Concerns\SendsNotificationChannelPayloads;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * Send a test ping through a configured system-level (superadmin) notification channel, reusing
 * the same driver wire protocol as room channels via SendsNotificationChannelPayloads.
 */
class SystemNotificationChannelDispatcher
{
    use SendsNotificationChannelPayloads;

    /**
     * Send a non-sensitive test payload through one configured system channel.
     *
     * @param SystemNotificationChannel $channel Configured channel.
     * @return void
     */
    public function test(SystemNotificationChannel $channel): void
    {
        $payload = [
            'event' => 'notification.test',
            'title' => __('messages.test_ping_title'),
            'room_name' => 'DrinkFlow System',
            'message' => __('messages.test_ping_body'),
        ];

        $this->send($channel->type, $this->config($channel), $payload);
    }

    /**
     * Deliver a payload to every enabled and correctly configured system channel.
     *
     * Used for platform alerts (a new Agent registration waiting for review, ops/security events).
     * A channel that is disabled, has no configuration or fails is skipped with a log entry: the
     * alert is already stored in the Superadmin inbox, so outbound delivery is best-effort.
     *
     * @param array<string, mixed> $payload Driver-neutral payload.
     * @return int Number of channels the payload was delivered to.
     */
    public function dispatch(array $payload): int
    {
        $delivered = 0;

        SystemNotificationChannel::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->each(function (SystemNotificationChannel $channel) use ($payload, &$delivered): void {
                try {
                    $config = $this->config($channel);
                    if ($config === []) {
                        return;
                    }
                    $this->send($channel->type, $config, $payload);
                    $delivered++;
                } catch (\Throwable $exception) {
                    Log::warning('System notification channel delivery failed.', [
                        'channel_id' => $channel->id,
                        'type' => $channel->type,
                        'event' => $payload['event'] ?? null,
                        'exception' => $exception::class,
                        'error' => $exception->getMessage(),
                    ]);
                }
            });

        return $delivered;
    }

    /**
     * Decrypt and decode a channel configuration without exposing it to callers.
     *
     * @param SystemNotificationChannel $channel Channel entity.
     * @return array<string, string> Channel configuration.
     */
    private function config(SystemNotificationChannel $channel): array
    {
        $encrypted = $channel->getRawOriginal('config_encrypted');
        if (! is_string($encrypted) || $encrypted === '') {
            return [];
        }
        $decrypted = Crypt::decrypt($encrypted);
        $decoded = json_decode(is_string($decrypted) ? $decrypted : '', true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? array_filter($decoded, 'is_string') : [];
    }
}

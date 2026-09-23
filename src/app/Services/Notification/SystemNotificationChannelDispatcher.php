<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Models\SystemNotificationChannel;
use App\Services\Notification\Concerns\SendsNotificationChannelPayloads;
use Illuminate\Support\Facades\Crypt;

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

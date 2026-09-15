<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Models\NotificationChannel;
use App\Models\Room;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RoomNotificationChannelDispatcher
{
    /**
     * Deliver a payload to every enabled and correctly configured room channel.
     *
     * @param Room $room Room owning the channels.
     * @param array<string, mixed> $payload Driver-neutral payload.
     * @return int Number of channel delivery attempts.
     */
    public function dispatch(Room $room, array $payload): int
    {
        $attempts = 0;
        $room->notificationChannels()->where('status', 'enabled')->each(function (NotificationChannel $channel) use ($payload, &$attempts): void {
            try {
                $config = $this->config($channel);
                if ($config === []) {
                    return;
                }
                $this->send($channel->type, $config, $payload);
                $attempts++;
            } catch (\Throwable $exception) {
                Log::warning('Room notification channel delivery failed.', ['channel_id' => $channel->id, 'type' => $channel->type, 'event' => $payload['event'] ?? null, 'exception' => $exception::class]);
            }
        });

        return $attempts;
    }

    /**
     * Send a non-sensitive test payload through one configured channel.
     *
     * @param NotificationChannel $channel Configured channel.
     * @return void
     */
    public function test(NotificationChannel $channel): void
    {
        $this->send($channel->type, $this->config($channel), [
            'event' => 'notification.test',
            'title' => 'DrinkFlow test notification',
            'message' => 'Kênh thông báo đã được kết nối thành công.',
        ]);
    }

    /**
     * Decrypt and decode a channel configuration without exposing it to callers.
     *
     * @param NotificationChannel $channel Channel entity.
     * @return array<string, string> Channel configuration.
     */
    private function config(NotificationChannel $channel): array
    {
        $encrypted = $channel->getRawOriginal('config_encrypted');
        if (! is_string($encrypted) || $encrypted === '') {
            return [];
        }
        $decrypted = Crypt::decrypt($encrypted);
        $decoded = json_decode(is_string($decrypted) ? $decrypted : '', true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? array_filter($decoded, 'is_string') : [];
    }

    /**
     * Send a payload using the concrete notification-channel driver protocol.
     *
     * @param string $type Driver type.
     * @param array<string, string> $config Driver configuration.
     * @param array<string, mixed> $payload Driver-neutral payload.
     * @return void
     */
    private function send(string $type, array $config, array $payload): void
    {
        $message = $this->formatMessage($payload);
        $deliveryPayload = array_merge($payload, [
            'icon' => $this->icon((string) ($payload['event'] ?? 'notification')),
            'message' => $message,
        ]);
        match ($type) {
            'slack' => Http::timeout(5)->post($this->required($config, 'webhook_url'), ['text' => $message])->throw(),
            'telegram' => Http::timeout(5)->post('https://api.telegram.org/bot'.$this->required($config, 'bot_token').'/sendMessage', ['chat_id' => $this->required($config, 'chat_id'), 'text' => $message])->throw(),
            'chatwork' => Http::timeout(5)->withHeaders(['X-ChatWorkToken' => $this->required($config, 'api_token')])->asForm()->post('https://api.chatwork.com/v2/rooms/'.$this->required($config, 'room_id').'/messages', ['body' => $message])->throw(),
            'webhook' => Http::timeout(5)->when(isset($config['secret_token']) && $config['secret_token'] !== '', fn ($request) => $request->withHeaders(['X-Webhook-Secret' => $config['secret_token']]))->post($this->required($config, 'webhook_url'), $deliveryPayload)->throw(),
            default => throw new \InvalidArgumentException('Unsupported notification channel type.'),
        };
    }

    /**
     * Format a notification consistently for chat-style channel drivers.
     *
     * @param array<string, mixed> $payload Driver-neutral notification payload.
     * @return string Readable notification text with an event icon and bullets.
     */
    private function formatMessage(array $payload): string
    {
        $event = (string) ($payload['event'] ?? 'notification');
        $title = trim((string) ($payload['title'] ?? 'DrinkFlow notification'));
        $rawMessage = trim((string) ($payload['message'] ?? ''));
        $lines = $rawMessage === '' ? [] : (preg_split('/\r\n|\r|\n/', $rawMessage) ?: []);

        // Campaign payloads already include the title as their first line.
        if ($lines !== [] && trim((string) $lines[0]) === $title) {
            array_shift($lines);
        }

        $details = array_values(array_filter(array_map(
            static fn (mixed $line): string => trim((string) $line),
            $lines,
        ), static fn (string $line): bool => $line !== ''));
        $formatted = $this->icon($event).' '.$title;
        if ($details !== []) {
            $formatted .= "\n\n".implode("\n", array_map(static fn (string $line): string => '• '.$line, $details));
        }

        return $formatted;
    }

    /**
     * Resolve a visual icon for a notification event.
     *
     * @param string $event Notification event name.
     * @return string Unicode icon.
     */
    private function icon(string $event): string
    {
        return match (true) {
            str_starts_with($event, 'campaign.') => $event === 'campaign.created' ? '🚀' : '📣',
            str_starts_with($event, 'order.') => '🧾',
            str_starts_with($event, 'debt.'), str_starts_with($event, 'payment.') => '💳',
            str_starts_with($event, 'user.') => '👤',
            str_starts_with($event, 'security.') => '🔐',
            $event === 'notification.test' => '✅',
            default => '🔔',
        };
    }

    /**
     * Return a required driver configuration value.
     *
     * @param array<string, string> $config Driver configuration.
     * @param string $key Required key.
     * @return string Configured value.
     */
    private function required(array $config, string $key): string
    {
        $value = $config[$key] ?? null;
        if (! is_string($value) || $value === '') {
            throw new \InvalidArgumentException('Notification channel configuration is incomplete.');
        }

        return $value;
    }
}

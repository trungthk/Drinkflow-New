<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Models\NotificationChannel;
use App\Models\Room;
use App\Support\Helpers\FormatHelper;
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
                Log::warning('Room notification channel delivery failed.', [
                    'channel_id' => $channel->id,
                    'type' => $channel->type,
                    'event' => $payload['event'] ?? null,
                    'exception' => $exception::class,
                    'error' => $exception->getMessage(),
                ]);
            }
        });

        return $attempts;
    }

    /**
     * Send a non-sensitive test payload through one configured channel.
     *
     * @param NotificationChannel $channel Configured channel.
     * @param string $template Template name (e.g., test_ping, campaign.created, campaign.closed, campaign.cancelled, debt.reminder).
     * @return void
     */
    public function test(NotificationChannel $channel, string $template = 'test_ping'): void
    {
        $payload = $this->buildTestPayload($template, $channel->room);
        $this->send($channel->type, $this->config($channel), $payload);
    }

    /**
     * Build realistic test payload for a given notification template.
     *
     * @param string $template Template identifier.
     * @param Room|null $room Associated room if available.
     * @return array<string, mixed> Driver-neutral notification payload.
     */
    public function buildTestPayload(string $template, ?Room $room = null): array
    {
        $roomName = $room ? $room->name : 'DrinkFlow Team';
        $roomSlug = $room ? $room->slug : 'general';

        return match ($template) {
            'campaign.created' => [
                'event' => 'campaign.created',
                'title' => __('messages.campaign_created_title'),
                'room_name' => $roomName,
                'campaign' => [
                    'id' => 999,
                    'name' => 'Trà Sữa Phê La (Mẫu thử)',
                    'restaurant' => 'Phê La Tea & Coffee',
                    'deadline' => now()->addMinutes(45)->toIso8601String(),
                    'sponsor_name' => 'Team Lead',
                    'sponsor_type' => 'fixed',
                    'sponsorship_amount' => 20000,
                    'max_product_budget' => 60000,
                    'order_url' => url("/rooms/{$roomSlug}/campaigns"),
                ],
                'message' => implode("\n", [
                    __('messages.campaign_created_title'),
                    __('messages.campaign_name', ['name' => 'Trà Sữa Phê La (Mẫu thử)']),
                    __('messages.campaign_restaurant', ['restaurant' => 'Phê La Tea & Coffee']),
                    __('messages.campaign_deadline', ['date' => FormatHelper::formatDateTime(now()->addMinutes(45), 'd/m/Y H:i')]),
                    __('messages.campaign_product_budget', ['amount' => FormatHelper::formatCurrency(60000)]),
                    __('messages.campaign_sponsorship', ['sponsor' => 'Team Lead', 'amount' => FormatHelper::formatCurrency(20000)]),
                    __('messages.campaign_order', ['url' => url("/rooms/{$roomSlug}/campaigns")]),
                ]),
            ],
            'campaign.closed' => [
                'event' => 'campaign.closed',
                'title' => __('messages.campaign_closed_title'),
                'room_name' => $roomName,
                'campaign' => [
                    'id' => 999,
                    'name' => 'Trà Sữa Phê La (Mẫu thử)',
                    'restaurant' => 'Phê La Tea & Coffee',
                ],
                'message' => implode("\n", [
                    __('messages.campaign_closed_title'),
                    __('messages.campaign_name', ['name' => 'Trà Sữa Phê La (Mẫu thử)']),
                    __('messages.campaign_restaurant', ['restaurant' => 'Phê La Tea & Coffee']),
                    __('messages.campaign_closed_body'),
                ]),
            ],
            'campaign.cancelled' => [
                'event' => 'campaign.cancelled',
                'title' => __('messages.campaign_cancelled_title'),
                'room_name' => $roomName,
                'campaign' => [
                    'id' => 999,
                    'name' => 'Trà Sữa Phê La (Mẫu thử)',
                    'restaurant' => 'Phê La Tea & Coffee',
                ],
                'message' => implode("\n", [
                    __('messages.campaign_cancelled_title'),
                    __('messages.campaign_name', ['name' => 'Trà Sữa Phê La (Mẫu thử)']),
                    __('messages.campaign_restaurant', ['restaurant' => 'Phê La Tea & Coffee']),
                    __('messages.campaign_cancelled_body'),
                ]),
            ],
            'debt.reminder' => [
                'event' => 'debt.reminder',
                'title' => __('messages.debt_channel_reminder_title'),
                'room_name' => $roomName,
                'message' => __('admin.debt_channel_reminder_body', [
                    'count' => 3,
                    'amount' => FormatHelper::formatCurrency(95000),
                ]),
            ],
            default => [
                'event' => 'notification.test',
                'title' => __('messages.test_ping_title'),
                'room_name' => $roomName,
                'message' => __('messages.test_ping_body'),
            ],
        };
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
        match ($type) {
            'slack' => Http::timeout(5)->post($this->required($config, 'webhook_url'), [
                'text' => $this->formatSlackMessage($payload),
            ])->throw(),
            'telegram' => Http::timeout(5)->post('https://api.telegram.org/bot'.$this->required($config, 'bot_token').'/sendMessage', [
                'chat_id' => $this->required($config, 'chat_id'),
                'text' => $this->formatTelegramMessage($payload),
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => false,
            ])->throw(),
            'chatwork' => Http::timeout(5)->withHeaders(['X-ChatWorkToken' => $this->required($config, 'api_token')])->asForm()->post('https://api.chatwork.com/v2/rooms/'.$this->required($config, 'room_id').'/messages', [
                'body' => $this->formatChatworkMessage($payload),
            ])->throw(),
            'webhook' => Http::timeout(5)->when(isset($config['secret_token']) && $config['secret_token'] !== '', fn ($request) => $request->withHeaders(['X-Webhook-Secret' => $config['secret_token']]))->post($this->required($config, 'webhook_url'), $this->formatWebhookPayload($payload))->throw(),
            default => throw new \InvalidArgumentException('Unsupported notification channel type.'),
        };
    }

    /**
     * Format notification for Telegram with rich HTML styling.
     *
     * @param array<string, mixed> $payload Notification payload.
     * @return string Telegram HTML message.
     */
    public function formatTelegramMessage(array $payload): string
    {
        $event = (string) ($payload['event'] ?? 'notification');
        $title = trim((string) ($payload['title'] ?? 'DrinkFlow Notification'));
        $icon = $this->icon($event);
        $details = $this->extractDetails($payload, $title);

        $escapedTitle = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $lines = ["<b>{$icon} [DrinkFlow] {$escapedTitle}</b>"];
        $lines[] = '━━━━━━━━━━━━━━━━━━━━';

        $orderUrl = null;
        foreach ($details as $line) {
            if (preg_match('/^(?:Đặt món|Order|注文):\s*(https?:\/\/\S+)/iu', $line, $matches)) {
                $orderUrl = $matches[1];
                continue;
            }

            if (str_contains($line, ':')) {
                [$label, $val] = explode(':', $line, 2);
                $escapedLabel = htmlspecialchars(trim($label), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $escapedVal = htmlspecialchars(trim($val), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $lineIcon = $this->fieldIcon(trim($label));
                $lines[] = "{$lineIcon} <b>{$escapedLabel}:</b> {$escapedVal}";
            } else {
                $escapedLine = htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $lines[] = "• {$escapedLine}";
            }
        }

        if ($orderUrl !== null) {
            $orderNowText = htmlspecialchars(__('messages.campaign_order_now'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $lines[] = '━━━━━━━━━━━━━━━━━━━━';
            $lines[] = "👉 <a href=\"{$orderUrl}\"><b>{$orderNowText}</b></a>";
        }

        $lines[] = '<i>⚡ DrinkFlow Notification</i>';

        return implode("\n", $lines);
    }

    /**
     * Format notification for Slack with mrkdwn styling.
     *
     * @param array<string, mixed> $payload Notification payload.
     * @return string Slack mrkdwn text.
     */
    public function formatSlackMessage(array $payload): string
    {
        $event = (string) ($payload['event'] ?? 'notification');
        $title = trim((string) ($payload['title'] ?? 'DrinkFlow Notification'));
        $icon = $this->icon($event);
        $details = $this->extractDetails($payload, $title);

        $lines = ["*{$icon} [DrinkFlow] {$title}*"];
        $lines[] = '────────────────────────────';

        $orderUrl = null;
        foreach ($details as $line) {
            if (preg_match('/^(?:Đặt món|Order|注文):\s*(https?:\/\/\S+)/iu', $line, $matches)) {
                $orderUrl = $matches[1];
                continue;
            }

            if (str_contains($line, ':')) {
                [$label, $val] = explode(':', $line, 2);
                $lineIcon = $this->fieldIcon(trim($label));
                $lines[] = "{$lineIcon} *".trim($label).":* ".trim($val);
            } else {
                $lines[] = "• {$line}";
            }
        }

        if ($orderUrl !== null) {
            $orderNowText = __('messages.campaign_order_now');
            $lines[] = '────────────────────────────';
            $lines[] = "👉 <{$orderUrl}|*{$orderNowText}*>";
        }

        $lines[] = '_DrinkFlow Notification_';

        return implode("\n", $lines);
    }

    /**
     * Format notification for ChatWork with tags.
     *
     * @param array<string, mixed> $payload Notification payload.
     * @return string ChatWork BBCode text.
     */
    public function formatChatworkMessage(array $payload): string
    {
        $event = (string) ($payload['event'] ?? 'notification');
        $title = trim((string) ($payload['title'] ?? 'DrinkFlow Notification'));
        $icon = $this->icon($event);
        $details = $this->extractDetails($payload, $title);

        $header = "[info][title]{$icon} [DrinkFlow] {$title}[/title]";
        $bodyLines = [];
        $orderUrl = null;

        foreach ($details as $line) {
            if (preg_match('/^(?:Đặt món|Order|注文):\s*(https?:\/\/\S+)/iu', $line, $matches)) {
                $orderUrl = $matches[1];
                continue;
            }

            if (str_contains($line, ':')) {
                [$label, $val] = explode(':', $line, 2);
                $lineIcon = $this->fieldIcon(trim($label));
                $bodyLines[] = "{$lineIcon} ".trim($label).": ".trim($val);
            } else {
                $bodyLines[] = "• {$line}";
            }
        }

        if ($orderUrl !== null) {
            $orderNowText = __('messages.campaign_order_now');
            $bodyLines[] = "[hr]👉 {$orderNowText}: {$orderUrl}";
        }

        return $header.implode("\n", $bodyLines).'[/info]';
    }

    /**
     * Format structured webhook payload.
     *
     * @param array<string, mixed> $payload Notification payload.
     * @return array<string, mixed> Webhook JSON data.
     */
    public function formatWebhookPayload(array $payload): array
    {
        $event = (string) ($payload['event'] ?? 'notification');
        $plainMessage = $this->formatMessage($payload);

        return array_merge($payload, [
            'icon' => $this->icon($event),
            'message' => $plainMessage,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Extract clean detail lines from a raw payload message.
     *
     * @param array<string, mixed> $payload Notification payload.
     * @param string $title Title to strip from lines.
     * @return array<int, string> Filtered detail lines.
     */
    private function extractDetails(array $payload, string $title): array
    {
        $rawMessage = trim((string) ($payload['message'] ?? ''));
        $lines = $rawMessage === '' ? [] : (preg_split('/\r\n|\r|\n/', $rawMessage) ?: []);

        if ($lines !== [] && trim((string) $lines[0]) === $title) {
            array_shift($lines);
        }

        return array_values(array_filter(array_map(
            static fn (mixed $line): string => trim((string) $line),
            $lines,
        ), static fn (string $line): bool => $line !== ''));
    }

    /**
     * Format fallback plain message.
     *
     * @param array<string, mixed> $payload Driver-neutral notification payload.
     * @return string Readable notification text.
     */
    public function formatMessage(array $payload): string
    {
        $event = (string) ($payload['event'] ?? 'notification');
        $title = trim((string) ($payload['title'] ?? 'DrinkFlow notification'));
        $details = $this->extractDetails($payload, $title);

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
            $event === 'campaign.created' => '🚀',
            $event === 'campaign.closed' => '🔒',
            $event === 'campaign.cancelled' => '🚫',
            str_starts_with($event, 'campaign.') => '📣',
            str_starts_with($event, 'order.') => '🧾',
            str_starts_with($event, 'debt.'), str_starts_with($event, 'payment.') => '💳',
            str_starts_with($event, 'user.') => '👤',
            str_starts_with($event, 'security.') => '🔐',
            $event === 'notification.test' => '🧪',
            default => '🔔',
        };
    }

    /**
     * Resolve icon for standard field labels.
     *
     * @param string $label Field label.
     * @return string Emoj/Symbol.
     */
    private function fieldIcon(string $label): string
    {
        $lower = mb_strtolower($label);
        return match (true) {
            str_contains($lower, 'tên') || str_contains($lower, 'name') || str_contains($lower, '名前') => '🧋',
            str_contains($lower, 'quán') || str_contains($lower, 'thương hiệu') || str_contains($lower, 'store') || str_contains($lower, 'restaurant') || str_contains($lower, '店舗') => '🏬',
            str_contains($lower, 'hạn') || str_contains($lower, 'thời gian') || str_contains($lower, 'deadline') || str_contains($lower, '締切') => '⏰',
            str_contains($lower, 'tài trợ') || str_contains($lower, 'sponsor') || str_contains($lower, 'スポンサー') => '🎁',
            str_contains($lower, 'trần') || str_contains($lower, 'giá') || str_contains($lower, 'budget') || str_contains($lower, 'amount') || str_contains($lower, 'tiền') || str_contains($lower, '価格') => '💰',
            default => '•',
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

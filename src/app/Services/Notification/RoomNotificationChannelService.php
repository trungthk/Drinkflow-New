<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Models\NotificationChannel;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class RoomNotificationChannelService
{
    /**
     * Save or update a notification channel entity.
     *
     * @param int $roomId Room identifier.
     * @param array<string, mixed> $data Channel settings and config.
     * @param ?NotificationChannel $channel Existing channel entity if updating.
     * @return NotificationChannel Saved notification channel.
     */
    public function save(int $roomId, array $data, ?NotificationChannel $channel = null): NotificationChannel
    {
        return DB::transaction(function () use ($roomId, $data, $channel): NotificationChannel {
            $channel ??= new NotificationChannel();
            $previousType = $channel->exists ? (string) $channel->type : null;
            $channel->room_id = $roomId;
            $channel->type = $data['type'];
            $channel->name = $data['name'];
            $channel->status = $data['status'] ?? $channel->status ?? 'disabled';
            if (array_key_exists('config', $data)) {
                $config = $data['config'];
                if ($channel->exists && $previousType === $channel->type) {
                    $encrypted = $channel->getRawOriginal('config_encrypted');
                    if (is_string($encrypted) && $encrypted !== '') {
                        $existing = json_decode((string) Crypt::decrypt($encrypted), true, 512, JSON_THROW_ON_ERROR);
                        if (is_array($existing)) {
                            $config = array_replace($existing, $config);
                        }
                    }
                }
                $channel->config_encrypted = json_encode($config, JSON_THROW_ON_ERROR);
            }
            $channel->save();
            return $channel->fresh();
        });
    }

    /**
     * Determine if a channel has credentials configured.
     *
     * @param NotificationChannel $channel Channel entity.
     * @return bool
     */
    public function configured(NotificationChannel $channel): bool
    {
        return (bool) $channel->getRawOriginal('config_encrypted');
    }

    /**
     * Disable a notification channel and write audit log.
     *
     * @param NotificationChannel $channel Channel entity.
     * @param AuditService $audit Audit logger.
     * @return void
     */
    public function disable(NotificationChannel $channel, AuditService $audit): void
    {
        $channel->update(['status' => 'disabled']);
        $audit->record('notification_channel.disabled', 'notification_channel', $channel->id, $channel->room_id, ['status' => 'enabled'], ['status' => 'disabled']);
    }

    /**
     * Mask sensitive credentials for safe public response.
     *
     * @param NotificationChannel $channel Channel entity.
     * @return array<string, mixed> Masked payload.
     */
    public function mask(NotificationChannel $channel): array
    {
        return [
            'id' => $channel->id,
            'type' => $channel->type,
            'name' => $channel->name,
            'status' => $channel->status,
            'configured' => (bool) $channel->getRawOriginal('config_encrypted'),
            'credential' => $channel->getRawOriginal('config_encrypted') ? '••••••••••' : null,
        ];
    }

    /**
     * Return channel details for an authorized room administrator editing a channel.
     *
     * @param NotificationChannel $channel Notification channel.
     * @return array<string, mixed> Channel details with decrypted configuration.
     */
    public function editable(NotificationChannel $channel): array
    {
        $encrypted = $channel->getRawOriginal('config_encrypted');
        $config = [];

        if (is_string($encrypted) && $encrypted !== '') {
            $decoded = json_decode((string) Crypt::decrypt($encrypted), true, 512, JSON_THROW_ON_ERROR);
            $config = is_array($decoded) ? $decoded : [];
        }

        return [
            'id' => $channel->id,
            'type' => $channel->type,
            'name' => $channel->name,
            'status' => $channel->status,
            'config' => $config,
        ];
    }
}

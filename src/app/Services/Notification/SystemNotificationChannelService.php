<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Models\SystemNotificationChannel;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Persist and mask system-level (superadmin) notification channel credentials, mirroring
 * RoomNotificationChannelService's rules for the platform-wide alert channels used by ops/security
 * events instead of per-room campaign notifications.
 */
class SystemNotificationChannelService
{
    /**
     * Configuration keys holding credentials; their stored values are never sent back to the browser.
     *
     * @var array<int, string>
     */
    private const SECRET_KEYS = ['bot_token', 'api_token', 'secret_token', 'webhook_url'];

    /**
     * Save or update a system notification channel entity.
     *
     * @param array<string, mixed> $data Channel settings and config.
     * @param ?SystemNotificationChannel $channel Existing channel entity if updating.
     * @return SystemNotificationChannel Saved notification channel.
     */
    public function save(array $data, ?SystemNotificationChannel $channel = null): SystemNotificationChannel
    {
        return DB::transaction(function () use ($data, $channel): SystemNotificationChannel {
            $channel ??= new SystemNotificationChannel();
            $previousType = $channel->exists ? (string) $channel->type : null;
            $channel->type = $data['type'];
            $channel->name = $data['name'];
            $channel->status = $data['status'] ?? $channel->status ?? 'active';
            if (array_key_exists('config', $data)) {
                $config = $data['config'];
                if ($channel->exists && $previousType === $channel->type) {
                    $encrypted = $channel->getRawOriginal('config_encrypted');
                    if (is_string($encrypted) && $encrypted !== '') {
                        $existing = json_decode((string) Crypt::decrypt($encrypted), true, 512, JSON_THROW_ON_ERROR);
                        if (is_array($existing)) {
                            // A blank credential means "keep the stored one"; secrets are never echoed to the editor.
                            foreach (self::SECRET_KEYS as $secretKey) {
                                if (array_key_exists($secretKey, $config) && trim((string) $config[$secretKey]) === '') {
                                    unset($config[$secretKey]);
                                }
                            }
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
     * @param SystemNotificationChannel $channel Channel entity.
     * @return bool
     */
    public function configured(SystemNotificationChannel $channel): bool
    {
        return (bool) $channel->getRawOriginal('config_encrypted');
    }

    /**
     * Mask sensitive credentials for safe public response.
     *
     * @param SystemNotificationChannel $channel Channel entity.
     * @return array<string, mixed> Masked payload.
     */
    public function mask(SystemNotificationChannel $channel): array
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
     * Return channel details for an authorized superadmin editing a channel, with credentials blanked out.
     *
     * @param SystemNotificationChannel $channel Notification channel.
     * @return array<string, mixed> Channel details; credential values are blank and listed in `secrets_configured`.
     */
    public function editable(SystemNotificationChannel $channel): array
    {
        $encrypted = $channel->getRawOriginal('config_encrypted');
        $config = [];

        if (is_string($encrypted) && $encrypted !== '') {
            $decoded = json_decode((string) Crypt::decrypt($encrypted), true, 512, JSON_THROW_ON_ERROR);
            $config = is_array($decoded) ? $decoded : [];
        }

        $secretsConfigured = array_values(array_filter(
            self::SECRET_KEYS,
            static fn (string $key): bool => isset($config[$key]) && $config[$key] !== '',
        ));
        foreach ($secretsConfigured as $key) {
            $config[$key] = '';
        }

        return [
            'id' => $channel->id,
            'type' => $channel->type,
            'name' => $channel->name,
            'status' => $channel->status,
            'config' => $config,
            'secrets_configured' => $secretsConfigured,
        ];
    }
}

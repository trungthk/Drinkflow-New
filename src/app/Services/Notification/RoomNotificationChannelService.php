<?php

namespace App\Services\Notification;

use App\Models\NotificationChannel;
use Illuminate\Support\Facades\DB;

class RoomNotificationChannelService
{
    /**
     * Handle the save operation.
     * @param int $roomId Parameter value.
     * @param array $data Parameter value.
     * @param ?NotificationChannel $channel Parameter value.
     * @return NotificationChannel Result of the operation.
     */
    public function save(int $roomId, array $data, ?NotificationChannel $channel = null): NotificationChannel
    {
        return DB::transaction(function () use ($roomId, $data, $channel): NotificationChannel {
            $channel ??= new NotificationChannel();
            $channel->room_id = $roomId;
            $channel->type = $data['type'];
            $channel->name = $data['name'];
            $channel->status = $data['status'] ?? $channel->status ?? 'disabled';
            if (array_key_exists('config', $data)) {
                $channel->config_encrypted = json_encode($data['config'], JSON_THROW_ON_ERROR);
            }
            $channel->save();
            return $channel->fresh();
        });
    }

    /**
     * Handle the configured operation.
     * @param NotificationChannel $channel Parameter value.
     * @return bool Result of the operation.
     */
    public function configured(NotificationChannel $channel): bool
    {
        return (bool) $channel->getRawOriginal('config_encrypted');
    }
}

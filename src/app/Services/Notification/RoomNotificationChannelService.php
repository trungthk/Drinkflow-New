<?php

namespace App\Services\Notification;

use App\Models\NotificationChannel;
use Illuminate\Support\Facades\DB;

class RoomNotificationChannelService
{
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

    public function configured(NotificationChannel $channel): bool
    {
        return (bool) $channel->getRawOriginal('config_encrypted');
    }
}

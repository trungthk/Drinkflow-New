<?php
namespace App\Services\Auth;

use App\Models\RoomUser;
use App\Models\RoomUserDevice;
use Illuminate\Support\Str;

class DeviceTrustService
{
    public function issue(RoomUser $roomUser, string $deviceUuid): string
    {
        $token = Str::random(64);
        RoomUserDevice::updateOrCreate(
            ['room_user_id' => $roomUser->id, 'device_uuid' => $deviceUuid],
            ['token_hash' => $this->hash($token), 'verified_at' => now(), 'last_seen_at' => now(), 'revoked_at' => null],
        );
        return $token;
    }

    public function resolve(string $deviceUuid, string $token, ?int $roomId = null): ?RoomUserDevice
    {
        if ($deviceUuid === '' || $token === '') return null;
        $query = RoomUserDevice::query()->with('roomUser.globalUser')->where('device_uuid', $deviceUuid)->whereNull('revoked_at');
        if ($roomId !== null) $query->whereHas('roomUser', fn ($q) => $q->where('room_id', $roomId));
        $device = $query->latest('id')->get()->first(fn (RoomUserDevice $device) => hash_equals($device->token_hash, $this->hash($token)));
        if ($device) $device->update(['last_seen_at' => now()]);
        return $device;
    }

    public function revoke(RoomUserDevice $device): void { $device->update(['revoked_at' => now()]); }

    private function hash(string $token): string { return hash('sha256', $token); }
}

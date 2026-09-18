<?php

declare(strict_types=1);

namespace App\Services\Realtime;

use App\Enums\AdminRole;
use App\Enums\GlobalUserStatus;
use App\Enums\RoomStatus;
use App\Enums\RoomUserStatus;
use App\Models\AdminAccount;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Models\RoomUser;
use Illuminate\Support\Str;

class SocketTokenService
{
    /**
     * Issue a realtime socket authentication token for a room user.
     *
     * @param  RoomUser  $roomUser  The room user entity requesting the token.
     * @param  int  $ttlSeconds  Time-to-live for the token in seconds.
     * @return string Signed HMAC token string.
     */
    public function issue(RoomUser $roomUser, int $ttlSeconds = 300): string
    {
        abort_unless(
            $roomUser->globalUser?->status === GlobalUserStatus::Active
            && $roomUser->status === RoomUserStatus::Active,
            403
        );

        $payload = [
            'actor_type' => 'user',
            'global_user_id' => $roomUser->global_user_id,
            'room_user_id' => $roomUser->id,
            'room_id' => $roomUser->room_id,
            'exp' => now()->addSeconds($ttlSeconds)->timestamp,
            'jti' => (string) Str::uuid(),
        ];
        $encoded = $this->encode($payload);

        return $encoded . '.' . hash_hmac('sha256', $encoded, $this->signingSecret());
    }

    /** Issue a realtime token scoped to one trusted user device. */
    public function issueForDevice(GlobalUser $user, string $deviceUuid, int $ttlSeconds = 300): string
    {
        abort_unless($user->status === GlobalUserStatus::Active && preg_match('/^[A-Za-z0-9-]{1,128}$/', $deviceUuid) === 1, 403);

        $payload = [
            'actor_type' => 'user',
            'global_user_id' => $user->id,
            'device_uuid' => $deviceUuid,
            'exp' => now()->addSeconds($ttlSeconds)->timestamp,
            'jti' => (string) Str::uuid(),
        ];
        $encoded = $this->encode($payload);

        return $encoded . '.' . hash_hmac('sha256', $encoded, $this->signingSecret());
    }

    /**
     * Issue a realtime socket authentication token for an admin account.
     *
     * @param  AdminAccount  $admin  The admin account instance.
     * @param  Room|null  $room  Optional specific room context.
     * @param  int  $ttlSeconds  Time-to-live for the token in seconds.
     * @return string Signed HMAC token string.
     */
    public function issueForAdmin(AdminAccount $admin, ?Room $room = null, int $ttlSeconds = 300): string
    {
        abort_unless($admin->isActive(), 403);

        $roomIds = $admin->isSuperadmin()
            ? Room::query()->where('status', RoomStatus::Active->value)->pluck('id')->all()
            : ($room ? [$room->id] : []);

        if (!$admin->isSuperadmin() && (!$room || !$admin->rooms()->whereKey($room->id)->exists())) {
            abort(403);
        }

        $payload = [
            'actor_type' => $admin->isSuperadmin() ? AdminRole::SuperAdmin->value : AdminRole::Admin->value,
            'admin_id' => $admin->id,
            'room_ids' => $roomIds,
            'exp' => now()->addSeconds($ttlSeconds)->timestamp,
            'jti' => (string) Str::uuid(),
        ];
        $encoded = $this->encode($payload);

        return $encoded . '.' . hash_hmac('sha256', $encoded, $this->signingSecret());
    }

    /**
     * Verify and decode a given realtime socket token.
     *
     * @param  string  $token  The raw token string.
     * @return array<string, mixed>|null Decoded payload array or null if invalid/expired.
     */
    public function verify(string $token): ?array
    {
        [$encoded, $signature] = array_pad(explode('.', $token, 2), 2, '');

        if ($encoded === '' || $signature === '' || !hash_equals(hash_hmac('sha256', $encoded, $this->signingSecret()), $signature)) {
            return null;
        }

        $payload = json_decode((string) base64_decode(strtr($encoded, '-_', '+/')), true);

        return is_array($payload) && ($payload['actor_type'] ?? null) === 'user' && ($payload['exp'] ?? 0) >= now()->timestamp ? $payload : null;
    }

    /**
     * Encode payload data into base64url format.
     *
     * @param  array<string, mixed>  $payload  The data payload to encode.
     * @return string Base64url encoded string.
     */
    private function encode(array $payload): string
    {
        return rtrim(strtr(base64_encode((string) json_encode($payload, JSON_UNESCAPED_SLASHES)), '+/', '-_'), '=');
    }

    /**
     * Resolve the shared HMAC secret used by Laravel and the realtime gateway.
     *
     * @return string Secret used to sign and verify socket tokens.
     */
    private function signingSecret(): string
    {
        $configured = trim((string) config('services.realtime.socket_token_secret', ''));

        return $configured !== '' ? $configured : (string) config('app.key');
    }
}

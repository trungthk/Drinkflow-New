<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Ask every open page of one user (or one trusted device) to reload, so the server re-checks access
 * right away instead of on the next request — e.g. after a trusted device is revoked or the account is deleted.
 *
 * Published as the private realtime event `session.force_reload` by App\Listeners\PublishRealtimeEvent.
 */
class ForceReloadRequested
{
    use Dispatchable;

    public const REASON_DEVICE_REVOKED = 'device_revoked';

    public const REASON_ACCOUNT_DELETED = 'account_deleted';

    /**
     * @param string $channel Target realtime channel: `device:{uuid}` or `global_user:{id}`.
     * @param string $reason One of the REASON_* constants, sent to the client for logging only.
     */
    public function __construct(public readonly string $channel, public readonly string $reason)
    {
    }

    /**
     * Target every page opened on one trusted device.
     *
     * @param string $deviceUuid Device UUID (drinkflow_device_uuid cookie value).
     * @param string $reason One of the REASON_* constants.
     */
    public static function forDevice(string $deviceUuid, string $reason): self
    {
        return new self('device:'.$deviceUuid, $reason);
    }

    /**
     * Target every page and device of one global user.
     *
     * @param int $globalUserId Global user id.
     * @param string $reason One of the REASON_* constants.
     */
    public static function forGlobalUser(int $globalUserId, string $reason): self
    {
        return new self('global_user:'.$globalUserId, $reason);
    }
}

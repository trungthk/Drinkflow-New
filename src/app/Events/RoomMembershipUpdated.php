<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\RoomUser;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoomMembershipUpdated
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a room membership update event.
     *
     * @param RoomUser $roomUser Updated room membership.
     * @return void
     */
    public function __construct(public readonly RoomUser $roomUser)
    {
    }
}

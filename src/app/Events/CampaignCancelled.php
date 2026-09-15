<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Campaign;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CampaignCancelled
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a cancelled campaign event.
     *
     * @param Campaign $campaign Cancelled campaign with its room loaded.
     */
    public function __construct(public readonly Campaign $campaign)
    {
    }
}

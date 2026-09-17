<?php

declare(strict_types=1);

namespace App\Enums;

enum CampaignStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Active = 'active';
    case Closing = 'closing';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
    case Archived = 'archived';

    /**
     * Get the translated label for this campaign status.
     *
     * @return string
     */
    public function label(): string
    {
        return __('admin.status_' . $this->value);
    }
}

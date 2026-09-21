<?php

declare(strict_types=1);

namespace App\Enums;

enum FeedbackStatus: string
{
    /** Approved by a superadmin; shown on /me/feedback when the rating is high enough. */
    case Active = 'active';

    /** Submitted but not approved yet (the default for every new feedback). */
    case Inactive = 'inactive';
}

<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Submitted = 'submitted';
    case Confirmed = 'confirmed';
    case Ordering = 'ordering';
    case Ordered = 'ordered';
    case Delivering = 'delivering';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function isActive(): bool
    {
        return in_array($this, [self::Submitted, self::Confirmed, self::Ordering, self::Ordered, self::Delivering], true);
    }
}

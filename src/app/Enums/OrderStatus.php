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

    /**
     * Return order statuses that are still active.
     *
     * @return array<string> Active order status values.
     */
    public static function activeValues(): array
    {
        return array_map(
            static fn (self $status): string => $status->value,
            [self::Submitted, self::Confirmed, self::Ordering, self::Ordered, self::Delivering]
        );
    }
}

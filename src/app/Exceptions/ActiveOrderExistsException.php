<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Validation\ValidationException;

/**
 * A member already has an active order in the campaign (one active order per member per campaign).
 *
 * Raised by CreateOrderAction before inserting; the database index on (campaign_id, active_order_key)
 * is the last line of defence against concurrent requests.
 */
class ActiveOrderExistsException extends ValidationException
{
    /** Error code returned to the client. */
    public const CODE = 'active_order_exists';

    /** Unique index guarding the rule on MySQL (see migration 2026_09_30_200000). */
    public const MYSQL_INDEX = 'orders_one_active_per_campaign_member';

    /** Partial unique index guarding the rule on SQLite/PostgreSQL. */
    public const PARTIAL_INDEX = 'orders_one_active_per_user_campaign';

    /**
     * Build the exception with the translated message.
     *
     * @return self Exception.
     */
    public static function make(): self
    {
        return self::withMessages(['order' => __('global.orders.active_order_exists')]);
    }

    /**
     * Whether a database error is the violation of the one-active-order rule.
     *
     * @param \Throwable $exception Database exception.
     * @return bool True for the active-order unique indexes.
     */
    public static function isViolation(\Throwable $exception): bool
    {
        $message = $exception->getMessage();

        return str_contains($message, self::MYSQL_INDEX)
            || str_contains($message, self::PARTIAL_INDEX)
            || str_contains($message, 'UNIQUE constraint failed: orders.campaign_id, orders.room_user_id');
    }
}

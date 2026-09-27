<?php

declare(strict_types=1);

namespace App\Support\Helpers;

use Carbon\CarbonImmutable;

class DateRangeHelper
{
    /** Default window, in days (today included), used by admin report / audit date filters. */
    public const DEFAULT_DAYS = 7;

    /**
     * Inclusive date range covering the last N days, today included — the same range as the
     * "7 ngày qua" preset of the admin date-range picker.
     *
     * @param int $days Number of days, today included.
     * @return array{0: string, 1: string} Start and end dates as Y-m-d.
     */
    public static function lastDays(int $days = self::DEFAULT_DAYS): array
    {
        $today = CarbonImmutable::today();

        return [$today->subDays(max(1, $days) - 1)->toDateString(), $today->toDateString()];
    }
}

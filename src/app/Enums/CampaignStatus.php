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
     * Statuses of a campaign that is still running: a room may only have one at a time.
     *
     * @return list<self>
     */
    public static function running(): array
    {
        return [self::Active, self::Closing];
    }

    /**
     * Whether this status counts as a running campaign.
     *
     * @return bool
     */
    public function isRunning(): bool
    {
        return in_array($this, self::running(), true);
    }

    /**
     * Get the translated label for this campaign status.
     *
     * @param bool $isExpired Whether an active campaign has passed its deadline.
     * @return string
     */
    public function label(bool $isExpired = false): string
    {
        if ($isExpired && $this === self::Active) {
            return __('admin.status_expired');
        }

        return match ($this) {
            self::Draft => __('admin.status_draft'),
            self::Scheduled => __('admin.status_scheduled'),
            self::Active => __('admin.filter_active'),
            self::Closing => __('admin.status_closing'),
            self::Closed => __('admin.status_closed'),
            self::Cancelled => __('admin.status_cancelled'),
            self::Archived => __('admin.status_archived'),
        };
    }

    /**
     * Get the Tailwind CSS badge styling classes for this campaign status.
     *
     * Every status (and the expired state) uses its own hue; the running status is filled solid
     * so it stands out from the finished ones in lists.
     *
     * @param bool $isExpired Whether an active campaign has passed its deadline.
     * @return string
     */
    public function badgeClass(bool $isExpired = false): string
    {
        if ($isExpired && $this === self::Active) {
            return 'bg-orange-100 text-orange-800 border-orange-300 dark:bg-orange-950/60 dark:text-orange-200 dark:border-orange-700';
        }

        return match ($this) {
            self::Active => 'bg-emerald-600 text-white border-emerald-700 shadow-xs dark:bg-emerald-500 dark:text-emerald-950 dark:border-emerald-400',
            self::Closing => 'bg-amber-100 text-amber-800 border-amber-300 animate-pulse dark:bg-amber-950/60 dark:text-amber-200 dark:border-amber-700',
            self::Scheduled => 'bg-sky-100 text-sky-800 border-sky-300 dark:bg-sky-950/60 dark:text-sky-200 dark:border-sky-700',
            self::Draft => 'bg-violet-100 text-violet-800 border-dashed border-violet-300 dark:bg-violet-950/60 dark:text-violet-200 dark:border-violet-700',
            self::Closed => 'bg-indigo-100 text-indigo-800 border-indigo-300 dark:bg-indigo-950/60 dark:text-indigo-200 dark:border-indigo-700',
            self::Cancelled => 'bg-rose-100 text-rose-800 border-rose-300 dark:bg-rose-950/60 dark:text-rose-200 dark:border-rose-700',
            self::Archived => 'bg-zinc-100 text-zinc-600 border-dashed border-zinc-300 dark:bg-zinc-800/60 dark:text-zinc-300 dark:border-zinc-600',
        };
    }

    /**
     * Get the Material Symbols icon name shown inside the status badge.
     *
     * @param bool $isExpired Whether an active campaign has passed its deadline.
     * @return string
     */
    public function icon(bool $isExpired = false): string
    {
        if ($isExpired && $this === self::Active) {
            return 'timer_off';
        }

        return match ($this) {
            self::Active => 'play_circle',
            self::Closing => 'hourglass_top',
            self::Scheduled => 'schedule',
            self::Draft => 'edit_note',
            self::Closed => 'task_alt',
            self::Cancelled => 'block',
            self::Archived => 'inventory_2',
        };
    }
}

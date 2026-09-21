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
     * @param bool $isExpired Whether an active campaign has passed its deadline.
     * @return string
     */
    public function badgeClass(bool $isExpired = false): string
    {
        if ($isExpired && $this === self::Active) {
            return 'bg-orange-50 text-orange-700 border-orange-200 dark:bg-orange-950/40 dark:text-orange-300 dark:border-orange-800';
        }

        return match ($this) {
            self::Active => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800',
            self::Closing => 'bg-amber-50 text-amber-700 border-amber-200 animate-pulse dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800',
            self::Scheduled => 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-800',
            self::Draft => 'bg-violet-50 text-violet-700 border-violet-200 dark:bg-violet-950/40 dark:text-violet-300 dark:border-violet-800',
            self::Closed => 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800/60 dark:text-slate-300 dark:border-slate-600',
            self::Cancelled => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800',
            self::Archived => 'bg-zinc-50 text-zinc-500 border-dashed border-zinc-300 dark:bg-zinc-900/40 dark:text-zinc-400 dark:border-zinc-600',
        };
    }

    /**
     * Get the Tailwind CSS dot indicator classes for this campaign status.
     *
     * @param bool $isExpired Whether an active campaign has passed its deadline.
     * @return string
     */
    public function dotClass(bool $isExpired = false): string
    {
        if ($isExpired && $this === self::Active) {
            return 'bg-orange-500';
        }

        return match ($this) {
            self::Active => 'bg-emerald-500 status-dot-pulse',
            self::Closing => 'bg-amber-500 status-dot-pulse',
            self::Scheduled => 'bg-sky-500',
            self::Draft => 'bg-violet-500',
            self::Closed => 'bg-slate-400',
            self::Cancelled => 'bg-rose-500',
            self::Archived => 'bg-zinc-400',
        };
    }
}

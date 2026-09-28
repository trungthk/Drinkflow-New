<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Enums\AdminStatus;
use App\Enums\OrderStatus;
use App\Models\AdminAccount;
use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\DebtPayment;
use App\Models\Order;
use App\Models\SecurityEvent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * Second tier of superadmin dashboard analytics: security trends and suspicious IPs, administrator
 * activity, and the order peak-hours heatmap. Kept separate from SuperadminDashboardService so the
 * primary KPIs load without waiting on these heavier queries.
 */
class SuperadminInsightsService
{
    /** Days covered by the security-by-severity chart. */
    public const SECURITY_DAYS = 14;

    /** Days covered by the security-by-type breakdown and admin activity counts. */
    public const ACTIVITY_DAYS = 30;

    /** Window in hours for the suspicious IP table. */
    public const IP_WINDOW_HOURS = 24;

    /** Failed logins from one IP inside IP_WINDOW_HOURS that mark it as suspicious. */
    public const SUSPICIOUS_FAILED_LOGINS = 10;

    public const TOP_IP_LIMIT = 10;
    public const TOP_TYPE_LIMIT = 8;

    /** An admin without a login for this many days is flagged as stale. */
    public const STALE_ADMIN_DAYS = 60;

    /** Days of orders feeding the peak-hours heatmap. */
    public const HEATMAP_DAYS = 90;

    public const SEVERITIES = ['low', 'medium', 'high'];

    public const FAILED_LOGIN_TYPE = 'failed_login';

    public const FLAG_STALE = 'stale';
    public const FLAG_NEVER_LOGGED_IN = 'never_logged_in';
    public const FLAG_NO_ROOMS = 'no_rooms';
    public const FLAG_INACTIVE = 'inactive';

    public const CACHE_TTL = 300;
    public const CACHE_KEY = 'superadmin.dashboard.insights';

    /**
     * Build (or read from cache) the insights payload.
     *
     * @param bool $fresh Bypass and refresh the cache.
     * @return array<string, mixed> Payload with keys security, admins, heatmap, generated_at.
     */
    public function insights(bool $fresh = false): array
    {
        if ($fresh) {
            Cache::forget(self::CACHE_KEY);
        }

        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn(): array => $this->compute(CarbonImmutable::now()));
    }

    /**
     * Compute the insights payload at a given moment.
     *
     * @param CarbonImmutable $now Reference time.
     * @return array<string, mixed> Insights payload.
     */
    public function compute(CarbonImmutable $now): array
    {
        return [
            'security' => [
                'daily' => $this->securityDaily($now),
                'types' => $this->securityTypes($now),
                'top_ips' => $this->topIps($now),
            ],
            'admins' => $this->adminActivity($now),
            'heatmap' => $this->orderHeatmap($now),
            'generated_at' => $now->toIso8601String(),
        ];
    }

    /**
     * Security events per day split by severity (zero-filled, oldest first).
     *
     * @param CarbonImmutable $now Reference time.
     * @return list<array{date: string, low: int, medium: int, high: int}> Daily counts.
     */
    public function securityDaily(CarbonImmutable $now): array
    {
        $firstDay = $now->startOfDay()->subDays(self::SECURITY_DAYS - 1);

        $rows = SecurityEvent::query()
            ->where('created_at', '>=', $firstDay)
            ->selectRaw('DATE(created_at) AS day, severity, COUNT(*) AS events')
            ->groupBy('day', 'severity')
            ->get();

        $series = [];
        for ($i = 0; $i < self::SECURITY_DAYS; $i++) {
            $date = $firstDay->addDays($i)->toDateString();
            $series[$date] = ['date' => $date] + array_fill_keys(self::SEVERITIES, 0);
        }
        foreach ($rows as $row) {
            $date = substr((string) $row->day, 0, 10);
            // Unknown severities are folded into "medium", the column default.
            $severity = in_array($row->severity, self::SEVERITIES, true) ? $row->severity : 'medium';
            if (isset($series[$date])) {
                $series[$date][$severity] += (int) $row->events;
            }
        }

        return array_values($series);
    }

    /**
     * Most frequent security event types over ACTIVITY_DAYS.
     *
     * @param CarbonImmutable $now Reference time.
     * @return list<array{type: string, count: int}> Types, most frequent first.
     */
    public function securityTypes(CarbonImmutable $now): array
    {
        return SecurityEvent::query()
            ->where('created_at', '>=', $now->subDays(self::ACTIVITY_DAYS))
            ->selectRaw('type, COUNT(*) AS events')
            ->groupBy('type')
            ->orderByDesc('events')
            ->limit(self::TOP_TYPE_LIMIT)
            ->get()
            ->map(fn($row): array => ['type' => (string) $row->type, 'count' => (int) $row->events])
            ->all();
    }

    /**
     * IPs with the most security events in the last IP_WINDOW_HOURS.
     *
     * @param CarbonImmutable $now Reference time.
     * @return list<array<string, mixed>> IP rows, most events first.
     */
    public function topIps(CarbonImmutable $now): array
    {
        return SecurityEvent::query()
            ->whereNotNull('ip_address')
            ->where('created_at', '>=', $now->subHours(self::IP_WINDOW_HOURS))
            ->selectRaw('ip_address, COUNT(*) AS events, COUNT(DISTINCT type) AS types')
            ->selectRaw('SUM(CASE WHEN type = ? THEN 1 ELSE 0 END) AS failed_logins', [self::FAILED_LOGIN_TYPE])
            ->selectRaw('SUM(CASE WHEN severity = ? THEN 1 ELSE 0 END) AS high_events', [SuperadminDashboardService::SECURITY_SEVERITY_HIGH])
            ->selectRaw('MAX(created_at) AS last_seen_at')
            ->groupBy('ip_address')
            ->orderByDesc('events')
            ->limit(self::TOP_IP_LIMIT)
            ->get()
            ->map(fn($row): array => [
                'ip_address' => (string) $row->ip_address,
                'events' => (int) $row->events,
                'failed_logins' => (int) $row->failed_logins,
                'high_events' => (int) $row->high_events,
                'types' => (int) $row->types,
                'last_seen_at' => CarbonImmutable::parse($row->last_seen_at)->toIso8601String(),
                'suspicious' => (int) $row->failed_logins >= self::SUSPICIOUS_FAILED_LOGINS,
            ])
            ->all();
    }

    /**
     * Activity of every admin/superadmin account over ACTIVITY_DAYS with warning flags.
     *
     * @param CarbonImmutable $now Reference time.
     * @return list<array<string, mixed>> Admin rows, most flags first then least recent login.
     */
    public function adminActivity(CarbonImmutable $now): array
    {
        $since = $now->subDays(self::ACTIVITY_DAYS);

        $campaigns = Campaign::query()
            ->whereNotNull('creator_admin_id')->where('created_at', '>=', $since)
            ->selectRaw('creator_admin_id AS admin_id, COUNT(*) AS total')
            ->groupBy('creator_admin_id')
            ->pluck('total', 'admin_id');

        $payments = DebtPayment::query()
            ->whereNotNull('created_by_admin_id')->where('created_at', '>=', $since)
            ->selectRaw('created_by_admin_id AS admin_id, COUNT(*) AS total')
            ->groupBy('created_by_admin_id')
            ->pluck('total', 'admin_id');

        $actions = AuditLog::query()
            ->whereIn('actor_type', AuditLog::ADMIN_ACTOR_TYPES)->whereNotNull('actor_id')->where('created_at', '>=', $since)
            ->selectRaw('actor_id AS admin_id, COUNT(*) AS total')
            ->groupBy('actor_id')
            ->pluck('total', 'admin_id');

        $staleBefore = $now->subDays(self::STALE_ADMIN_DAYS);

        return AdminAccount::query()
            ->select(['id', 'name', 'email', 'role', 'status', 'last_login_at', 'created_at'])
            ->withCount('rooms')
            ->get()
            ->map(function (AdminAccount $admin) use ($campaigns, $payments, $actions, $staleBefore): array {
                $status = $admin->status instanceof \BackedEnum ? $admin->status->value : (string) $admin->status;
                $role = $admin->role instanceof \BackedEnum ? $admin->role->value : (string) $admin->role;
                $lastLogin = $admin->last_login_at ? CarbonImmutable::parse($admin->last_login_at) : null;

                $flags = [];
                if ($status !== AdminStatus::Active->value) {
                    $flags[] = self::FLAG_INACTIVE;
                } elseif ($lastLogin === null) {
                    if (CarbonImmutable::parse($admin->created_at)->lt($staleBefore)) {
                        $flags[] = self::FLAG_NEVER_LOGGED_IN;
                    }
                } elseif ($lastLogin->lt($staleBefore)) {
                    $flags[] = self::FLAG_STALE;
                }
                if (! $admin->isSuperadmin() && (int) $admin->rooms_count === 0) {
                    $flags[] = self::FLAG_NO_ROOMS;
                }

                return [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'role' => $role,
                    'status' => $status,
                    'rooms' => (int) $admin->rooms_count,
                    'campaigns_created' => (int) ($campaigns[$admin->id] ?? 0),
                    'payments_confirmed' => (int) ($payments[$admin->id] ?? 0),
                    'audit_actions' => (int) ($actions[$admin->id] ?? 0),
                    'last_login_at' => $lastLogin?->toIso8601String(),
                    'flags' => $flags,
                ];
            })
            ->sortBy([
                fn(array $a, array $b): int => count($b['flags']) <=> count($a['flags']),
                fn(array $a, array $b): int => ($a['last_login_at'] ?? '') <=> ($b['last_login_at'] ?? ''),
            ])
            ->values()
            ->all();
    }

    /**
     * Non-cancelled orders over HEATMAP_DAYS bucketed by local weekday (ISO, Monday first) and hour.
     *
     * Bucketing happens in PHP in the display timezone so it is independent of the database's
     * date functions and of the UTC storage timezone.
     *
     * @param CarbonImmutable $now Reference time.
     * @return array{timezone: string, days: int, total: int, max: int, cells: list<list<int>>} 7×24 counts.
     */
    public function orderHeatmap(CarbonImmutable $now): array
    {
        $timezone = (string) config('app.display_timezone', config('app.timezone'));
        $cells = array_fill(0, 7, array_fill(0, 24, 0));

        Order::query()
            ->where('status', '!=', OrderStatus::Cancelled->value)
            ->where('created_at', '>=', $now->subDays(self::HEATMAP_DAYS))
            ->select(['id', 'created_at'])
            ->lazyById(2000)
            ->each(function (Order $order) use (&$cells, $timezone): void {
                $local = CarbonImmutable::parse($order->created_at)->setTimezone($timezone);
                $cells[$local->dayOfWeekIso - 1][$local->hour]++;
            });

        $flat = array_merge(...$cells);

        return [
            'timezone' => $timezone,
            'days' => self::HEATMAP_DAYS,
            'total' => array_sum($flat),
            'max' => max($flat),
            'cells' => $cells,
        ];
    }
}

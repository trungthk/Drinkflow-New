<?php

declare(strict_types=1);

namespace App\Services\Subscription;

use App\Enums\SubscriptionStatus;
use App\Models\Admin;
use App\Models\AdminSubscription;
use App\Models\Package;
use App\Models\Superadmin;
use App\Services\Audit\AuditService;
use App\Services\Room\RoomQuotaService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Agent subscriptions: activation with a package snapshot, package changes and the period lifecycle.
 *
 * The package price and room limit are copied into `price_snapshot` / `room_limit_snapshot` at
 * activation; billing and quota only read the snapshot, so editing a package later never changes
 * a running subscription. A package change starts a new subscription row (the old one is
 * superseded), which keeps the history. Upgrades apply immediately with a credit for the unused
 * part of the current period; an Agent's downgrade waits for the period end, and it is refused
 * when the Agent owns more rooms than the smaller package allows.
 */
class SubscriptionService
{
    /** Length of one subscription period in months. */
    public const PERIOD_MONTHS = 1;

    public const CHANGE_IMMEDIATE = 'immediate';

    public const CHANGE_SCHEDULED = 'scheduled';

    public function __construct(
        private readonly AuditService $audit,
        private readonly RoomQuotaService $quota,
    ) {}

    /**
     * Activate a subscription of the Agent on the package, superseding the current one.
     *
     * Runs inside the caller's transaction when there is one (approval, package change).
     *
     * @param Admin $admin Agent.
     * @param Package $package Package whose current terms are frozen.
     * @param Superadmin|null $approvedBy Superadmin approving the subscription.
     * @param CarbonInterface|null $startsAt Start of the first period (now by default).
     * @param int $prorationCredit Credit from the replaced subscription, deducted from the first invoice.
     * @return AdminSubscription New active subscription.
     */
    public function activate(Admin $admin, Package $package, ?Superadmin $approvedBy = null, ?CarbonInterface $startsAt = null, int $prorationCredit = 0): AdminSubscription
    {
        return DB::transaction(function () use ($admin, $package, $approvedBy, $startsAt, $prorationCredit): AdminSubscription {
            $startsAt ??= now();
            $previous = $this->endCurrent($admin, SubscriptionStatus::Superseded, $startsAt);

            return AdminSubscription::create([
                'admin_id' => $admin->id,
                'previous_subscription_id' => $previous?->id,
                'package_id' => $package->id,
                'status' => SubscriptionStatus::Active->value,
                'price_snapshot' => $package->monthly_price,
                'proration_credit' => min($prorationCredit, $package->monthly_price),
                'room_limit_snapshot' => $package->room_limit,
                'starts_at' => $startsAt,
                'expires_at' => $startsAt->copy()->addMonthsNoOverflow(self::PERIOD_MONTHS),
                'approved_by_superadmin_id' => $approvedBy?->id,
            ]);
        });
    }

    /**
     * Give the default package (config platform.billing.default_package) to every active or suspended
     * Agent that never had a subscription. Idempotent: Agents with any subscription history are skipped.
     *
     * @return int Number of Agents that received a subscription (0 when the default package does not exist).
     */
    public function assignDefaultToAgentsWithoutSubscription(): int
    {
        $package = Package::query()->where('code', (string) config('platform.billing.default_package', 'starter'))->first();
        if ($package === null) {
            return 0;
        }

        $assigned = 0;
        Admin::query()
            ->whereIn('status', [\App\Enums\AdminStatus::Active->value, \App\Enums\AdminStatus::Suspended->value])
            ->whereDoesntHave('subscriptions')
            ->orderBy('id')
            ->each(function (Admin $admin) use ($package, &$assigned): void {
                DB::transaction(function () use ($admin, $package, &$assigned): void {
                    Admin::query()->lockForUpdate()->findOrFail($admin->id);
                    if ($admin->subscriptions()->exists()) {
                        return;
                    }
                    $subscription = $this->activate($admin, $package);
                    $this->audit->record('subscription.started', 'admin', $admin->id, null, [], $this->snapshot($subscription), ['reason' => 'default_package']);
                    $assigned++;
                });
            });

        return $assigned;
    }

    /**
     * Current subscription of the Agent, locked for update.
     *
     * @param Admin $admin Agent.
     * @return AdminSubscription|null Active subscription, or null when there is none.
     */
    public function current(Admin $admin): ?AdminSubscription
    {
        return AdminSubscription::query()->where('admin_id', $admin->id)->active()->lockForUpdate()->first();
    }

    /**
     * End the Agent's current subscription, if any.
     *
     * @param Admin $admin Agent.
     * @param SubscriptionStatus $status Final status (superseded, expired or cancelled).
     * @param CarbonInterface|null $endedAt End time (now by default).
     * @return AdminSubscription|null The ended subscription, or null when there was none.
     */
    public function endCurrent(Admin $admin, SubscriptionStatus $status, ?CarbonInterface $endedAt = null): ?AdminSubscription
    {
        $current = $this->current($admin);
        $current?->update(['status' => $status->value, 'ended_at' => $endedAt ?? now(), 'scheduled_package_id' => null]);

        return $current;
    }

    /**
     * Whether moving to the package is an upgrade (more rooms, or same rooms for a higher price).
     *
     * @param AdminSubscription $subscription Current subscription.
     * @param Package $package Target package.
     * @return bool True for an upgrade, false for a downgrade or a lateral move.
     */
    public function isUpgrade(AdminSubscription $subscription, Package $package): bool
    {
        return $package->room_limit > $subscription->room_limit_snapshot
            || ($package->room_limit === $subscription->room_limit_snapshot && $package->monthly_price > $subscription->price_snapshot);
    }

    /**
     * Change the Agent's package.
     *
     * Upgrades start now with a proration credit. Downgrades are refused when the owned rooms do not
     * fit the new limit; requested by the Agent they are scheduled for the period end, while a
     * Superadmin applies them immediately. Without a current subscription (legacy Agent) only a
     * Superadmin can start one.
     *
     * @param Admin $admin Agent.
     * @param Package $package Target package (must be active).
     * @param Superadmin|null $actor Superadmin making the change, null when the Agent does it.
     * @return array{mode: string, subscription: AdminSubscription} Applied (immediate) or scheduled change.
     * @throws ValidationException When the package is unavailable, unchanged or too small.
     */
    public function changePackage(Admin $admin, Package $package, ?Superadmin $actor = null): array
    {
        return DB::transaction(function () use ($admin, $package, $actor): array {
            $admin = Admin::query()->lockForUpdate()->findOrFail($admin->id);
            $package = Package::query()->lockForUpdate()->findOrFail($package->id);
            if (! $package->status->isSelectable()) {
                throw ValidationException::withMessages(['package_id' => __('platform.subscriptions.package_unavailable')]);
            }

            $current = $this->current($admin);
            if ($current === null) {
                if ($actor === null) {
                    throw ValidationException::withMessages(['package_id' => __('platform.subscriptions.no_subscription')]);
                }
                $this->ensureRoomsFit($admin, $package);
                $subscription = $this->activate($admin, $package, $actor);
                $this->audit->record('subscription.started', 'admin', $admin->id, null, [], $this->snapshot($subscription));

                return ['mode' => self::CHANGE_IMMEDIATE, 'subscription' => $subscription];
            }

            if ($current->package_id === $package->id && $current->price_snapshot === $package->monthly_price && $current->room_limit_snapshot === $package->room_limit) {
                throw ValidationException::withMessages(['package_id' => __('platform.subscriptions.same_package')]);
            }

            $upgrade = $this->isUpgrade($current, $package);
            if (! $upgrade) {
                $this->ensureRoomsFit($admin, $package);
            }

            if (! $upgrade && $actor === null) {
                $before = ['scheduled_package_id' => $current->scheduled_package_id];
                $current->update(['scheduled_package_id' => $package->id, 'cancel_at_period_end' => false]);
                $this->audit->record('subscription.downgrade_scheduled', 'admin', $admin->id, null, $before, [
                    'scheduled_package_id' => $package->id,
                    'effective_at' => $current->expires_at?->toIso8601String(),
                ]);

                return ['mode' => self::CHANGE_SCHEDULED, 'subscription' => $current];
            }

            $before = $this->snapshot($current);
            $subscription = $this->activate($admin, $package, $actor, now(), $upgrade ? $this->unusedCredit($current) : 0);
            $this->audit->record($upgrade ? 'subscription.upgraded' : 'subscription.downgraded', 'admin', $admin->id, null, $before, $this->snapshot($subscription));

            return ['mode' => self::CHANGE_IMMEDIATE, 'subscription' => $subscription];
        });
    }

    /**
     * Drop a scheduled downgrade.
     *
     * @param Admin $admin Agent.
     * @return AdminSubscription|null Current subscription.
     */
    public function cancelScheduledChange(Admin $admin): ?AdminSubscription
    {
        return DB::transaction(function () use ($admin): ?AdminSubscription {
            $current = $this->current($admin);
            if ($current?->scheduled_package_id !== null) {
                $before = ['scheduled_package_id' => $current->scheduled_package_id];
                $current->update(['scheduled_package_id' => null]);
                $this->audit->record('subscription.scheduled_change_cancelled', 'admin', $admin->id, null, $before, ['scheduled_package_id' => null]);
            }

            return $current;
        });
    }

    /**
     * Stop (or resume) the renewal: the subscription ends at the close of the current period.
     *
     * @param Admin $admin Agent.
     * @param bool $cancel True to cancel at period end, false to resume.
     * @return AdminSubscription Current subscription.
     * @throws ValidationException When there is no active subscription.
     */
    public function setCancelAtPeriodEnd(Admin $admin, bool $cancel): AdminSubscription
    {
        return DB::transaction(function () use ($admin, $cancel): AdminSubscription {
            $current = $this->current($admin);
            if ($current === null) {
                throw ValidationException::withMessages(['subscription' => __('platform.subscriptions.no_subscription')]);
            }
            if ($current->cancel_at_period_end !== $cancel) {
                $current->update(['cancel_at_period_end' => $cancel, 'scheduled_package_id' => $cancel ? null : $current->scheduled_package_id]);
                $this->audit->record($cancel ? 'subscription.cancel_requested' : 'subscription.resumed', 'admin', $admin->id, null, [], [
                    'subscription_id' => $current->id,
                    'ends_at' => $current->expires_at?->toIso8601String(),
                ]);
            }

            return $current;
        });
    }

    /**
     * Cancel the current subscription immediately (Superadmin decision).
     *
     * @param Admin $admin Agent.
     * @return AdminSubscription|null The cancelled subscription, or null when there was none.
     */
    public function cancelNow(Admin $admin): ?AdminSubscription
    {
        return DB::transaction(function () use ($admin): ?AdminSubscription {
            $cancelled = $this->endCurrent($admin, SubscriptionStatus::Cancelled);
            if ($cancelled !== null) {
                $this->audit->record('subscription.cancelled', 'admin', $admin->id, null, ['status' => SubscriptionStatus::Active->value], ['status' => SubscriptionStatus::Cancelled->value, 'subscription_id' => $cancelled->id]);
            }

            return $cancelled;
        });
    }

    /**
     * Close every period that has ended: renew, apply the scheduled downgrade, or end cancelled subscriptions.
     *
     * Idempotent: each subscription is locked and re-checked, so a second run (or a concurrent one)
     * does nothing more.
     *
     * @param CarbonInterface|null $now Reference time (now by default).
     * @return array{renewed: int, changed: int, cancelled: int} Number of subscriptions per outcome.
     */
    public function processDuePeriods(?CarbonInterface $now = null): array
    {
        $now ??= now();
        $result = ['renewed' => 0, 'changed' => 0, 'cancelled' => 0];
        $dueIds = AdminSubscription::query()->active()->whereNotNull('expires_at')->where('expires_at', '<=', $now)->pluck('id');

        foreach ($dueIds as $id) {
            $outcome = DB::transaction(fn (): ?string => $this->closePeriod((int) $id, $now));
            if ($outcome !== null) {
                $result[$outcome]++;
            }
        }

        return $result;
    }

    /**
     * Close the ended period of one subscription.
     *
     * @param int $subscriptionId Subscription ID.
     * @param CarbonInterface $now Reference time.
     * @return string|null renewed, changed or cancelled; null when nothing was due anymore.
     */
    private function closePeriod(int $subscriptionId, CarbonInterface $now): ?string
    {
        $subscription = AdminSubscription::query()->lockForUpdate()->find($subscriptionId);
        if ($subscription === null || $subscription->status !== SubscriptionStatus::Active || $subscription->expires_at === null || $subscription->expires_at->greaterThan($now)) {
            return null;
        }
        $admin = Admin::query()->lockForUpdate()->findOrFail($subscription->admin_id);
        $periodEnd = $subscription->expires_at->copy();

        if ($subscription->cancel_at_period_end) {
            $subscription->update(['status' => SubscriptionStatus::Cancelled->value, 'ended_at' => $periodEnd]);
            $this->audit->record('subscription.cancelled', 'admin', $admin->id, null, ['status' => SubscriptionStatus::Active->value], ['status' => SubscriptionStatus::Cancelled->value, 'subscription_id' => $subscription->id]);

            return 'cancelled';
        }

        $scheduled = $subscription->scheduled_package_id !== null ? Package::query()->find($subscription->scheduled_package_id) : null;
        if ($scheduled !== null) {
            if ($scheduled->status->isSelectable() && $this->quota->used($admin) <= $scheduled->room_limit) {
                $before = $this->snapshot($subscription);
                $new = $this->activate($admin, $scheduled, null, $periodEnd);
                $this->audit->record('subscription.downgraded', 'admin', $admin->id, null, $before, $this->snapshot($new));
                $this->catchUp($new, $now);

                return 'changed';
            }

            // The Agent now owns more rooms than the smaller package allows (or it was retired): keep the plan.
            $subscription->update(['scheduled_package_id' => null]);
            $this->audit->record('subscription.scheduled_change_dropped', 'admin', $admin->id, null, ['scheduled_package_id' => $scheduled->id], ['scheduled_package_id' => null]);
        }

        $before = ['expires_at' => $periodEnd->toIso8601String()];
        $this->catchUp($subscription, $now);
        $this->audit->record('subscription.renewed', 'admin', $admin->id, null, $before, ['expires_at' => $subscription->expires_at->toIso8601String(), 'subscription_id' => $subscription->id]);

        return 'renewed';
    }

    /**
     * Move the period end forward until it is in the future (same snapshot terms).
     *
     * @param AdminSubscription $subscription Subscription to renew.
     * @param CarbonInterface $now Reference time.
     * @return void
     */
    private function catchUp(AdminSubscription $subscription, CarbonInterface $now): void
    {
        $expiresAt = $subscription->expires_at->copy();
        while ($expiresAt->lessThanOrEqualTo($now)) {
            $expiresAt = $expiresAt->addMonthsNoOverflow(self::PERIOD_MONTHS);
        }
        $subscription->update(['expires_at' => $expiresAt]);
    }

    /**
     * Refuse a package too small for the rooms the Agent already owns.
     *
     * @param Admin $admin Agent.
     * @param Package $package Target package.
     * @return void
     * @throws ValidationException When owned active/disabled rooms exceed the package limit.
     */
    private function ensureRoomsFit(Admin $admin, Package $package): void
    {
        $used = $this->quota->used($admin);
        if ($used > $package->room_limit) {
            throw ValidationException::withMessages(['package_id' => __('platform.subscriptions.too_many_rooms', ['used' => $used, 'limit' => $package->room_limit])]);
        }
    }

    /**
     * Value of the unused part of the current period, credited when upgrading.
     *
     * @param AdminSubscription $subscription Subscription being replaced.
     * @return int Credit in VND.
     */
    private function unusedCredit(AdminSubscription $subscription): int
    {
        if ($subscription->expires_at === null || $subscription->price_snapshot === 0) {
            return 0;
        }
        $total = max(1, $subscription->starts_at->diffInSeconds($subscription->expires_at, true));
        $remaining = max(0, now()->diffInSeconds($subscription->expires_at, false));

        return (int) min($subscription->price_snapshot, round($subscription->price_snapshot * $remaining / $total));
    }

    /**
     * Audited terms of a subscription.
     *
     * @param AdminSubscription $subscription Subscription.
     * @return array<string, mixed> Terms.
     */
    private function snapshot(AdminSubscription $subscription): array
    {
        return [
            'subscription_id' => $subscription->id,
            'package_id' => $subscription->package_id,
            'price_snapshot' => $subscription->price_snapshot,
            'room_limit_snapshot' => $subscription->room_limit_snapshot,
            'expires_at' => $subscription->expires_at?->toIso8601String(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Enums\AdminStatus;
use App\Enums\CampaignStatus;
use App\Enums\OrderStatus;
use App\Enums\Permission;
use App\Enums\PermissionScope;
use App\Enums\RoomStatus;
use App\Models\Admin;
use App\Models\AdminSubscription;
use App\Models\Campaign;
use App\Models\Order;
use App\Models\Room;
use App\Models\SecurityEvent;
use App\Models\Superadmin;
use App\Services\Authorization\AgentScope;
use App\Services\Billing\PlatformRevenueService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Permission-aware widgets of the Superadmin dashboard.
 *
 * Each widget is built only when the Superadmin holds its permission and every figure goes through
 * the Agent scope of that permission; a widget the Superadmin may not see is null (not rendered),
 * so a `managed` Superadmin's totals only cover the assigned Agents.
 */
class PlatformDashboardService
{
    /** Pending registrations listed on the widget. */
    public const PENDING_LIMIT = 5;

    public function __construct(
        private readonly AgentScope $scope,
        private readonly PlatformRevenueService $revenue,
    ) {}

    /**
     * All widgets the Superadmin may see.
     *
     * @param Superadmin $superadmin Viewer.
     * @return array{agents: ?array, revenue: ?array, rooms: ?array, campaigns: ?array, registrations: ?array, system: ?array} Widgets (null when hidden).
     */
    public function widgetsFor(Superadmin $superadmin): array
    {
        return [
            'agents' => $superadmin->hasPermission(Permission::AgentView) ? $this->agentMetrics($superadmin) : null,
            'revenue' => $superadmin->hasPermission(Permission::RevenueView) ? $this->revenue->summary($superadmin) : null,
            'rooms' => $superadmin->hasPermission(Permission::RoomView) ? $this->roomMetrics($superadmin) : null,
            'campaigns' => $superadmin->hasPermission(Permission::RoomView) ? $this->campaignMetrics($superadmin) : null,
            'registrations' => $superadmin->hasPermission(Permission::AgentApprove) ? $this->pendingRegistrations() : null,
            'system' => $this->systemMetrics($superadmin),
        ];
    }

    /**
     * Whether the Superadmin may open the platform-wide analytics (room health, heatmap, cohorts…).
     *
     * Those analytics are computed over the whole platform, so they require an unrestricted
     * (`all`) room scope plus the platform permissions their sections cover.
     *
     * @param Superadmin $superadmin Viewer.
     * @return bool True when every section may be shown.
     */
    public function canSeePlatformAnalytics(Superadmin $superadmin): bool
    {
        return $superadmin->scopeFor(Permission::RoomView) === PermissionScope::All
            && $superadmin->scopeFor(Permission::AgentView) === PermissionScope::All
            && $superadmin->hasPermission(Permission::SecurityView)
            && $superadmin->hasPermission(Permission::GlobalUserView)
            && $superadmin->hasPermission(Permission::FeedbackView)
            && $superadmin->hasPermission(Permission::SettingsView);
    }

    /**
     * Agent counts by status and new Agents this month (T56).
     *
     * @param Superadmin $superadmin Viewer.
     * @return array{total: int, active: int, suspended: int, new_month: int, with_subscription: int} Counts.
     */
    public function agentMetrics(Superadmin $superadmin): array
    {
        $agents = Admin::query()->visibleTo($superadmin)->whereNotIn('status', [AdminStatus::Pending->value, AdminStatus::Rejected->value]);
        $byStatus = (clone $agents)->select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->pluck('total', 'status');
        $visibleIds = (clone $agents)->select('admins.id');

        return [
            'total' => (int) $byStatus->sum(),
            'active' => (int) ($byStatus[AdminStatus::Active->value] ?? 0),
            'suspended' => (int) ($byStatus[AdminStatus::Suspended->value] ?? 0),
            'new_month' => (clone $agents)->where(static fn (Builder $q) => $q->where('reviewed_at', '>=', now()->startOfMonth())
                ->orWhere(static fn (Builder $legacy) => $legacy->whereNull('reviewed_at')->where('created_at', '>=', now()->startOfMonth())))->count(),
            'with_subscription' => AdminSubscription::query()->active()->whereIn('admin_id', $visibleIds)->count(),
        ];
    }

    /**
     * Rooms by status and quota utilisation of the visible Agents (T58).
     *
     * @param Superadmin $superadmin Viewer.
     * @return array{active: int, inactive: int, archived: int, quota_used: int, quota_limit: int} Counts.
     */
    public function roomMetrics(Superadmin $superadmin): array
    {
        $rooms = $this->scope->applyToRooms(Room::query(), $superadmin, Permission::RoomView);
        $byStatus = (clone $rooms)->select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->pluck('total', 'status');
        $ownerIds = $this->scope->adminIds($superadmin, Permission::RoomView);
        $subscriptions = AdminSubscription::query()->active()->when($ownerIds !== null, static fn ($q) => $q->whereIn('admin_id', $ownerIds));
        $owned = Room::query()->countingTowardQuota()->whereNotNull('owner_admin_id')->when($ownerIds !== null, static fn ($q) => $q->whereIn('owner_admin_id', $ownerIds));

        return [
            'active' => (int) ($byStatus[RoomStatus::Active->value] ?? 0),
            'inactive' => (int) ($byStatus[RoomStatus::Inactive->value] ?? 0),
            'archived' => (int) ($byStatus[RoomStatus::Archived->value] ?? 0),
            'quota_used' => $owned->count(),
            'quota_limit' => (int) $subscriptions->sum('room_limit_snapshot'),
        ];
    }

    /**
     * Running campaigns, campaigns and orders this month in the visible rooms (T59).
     *
     * @param Superadmin $superadmin Viewer.
     * @return array{running: int, month: int, orders_month: int, gmv_month: int} Counts and GMV in VND.
     */
    public function campaignMetrics(Superadmin $superadmin): array
    {
        $campaigns = $this->scope->applyToRoomOwned(Campaign::query(), $superadmin, Permission::RoomView);
        $orders = $this->scope->applyToRoomOwned(Order::query(), $superadmin, Permission::RoomView)
            ->where('orders.status', '!=', OrderStatus::Cancelled->value)
            ->where('orders.created_at', '>=', now()->startOfMonth());

        return [
            'running' => (clone $campaigns)->whereIn('status', CampaignStatus::running())->count(),
            'month' => (clone $campaigns)->where('created_at', '>=', now()->startOfMonth())->count(),
            'orders_month' => (clone $orders)->count(),
            'gmv_month' => (int) (clone $orders)->sum('final_amount'),
        ];
    }

    /**
     * Registrations waiting for review (T60); the queue is shared by every holder of `agent.approve`.
     *
     * @return array{count: int, verified: int, latest: \Illuminate\Support\Collection<int, Admin>} Queue summary.
     */
    public function pendingRegistrations(): array
    {
        $pending = Admin::query()->where('status', AdminStatus::Pending->value);

        return [
            'count' => (clone $pending)->count(),
            'verified' => (clone $pending)->whereNotNull('email_verified_at')->count(),
            'latest' => (clone $pending)->with('requestedPackage:id,name')->orderByDesc('registered_at')->limit(self::PENDING_LIMIT)->get(['id', 'name', 'email', 'company', 'registered_at', 'email_verified_at', 'requested_package_id']),
        ];
    }

    /**
     * Security and queue figures (T61), each only with its permission.
     *
     * @param Superadmin $superadmin Viewer.
     * @return array{security_high: ?int, security_total: ?int, failed_jobs: ?int, pending_jobs: ?int}|null Figures, null when none is allowed.
     */
    public function systemMetrics(Superadmin $superadmin): ?array
    {
        $security = $superadmin->hasPermission(Permission::SecurityView);
        $queue = $superadmin->hasPermission(Permission::QueueView);
        if (! $security && ! $queue) {
            return null;
        }
        $since = now()->subDay();

        return [
            'security_high' => $security ? SecurityEvent::query()->where('severity', 'high')->where('created_at', '>=', $since)->count() : null,
            'security_total' => $security ? SecurityEvent::query()->where('created_at', '>=', $since)->count() : null,
            'failed_jobs' => $queue ? DB::table('failed_jobs')->count() : null,
            'pending_jobs' => $queue ? DB::table('jobs')->count() : null,
        ];
    }
}

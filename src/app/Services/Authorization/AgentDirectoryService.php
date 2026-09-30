<?php

declare(strict_types=1);

namespace App\Services\Authorization;

use App\Enums\Permission;
use App\Models\Admin;
use App\Models\AdminInvoice;
use App\Models\Superadmin;
use Illuminate\Support\Collection;

/**
 * Per-column visibility of Agent data for a Superadmin.
 *
 * Seeing an Agent (`agent.view`) does not mean seeing its subscription, rooms or money: each column
 * of the Agent list and each tab of the Agent page follows its own permission and scope, so a
 * Superadmin with `agent.view: all` but `debt.view: managed` only sees balances of assigned Agents.
 */
class AgentDirectoryService
{
    /** Agent page tabs and the permission each one needs (null: covered by `agent.view`). */
    public const TABS = [
        'overview' => null,
        'subscription' => Permission::SubscriptionView,
        'rooms' => Permission::RoomView,
        'campaigns' => Permission::RoomView,
        'billing' => Permission::DebtView,
        'activity' => Permission::AuditView,
    ];

    public function __construct(private readonly AgentScope $scope) {}

    /**
     * Tabs of the Agent page the Superadmin may open for this Agent.
     *
     * @param Superadmin $superadmin Viewer.
     * @param Admin $admin Agent.
     * @return array<int, string> Tab keys.
     */
    public function tabsFor(Superadmin $superadmin, Admin $admin): array
    {
        return array_keys(array_filter(
            self::TABS,
            fn (?Permission $permission): bool => $permission === null || $this->scope->allows($superadmin, $permission, $admin->id),
        ));
    }

    /**
     * Whether the Superadmin may see a given kind of data of the Agent.
     *
     * @param Superadmin $superadmin Viewer.
     * @param Permission $permission Permission of that data.
     * @param Admin $admin Agent.
     * @return bool True when inside the scope.
     */
    public function allows(Superadmin $superadmin, Permission $permission, Admin $admin): bool
    {
        return $this->scope->allows($superadmin, $permission, $admin->id);
    }

    /**
     * Outstanding balances of the listed Agents, only for those visible with `debt.view`.
     *
     * @param Superadmin $superadmin Viewer.
     * @param Collection<int, Admin> $admins Listed Agents.
     * @return array<int, int> Agent ID => amount owed (Agents outside the scope are absent).
     */
    public function balances(Superadmin $superadmin, Collection $admins): array
    {
        $ids = $admins->pluck('id')->filter(fn (int $id): bool => $this->scope->allows($superadmin, Permission::DebtView, $id))->values()->all();
        if ($ids === []) {
            return [];
        }

        return AdminInvoice::query()->outstanding()->whereIn('admin_id', $ids)
            ->selectRaw('admin_id, COALESCE(SUM(total - paid_amount), 0) as due')
            ->groupBy('admin_id')
            ->pluck('due', 'admin_id')
            ->map(static fn ($due): int => (int) $due)
            ->all() + array_fill_keys($ids, 0);
    }
}

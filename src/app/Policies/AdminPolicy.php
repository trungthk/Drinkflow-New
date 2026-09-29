<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Admin;
use App\Models\Superadmin;
use App\Services\Authorization\AgentScope;

/**
 * Superadmin access to Agent (Admin) accounts: permission plus `all`/`managed` scope.
 */
class AdminPolicy
{
    public function __construct(private readonly AgentScope $scope) {}

    /**
     * List Agents (the list itself is filtered by AgentScope).
     *
     * @param Admin|Superadmin $user Signed-in account.
     * @return bool True when allowed.
     */
    public function viewAny(Admin|Superadmin $user): bool
    {
        return $user instanceof Superadmin && $user->hasPermission(Permission::AgentView);
    }

    /**
     * See one Agent.
     *
     * @param Admin|Superadmin $user Signed-in account.
     * @param Admin $admin Agent.
     * @return bool True when allowed.
     */
    public function view(Admin|Superadmin $user, Admin $admin): bool
    {
        return $user instanceof Superadmin && $this->scope->allows($user, Permission::AgentView, $admin->id);
    }

    /**
     * Create an Agent account.
     *
     * @param Admin|Superadmin $user Signed-in account.
     * @return bool True when allowed.
     */
    public function create(Admin|Superadmin $user): bool
    {
        return $user instanceof Superadmin && $user->hasPermission(Permission::AgentManage);
    }

    /**
     * Update an Agent (profile, status, password, rooms).
     *
     * @param Admin|Superadmin $user Signed-in account.
     * @param Admin $admin Agent.
     * @return bool True when allowed.
     */
    public function update(Admin|Superadmin $user, Admin $admin): bool
    {
        return $user instanceof Superadmin && $this->scope->allows($user, Permission::AgentManage, $admin->id);
    }

    /**
     * Delete an Agent.
     *
     * @param Admin|Superadmin $user Signed-in account.
     * @param Admin $admin Agent.
     * @return bool True when allowed.
     */
    public function delete(Admin|Superadmin $user, Admin $admin): bool
    {
        return $this->update($user, $admin);
    }
}

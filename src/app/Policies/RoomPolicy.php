<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Admin;
use App\Models\Room;
use App\Models\Superadmin;
use App\Services\Authorization\AgentScope;

/**
 * Superadmin access to rooms from the console: permission plus the Agent scope of the room.
 *
 * With the `managed` scope a room is visible when one of its Agents is assigned to the Superadmin.
 */
class RoomPolicy
{
    public function __construct(private readonly AgentScope $scope) {}

    /**
     * List rooms (the list itself is filtered by the scope).
     *
     * @param Admin|Superadmin $user Signed-in account.
     * @return bool True when allowed.
     */
    public function viewAny(Admin|Superadmin $user): bool
    {
        return $user instanceof Superadmin && $user->hasPermission(Permission::RoomView);
    }

    /**
     * See one room.
     *
     * @param Admin|Superadmin $user Signed-in account.
     * @param Room $room Room.
     * @return bool True when allowed.
     */
    public function view(Admin|Superadmin $user, Room $room): bool
    {
        return $user instanceof Superadmin && $this->visible($user, Permission::RoomView, $room);
    }

    /**
     * Create a room.
     *
     * @param Admin|Superadmin $user Signed-in account.
     * @return bool True when allowed.
     */
    public function create(Admin|Superadmin $user): bool
    {
        return $user instanceof Superadmin && $user->hasPermission(Permission::RoomManage);
    }

    /**
     * Update, change the status of, or force-close campaigns in a room.
     *
     * @param Admin|Superadmin $user Signed-in account.
     * @param Room $room Room.
     * @return bool True when allowed.
     */
    public function update(Admin|Superadmin $user, Room $room): bool
    {
        return $user instanceof Superadmin && $this->visible($user, Permission::RoomManage, $room);
    }

    /**
     * Delete a room.
     *
     * @param Admin|Superadmin $user Signed-in account.
     * @param Room $room Room.
     * @return bool True when allowed.
     */
    public function delete(Admin|Superadmin $user, Room $room): bool
    {
        return $this->update($user, $room);
    }

    /**
     * Whether the room belongs to an Agent visible for the permission.
     *
     * @param Superadmin $superadmin Acting superadmin.
     * @param Permission $permission Permission being exercised.
     * @param Room $room Room.
     * @return bool True when visible.
     */
    private function visible(Superadmin $superadmin, Permission $permission, Room $room): bool
    {
        $ids = $this->scope->adminIds($superadmin, $permission);

        return $ids === null || ($ids !== [] && $room->admins()->whereIn('admins.id', $ids)->exists());
    }
}

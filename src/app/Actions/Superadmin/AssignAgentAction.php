<?php

declare(strict_types=1);

namespace App\Actions\Superadmin;

use App\Models\Admin;
use App\Models\Superadmin;
use App\Models\SuperadminAdmin;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

/**
 * Assign Agents (Admins) to the Superadmins that manage them.
 *
 * An Agent may be assigned to several Superadmins, one of which can be its primary Superadmin.
 * The Agent row is locked for every change so concurrent requests cannot create two primaries
 * (the unique index on `superadmin_admins.primary_admin_id` is the last line of defence).
 */
class AssignAgentAction
{
    public function __construct(private readonly AuditService $audit) {}

    /**
     * Assign an Agent to a Superadmin, or update the primary flag of an existing assignment.
     *
     * Making the assignment primary demotes the Agent's previous primary Superadmin.
     *
     * @param Superadmin $superadmin Superadmin who will manage the Agent.
     * @param Admin $admin Agent being assigned.
     * @param Superadmin $actor Superadmin performing the assignment.
     * @param bool $primary Whether this Superadmin becomes the Agent's primary Superadmin.
     * @return SuperadminAdmin Stored assignment.
     */
    public function assign(Superadmin $superadmin, Admin $admin, Superadmin $actor, bool $primary = false): SuperadminAdmin
    {
        $assignment = DB::transaction(function () use ($superadmin, $admin, $actor, $primary): SuperadminAdmin {
            Admin::query()->lockForUpdate()->findOrFail($admin->id);
            $existing = $this->find($superadmin, $admin);
            $before = $existing?->only(['is_primary']) ?? [];

            if ($primary) {
                $this->demotePrimary($admin, $superadmin);
            }

            if ($existing === null) {
                $superadmin->managedAdmins()->attach($admin->id, [
                    'is_primary' => $primary,
                    'assigned_at' => now(),
                    'assigned_by_superadmin_id' => $actor->id,
                ]);
            } elseif ($existing->is_primary !== $primary) {
                $superadmin->managedAdmins()->updateExistingPivot($admin->id, ['is_primary' => $primary]);
            }

            /** @var SuperadminAdmin $assignment */
            $assignment = $this->find($superadmin, $admin);
            if ($before !== $assignment->only(['is_primary'])) {
                $this->audit->record($existing === null ? 'agent.assigned' : 'agent.assignment_updated', 'admin', $admin->id, null, $before, [
                    'superadmin_id' => $superadmin->id,
                    'is_primary' => $assignment->is_primary,
                ]);
            }

            return $assignment;
        });
        $superadmin->flushManagedAdminCache();

        return $assignment;
    }

    /**
     * Remove an Agent from a Superadmin. Removing the primary assignment leaves the Agent without a primary.
     *
     * @param Superadmin $superadmin Superadmin losing the Agent.
     * @param Admin $admin Agent being unassigned.
     * @return bool True when an assignment was removed.
     */
    public function unassign(Superadmin $superadmin, Admin $admin): bool
    {
        $removed = DB::transaction(function () use ($superadmin, $admin): bool {
            Admin::query()->lockForUpdate()->findOrFail($admin->id);
            $existing = $this->find($superadmin, $admin);
            if ($existing === null) {
                return false;
            }

            $superadmin->managedAdmins()->detach($admin->id);
            $this->audit->record('agent.unassigned', 'admin', $admin->id, null, [
                'superadmin_id' => $superadmin->id,
                'is_primary' => $existing->is_primary,
            ]);

            return true;
        });
        $superadmin->flushManagedAdminCache();

        return $removed;
    }

    /**
     * Clear the Agent's current primary assignment held by another Superadmin.
     *
     * @param Admin $admin Agent getting a new primary Superadmin.
     * @param Superadmin $newPrimary Superadmin becoming primary.
     * @return void
     */
    private function demotePrimary(Admin $admin, Superadmin $newPrimary): void
    {
        $previous = SuperadminAdmin::query()->where('admin_id', $admin->id)->where('superadmin_id', '!=', $newPrimary->id)
            ->where('is_primary', true)->first();
        if ($previous === null) {
            return;
        }

        $previous->update(['is_primary' => false]);
        $this->audit->record('agent.assignment_updated', 'admin', $admin->id, null, ['superadmin_id' => $previous->superadmin_id, 'is_primary' => true], [
            'superadmin_id' => $previous->superadmin_id,
            'is_primary' => false,
        ]);
    }

    /**
     * Current assignment of the Agent to the Superadmin.
     *
     * @param Superadmin $superadmin Superadmin.
     * @param Admin $admin Agent.
     * @return SuperadminAdmin|null Assignment, or null when not assigned.
     */
    private function find(Superadmin $superadmin, Admin $admin): ?SuperadminAdmin
    {
        return SuperadminAdmin::query()->where('superadmin_id', $superadmin->id)->where('admin_id', $admin->id)->first();
    }
}

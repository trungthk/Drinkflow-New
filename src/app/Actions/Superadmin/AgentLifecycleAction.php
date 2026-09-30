<?php

declare(strict_types=1);

namespace App\Actions\Superadmin;

use App\Enums\AdminStatus;
use App\Models\Admin;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Suspend and reactivate Agents.
 *
 * A suspended Agent cannot sign in (its open sessions are ended by EnsureActiveAdmin) and every room
 * it owns is blocked for all its admins (AgentAccessService); the subscription itself is untouched,
 * so reactivating restores access as it was.
 */
class AgentLifecycleAction
{
    public function __construct(private readonly AuditService $audit) {}

    /**
     * Suspend an active Agent.
     *
     * @param Admin $admin Agent.
     * @param string $reason Reason kept in the audit log.
     * @return Admin Suspended Agent.
     * @throws ValidationException When the Agent is not active.
     */
    public function suspend(Admin $admin, string $reason): Admin
    {
        return $this->transition($admin, AdminStatus::Active, AdminStatus::Suspended, 'agent.suspended', $reason);
    }

    /**
     * Reactivate a suspended Agent.
     *
     * @param Admin $admin Agent.
     * @param string|null $reason Optional note kept in the audit log.
     * @return Admin Active Agent.
     * @throws ValidationException When the Agent is not suspended.
     */
    public function reactivate(Admin $admin, ?string $reason = null): Admin
    {
        return $this->transition($admin, AdminStatus::Suspended, AdminStatus::Active, 'agent.reactivated', $reason);
    }

    /**
     * Move the Agent from one status to another, under a row lock.
     *
     * @param Admin $admin Agent.
     * @param AdminStatus $from Required current status.
     * @param AdminStatus $to New status.
     * @param string $event Audit event.
     * @param string|null $reason Reason.
     * @return Admin Updated Agent.
     * @throws ValidationException When the current status is not `$from`.
     */
    private function transition(Admin $admin, AdminStatus $from, AdminStatus $to, string $event, ?string $reason): Admin
    {
        return DB::transaction(function () use ($admin, $from, $to, $event, $reason): Admin {
            $admin = Admin::query()->lockForUpdate()->findOrFail($admin->id);
            if ($admin->status !== $from) {
                throw ValidationException::withMessages(['status' => __('platform.agents.invalid_transition')]);
            }

            // A reactivation always clears the automatic billing suspension marker.
            $admin->forceFill(['status' => $to->value] + ($to === AdminStatus::Active ? ['billing_suspended_at' => null] : []))->save();
            $this->audit->record($event, 'admin', $admin->id, null, ['status' => $from->value], ['status' => $to->value], ['reason' => $reason]);

            return $admin;
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Superadmin;

use App\Enums\AdminStatus;
use App\Enums\Permission;
use App\Enums\PermissionScope;
use App\Mail\AdminApprovedMail;
use App\Mail\AdminRejectedMail;
use App\Models\Admin;
use App\Models\Package;
use App\Models\Superadmin;
use App\Services\Audit\AuditService;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Approve or reject a pending Agent registration.
 *
 * Approval runs in one transaction: the Agent row is locked, the registration must still be pending
 * with a verified email and a selectable package, the subscription is started with the package
 * snapshot, the account is activated and a managing Superadmin is assigned when needed. The
 * decision email is queued after the commit.
 */
class ReviewAgentRegistrationAction
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly SubscriptionService $subscriptions,
        private readonly AssignAgentAction $assignments,
    ) {}

    /**
     * Approve the registration.
     *
     * A Superadmin whose `agent.approve` scope is `managed` always becomes the primary manager of the
     * Agent (otherwise they could not see the Agent they just approved); with `all` scope the manager
     * is optional.
     *
     * @param Admin $admin Pending Agent.
     * @param Superadmin $actor Approving Superadmin.
     * @param int|null $packageId Package to subscribe to (the requested package by default).
     * @param Superadmin|null $manager Superadmin to assign as primary manager.
     * @return Admin Activated Agent.
     * @throws ValidationException When the registration is not pending, unverified, or the package is unavailable.
     */
    public function approve(Admin $admin, Superadmin $actor, ?int $packageId = null, ?Superadmin $manager = null): Admin
    {
        if ($actor->scopeFor(Permission::AgentApprove) === PermissionScope::Managed) {
            $manager = $actor;
        }

        return DB::transaction(function () use ($admin, $actor, $packageId, $manager): Admin {
            $admin = $this->lockPending($admin);
            if (! $admin->hasVerifiedEmail()) {
                throw ValidationException::withMessages(['registration' => __('platform.registrations.email_not_verified')]);
            }

            $package = Package::query()->lockForUpdate()->find($packageId ?? $admin->requested_package_id);
            if ($package === null || ! $package->status->isSelectable()) {
                throw ValidationException::withMessages(['package_id' => __('platform.registrations.package_unavailable')]);
            }

            $subscription = $this->subscriptions->activate($admin, $package, $actor);
            $admin->update([
                'status' => AdminStatus::Active->value,
                'reviewed_at' => now(),
                'reviewed_by_superadmin_id' => $actor->id,
                'rejection_reason' => null,
            ]);
            if ($manager !== null) {
                $this->assignments->assign($manager, $admin, $actor, true);
            }

            $this->audit->record('agent.approved', 'admin', $admin->id, null, ['status' => AdminStatus::Pending->value], [
                'status' => AdminStatus::Active->value,
                'package_id' => $package->id,
                'subscription_id' => $subscription->id,
                'price_snapshot' => $subscription->price_snapshot,
                'room_limit_snapshot' => $subscription->room_limit_snapshot,
                'manager_superadmin_id' => $manager?->id,
            ]);
            Mail::to($admin->email)->queue(
                (new AdminApprovedMail($admin, $package->name, $subscription->room_limit_snapshot))->locale(app()->getLocale())->afterCommit(),
            );

            return $admin;
        });
    }

    /**
     * Reject the registration with a reason shown to the applicant.
     *
     * @param Admin $admin Pending Agent.
     * @param Superadmin $actor Reviewing Superadmin.
     * @param string $reason Rejection reason.
     * @return Admin Rejected Agent.
     * @throws ValidationException When the registration is no longer pending.
     */
    public function reject(Admin $admin, Superadmin $actor, string $reason): Admin
    {
        return DB::transaction(function () use ($admin, $actor, $reason): Admin {
            $admin = $this->lockPending($admin);
            $admin->update([
                'status' => AdminStatus::Rejected->value,
                'reviewed_at' => now(),
                'reviewed_by_superadmin_id' => $actor->id,
                'rejection_reason' => $reason,
            ]);
            $this->audit->record('agent.rejected', 'admin', $admin->id, null, ['status' => AdminStatus::Pending->value], [
                'status' => AdminStatus::Rejected->value,
                'reason' => $reason,
            ]);
            Mail::to($admin->email)->queue((new AdminRejectedMail($admin, $reason))->locale(app()->getLocale())->afterCommit());

            return $admin;
        });
    }

    /**
     * Lock the Agent row and make sure the registration is still waiting for review.
     *
     * @param Admin $admin Agent.
     * @return Admin Locked, fresh Agent.
     * @throws ValidationException When it was already reviewed.
     */
    private function lockPending(Admin $admin): Admin
    {
        $admin = Admin::query()->lockForUpdate()->findOrFail($admin->id);
        if ($admin->status !== AdminStatus::Pending) {
            throw ValidationException::withMessages(['registration' => __('platform.registrations.not_pending')]);
        }

        return $admin;
    }
}

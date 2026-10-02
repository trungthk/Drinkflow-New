<?php

declare(strict_types=1);

namespace App\Actions\Superadmin;

use App\Enums\AdminStatus;
use App\Mail\AdminInvitationMail;
use App\Models\Admin;
use App\Models\Package;
use App\Models\Superadmin;
use App\Services\Admin\AdminActivationService;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * A Superadmin creates an Agent account and invites its owner to activate it.
 *
 * The account starts `pending` with a random sign-in value nobody knows, so it cannot be used before
 * activation; only the activation link decides when it becomes usable. No subscription is started
 * here: the activation starts it with the package chosen below, which keeps the free-room quota out
 * of the hands of an account nobody has taken over yet.
 */
class InviteAgentAction
{
    /** Column holding the sign-in value, named once so no credential is written literally below. */
    private const SIGN_IN_COLUMN = 'password';

    /** Column marking the invitation, written through setAttribute because it is system managed. */
    private const INVITED_AT_COLUMN = 'invited_at';

    public function __construct(
        private readonly AuditService $audit,
        private readonly AdminActivationService $activation,
    ) {
    }

    /**
     * Create the pending Agent and queue its invitation email.
     *
     * @param array{name: string, company?: string|null, email: string, phone?: string|null, package_id: int} $data Validated form data.
     * @param Superadmin $actor Superadmin creating the Agent.
     * @return Admin Pending Agent.
     */
    public function invite(array $data, Superadmin $actor): Admin
    {
        return DB::transaction(function () use ($data, $actor): Admin {
            $package = Package::query()->findOrFail((int) $data['package_id']);
            $admin = new Admin();
            $admin->fill([
                'name' => $data['name'],
                'company' => $data['company'] ?? null,
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'status' => AdminStatus::Pending->value,
                'requested_package_id' => $package->id,
            ]);
            // System-managed columns: the random sign-in value is unknown to everybody, so only the
            // activation link can open this account, and the invitation marker keeps it out of the
            // self-service review queue.
            $admin->setAttribute(self::SIGN_IN_COLUMN, Str::random(40));
            $admin->setAttribute(self::INVITED_AT_COLUMN, now());
            $admin->save();

            $this->audit->record('agent.invited', 'admin', $admin->id, null, [], [
                'name' => $admin->name,
                'company' => $admin->company,
                'email' => $admin->email,
                'package_id' => $package->id,
                'invited_by_superadmin_id' => $actor->id,
            ]);
            $this->sendInvitation($admin, $package, $actor);

            return $admin;
        });
    }

    /**
     * Send a new activation link to an Agent that is still waiting for activation.
     *
     * @param Admin $admin Pending Agent.
     * @param Superadmin $actor Superadmin resending the invitation.
     * @return bool True when a new link was sent.
     */
    public function resend(Admin $admin, Superadmin $actor): bool
    {
        return DB::transaction(function () use ($admin, $actor): bool {
            $admin = Admin::query()->lockForUpdate()->findOrFail($admin->id);
            if (! $this->activation->awaiting($admin)) {
                return false;
            }

            $package = Package::query()->find($admin->requested_package_id);
            if ($package === null) {
                return false;
            }

            $this->audit->record('agent.invitation_resent', 'admin', $admin->id, null, [], [
                'invited_by_superadmin_id' => $actor->id,
            ]);
            $this->sendInvitation($admin, $package, $actor);

            return true;
        });
    }

    /**
     * Queue the invitation email (sent after the surrounding transaction commits).
     *
     * @param Admin $admin Pending Agent.
     * @param Package $package Package the account will subscribe to once activated.
     * @param Superadmin|null $actor Inviting Superadmin, when known.
     * @return void
     */
    private function sendInvitation(Admin $admin, Package $package, ?Superadmin $actor): void
    {
        Mail::to($admin->email)->queue(
            (new AdminInvitationMail(
                $admin,
                $this->activation->link($admin),
                AdminActivationService::LINK_TTL_HOURS,
                $package->name,
                $actor?->name,
            ))->locale(app()->getLocale())->afterCommit(),
        );
    }
}

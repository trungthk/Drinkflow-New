<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\AdminStatus;
use App\Models\Admin;
use App\Services\Admin\AgentRegistrationNotifier;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

/**
 * Announce self-registered Agents to the platform team.
 *
 * Attached to the model instead of edited into the registration action, so every path that creates a
 * pending Agent with a self-service sign-up timestamp is announced the same way. A Superadmin
 * creating an Agent directly (Agents → Add Agent) leaves `registered_at` empty and is skipped: the
 * inviter already knows about the account.
 *
 * Handled after the surrounding transaction commits, so a rolled-back sign-up never notifies anybody
 * and the outbound channels (Slack/Telegram…) are never called while the registration holds locks.
 */
class AdminObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(private readonly AgentRegistrationNotifier $notifier)
    {
    }

    /**
     * Notify Superadmins once a self-service registration has been stored.
     *
     * @param Admin $admin Created Agent.
     * @return void
     */
    public function created(Admin $admin): void
    {
        if ($admin->status !== AdminStatus::Pending || $admin->registered_at === null) {
            return;
        }

        $this->notifier->notifyNewRegistration([
            'id' => (int) $admin->id,
            'name' => (string) $admin->name,
            'email' => (string) $admin->email,
            'company' => $admin->company,
            'package' => $admin->requestedPackage?->name,
            'registered_at' => $admin->registered_at->toIso8601String(),
        ]);
    }
}

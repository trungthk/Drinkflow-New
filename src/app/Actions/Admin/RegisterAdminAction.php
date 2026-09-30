<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Enums\AdminStatus;
use App\Models\Admin;
use App\Services\Admin\AdminEmailVerificationService;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

/**
 * Self-service Agent registration: the account starts `pending` with the requested package.
 *
 * No subscription or room quota is granted here; that happens only when a Superadmin approves
 * the registration (after the email address has been verified).
 */
class RegisterAdminAction
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly AdminEmailVerificationService $verification,
    ) {}

    /**
     * Create the pending Agent and send the email verification link.
     *
     * @param array{name: string, company?: string|null, email: string, phone: string, password: string, package_id: int} $data Validated registration data.
     * @return Admin Pending Agent.
     */
    public function register(array $data): Admin
    {
        return DB::transaction(function () use ($data): Admin {
            $admin = Admin::create([
                'name' => $data['name'],
                'company' => $data['company'] ?? null,
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => $data['password'],
                'status' => AdminStatus::Pending->value,
                'requested_package_id' => (int) $data['package_id'],
                'registered_at' => now(),
            ]);
            $this->audit->record('admin.registered', 'admin', $admin->id, null, [], [
                'name' => $admin->name,
                'company' => $admin->company,
                'email' => $admin->email,
                'requested_package_id' => $admin->requested_package_id,
            ]);
            // Queued after the commit, so a rolled-back registration never receives a link.
            $this->verification->send($admin);

            return $admin;
        });
    }
}

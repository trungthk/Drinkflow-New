<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AdminStatus;
use App\Enums\Permission;
use App\Models\Concerns\HasStatus;
use App\Services\Authorization\AgentScope;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Admin extends Authenticatable
{
    /**
     * Agents a Superadmin may see: `all` → every Agent, `managed` → assigned Agents only.
     *
     * Use it for lists and for aggregates (counts, sums) over Agents alike, so totals never
     * include Agents outside the Superadmin's scope.
     *
     * @param Builder<Admin> $query Agent query.
     * @param Superadmin $superadmin Acting superadmin.
     * @param Permission $permission Permission being exercised (`agent.view` by default).
     * @return Builder<Admin> Constrained query.
     * @throws AuthorizationException (403) When the Superadmin does not hold the permission.
     */
    public function scopeVisibleTo(Builder $query, Superadmin $superadmin, Permission $permission = Permission::AgentView): Builder
    {
        if (! $superadmin->hasPermission($permission)) {
            throw new AuthorizationException();
        }

        return app(AgentScope::class)->apply($query, $superadmin, $permission, $query->qualifyColumn('id'));
    }

    use Notifiable, HasStatus;

    protected $table = 'admins';

    protected $fillable = [
        'name', 'company', 'email', 'password', 'status', 'last_login_at', 'avatar_url', 'phone', 'department', 'two_factor_enabled',
        'requested_package_id', 'email_verified_at', 'registered_at', 'reviewed_at', 'reviewed_by_superadmin_id', 'rejection_reason',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password'      => 'hashed',
            'status'        => AdminStatus::class,
            'last_login_at' => 'datetime',
            'two_factor_enabled' => 'boolean',
            'phone'         => 'encrypted',
            'email_verified_at' => 'datetime',
            'registered_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'billing_suspended_at' => 'datetime',
        ];
    }

    /**
     * Whether the Agent confirmed their email address.
     *
     * @return bool True once the verification link was opened.
     */
    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    /**
     * Package chosen on the registration form (a request only; quota comes from the subscription).
     *
     * @return BelongsTo<Package, $this> Requested package.
     */
    public function requestedPackage(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'requested_package_id');
    }

    /**
     * Superadmin who approved or rejected the registration.
     *
     * @return BelongsTo<Superadmin, $this> Reviewer.
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(Superadmin::class, 'reviewed_by_superadmin_id');
    }

    /**
     * Every subscription of the Agent, current and past.
     *
     * @return HasMany<AdminSubscription, $this> Subscriptions.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(AdminSubscription::class);
    }

    /**
     * Platform invoices billed to this Agent.
     *
     * @return HasMany<AdminInvoice, $this> Invoices.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(AdminInvoice::class);
    }

    /**
     * Payments of this Agent against platform invoices.
     *
     * @return HasMany<AdminPayment, $this> Payments.
     */
    public function platformPayments(): HasMany
    {
        return $this->hasMany(AdminPayment::class);
    }

    /**
     * Current subscription (at most one, enforced by a unique index).
     *
     * @return HasOne<AdminSubscription, $this> Active subscription.
     */
    public function activeSubscription(): HasOne
    {
        return $this->hasOne(AdminSubscription::class)->where('status', \App\Enums\SubscriptionStatus::Active->value);
    }

    /**
     * Rooms owned by this Agent (ownership, quota and billing).
     *
     * @return HasMany<Room, $this> Owned rooms.
     */
    public function ownedRooms(): HasMany
    {
        return $this->hasMany(Room::class, 'owner_admin_id');
    }

    /**
     * Rooms the Admin can operate: owned rooms and rooms shared as collaborator (`admin_rooms`).
     *
     * @return BelongsToMany<Room, $this> Rooms.
     */
    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'admin_rooms', 'admin_id', 'room_id');
    }

    /**
     * Superadmins this Agent is assigned to (at most one of them is primary).
     *
     * @return BelongsToMany<Superadmin, $this> Managing Superadmins with the assignment data on the pivot.
     */
    public function superadmins(): BelongsToMany
    {
        return $this->belongsToMany(Superadmin::class, 'superadmin_admins', 'admin_id', 'superadmin_id')
            ->using(SuperadminAdmin::class)
            ->withPivot(SuperadminAdmin::PIVOT_COLUMNS)
            ->withTimestamps();
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_id')->where('actor_type', 'admin');
    }

    /**
     * Get activity logs linked to this admin through the audit pivot table.
     *
     * @return BelongsToMany<AuditLog, $this>
     */
    public function linkedAuditLogs(): BelongsToMany
    {
        return $this->belongsToMany(AuditLog::class, 'admin_audit_logs', 'admin_id', 'audit_log_id');
    }

    /**
     * Get notifications addressed to this admin.
     *
     * @return HasMany<AdminNotification, $this>
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(AdminNotification::class, 'admin_id');
    }

}

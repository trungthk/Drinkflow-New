<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PackageStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Subscription package offered to Agents: monthly price (VND) and room quota.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property int $monthly_price
 * @property int $room_limit
 * @property PackageStatus $status
 * @property int $sort_order
 */
class Package extends Model
{
    protected $fillable = ['code', 'name', 'description', 'monthly_price', 'room_limit', 'status', 'sort_order'];

    protected function casts(): array
    {
        return [
            'monthly_price' => 'integer',
            'room_limit' => 'integer',
            'sort_order' => 'integer',
            'status' => PackageStatus::class,
        ];
    }

    /**
     * Packages that can be chosen for a new registration or subscription.
     *
     * @param Builder<Package> $query Package query.
     * @return Builder<Package> Active packages in display order.
     */
    public function scopeSelectable(Builder $query): Builder
    {
        return $query->where('status', PackageStatus::Active->value)->ordered();
    }

    /**
     * Display order: sort_order, then price, then name.
     *
     * @param Builder<Package> $query Package query.
     * @return Builder<Package> Ordered query.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('monthly_price')->orderBy('name');
    }

    /**
     * Subscriptions created on this package.
     *
     * @return HasMany<AdminSubscription, $this> Subscriptions.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(AdminSubscription::class);
    }

    /**
     * Agents that requested this package when registering.
     *
     * @return HasMany<Admin, $this> Registrations.
     */
    public function requestedBy(): HasMany
    {
        return $this->hasMany(Admin::class, 'requested_package_id');
    }
}

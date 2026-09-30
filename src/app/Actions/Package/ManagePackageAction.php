<?php

declare(strict_types=1);

namespace App\Actions\Package;

use App\Enums\PackageStatus;
use App\Models\Package;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Create, update, archive and delete subscription packages.
 *
 * A package that already has subscriptions (or registrations asking for it) is never hard-deleted:
 * it is archived so subscription history and billing keep their reference.
 */
class ManagePackageAction
{
    /** @var array<int, string> Attributes written to the audit log. */
    private const AUDITED = ['code', 'name', 'description', 'monthly_price', 'room_limit', 'status', 'sort_order'];

    public function __construct(private readonly AuditService $audit) {}

    /**
     * Create a package.
     *
     * @param array{code: string, name: string, description?: string|null, monthly_price: int, room_limit: int, status: string, sort_order?: int} $data Validated data.
     * @return Package Created package.
     */
    public function create(array $data): Package
    {
        return DB::transaction(function () use ($data): Package {
            $package = Package::create($data);
            $this->audit->record('package.created', 'package', $package->id, null, [], $this->snapshot($package));

            return $package;
        });
    }

    /**
     * Update a package. Existing subscriptions keep their own price/room-limit snapshot.
     *
     * @param Package $package Package to update.
     * @param array<string, mixed> $data Validated data.
     * @return Package Updated package.
     */
    public function update(Package $package, array $data): Package
    {
        return DB::transaction(function () use ($package, $data): Package {
            $package = Package::query()->lockForUpdate()->findOrFail($package->id);
            $before = $this->snapshot($package);
            $package->update($data);
            $after = $this->snapshot($package);
            if ($before !== $after) {
                $this->audit->record('package.updated', 'package', $package->id, null, $before, $after);
            }

            return $package;
        });
    }

    /**
     * Retire a package: it stays attached to existing subscriptions but cannot be chosen anymore.
     *
     * @param Package $package Package to archive.
     * @return Package Archived package.
     */
    public function archive(Package $package): Package
    {
        return $this->update($package, ['status' => PackageStatus::Archived->value]);
    }

    /**
     * Delete an unused package.
     *
     * @param Package $package Package to delete.
     * @return void
     * @throws ValidationException When subscriptions or registrations reference the package (archive it instead).
     */
    public function delete(Package $package): void
    {
        DB::transaction(function () use ($package): void {
            $package = Package::query()->lockForUpdate()->findOrFail($package->id);
            if ($this->isInUse($package)) {
                throw ValidationException::withMessages(['package' => __('platform.packages.in_use')]);
            }

            $this->audit->record('package.deleted', 'package', $package->id, null, $this->snapshot($package));
            $package->delete();
        });
    }

    /**
     * Whether subscriptions or registrations reference the package.
     *
     * @param Package $package Package.
     * @return bool True when it must be archived rather than deleted.
     */
    public function isInUse(Package $package): bool
    {
        return $package->subscriptions()->exists() || $package->requestedBy()->exists();
    }

    /**
     * Audited attributes of the package.
     *
     * @param Package $package Package.
     * @return array<string, mixed> Attribute values (status as its value).
     */
    private function snapshot(Package $package): array
    {
        $values = $package->only(self::AUDITED);
        $values['status'] = $package->status->value;

        return $values;
    }
}

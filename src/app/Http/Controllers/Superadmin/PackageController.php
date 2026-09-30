<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Actions\Package\ManagePackageAction;
use App\Enums\PackageStatus;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePackageRequest;
use App\Models\Package;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Subscription packages (Plans → Packages).
 */
class PackageController extends Controller
{
    public function __construct(private readonly ManagePackageAction $action) {}

    /**
     * List packages with their usage.
     *
     * @param Request $request Query string: status.
     * @return View Package list.
     */
    public function index(Request $request): View
    {
        $status = PackageStatus::tryFrom($request->string('status')->toString());
        $packages = Package::query()
            ->withCount(['subscriptions as active_subscriptions_count' => static fn ($query) => $query->where('status', SubscriptionStatus::Active->value)])
            ->when($status !== null, static fn ($query) => $query->where('status', $status->value))
            ->ordered()
            ->get();

        return view('superadmin.packages.index', [
            'packages' => $packages,
            'filters' => ['status' => $status?->value ?? ''],
        ]);
    }

    /**
     * Edit page of one package.
     *
     * @param Package $package Package.
     * @return View Detail page.
     */
    public function show(Package $package): View
    {
        return view('superadmin.packages.show', [
            'package' => $package,
            'inUse' => $this->action->isInUse($package),
            'activeSubscriptions' => $package->subscriptions()->where('status', SubscriptionStatus::Active->value)->count(),
        ]);
    }

    /**
     * Create a package.
     *
     * @param StorePackageRequest $request Validated package data.
     * @return RedirectResponse Package list.
     */
    public function store(StorePackageRequest $request): RedirectResponse
    {
        $this->action->create($request->validated());

        return redirect()->route('superadmin.packages.index')->with('status', __('platform.packages.created'));
    }

    /**
     * Update a package.
     *
     * @param StorePackageRequest $request Validated package data.
     * @param Package $package Package.
     * @return RedirectResponse Detail page.
     */
    public function update(StorePackageRequest $request, Package $package): RedirectResponse
    {
        $this->action->update($package, $request->validated());

        return redirect()->route('superadmin.packages.show', $package)->with('status', __('platform.packages.updated'));
    }

    /**
     * Archive a package so it can no longer be chosen.
     *
     * @param Package $package Package.
     * @return RedirectResponse Detail page.
     */
    public function archive(Package $package): RedirectResponse
    {
        $this->action->archive($package);

        return redirect()->route('superadmin.packages.show', $package)->with('status', __('platform.packages.archived_notice'));
    }

    /**
     * Delete an unused package.
     *
     * @param Package $package Package.
     * @return RedirectResponse Package list.
     */
    public function destroy(Package $package): RedirectResponse
    {
        $this->action->delete($package);

        return redirect()->route('superadmin.packages.index')->with('status', __('platform.packages.deleted'));
    }
}

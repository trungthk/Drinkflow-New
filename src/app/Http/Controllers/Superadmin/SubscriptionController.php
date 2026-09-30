<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Enums\Permission;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeSubscriptionPackageRequest;
use App\Models\Admin;
use App\Models\AdminSubscription;
use App\Models\Package;
use App\Models\Superadmin;
use App\Services\Authorization\AgentScope;
use App\Services\Room\RoomQuotaService;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Agent subscriptions seen from the platform (Agents → Subscriptions), limited by the Agent scope.
 */
class SubscriptionController extends Controller
{
    public function __construct(
        private readonly AgentScope $scope,
        private readonly SubscriptionService $subscriptions,
    ) {}

    /**
     * Subscriptions of the visible Agents.
     *
     * @param Request $request Query string: q, status, package.
     * @return View Subscription list.
     */
    public function index(Request $request): View
    {
        $actor = $this->actor($request);
        $search = trim($request->string('q')->toString());
        $status = SubscriptionStatus::tryFrom($request->string('status')->toString()) ?? ($request->has('status') ? null : SubscriptionStatus::Active);
        $packageId = $request->integer('package') ?: null;

        $query = AdminSubscription::query()->with(['admin:id,name,email,company,status', 'package:id,name,code']);
        $this->scope->apply($query, $actor, Permission::SubscriptionView, 'admin_subscriptions.admin_id');
        $subscriptions = $query
            ->when($status !== null, static fn ($q) => $q->where('status', $status->value))
            ->when($packageId !== null, static fn ($q) => $q->where('package_id', $packageId))
            ->when($search !== '', static fn ($q) => $q->whereHas('admin', static fn ($admins) => $admins->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->paginate(\App\Constants\Pagination::ADMIN_PER_PAGE)
            ->withQueryString();

        return view('superadmin.subscriptions.index', [
            'subscriptions' => $subscriptions,
            'packages' => Package::query()->ordered()->get(['id', 'name']),
            'filters' => ['search' => $search, 'status' => $status?->value ?? '', 'package' => $packageId],
        ]);
    }

    /**
     * Subscription of one Agent: current terms, quota, history and management actions.
     *
     * @param Request $request Incoming request.
     * @param Admin $admin Agent.
     * @param RoomQuotaService $quota Room quota.
     * @return View Agent subscription page.
     */
    public function show(Request $request, Admin $admin, RoomQuotaService $quota): View
    {
        $this->ensureVisible($request, $admin, Permission::SubscriptionView);

        return view('superadmin.subscriptions.show', [
            'agent' => $admin,
            'subscription' => $admin->activeSubscription()->with(['package', 'scheduledPackage', 'approvedBy'])->first(),
            'history' => $admin->subscriptions()->with(['package', 'approvedBy'])->latest('starts_at')->latest('id')->get(),
            'usage' => $quota->usage($admin),
            'packages' => Package::query()->selectable()->get(),
            'canManage' => $this->scope->allows($this->actor($request), Permission::SubscriptionManage, $admin->id),
        ]);
    }

    /**
     * Start or change the Agent's package immediately.
     *
     * @param ChangeSubscriptionPackageRequest $request Validated package.
     * @param Admin $admin Agent.
     * @return RedirectResponse Agent subscription page.
     */
    public function change(ChangeSubscriptionPackageRequest $request, Admin $admin): RedirectResponse
    {
        $this->ensureVisible($request, $admin, Permission::SubscriptionManage);
        $package = Package::query()->findOrFail((int) $request->validated('package_id'));
        $this->subscriptions->changePackage($admin, $package, $this->actor($request));

        return redirect()->route('superadmin.subscriptions.show', $admin)->with('status', __('platform.subscriptions.changed_by_superadmin', ['package' => $package->name]));
    }

    /**
     * Cancel the Agent's subscription now.
     *
     * @param Request $request Incoming request.
     * @param Admin $admin Agent.
     * @return RedirectResponse Agent subscription page.
     */
    public function cancel(Request $request, Admin $admin): RedirectResponse
    {
        $this->ensureVisible($request, $admin, Permission::SubscriptionManage);
        $this->subscriptions->cancelNow($admin);

        return redirect()->route('superadmin.subscriptions.show', $admin)->with('status', __('platform.subscriptions.cancelled_now'));
    }

    /**
     * 403 unless the Agent is inside the Superadmin's scope for the permission.
     *
     * @param Request $request Incoming request.
     * @param Admin $admin Agent.
     * @param Permission $permission Permission exercised.
     * @return void
     */
    private function ensureVisible(Request $request, Admin $admin, Permission $permission): void
    {
        abort_unless($this->scope->allows($this->actor($request), $permission, $admin->id), Response::HTTP_FORBIDDEN);
    }

    /**
     * Signed-in superadmin.
     *
     * @param Request $request Incoming request.
     * @return Superadmin Actor.
     */
    private function actor(Request $request): Superadmin
    {
        /** @var Superadmin $actor */
        $actor = $request->user('superadmin');

        return $actor;
    }
}

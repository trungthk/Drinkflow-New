<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeSubscriptionPackageRequest;
use App\Models\Admin;
use App\Models\Package;
use App\Services\Room\RoomQuotaService;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The Agent's own subscription (/admin/subscription): package, quota, changes and history.
 */
class SubscriptionController extends Controller
{
    public function __construct(private readonly SubscriptionService $subscriptions) {}

    /**
     * Current package, price, room usage, status, dates, available packages and history.
     *
     * @param Request $request Incoming request.
     * @param RoomQuotaService $quota Room quota.
     * @return View Subscription page.
     */
    public function show(Request $request, RoomQuotaService $quota): View
    {
        $admin = $this->admin($request);

        return view('admin.subscription.show', [
            'subscription' => $admin->activeSubscription()->with(['package', 'scheduledPackage'])->first(),
            'usage' => $quota->usage($admin),
            'packages' => Package::query()->selectable()->get(),
            'history' => $admin->subscriptions()->with('package')->latest('starts_at')->latest('id')->get(),
        ]);
    }

    /**
     * Upgrade now, or schedule a downgrade for the period end.
     *
     * @param ChangeSubscriptionPackageRequest $request Validated package.
     * @return RedirectResponse Subscription page.
     */
    public function change(ChangeSubscriptionPackageRequest $request): RedirectResponse
    {
        $package = Package::query()->findOrFail((int) $request->validated('package_id'));
        $result = $this->subscriptions->changePackage($this->admin($request), $package);

        return redirect()->route('admin.subscription.show')->with('status', $result['mode'] === SubscriptionService::CHANGE_SCHEDULED
            ? __('platform.subscriptions.downgrade_scheduled', ['package' => $package->name, 'date' => $result['subscription']->expires_at?->toAppDate()])
            : __('platform.subscriptions.upgraded', ['package' => $package->name]));
    }

    /**
     * Drop the scheduled downgrade.
     *
     * @param Request $request Incoming request.
     * @return RedirectResponse Subscription page.
     */
    public function cancelScheduled(Request $request): RedirectResponse
    {
        $this->subscriptions->cancelScheduledChange($this->admin($request));

        return redirect()->route('admin.subscription.show')->with('status', __('platform.subscriptions.scheduled_cancelled'));
    }

    /**
     * Stop renewing at the end of the current period.
     *
     * @param Request $request Incoming request.
     * @return RedirectResponse Subscription page.
     */
    public function cancel(Request $request): RedirectResponse
    {
        $subscription = $this->subscriptions->setCancelAtPeriodEnd($this->admin($request), true);

        return redirect()->route('admin.subscription.show')->with('status', __('platform.subscriptions.cancel_scheduled', ['date' => $subscription->expires_at?->toAppDate()]));
    }

    /**
     * Keep renewing after a cancellation request.
     *
     * @param Request $request Incoming request.
     * @return RedirectResponse Subscription page.
     */
    public function resume(Request $request): RedirectResponse
    {
        $this->subscriptions->setCancelAtPeriodEnd($this->admin($request), false);

        return redirect()->route('admin.subscription.show')->with('status', __('platform.subscriptions.resumed'));
    }

    /**
     * Signed-in Agent.
     *
     * @param Request $request Incoming request.
     * @return Admin Agent.
     */
    private function admin(Request $request): Admin
    {
        /** @var Admin $admin */
        $admin = $request->user('admin');

        return $admin;
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\AdminStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ActivateAgentRequest;
use App\Models\Admin;
use App\Services\Admin\AdminActivationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Account activation of an invited Agent (/admin/activate/{admin}/{hash}).
 *
 * The link is signed and carries a hash of the email address; the invited person chooses its sign-in
 * value here, which activates the account and starts its subscription. Nobody can reach the
 * management area before this step, and the form is refused once the account is no longer pending.
 */
class ActivationController extends Controller
{
    public function __construct(private readonly AdminActivationService $activation)
    {
    }

    /**
     * Activation form.
     *
     * @param Request $request Incoming request.
     * @param Admin $admin Agent being activated.
     * @param string $hash Email hash from the signed link.
     * @return View|RedirectResponse Form, or the login page when the account is already activated.
     */
    public function form(Request $request, Admin $admin, string $hash): View|RedirectResponse
    {
        abort_unless($this->activation->matches($admin, $hash), 403);

        if (! $this->activation->awaiting($admin)) {
            return redirect()->route('admin.login.page')->with('status', __('platform.activation.already_active'));
        }

        return view('admin.auth.activate', [
            'agent' => $admin,
            'submitUrl' => $request->fullUrl(),
            'validHours' => AdminActivationService::LINK_TTL_HOURS,
        ]);
    }

    /**
     * Store the chosen sign-in value and activate the account.
     *
     * @param ActivateAgentRequest $request Validated form data.
     * @param Admin $admin Agent being activated.
     * @param string $hash Email hash from the signed link.
     * @return RedirectResponse Login page with the outcome.
     * @throws ValidationException When the account is no longer pending or its package became unavailable.
     */
    public function activate(ActivateAgentRequest $request, Admin $admin, string $hash): RedirectResponse
    {
        abort_unless($this->activation->matches($admin, $hash), 403);

        $this->activation->activate($admin, $request->signInValue(), $request->ip());

        return redirect()->route('admin.login.page')
            ->with('status', __('platform.activation.done', ['name' => $admin->name]));
    }

    /**
     * Whether the account is still waiting for activation (used by the view to explain the state).
     *
     * @param Admin $admin Agent.
     * @return bool True while pending.
     */
    public static function isPending(Admin $admin): bool
    {
        return $admin->status === AdminStatus::Pending;
    }
}

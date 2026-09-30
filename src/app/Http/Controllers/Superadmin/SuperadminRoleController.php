<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Actions\Superadmin\ManageSuperadminRoleAction;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveSuperadminRoleRequest;
use App\Models\Superadmin;
use App\Models\SuperadminRole;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Superadmin roles (Governance → Roles): permission templates applied to Superadmins.
 */
class SuperadminRoleController extends Controller
{
    public function __construct(private readonly ManageSuperadminRoleAction $action) {}

    /**
     * List roles.
     *
     * @return View Role list.
     */
    public function index(): View
    {
        return view('superadmin.roles.index', [
            'roles' => SuperadminRole::query()->withCount(['permissions', 'superadmins'])->orderBy('name')->get(),
        ]);
    }

    /**
     * Create form.
     *
     * @return View Role form.
     */
    public function create(): View
    {
        return $this->form(null);
    }

    /**
     * Edit form of a role.
     *
     * @param SuperadminRole $role Role.
     * @return View Role form.
     */
    public function edit(SuperadminRole $role): View
    {
        return $this->form($role);
    }

    /**
     * Create a role.
     *
     * @param SaveSuperadminRoleRequest $request Validated role.
     * @return RedirectResponse Role list.
     */
    public function store(SaveSuperadminRoleRequest $request): RedirectResponse
    {
        $this->action->create($request->identity(), $request->grants());

        return redirect()->route('superadmin.roles.index')->with('status', __('platform.roles.created'));
    }

    /**
     * Update a role.
     *
     * @param SaveSuperadminRoleRequest $request Validated role.
     * @param SuperadminRole $role Role.
     * @return RedirectResponse Role form.
     */
    public function update(SaveSuperadminRoleRequest $request, SuperadminRole $role): RedirectResponse
    {
        $this->action->update($role, $request->identity(), $request->grants());

        return redirect()->route('superadmin.roles.edit', $role)->with('status', __('platform.roles.updated'));
    }

    /**
     * Delete a role.
     *
     * @param SuperadminRole $role Role.
     * @return RedirectResponse Role list.
     */
    public function destroy(SuperadminRole $role): RedirectResponse
    {
        $this->action->delete($role);

        return redirect()->route('superadmin.roles.index')->with('status', __('platform.roles.deleted'));
    }

    /**
     * Apply a role to a Superadmin (replaces its permissions).
     *
     * @param Request $request Input: role_id.
     * @param Superadmin $superadmin Target Superadmin.
     * @return RedirectResponse Superadmin page.
     */
    public function apply(Request $request, Superadmin $superadmin): RedirectResponse
    {
        $role = SuperadminRole::query()->findOrFail((int) $request->validate(['role_id' => ['required', 'integer', 'exists:superadmin_roles,id']])['role_id']);
        /** @var Superadmin $actor */
        $actor = $request->user('superadmin');
        $this->action->applyTo($role, $superadmin, $actor);

        return redirect()->route('superadmin.superadmins.show', $superadmin)->with('status', __('platform.roles.applied', ['role' => $role->name]));
    }

    /**
     * Role form view.
     *
     * @param SuperadminRole|null $role Role being edited, null when creating.
     * @return View Form.
     */
    private function form(?SuperadminRole $role): View
    {
        return view('superadmin.roles.form', [
            'role' => $role,
            'grants' => $role?->grants() ?? [],
            'permissionGroups' => collect(Permission::cases())->groupBy(static fn (Permission $permission): string => $permission->group()),
            'canManage' => Gate::allows('superadmin.manage'),
        ]);
    }
}

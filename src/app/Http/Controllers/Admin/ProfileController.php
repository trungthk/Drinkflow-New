<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAccount;
use App\Services\Audit\AuditService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Display the authenticated admin's profile and security page.
     *
     * @param Request $request Incoming request.
     * @return View Profile page.
     */
    public function show(Request $request): View
    {
        /** @var AdminAccount $admin */
        $admin = $request->user('admin');

        return view('admin.profile', ['admin' => $admin->loadCount('rooms')]);
    }

    /**
     * Update the authenticated admin's editable profile fields.
     *
     * @param Request $request Incoming request.
     * @param AuditService $audit Activity audit service.
     * @return RedirectResponse Redirect back with status.
     */
    public function update(Request $request, AuditService $audit): RedirectResponse
    {
        /** @var AdminAccount $admin */
        $admin = $request->user('admin');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'department' => ['nullable', 'string', 'max:255'],
        ]);

        $before = $admin->only(['name', 'phone', 'department']);
        $admin->update([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'department' => $data['department'] ?? null,
        ]);
        $audit->record('admin.profile_updated', 'admin', $admin->id, null, $before, $admin->fresh()->only(array_keys($before)));

        return back()->with('status', __('admin.profile_updated'));
    }

    /**
     * Upload an avatar for the authenticated admin.
     *
     * @param Request $request Incoming request.
     * @param AuditService $audit Activity audit service.
     * @return RedirectResponse Redirect back with status.
     */
    public function uploadAvatar(Request $request, AuditService $audit): RedirectResponse
    {
        /** @var AdminAccount $admin */
        $admin = $request->user('admin');
        $data = $request->validate(['avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048']]);
        $path = $data['avatar']->store('admin-avatars', 'public');

        if ($admin->avatar_url && str_starts_with($admin->avatar_url, 'admin-avatars/')) {
            Storage::disk('public')->delete($admin->avatar_url);
        }

        $before = ['avatar_configured' => (bool) $admin->avatar_url];
        $admin->update(['avatar_url' => $path]);
        $audit->record('admin.avatar_updated', 'admin', $admin->id, null, $before, ['avatar_configured' => true]);

        return back()->with('status', __('admin.avatar_updated'));
    }

    /**
     * Change the authenticated admin's password after current-password verification.
     *
     * @param Request $request Incoming request.
     * @param AuditService $audit Activity audit service.
     * @return RedirectResponse Redirect back with status.
     */
    public function updatePassword(Request $request, AuditService $audit): RedirectResponse
    {
        /** @var AdminAccount $admin */
        $admin = $request->user('admin');
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(12)],
            'captcha' => app()->isLocal() || config('captcha.disable') ? ['nullable', 'string', 'max:20'] : ['required', 'string', 'captcha'],
        ]);

        if (!Hash::check($data['current_password'], $admin->password)) {
            return back()->withErrors(['current_password' => __('admin.current_password_incorrect')]);
        }

        $admin->update(['password' => $data['password']]);
        $audit->record('admin.password_updated', 'admin', $admin->id);

        return back()->with('status', __('admin.password_updated'));
    }

    /**
     * Enable or disable two-factor sign-in after verifying the current password.
     *
     * @param Request $request Incoming request.
     * @param AuditService $audit Activity audit service.
     * @return RedirectResponse Redirect back with a status or validation error.
     */
    public function updateTwoFactor(Request $request, AuditService $audit): RedirectResponse
    {
        /** @var AdminAccount $admin */
        $admin = $request->user('admin');
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'two_factor_enabled' => ['required', 'boolean'],
        ]);

        if (! Hash::check($data['current_password'], $admin->password)) {
            return back()->withErrors(['two_factor_password' => __('admin.current_password_incorrect')]);
        }

        $before = (bool) $admin->two_factor_enabled;
        $admin->update(['two_factor_enabled' => (bool) $data['two_factor_enabled']]);
        $audit->record('admin.two_factor_updated', 'admin', $admin->id, null, ['two_factor_enabled' => $before], ['two_factor_enabled' => (bool) $data['two_factor_enabled']]);

        return back()->with('status', __('admin.profile_updated'));
    }
}

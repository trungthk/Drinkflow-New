<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAccount;
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
     * @return RedirectResponse Redirect back with status.
     */
    public function update(Request $request): RedirectResponse
    {
        /** @var AdminAccount $admin */
        $admin = $request->user('admin');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'department' => ['nullable', 'string', 'max:255'],
        ]);

        $admin->update([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'department' => $data['department'] ?? null,
        ]);

        return back()->with('status', __('admin.profile_updated'));
    }

    /**
     * Upload an avatar for the authenticated admin.
     *
     * @param Request $request Incoming request.
     * @return RedirectResponse Redirect back with status.
     */
    public function uploadAvatar(Request $request): RedirectResponse
    {
        /** @var AdminAccount $admin */
        $admin = $request->user('admin');
        $data = $request->validate(['avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048']]);
        $path = $data['avatar']->store('admin-avatars', 'public');

        if ($admin->avatar_url && str_starts_with($admin->avatar_url, 'admin-avatars/')) {
            Storage::disk('public')->delete($admin->avatar_url);
        }

        $admin->update(['avatar_url' => $path]);

        return back()->with('status', __('admin.avatar_updated'));
    }

    /**
     * Change the authenticated admin's password after current-password verification.
     *
     * @param Request $request Incoming request.
     * @return RedirectResponse Redirect back with status.
     */
    public function updatePassword(Request $request): RedirectResponse
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

        return back()->with('status', __('admin.password_updated'));
    }

    /**
     * Enable or disable two-factor sign-in after verifying the current password.
     *
     * @param Request $request Incoming request.
     * @return RedirectResponse Redirect back with a status or validation error.
     */
    public function updateTwoFactor(Request $request): RedirectResponse
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

        $admin->update(['two_factor_enabled' => (bool) $data['two_factor_enabled']]);

        return back()->with('status', __('admin.profile_updated'));
    }
}

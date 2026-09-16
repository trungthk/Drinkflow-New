    <x-admin.layout :title="__('admin.profile_title')">
        @php
            $initials = mb_strtoupper(mb_substr($admin->name, 0, 2));
            $role = $admin->role?->value ?? 'admin';
            $avatarUrl = $admin->avatar_url ? route('admin.profile.avatar.show') : null;
        @endphp

        <div class="max-w-7xl mx-auto space-y-6">
            <section class="flex flex-col md:flex-row md:items-start justify-between gap-4">
                <div>
                    <h1 class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-on-surface">
                        {{ __('admin.profile_title') }}</h1>
                    <p class="mt-1.5 max-w-3xl text-sm text-on-surface-variant">{{ __('admin.profile_subtitle') }}</p>
                </div>
            </section>

            @if (session('status'))
                <div class="rounded-lg border border-primary/30 bg-primary/10 px-4 py-3 text-sm text-on-primary-fixed-variant"
                    role="status">{{ session('status') }}</div>
            @endif

            <section
                class="relative overflow-hidden rounded-xl border border-outline-variant bg-surface-container-lowest p-5 sm:p-6">
                <div class="absolute -right-16 -top-16 w-48 h-48 rounded-full bg-primary/5 pointer-events-none"></div>
                <div class="relative flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5">
                        <div class="relative shrink-0">
                            <div
                                class="relative w-20 h-20 rounded-xl overflow-hidden border-2 border-primary/30 bg-primary/10 text-primary flex items-center justify-center text-2xl font-bold shadow-sm">
                                <span aria-hidden="true">{{ $initials }}</span>
                                @if ($avatarUrl)
                                    <img src="{{ $avatarUrl }}" alt="{{ $admin->name }}" loading="lazy"
                                        onerror="this.remove()"
                                        class="absolute inset-0 w-full h-full object-cover">
                                @endif
                            </div>
                            <form method="POST" action="{{ route('admin.profile.avatar') }}"
                                enctype="multipart/form-data" data-loading-form="true">@csrf
                                <label
                                    class="absolute -bottom-2 -right-2 w-8 h-8 rounded-lg bg-primary text-on-primary flex items-center justify-center shadow-md hover:bg-primary-container transition-colors cursor-pointer"
                                    title="{{ __('admin.change_avatar') }}"><span
                                        class="material-symbols-outlined text-[16px]">photo_camera</span><input
                                        name="avatar" type="file" accept="image/png,image/jpeg,image/webp"
                                        class="sr-only" onchange="this.form.requestSubmit()"></label>
                            </form>
                            @error('avatar')
                                <p class="absolute left-0 top-full z-10 mt-3 w-64 rounded-lg border border-error/30 bg-error-container px-3 py-2 text-xs text-on-error-container shadow-lg">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-xl font-bold text-on-surface">{{ $admin->name }}</h2><span
                                    class="inline-flex items-center gap-1 rounded-full border border-primary/25 bg-primary/10 px-2.5 py-0.5 text-[10px] font-mono font-bold uppercase text-primary"><span
                                        class="material-symbols-outlined text-[13px]">verified_user</span>{{ $role }}</span>
                                @if ($admin->two_factor_enabled)
                                    <span
                                        class="rounded-full border border-primary/25 bg-primary/10 px-2 py-0.5 text-[10px] font-mono font-semibold text-primary">2FA</span>
                                @endif
                            </div>
                            @if ($admin->department)
                                <p class="mt-1 text-sm text-on-surface-variant">{{ $admin->department }}</p>
                            @endif
                            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-outline"><span
                                    class="inline-flex items-center gap-1.5"><span
                                        class="material-symbols-outlined text-[16px]">mail</span>{{ $admin->email }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 shrink-0">
                        <div class="min-w-28 rounded-lg border border-outline-variant/60 bg-surface-container-low p-3">
                            <span
                                class="block text-[10px] font-mono uppercase text-outline">{{ __('admin.assigned_rooms') }}</span><strong
                                class="mt-1 block text-xl text-on-surface">{{ $admin->rooms_count }}</strong>
                        </div>
                        <div class="min-w-28 rounded-lg border border-outline-variant/60 bg-surface-container-low p-3">
                            <span class="block text-[10px] font-mono uppercase text-outline">2FA</span><strong
                                class="mt-1 block text-sm text-on-surface">{{ $admin->two_factor_enabled ? __('admin.enabled') : __('admin.disabled') }}</strong>
                        </div>
                    </div>
                </div>
            </section>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <section
                    class="lg:col-span-7 rounded-xl border border-outline-variant bg-surface-container-lowest p-5 sm:p-6">
                    <div class="flex items-start gap-3 pb-4 border-b border-outline-variant/60"><span
                            class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0"><span
                                class="material-symbols-outlined">badge</span></span>
                        <div>
                            <h2 class="text-lg font-bold text-on-surface">{{ __('admin.basic_information') }}</h2>
                            <p class="mt-0.5 text-xs text-on-surface-variant">{{ __('admin.profile_subtitle') }}</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('admin.profile.update') }}" data-loading-form="true"
                        data-loading-text="{{ __('admin.processing') }}" class="pt-5 space-y-4">@csrf @method('PATCH')
                        <div class="space-y-1.5"><label for="profile-name"
                                class="block text-xs font-semibold text-on-surface">{{ __('validation.attributes.name') }}</label><input
                                id="profile-name" name="name" value="{{ old('name', $admin->name) }}" required
                                class="w-full h-10 rounded-lg border border-outline-variant bg-surface px-3 text-sm text-on-surface focus:border-primary focus:ring-1 focus:ring-primary">
                            @error('name')
                                <p class="text-xs text-error">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between gap-3"><label
                                    class="block text-xs font-semibold text-on-surface">{{ __('admin.work_email') }}</label><span
                                    class="text-[10px] font-mono text-primary">{{ __('admin.verified') }}</span></div>
                            <div class="relative"><input value="{{ $admin->email }}" readonly
                                    class="w-full h-10 rounded-lg border border-outline-variant bg-surface-container-low px-3 pr-10 text-sm text-outline cursor-not-allowed"><span
                                    class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-outline text-[17px]">lock</span>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1.5"><label for="profile-phone"
                                    class="block text-xs font-semibold text-on-surface">{{ __('admin.phone_number') }}</label><input
                                    id="profile-phone" name="phone" value="{{ old('phone', $admin->phone) }}"
                                    class="w-full h-10 rounded-lg border border-outline-variant bg-surface px-3 text-sm focus:border-primary focus:ring-1 focus:ring-primary">
                                @error('phone')
                                    <p class="text-xs text-error">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="space-y-1.5"><label for="profile-department"
                                    class="block text-xs font-semibold text-on-surface">{{ __('admin.department') }}</label><input
                                    id="profile-department" name="department"
                                    value="{{ old('department', $admin->department) }}"
                                    class="w-full h-10 rounded-lg border border-outline-variant bg-surface px-3 text-sm focus:border-primary focus:ring-1 focus:ring-primary">
                                @error('department')
                                    <p class="text-xs text-error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="pt-4 border-t border-outline-variant/60 flex justify-end"><button type="submit"
                                class="h-10 px-4 rounded-lg bg-primary text-on-primary text-xs font-bold hover:bg-primary-container transition-colors inline-flex items-center gap-1.5"><span
                                    class="material-symbols-outlined text-[16px]">check</span>{{ __('admin.save_profile') }}</button>
                        </div>
                    </form>
                </section>

                <section
                    class="lg:col-span-5 rounded-xl border border-outline-variant bg-surface-container-lowest p-5 sm:p-6">
                    <div class="flex items-start gap-3 pb-4 border-b border-outline-variant/60"><span
                            class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0"><span
                                class="material-symbols-outlined">shield</span></span>
                        <div>
                            <h2 class="text-lg font-bold text-on-surface">{{ __('admin.login_security') }}</h2>
                            <p class="mt-0.5 text-xs text-on-surface-variant">{{ __('admin.two_factor_description') }}
                            </p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('admin.profile.password') }}" data-loading-form="true"
                        data-loading-text="{{ __('admin.processing') }}"
                        class="mt-5 rounded-lg border border-outline-variant bg-surface-container-low/40 p-4 space-y-3">
                        @csrf @method('PATCH')
                        <div class="flex items-center gap-2 text-sm font-bold text-on-surface"><span
                                class="material-symbols-outlined text-primary">key</span>{{ __('admin.update_password') }}
                        </div>
                        <div class="relative"><input id="profile-current-password" name="current_password" required
                                type="password" autocomplete="current-password"
                                placeholder="{{ __('admin.current_password') }}"
                                class="w-full h-9 rounded-lg border border-outline-variant bg-surface px-3 pr-10 text-sm focus:border-primary focus:ring-1 focus:ring-primary"><button
                                type="button" data-password-toggle="profile-current-password"
                                class="absolute right-2 top-1/2 -translate-y-1/2 text-outline hover:text-primary"><span
                                    class="material-symbols-outlined text-[18px]">visibility</span></button></div>
                        <div class="relative"><input id="profile-new-password" name="password" required
                                type="password" autocomplete="new-password"
                                placeholder="{{ __('admin.new_password') }}"
                                class="w-full h-9 rounded-lg border border-outline-variant bg-surface px-3 pr-10 text-sm focus:border-primary focus:ring-1 focus:ring-primary"><button
                                type="button" data-password-toggle="profile-new-password"
                                class="absolute right-2 top-1/2 -translate-y-1/2 text-outline hover:text-primary"><span
                                    class="material-symbols-outlined text-[18px]">visibility</span></button></div>
                        <div class="relative"><input id="profile-confirm-password" name="password_confirmation"
                                required type="password" autocomplete="new-password"
                                placeholder="{{ __('admin.confirm_new_password') }}"
                                class="w-full h-9 rounded-lg border border-outline-variant bg-surface px-3 pr-10 text-sm focus:border-primary focus:ring-1 focus:ring-primary"><button
                                type="button" data-password-toggle="profile-confirm-password"
                                class="absolute right-2 top-1/2 -translate-y-1/2 text-outline hover:text-primary"><span
                                    class="material-symbols-outlined text-[18px]">visibility</span></button></div>
                        @if (!config('captcha.disable') && extension_loaded('gd') && function_exists('captcha_img'))
                            <div class="flex gap-2">
                                <div
                                    class="h-9 w-28 overflow-hidden rounded-lg border border-outline-variant bg-surface-container">
                                    {!! captcha_img('contact') !!}</div><input name="captcha" required maxlength="6"
                                    placeholder="{{ __('global.feedback.captcha_placeholder') }}"
                                    class="min-w-0 flex-1 h-9 rounded-lg border border-outline-variant bg-surface px-3 text-sm focus:border-primary focus:ring-1 focus:ring-primary">
                            </div>
                        @endif
                        @error('current_password')
                            <p class="text-xs text-error">{{ $message }}</p>
                            @enderror @error('password')
                            <p class="text-xs text-error">{{ $message }}</p>
                        @enderror
                        <button type="submit"
                            class="w-full h-9 rounded-lg bg-primary text-on-primary text-xs font-bold hover:bg-primary-container transition-colors">{{ __('admin.update_password') }}</button>
                    </form>
                    <div class="mt-4 rounded-lg border border-outline-variant bg-surface-container-low/40 p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2"><span
                                        class="w-2 h-2 rounded-full {{ $admin->two_factor_enabled ? 'bg-primary' : 'bg-outline' }}"></span>
                                    <h3 class="text-sm font-bold text-on-surface">
                                        {{ __('admin.two_factor_authentication') }}</h3>
                                </div>
                                <p class="mt-1 text-xs leading-relaxed text-on-surface-variant">
                                    {{ __('admin.two_factor_description') }}</p>
                            </div><button type="button" data-two-factor-open
                                class="relative inline-flex items-center shrink-0"><span
                                    class="w-10 h-6 rounded-full {{ $admin->two_factor_enabled ? 'bg-primary' : 'bg-outline-variant/60' }} after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all {{ $admin->two_factor_enabled ? 'after:translate-x-4' : '' }}"></span></button>
                        </div>
                    </div>
                </section>
            </div>

        </div>

        <div data-two-factor-modal
            class="{{ $errors->has('two_factor_password') ? 'flex' : 'hidden' }} fixed inset-0 z-50 items-center justify-center bg-on-surface/40 p-4"
            role="dialog" aria-modal="true" aria-labelledby="two-factor-title">
            <form method="POST" action="{{ route('admin.profile.two-factor') }}" data-loading-form="true"
                data-loading-text="{{ __('admin.processing') }}"
                class="w-full max-w-md rounded-xl border border-outline-variant bg-surface-container-lowest p-5 shadow-2xl space-y-4">
                @csrf
                @method('PATCH')
                <input type="hidden" name="two_factor_enabled"
                    value="{{ $admin->two_factor_enabled ? '0' : '1' }}">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 id="two-factor-title" class="text-lg font-bold text-on-surface">
                            {{ __('admin.confirm_two_factor') }}</h2>
                        <p class="mt-1 text-xs text-outline">{{ __('admin.two_factor_description') }}</p>
                    </div><button type="button" data-two-factor-close
                        class="text-outline hover:text-on-surface"><span
                            class="material-symbols-outlined">close</span></button>
                </div>
                <div><label for="two-factor-password"
                        class="block mb-1.5 text-xs font-semibold text-on-surface">{{ __('admin.verify_current_password') }}</label>
                    <div class="relative"><input id="two-factor-password" name="current_password" type="password"
                            required autocomplete="current-password"
                            class="w-full h-10 rounded-lg border border-outline-variant bg-surface px-3 pr-10 text-sm focus:border-primary focus:ring-1 focus:ring-primary"><button
                            type="button" data-password-toggle="two-factor-password"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-outline hover:text-primary"><span
                                class="material-symbols-outlined text-[18px]">visibility</span></button></div>
                    @error('two_factor_password')
                        <p class="mt-1 text-xs text-error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex justify-end gap-2 pt-2"><button type="button" data-two-factor-close
                        class="h-9 px-3 rounded-lg border border-outline-variant text-xs font-semibold hover:bg-surface-container-low">{{ __('admin.cancel') }}</button><button
                        type="submit"
                        class="h-9 px-4 rounded-lg bg-primary text-on-primary text-xs font-bold hover:bg-primary-container">{{ __('admin.confirm_two_factor') }}</button>
                </div>
            </form>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('[data-password-toggle]').forEach((button) => button.addEventListener('click',
                    () => {
                        const input = document.getElementById(button.dataset.passwordToggle);
                        if (!input) return;
                        const visible = input.type === 'password';
                        input.type = visible ? 'text' : 'password';
                        button.querySelector('.material-symbols-outlined').textContent = visible ?
                            'visibility_off' : 'visibility';
                    }));
                const modal = document.querySelector('[data-two-factor-modal]');
                const openModal = () => {
                    modal?.classList.remove('hidden');
                    modal?.classList.add('flex');
                    modal?.querySelector('input[name="current_password"]')?.focus();
                };
                const closeModal = () => {
                    modal?.classList.add('hidden');
                    modal?.classList.remove('flex');
                };
                document.querySelector('[data-two-factor-open]')?.addEventListener('click', openModal);
                document.querySelectorAll('[data-two-factor-close]').forEach((button) => button.addEventListener(
                    'click', closeModal));
                modal?.addEventListener('click', (event) => {
                    if (event.target === modal) closeModal();
                });
            });
        </script>
    </x-admin.layout>

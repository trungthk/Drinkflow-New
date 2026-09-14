<x-admin-auth.layout :title="__('admin.login_page_title')">
    <div class="mb-5">
        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-secondary-container text-on-secondary-container text-xs font-mono font-semibold mb-2">
            <span class="material-symbols-outlined text-[15px]">admin_panel_settings</span>
            <span>{{ __('admin.restricted_access_area') }}</span>
        </div>
        <h2 class="text-2xl font-bold text-on-surface tracking-tight">{{ __('admin.login_heading') }}</h2>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-xl bg-error-container p-3 text-xs font-medium text-on-error-container border border-error/20 flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px] text-error shrink-0">error</span>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    @if (session('status'))
        <div class="mb-4 rounded-xl bg-emerald-50 p-3 text-xs font-medium text-emerald-800 border border-emerald-200 flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px] text-emerald-600 shrink-0">check_circle</span>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <form method="post" action="{{ route('admin.login') }}" data-loading-form="true" class="space-y-4">
        @csrf
        <div>
            <label for="admin-email" class="block text-xs font-semibold text-on-surface mb-1">
                {{ __('admin.email_address') }}
            </label>
            <div class="relative flex items-center rounded-lg border border-outline-variant bg-surface-container-lowest focus-within:border-primary focus-within:ring-1 focus-within:ring-primary transition-all">
                <span class="material-symbols-outlined text-outline pl-3 text-[18px]">alternate_email</span>
                <input id="admin-email" name="email" type="email" value="{{ old('email') }}" required autofocus
                    class="w-full border-0 bg-transparent px-3 py-2.5 text-sm text-on-surface placeholder:text-outline/60 focus:ring-0 outline-none font-medium"
                    placeholder="admin@company.com">
            </div>
        </div>

        <div>
            <label for="admin-password" class="block text-xs font-semibold text-on-surface mb-1">
                {{ __('admin.password') }}
            </label>
            <div class="relative flex items-center rounded-lg border border-outline-variant bg-surface-container-lowest focus-within:border-primary focus-within:ring-1 focus-within:ring-primary transition-all">
                <span class="material-symbols-outlined text-outline pl-3 text-[18px]">lock</span>
                <input id="admin-password" name="password" type="password" required
                    class="w-full border-0 bg-transparent px-3 py-2.5 text-sm text-on-surface placeholder:text-outline/60 focus:ring-0 outline-none font-mono"
                    placeholder="••••••••••••">
                <button type="button" id="toggle-admin-password" class="pr-3 text-outline hover:text-on-surface focus:outline-none cursor-pointer" aria-label="{{ __('admin.toggle_password_visibility') }}">
                    <span class="material-symbols-outlined text-[18px]">visibility</span>
                </button>
            </div>
        </div>

        <!-- Captcha Challenge (Bypassed in Local Environment) -->
        @if(!app()->isLocal() && extension_loaded('gd') && function_exists('captcha_img'))
            <div class="rounded-xl bg-surface-container-low border border-outline-variant/60 p-3.5 space-y-2">
                <div class="flex items-center justify-between text-xs font-semibold text-on-surface">
                    <div class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px] text-primary">shield</span>
                        <span>{{ __('admin.captcha_bot_protection') }}</span>
                    </div>
                    <span class="text-[10px] font-mono text-primary font-bold">{{ __('admin.level_2_active') }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-1.5">
                        <div id="captcha-img-wrapper"
                             data-captcha-api="{{ url('/captcha/api/contact') }}"
                             data-captcha-fallback="{{ captcha_src('contact') }}"
                             data-loading-text="{{ __('global.feedback.captcha_loading') }}"
                             class="w-[120px] h-[38px] rounded-lg border border-outline-variant bg-surface-container flex items-center justify-center overflow-hidden cursor-pointer shrink-0 shadow-2xs hover:border-primary/50 transition-colors"
                             title="{{ __('global.feedback.captcha_refresh') }}">
                            {!! captcha_img('contact') !!}
                        </div>
                        <button type="button"
                                id="refresh-captcha-btn"
                                class="w-[38px] h-[38px] rounded-lg border border-outline-variant bg-surface-container-lowest hover:bg-surface-container text-on-surface-variant hover:text-primary flex items-center justify-center transition-colors cursor-pointer shrink-0 shadow-2xs"
                                title="{{ __('global.feedback.captcha_refresh') }}">
                            <span class="material-symbols-outlined text-[18px] transition-transform duration-300">sync</span>
                        </button>
                    </div>
                    <input class="flex-1 w-full h-[38px] px-3 rounded-lg border @error('captcha') border-red-500 @else border-outline-variant @enderror bg-surface-container-lowest text-on-surface text-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all placeholder:text-outline/60 font-mono"
                           id="admin-captcha"
                           name="captcha"
                           type="text"
                           maxlength="6"
                           required
                           placeholder="{{ __('global.feedback.captcha_placeholder') }}">
                </div>
                @error('captcha')
                    <p class="text-xs text-red-500 mt-1 flex items-center gap-1 font-medium">
                        <span class="material-symbols-outlined text-[14px]">error</span>
                        {{ $message }}
                    </p>
                @enderror
            </div>
        @endif

        <div class="flex items-center justify-between pt-1">
            <label class="flex items-center gap-2 cursor-pointer select-none text-xs text-outline hover:text-on-surface transition-colors">
                <input name="remember" type="checkbox" value="1" class="rounded border-outline-variant text-primary focus:ring-primary">
                <span>{{ __('admin.remember_session') }}</span>
            </label>
            <a href="{{ route('admin.forgot-password.page') }}" class="text-xs font-semibold text-primary hover:underline no-underline">
                {{ __('admin.forgot_password') }}
            </a>
        </div>

        <button type="submit"
            class="w-full h-10 flex items-center justify-center gap-2 bg-primary hover:bg-primary-container text-on-primary text-sm font-semibold rounded shadow-sm hover:shadow transition-all duration-150 active:scale-[0.99] cursor-pointer">
            <span class="material-symbols-outlined text-[18px]">login</span>
            <span>{{ __('admin.sign_in') }}</span>
        </button>
    </form>

    <!-- Security Assurance Pill -->
    <div class="p-3 rounded-xl bg-surface border border-outline-variant/60 flex items-start gap-2.5 mt-5">
        <div class="p-1 rounded bg-primary-fixed-dim/20 text-primary shrink-0">
            <span class="material-symbols-outlined text-[16px]">vpn_key</span>
        </div>
        <div>
            <div class="text-xs font-semibold text-on-surface flex items-center gap-1.5">
                {{ __('admin.e2e_encryption') }}
                <span class="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 font-mono text-[9px] font-bold">{{ __('admin.mfa_ready') }}</span>
            </div>
            <p class="text-[11px] text-outline leading-snug mt-0.5">
                {{ __('admin.security_assurance_desc') }}
            </p>
        </div>
    </div>

    <p class="text-center text-[10px] text-outline leading-tight mt-3">
        {{ __('admin.security_warning_footer') }}
    </p>
</x-admin-auth.layout>

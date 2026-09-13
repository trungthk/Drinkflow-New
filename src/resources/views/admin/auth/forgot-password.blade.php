<x-admin-auth.layout :title="__('admin.forgot_password_title')">
    <x-slot name="brandHero">
        <div>
            <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded bg-white/5 border border-white/10 text-emerald-400 text-xs font-mono font-semibold mb-3">
                <span class="material-symbols-outlined text-[15px]">lock_reset</span>
                <span>{{ __('admin.recovery_protocol') }}</span>
            </div>
            <h2 class="text-2xl lg:text-3xl font-bold tracking-tight text-white leading-tight">
                {{ __('admin.forgot_hero_heading') }}
            </h2>
        </div>

        <p class="text-xs lg:text-sm text-slate-300/80 leading-relaxed">
            {{ __('admin.forgot_hero_desc') }}
        </p>

        <div class="space-y-3 pt-2">
            <div class="flex items-center gap-3 p-3 rounded-lg bg-white/5 border border-white/10">
                <div class="w-8 h-8 rounded bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-[18px]">verified_user</span>
                </div>
                <div>
                    <strong class="text-xs font-semibold text-white block">{{ __('admin.two_factor_otp') }}</strong>
                    <span class="text-[11px] text-slate-400">{{ __('admin.otp_validity_15m') }}</span>
                </div>
            </div>

            <div class="flex items-center gap-3 p-3 rounded-lg bg-white/5 border border-white/10">
                <div class="w-8 h-8 rounded bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-[18px]">policy</span>
                </div>
                <div>
                    <strong class="text-xs font-semibold text-white block">{{ __('admin.security_audit_trail') }}</strong>
                    <span class="text-[11px] text-slate-400">{{ __('admin.security_audit_desc') }}</span>
                </div>
            </div>
        </div>
    </x-slot>

    <div>
        <a href="{{ route('admin.login.page') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary hover:text-primary-container mb-4 no-underline">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            <span>{{ __('admin.back_to_login') }}</span>
        </a>
        <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-on-surface">{{ __('admin.forgot_password_heading') }}</h1>
        <p class="text-xs text-outline mt-1">{{ __('admin.forgot_password_subheading') }}</p>
    </div>

    @if ($errors->any())
        <div class="mt-4 rounded-xl bg-error-container p-3 text-xs font-medium text-on-error-container border border-error/20 flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px] text-error shrink-0">error</span>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    @if (session('status'))
        <div class="mt-4 rounded-xl bg-emerald-50 p-3 text-xs font-medium text-emerald-800 border border-emerald-200 flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px] text-emerald-600 shrink-0">check_circle</span>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <form method="post" action="{{ route('admin.forgot-password.send') }}" data-loading-form="true" class="mt-5 space-y-4">
        @csrf
        <div>
            <label for="admin-email" class="block text-xs font-semibold text-on-surface mb-1">
                {{ __('admin.email_address') }}
                <span class="float-right font-normal text-outline">{{ __('admin.sso_local_identity') }}</span>
            </label>
            <div class="relative flex items-center rounded-lg border border-outline-variant bg-surface-container-lowest focus-within:border-primary focus-within:ring-1 focus-within:ring-primary transition-all">
                <span class="material-symbols-outlined text-outline pl-3 text-[18px]">alternate_email</span>
                <input id="admin-email" name="email" type="email" value="{{ old('email') }}" required autofocus
                    class="w-full border-0 bg-transparent px-3 py-2.5 text-sm text-on-surface placeholder:text-outline/60 focus:ring-0 outline-none font-medium"
                    placeholder="admin@company.com">
            </div>
        </div>

        <button type="submit"
            class="w-full flex items-center justify-center gap-2 rounded-lg bg-primary hover:bg-primary-container text-on-primary py-2.5 px-4 text-sm font-semibold shadow-sm transition-colors cursor-pointer">
            <span class="material-symbols-outlined text-[18px]">send</span>
            <span>{{ __('admin.send_otp_code') }}</span>
        </button>
    </form>

    <div class="mt-6 p-4 rounded-xl bg-surface-container-low border border-outline-variant/60 text-xs text-outline space-y-1">
        <div class="font-semibold text-on-surface flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[16px] text-primary">info</span>
            <span>{{ __('admin.important_note') }}</span>
        </div>
        <p class="leading-relaxed">
            {{ __('admin.forgot_note_desc') }}
        </p>
    </div>
</x-admin-auth.layout>

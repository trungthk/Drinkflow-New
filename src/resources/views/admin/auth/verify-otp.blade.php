<x-admin-auth.layout :title="__('admin.verify_otp_title')">
    <x-slot name="brandHero">
        <div>
            <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded bg-white/5 border border-white/10 text-emerald-400 text-xs font-mono font-semibold mb-3">
                <span class="material-symbols-outlined text-[15px]">security</span>
                <span>{{ __('admin.mfa_challenge') }}</span>
            </div>
            <h2 class="text-2xl lg:text-3xl font-bold tracking-tight text-white leading-tight">
                {{ __('admin.verify_hero_heading') }}
            </h2>
        </div>

        <p class="text-xs lg:text-sm text-slate-300/80 leading-relaxed">
            {{ __('admin.verify_hero_desc', ['email' => $email]) }}
        </p>

        <div class="p-4 rounded-xl bg-white/5 border border-white/10 space-y-2">
            <div class="flex items-center justify-between text-xs text-slate-300 font-mono">
                <span>{{ __('admin.session_challenge_id') }}</span>
                <span class="text-emerald-400 font-bold">DF-OTP-{{ substr(md5($email), 0, 6) }}</span>
            </div>
            <div class="flex items-center justify-between text-xs text-slate-300 font-mono">
                <span>{{ __('admin.validity_duration') }}</span>
                <span class="text-amber-400 font-bold">{{ __('admin.duration_15_mins') }}</span>
            </div>
        </div>
    </x-slot>

    <div>
        <a href="{{ route('admin.forgot-password.page') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary hover:text-primary-container mb-4 no-underline">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            <span>{{ __('admin.back') }}</span>
        </a>
        <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-on-surface">{{ __('admin.verify_otp_heading') }}</h1>
        <p class="text-xs text-outline mt-1">{{ __('admin.verify_otp_subheading') }}</p>
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

    <form method="post" action="{{ route('admin.verify-otp.submit') }}" class="mt-5 space-y-4">
        @csrf
        <div>
            <label for="admin-otp" class="block text-xs font-semibold text-on-surface mb-2 text-center">
                {{ __('admin.enter_6_digit_otp') }}
            </label>
            <div class="flex justify-center">
                <input id="admin-otp" name="otp" type="text" maxlength="6" inputmode="numeric" required autofocus
                    class="w-64 text-center tracking-[0.5em] text-2xl font-mono font-bold py-3 px-4 rounded-xl border-2 border-outline-variant bg-surface-container-lowest focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all"
                    placeholder="••••••">
            </div>
        </div>

        <button type="submit"
            class="w-full flex items-center justify-center gap-2 rounded-lg bg-primary hover:bg-primary-container text-on-primary py-2.5 px-4 text-sm font-semibold shadow-sm transition-colors cursor-pointer">
            <span class="material-symbols-outlined text-[18px]">lock_open</span>
            <span>{{ __('admin.verify_and_continue') }}</span>
        </button>
    </form>

    <div class="text-center pt-3">
        <form method="post" action="{{ route('admin.forgot-password.send') }}" class="inline">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">
            <button type="submit" class="text-xs font-semibold text-primary hover:underline bg-transparent border-0 cursor-pointer">
                {{ __('admin.resend_otp_code') }}
            </button>
        </form>
    </div>
</x-admin-auth.layout>

{{-- Shown after registering: verify the email, then wait for a Superadmin to approve the account. --}}
<x-admin-auth.layout :title="__('platform.registration.pending_title')">
    <div class="mb-5">
        <div class="w-12 h-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center mb-3">
            <span class="material-symbols-outlined text-[26px]">mark_email_unread</span>
        </div>
        <h2 class="text-2xl font-bold text-on-surface tracking-tight">{{ __('platform.registration.pending_title') }}</h2>
        <p class="text-sm text-on-surface-variant mt-2">
            {{ $email !== '' ? __('platform.registration.pending_sent_to', ['email' => $email]) : __('platform.registration.pending_generic') }}
        </p>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-xl bg-emerald-50 p-3 text-xs font-medium text-emerald-800 border border-emerald-200 flex items-center gap-2" role="status">
            <span class="material-symbols-outlined text-[18px] text-emerald-600 shrink-0">check_circle</span>
            <span>{{ session('status') }}</span>
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-xl bg-error-container p-3 text-xs font-medium text-on-error-container border border-error/20" role="alert">{{ $errors->first() }}</div>
    @endif

    <ol class="space-y-2 text-sm text-on-surface-variant list-decimal pl-5 mb-6">
        <li>{{ __('platform.registration.step_verify') }}</li>
        <li>{{ __('platform.registration.step_review') }}</li>
        <li>{{ __('platform.registration.step_sign_in') }}</li>
    </ol>

    <form method="post" action="{{ route('admin.register.resend') }}" data-loading-form="true" class="space-y-3">
        @csrf
        <label for="resend-email" class="block text-xs font-semibold text-on-surface">{{ __('platform.registration.resend_label') }}</label>
        <div class="flex gap-2">
            <input id="resend-email" name="email" type="email" required maxlength="255" value="{{ old('email', $email) }}"
                class="flex-1 min-w-0 rounded-lg border border-outline-variant bg-surface-container-lowest px-3 py-2 text-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none">
            <button type="submit" class="shrink-0 h-10 px-4 rounded-lg border border-outline-variant bg-surface text-on-surface text-sm font-semibold hover:bg-surface-container cursor-pointer">
                {{ __('platform.registration.resend') }}
            </button>
        </div>
    </form>

    <p class="text-center text-xs text-outline mt-6">
        <a href="{{ route('admin.login.page') }}" class="font-semibold text-primary hover:underline">{{ __('platform.registration.back_to_login') }}</a>
    </p>
</x-admin-auth.layout>

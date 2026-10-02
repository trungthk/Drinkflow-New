{{-- Activation of an invited Agent: the account stays pending until the sign-in value is chosen. --}}
<x-admin-auth.layout :title="__('platform.activation.page_title')">
    @php
        $inputWrap = 'relative flex items-center rounded-lg border border-outline-variant bg-surface-container-lowest focus-within:border-primary focus-within:ring-1 focus-within:ring-primary transition-all';
        $input = 'w-full border-0 bg-transparent px-3 py-2.5 text-sm text-on-surface placeholder:text-outline/60 focus:ring-0 outline-none font-medium';
        $label = 'block text-xs font-semibold text-on-surface mb-1';
    @endphp

    <div class="mb-5">
        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-secondary-container text-on-secondary-container text-xs font-mono font-semibold mb-2">
            <span class="material-symbols-outlined text-[15px]">verified_user</span>
            <span>{{ __('platform.activation.badge') }}</span>
        </div>
        <h2 class="text-2xl font-bold text-on-surface tracking-tight">{{ __('platform.activation.heading') }}</h2>
        <p class="text-xs text-outline mt-1">{{ __('platform.activation.subheading', ['name' => $agent->name]) }}</p>
    </div>

    <div class="mb-4 rounded-xl bg-surface-container-low border border-outline-variant/60 p-3.5 text-xs text-on-surface-variant space-y-1">
        <p><strong class="text-on-surface">{{ __('platform.activation.account_label') }}</strong> {{ $agent->email }}</p>
        @if ($agent->company)
            <p><strong class="text-on-surface">{{ __('platform.registration.field_company') }}</strong> {{ $agent->company }}</p>
        @endif
        <p class="text-outline">{{ __('platform.activation.validity', ['hours' => $validHours]) }}</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-xl bg-error-container p-3 text-xs font-medium text-on-error-container border border-error/20 flex items-center gap-2" role="alert">
            <span class="material-symbols-outlined text-[18px] text-error shrink-0">error</span>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <form method="post" action="{{ $submitUrl }}" data-loading-form="true" class="space-y-4">
        @csrf
        <div>
            <label for="activate-password" class="{{ $label }}">{{ __('platform.activation.field_value') }}</label>
            <div class="{{ $inputWrap }}">
                <span class="material-symbols-outlined text-outline pl-3 text-[18px]">lock</span>
                <input id="activate-password" name="sign_in_value" type="password" required minlength="8" autocomplete="new-password" class="{{ $input }} font-mono">
            </div>
            <p class="mt-1 text-[11px] text-outline">{{ __('platform.activation.value_hint') }}</p>
        </div>
        <div>
            <label for="activate-password-confirmation" class="{{ $label }}">{{ __('platform.registration.field_password_confirmation') }}</label>
            <div class="{{ $inputWrap }}">
                <span class="material-symbols-outlined text-outline pl-3 text-[18px]">lock</span>
                <input id="activate-password-confirmation" name="sign_in_value_confirmation" type="password" required minlength="8" autocomplete="new-password" class="{{ $input }} font-mono">
            </div>
        </div>

        <button type="submit"
            class="w-full h-10 flex items-center justify-center gap-2 bg-primary hover:bg-primary-container text-on-primary text-sm font-semibold rounded shadow-sm hover:shadow transition-all duration-150 active:scale-[0.99] cursor-pointer">
            <span class="material-symbols-outlined text-[18px]">check_circle</span>
            <span>{{ __('platform.activation.submit') }}</span>
        </button>
    </form>

    <p class="text-center text-xs text-outline mt-4">
        {{ __('platform.activation.have_account') }}
        <a href="{{ route('admin.login.page') }}" class="font-semibold text-primary hover:underline">{{ __('admin.sign_in') }}</a>
    </p>
</x-admin-auth.layout>

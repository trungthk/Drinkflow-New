{{-- Public Agent registration (/admin/register). The account stays pending until a Superadmin approves it. --}}
<x-admin-auth.layout :title="__('platform.registration.page_title')">
    @php
        $inputWrap = 'relative flex items-center rounded-lg border border-outline-variant bg-surface-container-lowest focus-within:border-primary focus-within:ring-1 focus-within:ring-primary transition-all';
        $input = 'w-full border-0 bg-transparent px-3 py-2.5 text-sm text-on-surface placeholder:text-outline/60 focus:ring-0 outline-none font-medium';
        $label = 'block text-xs font-semibold text-on-surface mb-1';
        $fields = [
            ['name', 'text', 'person', 'field_name', true, 'name'],
            ['company', 'text', 'apartment', 'field_company', false, 'organization'],
            ['email', 'email', 'alternate_email', 'field_email', true, 'email'],
            ['phone', 'tel', 'call', 'field_phone', true, 'tel'],
        ];
    @endphp

    <div class="mb-5">
        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-secondary-container text-on-secondary-container text-xs font-mono font-semibold mb-2">
            <span class="material-symbols-outlined text-[15px]">storefront</span>
            <span>{{ __('platform.registration.badge') }}</span>
        </div>
        <h2 class="text-2xl font-bold text-on-surface tracking-tight">{{ __('platform.registration.heading') }}</h2>
        <p class="text-xs text-outline mt-1">{{ __('platform.registration.subheading') }}</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-xl bg-error-container p-3 text-xs font-medium text-on-error-container border border-error/20 flex items-center gap-2" role="alert">
            <span class="material-symbols-outlined text-[18px] text-error shrink-0">error</span>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    @if ($packages->isEmpty())
        <div class="rounded-xl border border-outline-variant bg-surface-container-low p-4 text-sm text-on-surface-variant">
            {{ __('platform.registration.no_packages') }}
        </div>
    @else
        <form method="post" action="{{ route('admin.register') }}" data-loading-form="true" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach ($fields as [$field, $type, $icon, $labelKey, $required, $autocomplete])
                    <div>
                        <label for="register-{{ $field }}" class="{{ $label }}">{{ __('platform.registration.'.$labelKey) }}@unless ($required) <span class="font-normal text-outline">({{ __('platform.registration.optional') }})</span>@endunless</label>
                        <div class="{{ $inputWrap }}">
                            <span class="material-symbols-outlined text-outline pl-3 text-[18px]">{{ $icon }}</span>
                            <input id="register-{{ $field }}" name="{{ $field }}" type="{{ $type }}" value="{{ old($field) }}" maxlength="255" autocomplete="{{ $autocomplete }}" @required($required) class="{{ $input }}">
                        </div>
                    </div>
                @endforeach
                <div>
                    <label for="register-password" class="{{ $label }}">{{ __('admin.password') }}</label>
                    <div class="{{ $inputWrap }}">
                        <span class="material-symbols-outlined text-outline pl-3 text-[18px]">lock</span>
                        <input id="register-password" name="password" type="password" required minlength="8" autocomplete="new-password" class="{{ $input }} font-mono">
                    </div>
                </div>
                <div>
                    <label for="register-password-confirmation" class="{{ $label }}">{{ __('platform.registration.field_password_confirmation') }}</label>
                    <div class="{{ $inputWrap }}">
                        <span class="material-symbols-outlined text-outline pl-3 text-[18px]">lock</span>
                        <input id="register-password-confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password" class="{{ $input }} font-mono">
                    </div>
                </div>
            </div>

            <fieldset>
                <legend class="{{ $label }}">{{ __('platform.registration.field_package') }}</legend>
                <p class="text-[11px] text-outline mb-2">{{ __('platform.registration.package_hint') }}</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach ($packages as $package)
                        <label class="flex items-start gap-3 rounded-xl border border-outline-variant bg-surface-container-lowest p-3 cursor-pointer hover:border-primary/60 has-[:checked]:border-primary has-[:checked]:ring-1 has-[:checked]:ring-primary transition-all">
                            <input type="radio" name="package_id" value="{{ $package->id }}" required class="mt-1 text-primary focus:ring-primary"
                                @checked((string) old('package_id', $packages->count() === 1 ? $package->id : '') === (string) $package->id)>
                            <span class="min-w-0">
                                <strong class="block text-sm text-on-surface">{{ $package->name }}</strong>
                                <span class="block text-xs font-semibold text-primary">{{ __('platform.packages.per_month', ['price' => \App\Support\Helpers\FormatHelper::formatCurrency($package->monthly_price)]) }}</span>
                                <span class="block text-xs text-outline">{{ __('platform.packages.rooms_limit', ['count' => $package->room_limit]) }}</span>
                                @if ($package->description)
                                    <span class="block text-[11px] text-on-surface-variant mt-1">{{ $package->description }}</span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <x-admin-auth.captcha />

            <button type="submit"
                class="w-full h-10 flex items-center justify-center gap-2 bg-primary hover:bg-primary-container text-on-primary text-sm font-semibold rounded shadow-sm hover:shadow transition-all duration-150 active:scale-[0.99] cursor-pointer">
                <span class="material-symbols-outlined text-[18px]">how_to_reg</span>
                <span>{{ __('platform.registration.submit') }}</span>
            </button>
        </form>
    @endif

    <p class="text-center text-xs text-outline mt-4">
        {{ __('platform.registration.have_account') }}
        <a href="{{ route('admin.login.page') }}" class="font-semibold text-primary hover:underline">{{ __('admin.sign_in') }}</a>
    </p>
</x-admin-auth.layout>

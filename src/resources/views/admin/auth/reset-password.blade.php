<x-admin-auth.layout :title="__('admin.reset_password_title')">
    <x-slot name="brandHero">
        <div>
            <h2 class="text-2xl lg:text-3xl font-bold tracking-tight text-on-surface leading-tight">
                {{ __('admin.reset_hero_heading') }}
            </h2>
        </div>

        <p class="text-xs lg:text-sm text-slate-600 leading-relaxed">
            {{ __('admin.reset_hero_desc', ['email' => $email]) }}
        </p>

        <div class="p-4 rounded-xl bg-white/80 shadow-xs border border-emerald-100 space-y-2">
            <div class="text-xs font-semibold text-on-surface">{{ __('admin.security_requirements') }}</div>
            <ul class="text-xs text-slate-600 space-y-1 list-disc list-inside">
                <li>{{ __('admin.req_min_8_chars') }}</li>
                <li>{{ __('admin.req_alphanumeric') }}</li>
                <li>{{ __('admin.req_match_confirmation') }}</li>
            </ul>
        </div>
    </x-slot>

    <div>
        <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-on-surface">{{ __('admin.reset_password_heading') }}</h1>
        <p class="text-xs text-outline mt-1">{{ __('admin.reset_password_subheading') }}</p>
    </div>

    @if ($errors->any())
        <div class="mt-4 rounded-xl bg-error-container p-3 text-xs font-medium text-on-error-container border border-error/20 flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px] text-error shrink-0">error</span>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <form method="post" action="{{ route('admin.reset-password.submit', request()->query()) }}" data-loading-form="true" class="mt-5 space-y-4">
        @csrf

        <div>
            <label for="admin-password" class="block text-xs font-semibold text-on-surface mb-1">
                {{ __('admin.new_password') }}
            </label>
            <div class="relative flex items-center rounded-lg border border-outline-variant bg-surface-container-lowest focus-within:border-primary focus-within:ring-1 focus-within:ring-primary transition-all">
                <span class="material-symbols-outlined text-outline pl-3 text-[18px]">lock</span>
                <input id="admin-password" name="password" type="password" required autofocus
                    class="w-full border-0 bg-transparent px-3 py-2.5 text-sm text-on-surface placeholder:text-outline/60 focus:ring-0 outline-none font-medium"
                    placeholder="••••••••">
                <button type="button" id="toggle-admin-password" class="pr-3 text-outline hover:text-on-surface focus:outline-none cursor-pointer" aria-label="{{ __('admin.toggle_password_visibility') }}">
                    <span class="material-symbols-outlined text-[18px]">visibility</span>
                </button>
            </div>
        </div>

        <div>
            <label for="admin-password-confirmation" class="block text-xs font-semibold text-on-surface mb-1">
                {{ __('admin.confirm_new_password') }}
            </label>
            <div class="relative flex items-center rounded-lg border border-outline-variant bg-surface-container-lowest focus-within:border-primary focus-within:ring-1 focus-within:ring-primary transition-all">
                <span class="material-symbols-outlined text-outline pl-3 text-[18px]">lock_reset</span>
                <input id="admin-password-confirmation" name="password_confirmation" type="password" required
                    class="w-full border-0 bg-transparent px-3 py-2.5 text-sm text-on-surface placeholder:text-outline/60 focus:ring-0 outline-none font-medium"
                    placeholder="••••••••">
                <button type="button" id="toggle-admin-password-confirmation" class="pr-3 text-outline hover:text-on-surface focus:outline-none cursor-pointer" aria-label="{{ __('admin.toggle_password_visibility') }}">
                    <span class="material-symbols-outlined text-[18px]">visibility</span>
                </button>
            </div>
        </div>

        <button type="submit"
            class="w-full flex items-center justify-center gap-2 rounded-lg bg-primary hover:bg-primary-container text-on-primary py-2.5 px-4 text-sm font-semibold shadow-sm transition-colors cursor-pointer">
            <span class="material-symbols-outlined text-[18px]">check_circle</span>
            <span>{{ __('admin.save_new_password') }}</span>
        </button>
    </form>
</x-admin-auth.layout>

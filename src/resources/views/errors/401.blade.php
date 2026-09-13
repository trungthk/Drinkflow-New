<x-public.layout :title="__('errors.401.page_title')">
    <main class="flex-1 flex flex-col justify-center py-12 md:py-20">
        <div class="w-full max-w-[1200px] mx-auto px-6">
            <div class="max-w-2xl mx-auto text-center flex flex-col items-center">
                <!-- Status Badge -->
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-surface-container-lowest border border-outline-variant shadow-sm mb-6">
                    <span class="w-2 h-2 rounded-full bg-primary animate-ping"></span>
                    <span class="font-label-sm text-label-sm font-semibold text-on-surface uppercase tracking-wider">{{ __('errors.401.badge') }}</span>
                </div>

                <!-- Number Display -->
                <div class="flex items-center justify-center mb-4">
                    <div class="w-20 h-20 rounded-2xl bg-surface-container flex items-center justify-center border border-outline-variant text-primary shadow-inner">
                        <span class="material-symbols-outlined text-[42px]" style="font-variation-settings: 'FILL' 1;">vpn_key</span>
                    </div>
                </div>

                <!-- Headline & Description -->
                <h1 class="font-headline-xl text-headline-xl text-on-surface mb-3 tracking-tight font-bold">
                    {{ __('errors.401.title') }}
                </h1>
                <p class="font-body-lg text-body-lg text-on-surface-variant max-w-xl mx-auto leading-relaxed mb-8">
                    {{ __('errors.401.description') }}
                </p>

                <!-- Actions -->
                <div class="flex flex-wrap items-center justify-center gap-3">
                    <a class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-primary text-on-primary hover:bg-primary-container font-label-md text-label-md font-semibold shadow-sm transition-colors duration-150" href="{{ route('auth.google') }}">
                        <span class="material-symbols-outlined text-[18px]">login</span>
                        <span>{{ __('errors.401.login_now') }}</span>
                    </a>
                    <a class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-surface-container-lowest border border-outline-variant hover:bg-surface-container-low text-on-surface font-label-md text-label-md font-medium transition-colors duration-150" href="{{ url('/') }}">
                        <span class="material-symbols-outlined text-[18px]">home</span>
                        <span>{{ __('errors.common.back_home') }}</span>
                    </a>
                </div>
            </div>
        </div>
    </main>
</x-public.layout>

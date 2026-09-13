<x-public.layout :title="__('errors.404.page_title')">
    <main class="flex-1 flex flex-col justify-center py-12 md:py-20">
        <div class="w-full max-w-[1200px] mx-auto px-6">
            <!-- Central Hero Container -->
            <div class="max-w-3xl mx-auto text-center flex flex-col items-center">
                <!-- Status Badge -->
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-surface-container-lowest border border-outline-variant shadow-sm mb-6">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-error opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-error"></span>
                    </span>
                    <span class="font-label-sm text-label-sm font-semibold text-on-surface uppercase tracking-wider">{{ __('errors.404.badge') }}</span>
                </div>

                <!-- Distinct Modern Typographic 404 Display -->
                <div class="relative select-none flex items-center justify-center my-2">
                    <h1 class="text-[96px] md:text-[128px] font-bold leading-none tracking-tighter text-on-surface flex items-center justify-center gap-1">
                        <span class="text-primary">4</span>
                        <span class="inline-flex items-center justify-center mx-2 text-outline-variant relative">
                            <span class="material-symbols-outlined text-[72px] md:text-[96px] text-tertiary-fixed-dim" style="font-variation-settings: 'FILL' 0;">sync_problem</span>
                        </span>
                        <span class="text-primary">4</span>
                    </h1>
                </div>

                <!-- Headline & Description -->
                <h2 class="font-headline-xl text-headline-xl text-on-surface mt-2 mb-4 tracking-tight font-bold">
                    {{ __('errors.404.title') }}
                </h2>
                <p class="font-body-lg text-body-lg text-on-surface-variant max-w-2xl mx-auto leading-relaxed mb-8">
                    {{ __('errors.404.description') }}
                </p>

                <!-- CTA Buttons Primary Cluster -->
                <div class="flex flex-wrap items-center justify-center gap-3 w-full sm:w-auto">
                    <a class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-primary text-on-primary hover:bg-primary-container font-label-md text-label-md font-semibold shadow-sm transition-colors duration-150" href="{{ url('/') }}">
                        <span class="material-symbols-outlined text-[18px]">home</span>
                        <span>{{ __('errors.common.back_home') }}</span>
                    </a>
                    <button class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-surface-container-lowest border border-outline-variant hover:bg-surface-container-low text-on-surface font-label-md text-label-md font-medium transition-colors duration-150" onclick="window.history.length > 1 ? window.history.back() : window.location.href='{{ url('/') }}'" type="button">
                        <span class="material-symbols-outlined text-[18px] text-tertiary">arrow_back</span>
                        <span>{{ __('errors.common.back_previous') }}</span>
                    </button>
                    <a class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-surface-container-lowest border border-outline-variant hover:bg-surface-container-low text-tertiary hover:text-error font-label-md text-label-md transition-colors duration-150" href="{{ route('contact') }}">
                        <span class="material-symbols-outlined text-[18px]">flag</span>
                        <span>{{ __('errors.404.report_broken') }}</span>
                    </a>
                </div>
            </div>
        </div>
    </main>
</x-public.layout>

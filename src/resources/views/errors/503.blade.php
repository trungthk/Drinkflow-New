<x-public.layout :title="__('errors.503.page_title')">
    <main class="flex-1 w-full max-w-[1200px] mx-auto px-6 py-8" x-data="{
        countdown: 30,
        init() {
            setInterval(() => {
                if (this.countdown > 1) {
                    this.countdown--;
                } else {
                    location.reload();
                }
            }, 1000);
        }
    }">
        <!-- Top Status Banner -->
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 shadow-sm mb-8 relative overflow-hidden">
            <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-primary"></div>
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                <!-- Left: Badge & 503 Visual Accent -->
                <div class="flex items-start gap-5">
                    <div class="flex flex-col items-center justify-center bg-surface-container border border-outline-variant rounded-lg px-5 py-3 select-none shrink-0">
                        <span class="text-caption font-caption text-tertiary tracking-wider uppercase">HTTP STATUS</span>
                        <span class="text-headline-xl font-headline-xl text-primary font-bold tracking-tight">503</span>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2 mb-2">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-caption font-caption bg-error-container text-on-error-container border border-error/20 font-semibold">
                                <span class="w-1.5 h-1.5 rounded-full bg-error animate-pulse"></span>
                                {{ __('errors.503.badge_main') }}
                            </span>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-caption font-caption bg-surface-container text-tertiary border border-outline-variant font-medium">
                                <span class="material-symbols-outlined text-xs">build_circle</span>
                                {{ __('errors.503.badge_sub') }}
                            </span>
                        </div>
                        <h1 class="text-headline-lg font-headline-lg text-on-surface tracking-tight mb-2 font-bold">
                            {{ __('errors.503.title') }}
                        </h1>
                        <p class="text-body-md font-body-md text-on-surface-variant max-w-3xl leading-relaxed">
                            {{ __('errors.503.description') }}
                        </p>
                    </div>
                </div>

                <!-- Right: Primary Recovery Action -->
                <div class="flex flex-row lg:flex-col items-stretch w-full lg:w-auto gap-2 shrink-0">
                    <button class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-primary text-on-primary hover:bg-primary-container text-label-md font-label-md font-semibold transition-colors duration-150 shadow-sm" onclick="location.reload()" type="button">
                        <span class="material-symbols-outlined text-lg">autorenew</span>
                        <span>{{ __('errors.503.refresh') }}</span>
                    </button>
                    <span class="text-caption font-caption text-tertiary text-center lg:text-right">
                        {{ __('errors.503.auto_retry_in') }} <span class="font-semibold text-on-surface" x-text="countdown">30</span>s
                    </span>
                </div>
            </div>
        </div>

        <!-- Bento Grid Details -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left 8 Columns: Maintenance Timeline -->
            <div class="lg:col-span-8 flex flex-col gap-6">
                <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 shadow-sm">
                    <div class="flex items-center justify-between border-b border-outline-variant pb-4 mb-5">
                        <div class="flex items-center gap-2.5">
                            <span class="material-symbols-outlined text-primary text-xl">schedule</span>
                            <h2 class="text-headline-sm font-headline-sm text-on-surface font-bold">{{ __('errors.503.schedule_title') }}</h2>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-caption font-caption text-on-surface-variant">{{ __('errors.503.estimated_remaining') }}</span>
                            <span class="px-2 py-0.5 rounded bg-surface-container text-on-surface text-caption font-caption font-semibold border border-outline-variant">
                                {{ __('errors.503.remaining_val') }}
                            </span>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-4 p-4 rounded-lg bg-surface-container-low border border-outline-variant">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-surface-container-lowest rounded-lg border border-outline-variant text-primary flex items-center justify-center">
                                <span class="material-symbols-outlined text-lg">timelapse</span>
                            </div>
                            <div>
                                <div class="text-label-sm font-label-sm text-tertiary uppercase tracking-wider">{{ __('errors.503.window_label') }}</div>
                                <div class="text-headline-sm font-headline-sm text-on-surface font-semibold">{{ __('errors.503.window_val') }}</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="text-right">
                                <div class="text-caption font-caption text-tertiary">{{ __('errors.503.status_progress') }}</div>
                                <div class="text-label-sm font-label-sm font-semibold text-primary">{{ __('errors.503.status_val') }}</div>
                            </div>
                            <div class="w-10 h-10 rounded-full border-2 border-primary border-t-transparent animate-spin flex items-center justify-center">
                                <span class="text-caption font-caption text-primary font-bold">85%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right 4 Columns: Communication & Support -->
            <div class="lg:col-span-4 flex flex-col gap-6">
                <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 shadow-sm space-y-3">
                    <h3 class="text-headline-sm font-headline-sm text-on-surface pb-2 border-b border-outline-variant font-bold">
                        {{ __('errors.503.channels_title') }}
                    </h3>
                    <div class="flex flex-col gap-2.5">
                        <a class="w-full inline-flex items-center justify-between p-3 rounded-lg border border-outline-variant hover:bg-surface-container-low text-on-surface transition-colors duration-150" href="{{ route('contact') }}">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-primary text-lg">support_agent</span>
                                <span class="text-label-md font-label-md font-medium">{{ __('errors.common.contact_support') }}</span>
                            </div>
                            <span class="material-symbols-outlined text-sm text-tertiary">arrow_outward</span>
                        </a>
                        <a class="w-full inline-flex items-center justify-between p-3 rounded-lg border border-outline-variant hover:bg-surface-container-low text-on-surface transition-colors duration-150" href="{{ url('/') }}">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-primary text-lg">home</span>
                                <span class="text-label-md font-label-md font-medium">{{ __('errors.common.back_home') }}</span>
                            </div>
                            <span class="material-symbols-outlined text-sm text-tertiary">arrow_outward</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</x-public.layout>

<x-public.layout :title="__('errors.429.page_title')">
    <main class="flex-1 w-full max-w-[1200px] mx-auto px-6 py-8" x-data="{
        countdown: 30,
        totalTime: 60,
        progress: 50,
        timer: null,
        init() {
            this.timer = setInterval(() => {
                if (this.countdown > 1) {
                    this.countdown--;
                    this.progress = ((this.totalTime - this.countdown) / this.totalTime) * 100;
                } else {
                    location.reload();
                }
            }, 1000);
        }
    }">
        <div class="space-y-6">
            <!-- Rate Limit Main Hero Card -->
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 md:p-8 shadow-sm relative overflow-hidden">
                <div class="absolute top-0 left-0 right-0 h-1.5 bg-amber-500"></div>

                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6 pb-6 border-b border-outline-variant">
                    <div class="space-y-2">
                        <!-- Warning Badge -->
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-caption font-caption bg-amber-50 text-amber-800 border border-amber-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-600 animate-pulse"></span>
                            <span>{{ __('errors.429.badge') }}</span>
                        </div>
                        <h1 class="text-headline-lg font-headline-lg text-on-surface tracking-tight font-bold">
                            {{ __('errors.429.title') }}
                        </h1>
                    </div>

                    <!-- Numeric visual anchor -->
                    <div class="flex items-center px-4 py-2 rounded-xl bg-surface-container border border-outline-variant select-none">
                        <span class="text-[44px] font-bold text-amber-700 tracking-tighter leading-none">429</span>
                    </div>
                </div>

                <!-- Description Paragraph -->
                <p class="text-body-md font-body-md text-on-surface-variant pt-4 leading-relaxed">
                    {{ __('errors.429.description') }}
                </p>

                <!-- Cooldown Timer Card -->
                <div class="mt-6 p-5 rounded-lg bg-surface-container-low border border-outline-variant space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-[20px]">timer</span>
                            <span class="text-label-md font-label-md text-on-surface">
                                {{ __('errors.429.cooldown') }} <span class="font-bold text-primary" x-text="countdown + ' ' + '{{ __('errors.429.seconds') }}'">30s</span>
                            </span>
                        </div>
                        <span class="text-caption font-caption text-on-surface-variant">{{ __('errors.429.token_bucket') }}</span>
                    </div>

                    <!-- Progress Bar -->
                    <div class="w-full bg-surface-container-high rounded-full h-2 overflow-hidden">
                        <div class="bg-primary h-2 rounded-full transition-all duration-1000 ease-linear" :style="'width: ' + progress + '%;'"></div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                        <span class="text-caption font-caption text-tertiary">
                            {{ __('errors.429.sliding_window') }}
                        </span>
                        <button class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-primary hover:bg-primary-container text-on-primary text-label-sm font-label-sm shadow-sm transition-colors duration-150" onclick="location.reload()" type="button">
                            <span class="material-symbols-outlined text-[16px]">refresh</span>
                            <span>{{ __('errors.429.retry_now') }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- User Instruction & Recommendations Card -->
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 shadow-sm space-y-3">
                <h3 class="text-label-md font-label-md text-on-surface font-semibold flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-[18px]">info</span>
                    {{ __('errors.429.guide_title') }}
                </h3>
                <ul class="space-y-2.5 text-body-sm font-body-sm text-on-surface-variant">
                    <li class="flex items-start gap-2">
                        <span class="material-symbols-outlined text-primary text-[18px] mt-0.5">check_circle</span>
                        <span>{{ __('errors.429.guide_1') }}</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="material-symbols-outlined text-amber-600 text-[18px] mt-0.5">warning</span>
                        <span>{{ __('errors.429.guide_2') }}</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="material-symbols-outlined text-primary text-[18px] mt-0.5">support_agent</span>
                        <span>{{ __('errors.429.guide_3') }}</span>
                    </li>
                </ul>
            </div>

            <!-- Action Buttons Row (CTA) -->
            <div class="flex flex-col sm:flex-row items-center justify-end gap-3 pt-2">
                <a class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-lg bg-primary hover:bg-primary-container text-on-primary text-label-md font-label-md shadow-sm transition-colors duration-150 font-medium" href="{{ url('/') }}">
                    <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                    <span>{{ __('errors.429.back_home') }}</span>
                </a>
                <a class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-lg border border-outline-variant bg-surface-container-lowest hover:bg-surface-container-low text-on-surface text-label-md font-label-md transition-colors duration-150 font-medium" href="{{ route('contact') }}">
                    <span class="material-symbols-outlined text-[18px]">headset_mic</span>
                    <span>{{ __('errors.429.contact_it') }}</span>
                </a>
            </div>
        </div>
    </main>
</x-public.layout>

<x-public.layout :title="__('errors.500.page_title')">
    <main class="w-full max-w-[1200px] mx-auto px-6 py-8 flex-1 flex flex-col gap-8" x-data="{
        countdown: 15,
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
        <!-- BANNER MÃ LỖI HTTP 500 NỔI BẬT -->
        <section class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 lg:p-8 shadow-sm">
            <div class="flex flex-col lg:flex-row items-start justify-between gap-6 pb-6 border-b border-outline-variant">
                <!-- Status indicator & Big Error Heading -->
                <div class="flex-1 space-y-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-label-sm font-label-sm bg-error-container text-on-error-container font-semibold border border-error/20">
                            <span class="w-2 h-2 rounded-full bg-error animate-ping"></span>
                            {{ __('errors.500.badge_main') }}
                        </span>
                        <span class="px-2.5 py-1 rounded-full text-caption font-caption bg-surface-container-high text-on-surface-variant font-medium">
                            {{ __('errors.500.badge_sub') }}
                        </span>
                    </div>
                    <h1 class="text-headline-xl font-headline-xl text-on-surface tracking-tight font-bold">
                        {{ __('errors.500.title') }}
                    </h1>
                    <p class="text-body-lg font-body-lg text-on-surface-variant max-w-3xl leading-relaxed">
                        {{ __('errors.500.description') }}
                    </p>
                </div>

                <!-- Distinct Monospace 500 Stamp Graphic -->
                <div class="flex flex-col items-center justify-center p-6 bg-surface-container-low border border-outline-variant rounded-xl min-w-[200px] select-none">
                    <div class="flex items-center text-error mb-1">
                        <span class="material-symbols-outlined text-[36px]" style="font-variation-settings: 'FILL' 1;">cloud_off</span>
                    </div>
                    <div class="text-[52px] leading-none font-bold tracking-tighter text-error font-mono">
                        500
                    </div>
                    <span class="text-caption font-caption text-outline uppercase tracking-wider mt-1">{{ __('errors.500.stamp') }}</span>
                </div>
            </div>

            <!-- Action Cluster -->
            <div class="pt-6 flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-3">
                    <button class="inline-flex items-center gap-2 bg-primary hover:bg-primary-container text-on-primary px-5 py-2.5 rounded-lg text-label-md font-label-md font-semibold shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2" onclick="location.reload();" type="button">
                        <span class="material-symbols-outlined text-[18px]">autorenew</span>
                        <span>{{ __('errors.500.retry') }}</span>
                        <span class="ml-1 text-caption font-caption bg-on-primary/20 px-2 py-0.5 rounded-full" x-text="countdown + 's'">15s</span>
                    </button>
                    <a class="inline-flex items-center gap-2 bg-surface-container-lowest hover:bg-surface-container-low text-on-surface border border-outline-variant px-4 py-2.5 rounded-lg text-label-md font-label-md font-medium transition-colors" href="{{ url('/me') }}">
                        <span class="material-symbols-outlined text-[18px]">home</span>
                        <span>{{ __('errors.500.back_portal') }}</span>
                    </a>
                    <a class="inline-flex items-center gap-2 bg-surface-container-lowest hover:bg-error-container/20 text-error border border-error/30 px-4 py-2.5 rounded-lg text-label-md font-label-md font-medium transition-colors" href="{{ route('contact') }}">
                        <span class="material-symbols-outlined text-[18px]">report_problem</span>
                        <span>{{ __('errors.500.report_incident') }}</span>
                    </a>
                </div>

                <!-- Telemetry Pill -->
                <div class="text-caption font-caption text-outline flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-error"></span>
                    {{ __('errors.500.auto_recovery') }}
                </div>
            </div>
        </section>

        <!-- KHỐI CHẨN ĐOÁN KỸ THUẬT & TRẠNG THÁI DỊCH VỤ -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-stretch">
            <!-- CỘT 1: Support & Hotline Box -->
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 shadow-sm flex flex-col justify-between space-y-5">
                <div class="space-y-4">
                    <div class="flex items-center gap-2 pb-3 border-b border-outline-variant">
                        <span class="material-symbols-outlined text-primary text-[24px]">contact_support</span>
                        <h2 class="text-headline-sm font-headline-sm text-on-surface font-semibold">{{ __('errors.500.hotline_box') }}</h2>
                    </div>
                    <div class="space-y-3.5 text-body-sm font-body-sm">
                        <div class="p-3.5 bg-surface-container-low rounded-lg border border-outline-variant flex items-center justify-between">
                            <div>
                                <span class="text-caption font-caption text-outline block">{{ __('errors.500.hotline_label') }}</span>
                                <span class="text-headline-md font-headline-md text-primary font-bold">{{ __('errors.500.hotline_val') }}</span>
                            </div>
                            <a class="p-2.5 rounded-lg bg-primary text-on-primary hover:bg-primary-container transition-colors inline-flex items-center justify-center" href="tel:19006868">
                                <span class="material-symbols-outlined text-[20px]">call</span>
                            </a>
                        </div>
                        <div class="p-3.5 bg-surface-container-low rounded-lg border border-outline-variant flex items-center justify-between">
                            <div>
                                <span class="font-mono text-body-sm font-semibold text-on-surface">{{ __('errors.500.slack_incident') }}</span>
                            </div>
                        </div>
                        <div class="p-3.5 bg-surface-container-low rounded-lg border border-outline-variant flex items-center justify-between">
                            <div>
                                <span class="font-mono text-body-sm text-primary font-medium">{{ __('errors.500.status_page') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CỘT 2: Troubleshooting Steps Box -->
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 shadow-sm flex flex-col justify-between space-y-5">
                <div class="space-y-4">
                    <div class="flex items-center gap-2 pb-3 border-b border-outline-variant">
                        <span class="material-symbols-outlined text-outline text-[22px]">checklist</span>
                        <h2 class="text-headline-sm font-headline-sm text-on-surface font-semibold">{{ __('errors.500.troubleshoot_title') }}</h2>
                    </div>
                    <ul class="space-y-3.5 text-body-sm font-body-sm text-on-surface-variant">
                        <li class="flex items-start gap-3">
                            <span class="w-5 h-5 rounded-full bg-surface-container-high text-on-surface font-semibold text-caption flex items-center justify-center shrink-0 mt-0.5">1</span>
                            <span>{{ __('errors.500.step_1') }}</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="w-5 h-5 rounded-full bg-surface-container-high text-on-surface font-semibold text-caption flex items-center justify-center shrink-0 mt-0.5">2</span>
                            <span>{{ __('errors.500.step_2') }}</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="w-5 h-5 rounded-full bg-surface-container-high text-on-surface font-semibold text-caption flex items-center justify-center shrink-0 mt-0.5">3</span>
                            <span>{{ __('errors.500.step_3') }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </main>
</x-public.layout>

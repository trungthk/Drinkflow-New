{{-- Shown by App\Http\Middleware\CheckMaintenanceMode to everyone except superadmins while maintenance is active.
     $maintenance comes from SystemSettingsService::maintenanceState(). Only sign-in/locale routes work meanwhile,
     so this page deliberately links nowhere else. --}}
@php
    $starts = $maintenance['starts'] ?? null;
    $ends = $maintenance['ends'] ?? null;
    $dateFormat = 'H:i d/m/Y';
@endphp
<x-public.layout :title="__('errors.maintenance.page_title')">
    <main class="flex-1 w-full max-w-[960px] mx-auto px-4 sm:px-6 py-10" x-data="{
        countdown: 60,
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
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 shadow-sm mb-6 relative overflow-hidden">
            <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-amber-500"></div>
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                <div class="flex items-start gap-5">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-amber-50 border border-amber-200 text-amber-600">
                        <span class="material-symbols-outlined text-[32px]" aria-hidden="true">construction</span>
                    </div>
                    <div>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 mb-2 rounded-full text-caption font-caption bg-amber-50 text-amber-800 border border-amber-200 font-semibold">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                            {{ __('errors.maintenance.badge') }}
                        </span>
                        <h1 class="text-headline-lg font-headline-lg text-on-surface tracking-tight mb-2 font-bold">{{ __('errors.maintenance.title') }}</h1>
                        <p class="text-body-md font-body-md text-on-surface-variant max-w-2xl leading-relaxed">{{ __('errors.maintenance.description') }}</p>
                    </div>
                </div>
                <div class="flex flex-row md:flex-col items-center md:items-stretch w-full md:w-auto gap-2 shrink-0">
                    <button class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-primary text-on-primary hover:bg-primary-container text-label-md font-label-md font-semibold transition-colors duration-150 shadow-sm" onclick="location.reload()" type="button">
                        <span class="material-symbols-outlined text-lg" aria-hidden="true">autorenew</span>
                        <span>{{ __('errors.maintenance.refresh') }}</span>
                    </button>
                    <span class="text-caption font-caption text-tertiary text-center">
                        {{ __('errors.maintenance.auto_retry_in') }} <span class="font-semibold text-on-surface" x-text="countdown">60</span>s
                    </span>
                </div>
            </div>
        </div>

        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 shadow-sm">
            <div class="flex items-center gap-2.5 border-b border-outline-variant pb-4 mb-5">
                <span class="material-symbols-outlined text-primary text-xl" aria-hidden="true">schedule</span>
                <h2 class="text-headline-sm font-headline-sm text-on-surface font-bold">{{ __('errors.maintenance.window_title') }}</h2>
            </div>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="p-4 rounded-lg bg-surface-container-low border border-outline-variant">
                    <dt class="text-label-sm font-label-sm text-tertiary uppercase tracking-wider">{{ __('errors.maintenance.starts_at') }}</dt>
                    <dd class="mt-1 text-headline-sm font-headline-sm text-on-surface font-semibold">{{ $starts?->format($dateFormat) ?? __('errors.maintenance.not_scheduled') }}</dd>
                </div>
                <div class="p-4 rounded-lg bg-surface-container-low border border-outline-variant">
                    <dt class="text-label-sm font-label-sm text-tertiary uppercase tracking-wider">{{ __('errors.maintenance.ends_at') }}</dt>
                    <dd class="mt-1 text-headline-sm font-headline-sm text-on-surface font-semibold">{{ $ends?->format($dateFormat) ?? __('errors.maintenance.not_scheduled') }}</dd>
                </div>
            </dl>
            <p class="mt-4 text-body-sm font-body-sm text-on-surface-variant">
                {{ $ends ? __('errors.maintenance.back_at', ['time' => $ends->format($dateFormat)]) : __('errors.maintenance.no_end') }}
            </p>
        </div>

        <p class="mt-6 text-center text-caption font-caption">
            <a class="inline-flex items-center gap-1 text-tertiary hover:text-primary" href="{{ route('admin.login.page') }}">
                <span class="material-symbols-outlined text-sm" aria-hidden="true">admin_panel_settings</span>{{ __('errors.maintenance.admin_login') }}
            </a>
        </p>
    </main>
</x-public.layout>

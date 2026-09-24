{{-- Shown by App\Http\Middleware\CheckMaintenanceMode to everyone except superadmins while maintenance is active.
     $maintenance comes from SystemSettingsService::maintenanceState(). The page shows only the maintenance
     notice (no header, footer or links) and reloads itself every 60 seconds. --}}
@php
    $starts = $maintenance['starts'] ?? null;
    $ends = $maintenance['ends'] ?? null;
    $dateFormat = 'H:i d/m/Y';
@endphp
<x-maintenance.layout :title="__('errors.maintenance.page_title')">
    <main class="mt-card">
        <div class="mt-icon" aria-hidden="true">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
            </svg>
        </div>

        <div>
            <span class="mt-badge"><span class="mt-dot"></span>{{ __('errors.maintenance.badge') }}</span>
        </div>
        <h1 class="mt-title">{{ __('errors.maintenance.title') }}</h1>
        <p class="mt-desc">{{ __('errors.maintenance.description') }}</p>

        <dl class="mt-window" aria-label="{{ __('errors.maintenance.window_title') }}">
            <div>
                <dt>{{ __('errors.maintenance.starts_at') }}</dt>
                <dd>{{ $starts?->format($dateFormat) ?? __('errors.maintenance.not_scheduled') }}</dd>
            </div>
            <div>
                <dt>{{ __('errors.maintenance.ends_at') }}</dt>
                <dd>{{ $ends?->format($dateFormat) ?? __('errors.maintenance.not_scheduled') }}</dd>
            </div>
        </dl>
        <p class="mt-note">
            {{ $ends ? __('errors.maintenance.back_at', ['time' => $ends->format($dateFormat)]) : __('errors.maintenance.no_end') }}
        </p>

        <button class="mt-refresh" type="button" onclick="location.reload()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 12a9 9 0 1 1-2.64-6.36"/><path d="M21 3v6h-6"/>
            </svg>
            {{ __('errors.maintenance.refresh') }}
        </button>
        <p class="mt-retry">
            {{ __('errors.maintenance.auto_retry_in') }} <strong id="mt-countdown">60</strong>s
        </p>
    </main>

    <script>
        (function () {
            var el = document.getElementById('mt-countdown');
            var remaining = 60;
            setInterval(function () {
                remaining -= 1;
                if (remaining <= 0) {
                    location.reload();
                    return;
                }
                el.textContent = remaining;
            }, 1000);
        })();
    </script>
</x-maintenance.layout>

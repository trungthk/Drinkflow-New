@extends('superadmin.layout', ['title' => __('superadmin.dashboard.title'), 'active' => 'dashboard'])

@section('content')
    <div class="superadmin-heading">
        <div>
            <p class="superadmin-eyebrow">{{ __('superadmin.dashboard.eyebrow') }}</p>
            <h1>{{ __('superadmin.dashboard.title') }}</h1>
            <p>{{ __('superadmin.dashboard.description') }}</p>
        </div>
        <div class="superadmin-actions"><a class="sa-button secondary" href="{{ route('superadmin.socket.page') }}"><span
                    class="material-symbols-outlined">hub</span>{{ __('superadmin.dashboard.live_monitoring') }}</a><a class="sa-button"
                href="{{ route('superadmin.system.page') }}"><span class="material-symbols-outlined">build_circle</span>System
                {{ __('superadmin.dashboard.system_settings') }}</a></div>
    </div>
    <div id="notice" class="sa-notice"></div>
    <section class="sa-grid kpis kpis-4">
        <article class="sa-card sa-kpi"><span class="label">{{ __('superadmin.dashboard.total_rooms') }}</span><strong id="total-rooms"
                class="value">—</strong><span id="active-rooms" class="hint">{{ __('superadmin.common.loading') }}</span></article>
        <article class="sa-card sa-kpi"><span class="label">{{ __('superadmin.dashboard.global_users') }}</span><strong id="total-users"
                class="value">—</strong><span id="active-users" class="hint">{{ __('superadmin.common.loading') }}</span></article>
        <article class="sa-card sa-kpi"><span class="label">{{ __('superadmin.dashboard.admins') }}</span><strong id="total-admins"
                class="value">—</strong><span class="hint">{{ __('superadmin.dashboard.system_admins') }}</span></article>
        <article class="sa-card sa-kpi"><span class="label">{{ __('superadmin.dashboard.orders_today') }}</span><strong id="orders-today"
                class="value">—</strong><span class="hint">{{ __('superadmin.dashboard.non_cancelled_orders') }}</span></article>
    </section>
    <div class="sa-split">
        <section class="sa-card sa-section">
            <div class="sa-section-header">
                <div>
                    <h2>{{ __('superadmin.dashboard.system_health') }}</h2>
                    <p>{{ __('superadmin.dashboard.health_description') }}</p>
                </div><span class="status-pill status-ok"><span class="status-dot"></span>{{ __('superadmin.dashboard.telemetry') }}</span>
            </div>
            <div class="sa-health-list">
                <div class="sa-health-row">
                    <div><strong>{{ __('superadmin.dashboard.database') }}</strong><small id="database-status">{{ __('superadmin.dashboard.checking') }}</small></div><span
                        class="material-symbols-outlined">database</span>
                </div>
                <div class="sa-health-row">
                    <div><strong>{{ __('superadmin.dashboard.queue') }}</strong><small id="queue-status">{{ __('superadmin.dashboard.checking') }}</small></div><span
                        class="material-symbols-outlined">sync_alt</span>
                </div>
                <div class="sa-health-row">
                    <div><strong>Socket.IO</strong><small id="socket-status">{{ __('superadmin.dashboard.checking') }}</small></div><span
                        class="material-symbols-outlined">hub</span>
                </div>
                <div class="sa-health-row">
                    <div><strong>{{ __('superadmin.dashboard.mail') }}</strong><small id="mail-status">{{ __('superadmin.dashboard.checking') }}</small></div><span
                        class="material-symbols-outlined">mail</span>
                </div>
                <div class="sa-health-row">
                    <div><strong>{{ __('superadmin.dashboard.storage') }}</strong><small id="storage-status">{{ __('superadmin.dashboard.checking') }}</small></div><span
                        class="material-symbols-outlined">hard_drive</span>
                </div>
                <div class="sa-health-row" id="supervisor-row" hidden>
                    <div><strong>{{ __('superadmin.dashboard.supervisor') }}</strong><small id="supervisor-status">{{ __('superadmin.dashboard.checking') }}</small></div><span
                        class="material-symbols-outlined">engineering</span>
                </div>
            </div>
        </section>
        <section class="sa-card sa-section">
            <div class="sa-section-header">
                <div>
                    <h2>{{ __('superadmin.dashboard.quick_access') }}</h2>
                    <p>{{ __('superadmin.dashboard.quick_access_description') }}</p>
                </div>
            </div>
            <div class="sa-health-list"><a class="sa-health-row" href="{{ route('superadmin.security.page') }}">
                    <div><strong>{{ __('superadmin.layout.security_center') }}</strong><small>{{ __('superadmin.dashboard.failed_login_events') }}</small></div><span
                        class="material-symbols-outlined">arrow_forward</span>
                </a><a class="sa-health-row" href="{{ route('superadmin.audit.page') }}">
                    <div><strong>{{ __('superadmin.audit.title') }}</strong><small>{{ __('superadmin.dashboard.global_change_history') }}</small></div><span
                        class="material-symbols-outlined">arrow_forward</span>
                </a><a class="sa-health-row" href="{{ route('superadmin.queue.page') }}">
                    <div><strong>{{ __('superadmin.dashboard.failed_jobs') }}</strong><small>{{ __('superadmin.dashboard.failed_jobs_description') }}</small></div><span
                        class="material-symbols-outlined">arrow_forward</span>
                </a></div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        dfApi('{{ route('superadmin.dashboard') }}').then(({
            data
        }) => {
            document.querySelector('#total-rooms').textContent = data.total_rooms;
            document.querySelector('#active-rooms').textContent = @js(__('superadmin.dashboard.active_rooms', ['count' => '__COUNT__'])).replace('__COUNT__', data.active_rooms);
            document.querySelector('#total-users').textContent = data.total_global_users;
            document.querySelector('#active-users').textContent = @js(__('superadmin.dashboard.active_users', ['count' => '__COUNT__'])).replace('__COUNT__', data.active_global_users);
            document.querySelector('#total-admins').textContent = data.total_admins;
            document.querySelector('#orders-today').textContent = data.orders_today;
            const health = data.system_health;
            document.querySelector('#database-status').innerHTML = statusPill(health.database.status);
            document.querySelector('#queue-status').textContent =
                `${health.queue.connection} · ${@js(__('superadmin.dashboard.failed_count', ['count' => '__COUNT__'])).replace('__COUNT__', health.queue.failed_jobs)}`;
            document.querySelector('#socket-status').innerHTML = statusPill(health.socket.status);
            document.querySelector('#mail-status').innerHTML = statusPill(health.mail.configured ? 'configured' : 'not_configured');
            document.querySelector('#storage-status').innerHTML = statusPill(health.storage.status);
            if (health.supervisor) {
                document.querySelector('#supervisor-row').hidden = false;
                document.querySelector('#supervisor-status').innerHTML = statusPill(health.supervisor.status);
            }
        }).catch(error => {
            const n = document.querySelector('#notice');
            n.textContent = error.message;
            n.className = 'sa-notice error is-visible';
        });
    </script>
@endpush

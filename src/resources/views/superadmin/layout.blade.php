<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Superadmin' }} · DrinkFlow</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,400..700,0..1,-50..200&display=block"
        rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/superadmin.css', 'resources/js/app.js'])
    @endif
</head>

<body data-submit-loading-text="{{ __('global.common.loading') }}" data-status-labels="{{ json_encode([
        'active' => __('superadmin.common.active'), 'disabled' => __('superadmin.common.disabled'),
        'inactive' => __('superadmin.common.inactive'),
        'archived' => __('superadmin.common.archived'), 'blocked' => __('superadmin.common.blocked'),
        'pending' => __('superadmin.common.pending'), 'scheduled' => __('superadmin.common.scheduled'),
        'closed' => __('superadmin.common.closed'), 'cancelled' => __('superadmin.common.cancelled'),
        'draft' => __('admin.status_draft'), 'closing' => __('admin.status_closing'),
        'high' => __('superadmin.common.high'), 'medium' => __('superadmin.common.medium'),
        'low' => __('superadmin.common.low'),
    ] + __('superadmin.health'), JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) }}" class="superadmin-shell">
    <x-superadmin.loading />
    <aside class="superadmin-sidebar" id="superadmin-sidebar">
        <div class="superadmin-brand">
            <div class="superadmin-logo">D</div>
            <div><strong>DrinkFlow</strong><span>{{ __('superadmin.layout.enterprise_superadmin') }}</span></div>
        </div>
        <nav class="superadmin-nav">
            <span class="superadmin-nav-label">{{ __('superadmin.common.core_system') }}</span>
            <a class="{{ ($active ?? '') === 'dashboard' ? 'is-active' : '' }}"
                href="{{ route('superadmin.dashboard') }}"><span
                    class="material-symbols-outlined">monitoring</span>{{ __('superadmin.layout.dashboard_health') }}</a>
            <a class="{{ ($active ?? '') === 'rooms' ? 'is-active' : '' }}"
                href="{{ route('superadmin.rooms.page') }}"><span
                    class="material-symbols-outlined">meeting_room</span>{{ __('superadmin.layout.room_management') }}</a>
            <a class="{{ ($active ?? '') === 'admins' ? 'is-active' : '' }}"
                href="{{ route('superadmin.admins.page') }}"><span
                    class="material-symbols-outlined">admin_panel_settings</span>{{ __('superadmin.layout.admin_assignments') }}</a>
            <a class="{{ ($active ?? '') === 'users' ? 'is-active' : '' }}"
                href="{{ route('superadmin.global-users.page') }}"><span
                    class="material-symbols-outlined">badge</span>{{ __('superadmin.layout.global_users') }}</a>
            <span class="superadmin-nav-label">{{ __('superadmin.common.platform_data') }}</span>
            <a class="{{ ($active ?? '') === 'campaigns' ? 'is-active' : '' }}"
                href="{{ route('superadmin.campaigns.page') }}"><span
                    class="material-symbols-outlined">campaign</span>{{ __('superadmin.layout.global_campaigns') }}</a>
            <a class="{{ ($active ?? '') === 'feedbacks' ? 'is-active' : '' }}"
                href="{{ route('superadmin.feedbacks.page') }}"><span
                    class="material-symbols-outlined">rate_review</span>{{ __('superadmin.layout.feedbacks') }}</a>
            <a class="{{ ($active ?? '') === 'audit' ? 'is-active' : '' }}"
                href="{{ route('superadmin.audit.page') }}"><span
                    class="material-symbols-outlined">history_toggle_off</span>{{ __('superadmin.layout.audit_logs') }}</a>
            <span class="superadmin-nav-label">{{ __('superadmin.common.infra_security') }}</span>
            <a class="{{ ($active ?? '') === 'security' ? 'is-active' : '' }}"
                href="{{ route('superadmin.security.page') }}"><span
                    class="material-symbols-outlined">shield_locked</span>{{ __('superadmin.layout.security_center') }}</a>
            <a class="{{ ($active ?? '') === 'socket' ? 'is-active' : '' }}"
                href="{{ route('superadmin.socket.page') }}"><span
                    class="material-symbols-outlined">hub</span>{{ __('superadmin.layout.socket_queue') }}</a>
            <a class="{{ ($active ?? '') === 'system' ? 'is-active' : '' }}"
                href="{{ route('superadmin.system.page') }}"><span
                    class="material-symbols-outlined">build_circle</span>{{ __('superadmin.layout.system_settings') }}</a>
            <a class="{{ ($active ?? '') === 'notifications' ? 'is-active' : '' }}"
                href="{{ route('superadmin.notifications.page') }}"><span
                    class="material-symbols-outlined">notifications</span>{{ __('superadmin.layout.global_notifications') }}</a>
            <a class="{{ ($active ?? '') === 'versions' ? 'is-active' : '' }}"
                href="{{ route('superadmin.versions.page') }}"><span
                    class="material-symbols-outlined">new_releases</span>{{ __('superadmin.layout.versions') }}</a>
        </nav>
        <div class="superadmin-trust"><span class="material-symbols-outlined">verified_user</span>
            <div><strong>{{ __('superadmin.layout.zero_trust') }}</strong><small>{{ __('superadmin.layout.root_authority') }}</small></div>
        </div>
    </aside>
    <div class="superadmin-main">
        <x-superadmin.maintenance-banner />
        <header class="superadmin-topbar"><button class="superadmin-menu"
                onclick="document.getElementById('superadmin-sidebar').classList.toggle('is-open')"><span
                    class="material-symbols-outlined">menu</span></button>
            <div class="superadmin-clearance"><span class="material-symbols-outlined">verified</span>{{ __('superadmin.layout.clearance') }}</div>
            <div class="superadmin-top-status"><span class="status-dot"></span><span id="top-socket-status">System
                    {{ __('superadmin.layout.monitoring_active') }}</span></div>
            <div class="superadmin-profile">
                <div><strong>{{ request()->user('admin')->name }}</strong><small>{{ __('superadmin.layout.root_admin') }}</small></div>
                <div class="superadmin-avatar">{{ strtoupper(substr(request()->user('admin')->name, 0, 1)) }}</div>
                <form method="post" action="{{ route('admin.logout') }}">@csrf<button title="{{ __('superadmin.layout.logout') }}"
                        class="icon-button"><span class="material-symbols-outlined">logout</span></button></form>
            </div>
        </header>
        <main class="superadmin-content">@yield('content')</main>
    </div>
    <x-superadmin.confirm-modal />
    {{-- Temporary compatibility shim: the deferred Vite module (resources/js/superadmin/shared.js) sets
    window.dfApi/escapeHtml/statusPill/money too, but as a `type="module"` script it only runs right before
    DOMContentLoaded — after the plain inline scripts below have already executed. Pages are migrated to
    `import { dfApi, ... } from '../shared'` one at a time (see plan); once every page/@push('scripts') block
    no longer calls these as bare globals, this shim and the matching window.* assignment in shared.js can
    both be deleted. --}}
    <script>
        window.dfApi = window.dfApi || async function (url, options = {}) {
            const headers = { Accept: 'application/json', ...(options.headers || {}) };
            if (options.body && typeof options.body !== 'string') {
                headers['Content-Type'] = 'application/json';
                options.body = JSON.stringify(options.body);
            }
            headers['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const response = await fetch(url, { ...options, headers });
            if (!response.ok) throw new Error((await response.json().catch(() => ({}))).message || `HTTP ${response.status}`);
            return response.json();
        };
        window.money = window.money || (value => new Intl.NumberFormat('vi-VN').format(value || 0) + 'đ');
        window.escapeHtml = window.escapeHtml || (value => String(value ?? '').replace(/[&<>'"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[c] || c)));
        window.statusPill = window.statusPill || (value => {
            const labels = JSON.parse(document.body.dataset.statusLabels || '{}');
            return `<span class="status-pill status-${window.escapeHtml(value)}"><span class="status-dot"></span>${window.escapeHtml(labels[value] || value)}</span>`;
        });
    </script>
    @stack('scripts')
</body>

</html>

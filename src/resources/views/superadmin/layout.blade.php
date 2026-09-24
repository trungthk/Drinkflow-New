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
    {{-- Apply the remembered collapsed sidebar before first paint (resources/js/superadmin/sidebar.js). --}}
    <script>
        try {
            if (localStorage.getItem('df_superadmin_sidebar_collapsed') === 'true') document.documentElement.classList.add('sa-sidebar-collapsed');
        } catch (e) {}
    </script>
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
            <div class="superadmin-brand-text"><strong>DrinkFlow</strong><span>{{ __('superadmin.layout.enterprise_superadmin') }}</span></div>
        </div>
        @php
            $navSections = [
                __('superadmin.common.core_system') => [
                    ['dashboard', 'superadmin.dashboard', 'monitoring', 'superadmin.layout.dashboard_health'],
                    ['rooms', 'superadmin.rooms.page', 'meeting_room', 'superadmin.layout.room_management'],
                    ['admins', 'superadmin.admins.page', 'admin_panel_settings', 'superadmin.layout.admin_assignments'],
                    ['users', 'superadmin.global-users.page', 'badge', 'superadmin.layout.global_users'],
                ],
                __('superadmin.common.platform_data') => [
                    ['campaigns', 'superadmin.campaigns.page', 'campaign', 'superadmin.layout.global_campaigns'],
                    ['feedbacks', 'superadmin.feedbacks.page', 'rate_review', 'superadmin.layout.feedbacks'],
                    ['audit', 'superadmin.audit.page', 'history_toggle_off', 'superadmin.layout.audit_logs'],
                ],
                __('superadmin.common.infra_security') => [
                    ['security', 'superadmin.security.page', 'shield_locked', 'superadmin.layout.security_center'],
                    ['socket', 'superadmin.socket.page', 'hub', 'superadmin.layout.socket_queue'],
                    ['system', 'superadmin.system.page', 'build_circle', 'superadmin.layout.system_settings'],
                    ['notifications', 'superadmin.notifications.page', 'notifications', 'superadmin.layout.global_notifications'],
                    ['versions', 'superadmin.versions.page', 'new_releases', 'superadmin.layout.versions'],
                ],
            ];
        @endphp
        <nav class="superadmin-nav">
            @foreach ($navSections as $sectionLabel => $links)
                <span class="superadmin-nav-label">{{ $sectionLabel }}</span>
                @foreach ($links as [$key, $routeName, $icon, $labelKey])
                    <a class="{{ ($active ?? '') === $key ? 'is-active' : '' }}" href="{{ route($routeName) }}"
                        aria-label="{{ __($labelKey) }}"><span class="material-symbols-outlined">{{ $icon }}</span><span
                            class="superadmin-nav-text">{{ __($labelKey) }}</span><span class="superadmin-nav-tooltip"
                            aria-hidden="true">{{ __($labelKey) }}</span></a>
                @endforeach
            @endforeach
        </nav>
        <div class="superadmin-trust"><span class="material-symbols-outlined">verified_user</span>
            <div class="superadmin-trust-text"><strong>{{ __('superadmin.layout.zero_trust') }}</strong><small>{{ __('superadmin.layout.root_authority') }}</small></div>
        </div>
    </aside>
    <div class="superadmin-main">
        <x-superadmin.maintenance-banner />
        <header class="superadmin-topbar">
            <button type="button" class="superadmin-menu" aria-label="{{ __('superadmin.layout.toggle_sidebar') }}"
                onclick="document.getElementById('superadmin-sidebar').classList.toggle('is-open')"><span
                    class="material-symbols-outlined">menu</span></button>
            <button type="button" class="superadmin-collapse-toggle" data-sidebar-toggle
                title="{{ __('superadmin.layout.toggle_sidebar') }}" aria-label="{{ __('superadmin.layout.toggle_sidebar') }}"><span
                    class="material-symbols-outlined" data-sidebar-toggle-icon>dock_to_left</span></button>
            <div class="superadmin-clearance"><span class="material-symbols-outlined">verified</span>{{ __('superadmin.layout.clearance') }}</div>
            <div class="superadmin-profile">
                <x-superadmin.notification-bell :notifications="$headerNotifications" :presentations="$headerNotificationPresentations" :unread-count="$headerUnreadCount" />
                <div><strong>{{ request()->user('admin')->name }}</strong><small>{{ request()->user('admin')->email }}</small></div>
                <div class="superadmin-avatar">{{ strtoupper(substr(request()->user('admin')->name, 0, 1)) }}</div>
                <button type="button" title="{{ __('superadmin.layout.logout') }}" aria-label="{{ __('superadmin.layout.logout') }}"
                    class="icon-button" data-modal-open="superadmin-logout-modal"><span class="material-symbols-outlined">logout</span></button>
            </div>
        </header>
        <main class="superadmin-content">@yield('content')</main>
    </div>
    <x-superadmin.confirm-modal />
    <x-superadmin.logout-modal />
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

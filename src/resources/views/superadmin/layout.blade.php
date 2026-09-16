<!doctype html>
<html lang="vi">

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
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,400..700,0..1,-50..200&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/superadmin.css', 'resources/js/app.js'])
    @endif
</head>

<body data-submit-loading-text="{{ __('global.common.loading') }}" class="superadmin-shell">
    <aside class="superadmin-sidebar" id="superadmin-sidebar">
        <div class="superadmin-brand">
            <div class="superadmin-logo">D</div>
            <div><strong>DrinkFlow</strong><span>Enterprise Superadmin</span></div>
        </div>
        <nav class="superadmin-nav">
            <span class="superadmin-nav-label">Core System</span>
            <a class="{{ ($active ?? '') === 'dashboard' ? 'is-active' : '' }}"
                href="{{ route('superadmin.dashboard') }}"><span
                    class="material-symbols-outlined">monitoring</span>Dashboard &amp; Health</a>
            <a class="{{ ($active ?? '') === 'rooms' ? 'is-active' : '' }}"
                href="{{ route('superadmin.rooms.page') }}"><span
                    class="material-symbols-outlined">meeting_room</span>Room Management</a>
            <a class="{{ ($active ?? '') === 'admins' ? 'is-active' : '' }}"
                href="{{ route('superadmin.admins.page') }}"><span
                    class="material-symbols-outlined">admin_panel_settings</span>Admin &amp; Assignments</a>
            <a class="{{ ($active ?? '') === 'users' ? 'is-active' : '' }}"
                href="{{ route('superadmin.global-users.page') }}"><span
                    class="material-symbols-outlined">badge</span>Global Users &amp; Identities</a>
            <span class="superadmin-nav-label">Platform Data</span>
            <a class="{{ ($active ?? '') === 'campaigns' ? 'is-active' : '' }}"
                href="{{ route('superadmin.campaigns.page') }}"><span
                    class="material-symbols-outlined">campaign</span>Global Campaigns</a>
            <a class="{{ ($active ?? '') === 'debts' ? 'is-active' : '' }}"
                href="{{ route('superadmin.debts.page') }}"><span
                    class="material-symbols-outlined">account_balance</span>Global Debt Overview</a>
            <a class="{{ ($active ?? '') === 'audit' ? 'is-active' : '' }}"
                href="{{ route('superadmin.audit.page') }}"><span
                    class="material-symbols-outlined">history_toggle_off</span>Audit Logs</a>
            <span class="superadmin-nav-label">Infra &amp; Security</span>
            <a class="{{ ($active ?? '') === 'security' ? 'is-active' : '' }}"
                href="{{ route('superadmin.security.page') }}"><span
                    class="material-symbols-outlined">shield_locked</span>Security Center</a>
            <a class="{{ ($active ?? '') === 'socket' ? 'is-active' : '' }}"
                href="{{ route('superadmin.socket.page') }}"><span
                    class="material-symbols-outlined">hub</span>Socket.IO &amp; Queue</a>
            <a class="{{ ($active ?? '') === 'system' ? 'is-active' : '' }}"
                href="{{ route('superadmin.system.page') }}"><span
                    class="material-symbols-outlined">build_circle</span>System Settings</a>
            <a class="{{ ($active ?? '') === 'notifications' ? 'is-active' : '' }}"
                href="{{ route('superadmin.notifications.page') }}"><span
                    class="material-symbols-outlined">notifications</span>Global Notifications</a>
            <a class="{{ ($active ?? '') === 'versions' ? 'is-active' : '' }}"
                href="{{ route('superadmin.versions.page') }}"><span
                    class="material-symbols-outlined">new_releases</span>Versions</a>
        </nav>
        <div class="superadmin-trust"><span class="material-symbols-outlined">verified_user</span>
            <div><strong>Zero Trust Enforced</strong><small>Root authority · all rooms</small></div>
        </div>
    </aside>
    <div class="superadmin-main">
        <header class="superadmin-topbar"><button class="superadmin-menu"
                onclick="document.getElementById('superadmin-sidebar').classList.toggle('is-open')"><span
                    class="material-symbols-outlined">menu</span></button>
            <div class="superadmin-clearance"><span class="material-symbols-outlined">verified</span>Superadmin
                Clearance</div>
            <div class="superadmin-top-status"><span class="status-dot"></span><span id="top-socket-status">System
                    monitoring active</span></div>
            <div class="superadmin-profile">
                <div><strong>{{ request()->user('admin')->name }}</strong><small>Enterprise Root Admin</small></div>
                <div class="superadmin-avatar">{{ strtoupper(substr(request()->user('admin')->name, 0, 1)) }}</div>
                <form method="post" action="{{ route('admin.logout') }}">@csrf<button title="Đăng xuất"
                        class="icon-button"><span class="material-symbols-outlined">logout</span></button></form>
            </div>
        </header>
        <main class="superadmin-content">@yield('content')</main>
    </div>
    <script>
        const dfApi = async (url, options = {}) => {
            const headers = {
                'Accept': 'application/json',
                ...(options.headers || {})
            };
            if (options.body && typeof options.body !== 'string') {
                headers['Content-Type'] = 'application/json';
                options.body = JSON.stringify(options.body);
            }
            headers['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;
            const response = await fetch(url, {
                ...options,
                headers
            });
            if (!response.ok) throw new Error((await response.json().catch(() => ({}))).message ||
                `HTTP ${response.status}`);
            return response.json();
        };
        const money = value => new Intl.NumberFormat('vi-VN').format(value || 0) + ' ₫';
        const escapeHtml = value => String(value ?? '').replace(/[&<>'"]/g, character => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#039;',
            '"': '&quot;'
        } [character]));
        const statusPill = value =>
            `<span class="status-pill status-${escapeHtml(value)}"><span class="status-dot"></span>${escapeHtml(value)}</span>`;
    </script>
    @stack('scripts')
</body>

</html>

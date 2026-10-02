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

<body data-submit-loading-text="{{ __('global.common.loading') }}"
    data-sa-realtime-url="{{ rtrim((string) config('services.realtime.public_url', 'http://localhost:3001'), '/') }}"
    @auth('superadmin') data-sa-socket-token-url="{{ route('superadmin.socket-token') }}" @endauth
    data-status-labels="{{ json_encode([
        'active' => __('superadmin.common.active'), 'disabled' => __('superadmin.common.disabled'),
        'inactive' => __('superadmin.common.inactive'),
        'archived' => __('superadmin.common.archived'), 'blocked' => __('superadmin.common.blocked'),
        'suspended' => __('superadmin.common.suspended'), 'rejected' => __('superadmin.common.rejected'),
        'deleted' => __('superadmin.common.deleted'), 'removed' => __('superadmin.common.removed'),
        'pending' => __('superadmin.common.pending'), 'scheduled' => __('superadmin.common.scheduled'),
        'closed' => __('superadmin.common.closed'), 'cancelled' => __('superadmin.common.cancelled'),
        'draft' => __('admin.status_draft'), 'closing' => __('admin.status_closing'),
        'high' => __('superadmin.common.high'), 'medium' => __('superadmin.common.medium'),
        'low' => __('superadmin.common.low'),
    ] + __('superadmin.health'), JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) }}" class="superadmin-shell">
    <x-superadmin.loading />
    <aside class="superadmin-sidebar" id="superadmin-sidebar">
        {{-- Same brand mark as the room admin console (components/admin/layout.blade.php). --}}
        <a class="superadmin-brand" href="{{ route('superadmin.dashboard') }}" title="DrinkFlow">
            <span class="superadmin-logo" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
                    <path d="M20 3H4v10c0 2.21 1.79 4 4 4h6c2.21 0 4-1.79 4-4v-3h2c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 5h-2V5h2v3zM4 19h16v2H4z" />
                </svg>
            </span>
            <div class="superadmin-brand-text"><strong>DrinkFlow</strong><span>{{ __('superadmin.layout.enterprise_superadmin') }}</span></div>
        </a>
        @php
            // Grouped as in the Navigation Target of documents/features/development_tasks_saas_extension.md.
            $navSections = [
                '' => [
                    ['dashboard', 'superadmin.dashboard', 'monitoring', 'superadmin.layout.dashboard_health', null],
                ],
                __('platform.nav.agents') => [
                    ['agents', 'superadmin.agents.index', 'storefront', 'platform.nav.agent_list', 'agent.view'],
                    ['admins', 'superadmin.admins.page', 'admin_panel_settings', 'superadmin.layout.admin_assignments', 'agent.view'],
                    ['registrations', 'superadmin.registrations.index', 'how_to_reg', 'platform.nav.registrations', 'agent.approve'],
                    ['subscriptions', 'superadmin.subscriptions.index', 'workspace_premium', 'platform.nav.subscriptions', 'subscription.view'],
                ],
                __('platform.nav.plans') => [
                    ['packages', 'superadmin.packages.index', 'inventory_2', 'platform.nav.packages', 'package.view'],
                ],
                __('platform.nav.platform_finance') => [
                    ['revenue', 'superadmin.billing.revenue', 'payments', 'platform.nav.revenue', 'revenue.view'],
                    ['outstanding', 'superadmin.billing.outstanding', 'request_quote', 'platform.nav.outstanding', 'debt.view'],
                ],
                __('platform.nav.system') => [
                    ['rooms', 'superadmin.rooms.page', 'meeting_room', 'superadmin.layout.room_management', 'room.view'],
                    ['users', 'superadmin.global-users.page', 'badge', 'superadmin.layout.global_users', 'global_user.view'],
                    ['campaigns', 'superadmin.campaigns.page', 'campaign', 'superadmin.layout.global_campaigns', 'room.view'],
                    ['feedbacks', 'superadmin.feedbacks.page', 'rate_review', 'superadmin.layout.feedbacks', 'feedback.view'],
                ],
                __('platform.nav.content') => [
                    ['versions', 'superadmin.versions.page', 'new_releases', 'superadmin.layout.versions', 'version.view'],
                    ['notifications', 'superadmin.notifications.page', 'notifications', 'superadmin.layout.global_notifications', 'settings.view'],
                ],
                __('platform.nav.governance') => [
                    ['superadmins', 'superadmin.superadmins.index', 'manage_accounts', 'superadmin.layout.superadmins', 'superadmin.view'],
                    ['roles', 'superadmin.roles.index', 'badge', 'platform.nav.roles', 'superadmin.view'],
                    ['audit', 'superadmin.audit.page', 'history_toggle_off', 'superadmin.layout.audit_logs', 'audit.view'],
                    ['security', 'superadmin.security.page', 'shield_locked', 'superadmin.layout.security_center', 'security.view'],
                ],
                __('platform.nav.infrastructure') => [
                    ['socket', 'superadmin.socket.page', 'hub', 'superadmin.layout.socket_queue', 'queue.view'],
                    ['system', 'superadmin.system.page', 'build_circle', 'superadmin.layout.system_settings', 'settings.view'],
                ],
            ];
            // The menu follows the same Gates as the routes (the routes still enforce them).
            $navGate = \Illuminate\Support\Facades\Gate::forUser(request()->user('superadmin'));
            $navSections = array_filter(array_map(
                static fn (array $links): array => array_values(array_filter($links, static fn (array $link): bool => $link[4] === null || $navGate->allows($link[4]))),
                $navSections,
            ));
        @endphp
        <nav class="superadmin-nav">
            @foreach ($navSections as $sectionLabel => $links)
                @if ($sectionLabel !== '')
                    <span class="superadmin-nav-label">{{ $sectionLabel }}</span>
                @endif
                @foreach ($links as [$key, $routeName, $icon, $labelKey, $permission])
                    <a class="{{ ($active ?? '') === $key ? 'is-active' : '' }}" href="{{ route($routeName) }}"
                        aria-label="{{ __($labelKey) }}"><span class="material-symbols-outlined">{{ $icon }}</span><span
                            class="superadmin-nav-text">{{ __($labelKey) }}</span><span class="superadmin-nav-tooltip"
                            aria-hidden="true">{{ __($labelKey) }}</span></a>
                @endforeach
            @endforeach
        </nav>
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
                <div><strong>{{ request()->user('superadmin')->name }}</strong><small>{{ request()->user('superadmin')->email }}</small></div>
                <div class="superadmin-avatar">{{ strtoupper(substr(request()->user('superadmin')->name, 0, 1)) }}</div>
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
            const headers = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', ...(options.headers || {}) };
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
    {{-- Socket.IO client served by the realtime gateway (live inbox notifications, resources/js/superadmin/realtime.js). --}}
    @auth('superadmin')
        <script src="{{ rtrim((string) config('services.realtime.public_url', 'http://localhost:3001'), '/') }}/socket.io/socket.io.js" defer></script>
    @endauth
    @stack('scripts')
</body>

</html>

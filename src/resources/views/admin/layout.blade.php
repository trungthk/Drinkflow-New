@php
    $active = trim($__env->yieldContent('active')) ?: 'dashboard';
    $pageTitle = trim($__env->yieldContent('title')) ?: 'DrinkFlow Admin';
    $roomLabel = $room->name;
@endphp
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }} · {{ $roomLabel }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200"
        rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="superadmin-shell">
    <aside id="admin-sidebar" class="superadmin-sidebar">
        <div>
            <div class="superadmin-brand">
                <div class="superadmin-logo"><span class="material-symbols-outlined">local_cafe</span></div>
                <div><strong>DrinkFlow</strong><span>Enterprise Admin</span></div>
                <span class="ml-auto rounded bg-[#dce9ff] px-2 py-1 text-[10px] font-bold text-[#0b1c30]">v2.4</span>
            </div>
            <div class="px-4 py-4">
                <div class="mb-2 px-2 text-[10px] font-bold uppercase tracking-[.12em] text-slate-500">Phân hệ điều hành
                </div>
                <div class="superadmin-nav !p-0">
                    <a class="{{ $active === 'dashboard' ? 'is-active' : '' }}"
                        href="{{ route('admin.dashboard.page', $room) }}"><span
                            class="material-symbols-outlined">space_dashboard</span><span>Tổng quan Room</span></a>
                    <a class="{{ $active === 'campaigns' ? 'is-active' : '' }}"
                        href="{{ route('admin.manage.page', [$room, 'tab' => 'campaigns']) }}"><span
                            class="material-symbols-outlined">restaurant_menu</span><span>Chiến dịch &amp; Menu</span>
                        @if ($active === 'campaigns')
                            <span class="ml-auto rounded bg-[#d1fae5] px-2 py-1 text-[10px] text-[#047857]">Live</span>
                        @endif
                    </a>
                    <a class="{{ $active === 'orders' ? 'is-active' : '' }}"
                        href="{{ route('admin.manage.page', [$room, 'tab' => 'orders']) }}"><span
                            class="material-symbols-outlined">sync_alt</span><span>Đơn gom Realtime</span><span
                            class="ml-auto h-2 w-2 rounded-full bg-emerald-400"></span></a>
                    <a class="{{ $active === 'debts' ? 'is-active' : '' }}"
                        href="{{ route('admin.manage.page', [$room, 'tab' => 'debts']) }}"><span
                            class="material-symbols-outlined">account_balance_wallet</span><span>Quản lý Công
                            nợ</span></a>
                    <a class="{{ $active === 'users' ? 'is-active' : '' }}"
                        href="{{ route('admin.manage.page', [$room, 'tab' => 'users']) }}"><span
                            class="material-symbols-outlined">group</span><span>Thành viên Room</span></a>
                    <a class="{{ in_array($active, ['payments', 'settings', 'notifications']) ? 'is-active' : '' }}"
                        href="{{ route('admin.manage.page', [$room, 'tab' => 'payments']) }}"><span
                            class="material-symbols-outlined">tune</span><span>Tài khoản &amp; Cấu hình</span></a>
                    <a class="{{ in_array($active, ['reports', 'audit']) ? 'is-active' : '' }}"
                        href="{{ route('admin.manage.page', [$room, 'tab' => 'reports']) }}"><span
                            class="material-symbols-outlined">history_toggle_off</span><span>Báo cáo &amp; Audit
                            Log</span></a>
                </div>
            </div>
        </div>
        <div class="p-4">
            <div class="superadmin-trust !m-0 !flex-col !gap-1">
                <strong class="!text-[#006c49]">● ENTERPRISE ACTIVE</strong>
                <strong>Gói Room Không Giới Hạn</strong>
                <small>Scope: {{ $roomLabel }}</small>
                <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-[#dce9ff]">
                    <div class="h-full w-2/3 rounded-full bg-[#00875a]"></div>
                </div>
            </div>
        </div>
    </aside>
    <div class="superadmin-main">
        <header class="superadmin-topbar">
            <button type="button" class="superadmin-menu icon-button" aria-label="Mở menu"
                onclick="document.querySelector('#admin-sidebar')?.classList.toggle('is-open')"><span
                    class="material-symbols-outlined">menu</span></button>
            <a class="inline-flex items-center gap-2 rounded-lg border border-[#dce4f0] bg-white px-3 py-2 text-[11px] font-semibold text-[#0b1c30] no-underline"
                href="{{ route('admin.landing') }}"><span
                    class="material-symbols-outlined text-[17px] text-slate-500">meeting_room</span><span><small
                        class="block text-[9px] font-medium uppercase tracking-wider text-slate-500">Không gian hiện
                        tại</small>{{ $roomLabel }}</span><span
                    class="material-symbols-outlined text-[16px] text-slate-500">expand_more</span></a>
            <span id="socket-state" class="superadmin-top-status"><span class="status-dot"></span>Socket Live
                (wss)</span>
            <div class="superadmin-profile">
                <a class="sa-button" href="{{ route('admin.manage.page', [$room, 'tab' => 'campaigns']) }}"><span
                        class="material-symbols-outlined">add</span>Tạo Campaign</a>
                <span class="material-symbols-outlined hidden text-slate-500 sm:inline"
                    title="Thông báo">notifications</span>
                <div><strong>{{ auth('admin')->user()?->name ?? 'Admin Room' }}</strong><small>Room Host /
                        Admin</small></div>
                <div class="superadmin-avatar">
                    {{ mb_strtoupper(mb_substr(auth('admin')->user()?->name ?? 'AD', 0, 2)) }}</div>
                <form method="post" action="{{ route('admin.logout') }}" class="hidden sm:block">@csrf<button
                        class="icon-button" title="Đăng xuất" type="submit"><span
                            class="material-symbols-outlined">logout</span></button></form>
            </div>
        </header>
        <main class="superadmin-content">
            @yield('content')
        </main>
    </div>
    @stack('scripts')
</body>

</html>

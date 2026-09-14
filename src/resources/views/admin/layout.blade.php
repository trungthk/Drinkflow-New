@php
    $active = trim($__env->yieldContent('active')) ?: 'dashboard';
    $pageTitle = trim($__env->yieldContent('title')) ?: __('admin.dashboard');
    $roomLabel = $room?->name ?? 'DrinkFlow';
    $adminUser = auth('admin')->user();
    
    if (!isset($assignedRooms) || $assignedRooms === null) {
        if ($adminUser) {
            if ($adminUser->isSuperadmin()) {
                $assignedRoomsList = \App\Models\Room::where('status', \App\Enums\RoomStatus::Active)->orderBy('name')->get();
            } else {
                $assignedRoomsList = $adminUser->rooms()->where('status', \App\Enums\RoomStatus::Active)->orderBy('name')->get();
            }
        } else {
            $assignedRoomsList = collect();
        }
    } else {
        $assignedRoomsList = $assignedRooms;
    }

    if ($room && !$assignedRoomsList->contains('id', $room->id)) {
        $assignedRoomsList = $assignedRoomsList->prepend($room);
    }

    $unreadNotifications = collect();
    $unreadCount = 0;
    if ($room) {
        $unreadNotifications = \App\Models\AuditLog::where('room_id', $room->id)->latest('created_at')->take(5)->get();
        $unreadCount = \App\Models\Debt::where('room_id', $room->id)->whereIn('status', \App\Enums\DebtStatus::outstandingValues())->count();
    }
@endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle }} · {{ $roomLabel }} · DrinkFlow Admin</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:ital,wght@0,300..900;1,300..900&family=JetBrains+Mono:ital,wght@0,100..800;1,100..800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

    @vite(['resources/css/admin.css', 'resources/js/app.js'])
</head>

<body class="bg-surface text-on-surface font-sans min-h-screen flex antialiased">
    <!-- ================= LEFT SIDEBAR ================= -->
    <aside id="admin-sidebar" class="fixed top-0 left-0 h-screen w-64 flex flex-col z-30 bg-surface-container-lowest border-r border-outline-variant transition-transform duration-200 -translate-x-full lg:translate-x-0">
        <div class="w-64 h-full p-4 flex flex-col justify-between overflow-y-auto">
            <div>
                <!-- Brand Logo & Header -->
                <div class="flex items-center gap-2 mb-5 px-1 justify-between">
                    <a href="{{ route('admin.landing') }}" class="flex items-center gap-2.5 no-underline group hover:opacity-90 transition-opacity">
                        <span class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-xs shrink-0"
                              style="background: linear-gradient(135deg, #006948 0%, #047857 100%); background-color: #006948; border: 1px solid #005137; box-shadow: 0 1px 2px rgba(0,0,0,0.08);">
                            <svg class="w-4.5 h-4.5 text-white fill-current" viewBox="0 0 24 24" aria-hidden="true" style="width: 18px; height: 18px; fill: #ffffff; color: #ffffff;">
                                <path d="M20 3H4v10c0 2.21 1.79 4 4 4h6c2.21 0 4-1.79 4-4v-3h2c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 5h-2V5h2v3zM4 19h16v2H4z"/>
                            </svg>
                        </span>
                        <div>
                            <span class="text-base font-bold text-on-surface tracking-tight block leading-tight">{{ __('admin.brand_title') }}</span>
                            <span class="text-[11px] font-mono text-outline block">{{ __('admin.brand_subtitle') }}</span>
                        </div>
                    </a>
                    <button type="button" class="lg:hidden w-8 h-8 flex items-center justify-center rounded text-outline hover:text-on-surface hover:bg-surface-container-low transition-colors" onclick="document.querySelector('#admin-sidebar')?.classList.toggle('-translate-x-full')">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <!-- Active Workspace Dropdown Trigger -->
                <div class="mb-5 relative" x-data="{ open: false }">
                    <label class="text-[10px] font-mono text-outline block mb-1 uppercase tracking-wider font-semibold">{{ __('admin.active_workspace') }}</label>
                    <button type="button" @click="open = !open" class="w-full flex items-center justify-between px-3 py-2 bg-surface border border-outline-variant rounded hover:border-outline text-left transition-colors cursor-pointer">
                        <div class="flex items-center gap-2 truncate">
                            <span class="w-2 h-2 rounded-full {{ $room ? 'bg-primary' : 'bg-outline' }} inline-block shrink-0"></span>
                            <span class="text-xs font-semibold text-on-surface truncate">{{ $room ? $roomLabel : __('admin.select_room_title') }}</span>
                        </div>
                        <span class="material-symbols-outlined text-[16px] text-outline transition-transform duration-200" :class="{ 'rotate-180': open }">expand_more</span>
                    </button>

                    <div x-show="open" @click.away="open = false" x-cloak class="absolute left-0 right-0 top-full mt-1 bg-surface-container-lowest border border-outline-variant rounded-lg shadow-lg z-50 py-1 max-h-56 overflow-y-auto">
                        @if($assignedRoomsList->isNotEmpty())
                            <div class="px-2.5 py-1 text-[10px] font-mono uppercase text-outline tracking-wider font-semibold">{{ __('admin.assigned_rooms') }}</div>
                            @foreach($assignedRoomsList as $assigned)
                            <a href="{{ route('admin.dashboard.page', $assigned) }}" class="flex items-center justify-between px-3 py-2 text-xs hover:bg-surface-container-low transition-colors {{ ($room && $assigned->id === $room->id) ? 'font-bold text-primary bg-primary/5' : 'text-on-surface' }}">
                                <span class="truncate">{{ $assigned->name }}</span>
                                @if($room && $assigned->id === $room->id)
                                <span class="material-symbols-outlined text-[14px] text-primary">check</span>
                                @endif
                            </a>
                            @endforeach
                            <div class="border-t border-outline-variant/60 my-1"></div>
                        @endif
                        <a href="{{ route('admin.landing') }}" class="flex items-center gap-2 px-3 py-2 text-xs text-primary font-semibold hover:bg-surface-container-low transition-colors">
                            <span class="material-symbols-outlined text-[16px]">grid_view</span>
                            <span class="truncate">{{ __('admin.all_rooms') }}</span>
                        </a>
                    </div>
                </div>

                <!-- Main Nav Links -->
                <nav class="space-y-1 text-xs">
                    @if($room)
                    <!-- Dashboard -->
                    <a class="flex items-center gap-3 px-3 py-2 rounded font-medium transition-colors {{ $active === 'dashboard' ? 'bg-secondary-container text-on-secondary-container border-l-4 border-primary font-bold' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low' }}"
                        href="{{ route('admin.dashboard.page', $room) }}">
                        <span class="material-symbols-outlined text-[18px]">space_dashboard</span>
                        <span>{{ __('admin.dashboard') }}</span>
                    </a>

                    <!-- Campaigns -->
                    <a class="flex items-center justify-between px-3 py-2 rounded font-medium transition-colors {{ $active === 'campaigns' ? 'bg-secondary-container text-on-secondary-container border-l-4 border-primary font-bold' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low' }}"
                        href="{{ route('admin.campaigns.page', $room) }}">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-[18px]">campaign</span>
                            <span>{{ __('admin.campaigns') }}</span>
                        </div>
                        <span id="nav-live-badge" class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-error-container text-on-error-container font-bold hidden">{{ __('admin.live_badge') }}</span>
                    </a>

                    <!-- Orders -->
                    <a class="flex items-center gap-3 px-3 py-2 rounded font-medium transition-colors {{ $active === 'orders' ? 'bg-secondary-container text-on-secondary-container border-l-4 border-primary font-bold' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low' }}"
                        href="{{ route('admin.orders.page', $room) }}">
                        <span class="material-symbols-outlined text-[18px]">local_shipping</span>
                        <span>{{ __('admin.orders') }}</span>
                    </a>

                    <!-- Payments & Debt -->
                    <a class="flex items-center justify-between px-3 py-2 rounded font-medium transition-colors {{ in_array($active, ['debts', 'payments']) ? 'bg-secondary-container text-on-secondary-container border-l-4 border-primary font-bold' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low' }}"
                        href="{{ route('admin.debts.page', $room) }}">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-[18px]">payments</span>
                            <span>{{ __('admin.debts') }}</span>
                        </div>
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500" title="{{ __('admin.needs_settlement') }}"></span>
                    </a>

                    <!-- Users -->
                    <a class="flex items-center gap-3 px-3 py-2 rounded font-medium transition-colors {{ $active === 'users' ? 'bg-secondary-container text-on-secondary-container border-l-4 border-primary font-bold' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low' }}"
                        href="{{ route('admin.room-users.page', $room) }}">
                        <span class="material-symbols-outlined text-[18px]">group</span>
                        <span>{{ __('admin.users') }}</span>
                    </a>

                    <!-- Reports -->
                    <a class="flex items-center gap-3 px-3 py-2 rounded font-medium transition-colors {{ $active === 'reports' ? 'bg-secondary-container text-on-secondary-container border-l-4 border-primary font-bold' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low' }}"
                        href="{{ route('admin.reports.page', $room) }}">
                        <span class="material-symbols-outlined text-[18px]">assessment</span>
                        <span>{{ __('admin.reports_audit') }}</span>
                    </a>

                    <!-- VietQR Accounts -->
                    <a class="flex items-center gap-3 px-3 py-2 rounded font-medium transition-colors {{ $active === 'payment_accounts' ? 'bg-secondary-container text-on-secondary-container border-l-4 border-primary font-bold' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low' }}"
                        href="{{ route('admin.payment-accounts.page', $room) }}">
                        <span class="material-symbols-outlined text-[18px]">qr_code_2</span>
                        <span>{{ __('admin.config_vietqr') }}</span>
                    </a>

                    <!-- Room Settings -->
                    <a class="flex items-center gap-3 px-3 py-2 rounded font-medium transition-colors {{ in_array($active, ['settings', 'notifications']) ? 'bg-secondary-container text-on-secondary-container border-l-4 border-primary font-bold' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low' }}"
                        href="{{ route('admin.settings.page', $room) }}">
                        <span class="material-symbols-outlined text-[18px]">settings</span>
                        <span>{{ __('admin.payments_settings') }}</span>
                    </a>

                    <!-- System Diagnostics -->
                    <a class="flex items-center gap-3 px-3 py-2 rounded font-medium transition-colors {{ $active === 'audit' ? 'bg-secondary-container text-on-secondary-container border-l-4 border-primary font-bold' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low' }}"
                        href="{{ route('admin.audit.page', $room) }}">
                        <span class="material-symbols-outlined text-[18px]">health_and_safety</span>
                        <span>{{ __('admin.system_diagnostics') }}</span>
                    </a>
                    @endif
                </nav>
            </div>

            <!-- Footer Action Links & Admin Info -->
            <div class="pt-4 border-t border-outline-variant space-y-2 text-xs">
                <!-- Admin Profile Info Chip -->
                <div class="flex items-center gap-2.5 p-2 rounded-lg bg-surface-container border border-outline-variant/60">
                    <div class="w-8 h-8 rounded-full bg-secondary text-on-secondary font-mono text-xs flex items-center justify-center font-bold ring-1 ring-emerald-600/30 shrink-0">
                        {{ mb_strtoupper(mb_substr($adminUser?->name ?? 'AD', 0, 2)) }}
                    </div>
                    <div class="flex flex-col min-w-0 flex-1">
                        <span class="text-xs font-semibold text-on-surface truncate leading-tight">{{ $adminUser?->name ?? __('admin.default_admin_name') }}</span>
                        <span class="text-[10px] text-outline font-mono truncate leading-tight">{{ $adminUser?->email ?? ($adminUser?->isSuperadmin() ? __('admin.super_admin_role') : __('admin.room_dispatcher_role')) }}</span>
                    </div>
                </div>

                <!-- Logout Button -->
                <button type="button" onclick="openAdminLogoutModal()" class="btn-admin-logout w-full flex items-center gap-2.5 px-3 py-2 text-error hover:bg-error-container/40 rounded-lg font-medium transition-colors text-left cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">logout</span>
                    <span>{{ __('admin.logout') }}</span>
                </button>
            </div>
        </div>
    </aside>

    <!-- ================= MAIN CONTENT WORKSPACE ================= -->
    <div class="flex-1 lg:pl-64 flex flex-col min-w-0 min-h-screen">
        <!-- Top Navigation Bar -->
        <header class="flex justify-between items-center w-full px-6 py-2 h-14 bg-surface-container-lowest border-b border-outline-variant sticky top-0 z-20">
            <!-- Left side: Mobile menu toggle & Breadcrumbs -->
            <div class="flex items-center gap-3">
                <button type="button" class="lg:hidden w-8 h-8 flex items-center justify-center rounded text-outline hover:text-on-surface hover:bg-surface-container-low transition-colors"
                    onclick="document.querySelector('#admin-sidebar')?.classList.toggle('-translate-x-full')">
                    <span class="material-symbols-outlined text-[20px]">menu</span>
                </button>
                <nav class="flex items-center gap-2 text-xs text-outline font-medium">
                    <span>{{ __('admin.breadcrumb_admin') }}</span>
                    <span>/</span>
                    <a href="{{ route('admin.landing') }}" class="hover:text-on-surface transition-colors">{{ __('admin.breadcrumb_rooms') }}</a>
                    @if($room)
                    <span>/</span>
                    <span class="text-on-surface font-semibold truncate max-w-[180px] sm:max-w-none">{{ $roomLabel }}</span>
                    @endif
                </nav>
            </div>

            <!-- Right side: Notifications Dropdown, Fast Action -->
            <div class="flex items-center gap-3">
                @if($room)
                <!-- Notifications Dropdown -->
                <div class="relative" x-data="{ notifOpen: false }">
                    <button type="button" @click="notifOpen = !notifOpen" class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant hover:bg-surface-container-low transition-colors relative cursor-pointer" title="{{ __('admin.notifications') }}">
                        <span class="material-symbols-outlined text-[20px]">notifications</span>
                        @if($unreadCount > 0 || $unreadNotifications->isNotEmpty())
                        <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-error rounded-full ring-2 ring-surface"></span>
                        @endif
                    </button>

                    <div x-show="notifOpen" @click.away="notifOpen = false" x-cloak class="absolute right-0 top-full mt-2 w-80 bg-surface-container-lowest border border-outline-variant rounded-xl shadow-xl z-50 py-2 overflow-hidden">
                        <div class="px-4 py-2 border-b border-outline-variant/60 flex items-center justify-between">
                            <span class="font-bold text-xs text-on-surface">{{ __('admin.unread_notifications') }}</span>
                            @if($unreadCount > 0)
                            <span class="px-1.5 py-0.5 rounded-full bg-error-container text-error text-[10px] font-mono font-bold">{{ $unreadCount }}</span>
                            @endif
                        </div>

                        <div class="max-h-64 overflow-y-auto divide-y divide-outline-variant/40">
                            @forelse($unreadNotifications as $notif)
                            <div class="px-4 py-2.5 hover:bg-surface-container-low transition-colors">
                                <div class="flex items-start gap-2.5">
                                    <span class="w-6 h-6 rounded-full bg-primary/10 text-primary flex items-center justify-center shrink-0 mt-0.5">
                                        <span class="material-symbols-outlined text-[14px]">info</span>
                                    </span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-medium text-on-surface leading-snug">{{ $notif->event ?? __('admin.system_updated') }}</p>
                                        <span class="text-[10px] font-mono text-outline">{{ $notif->created_at ? $notif->created_at->diffForHumans() : __('admin.just_now') }}</span>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="px-4 py-6 text-center text-outline text-xs">
                                <span class="material-symbols-outlined text-[28px] text-outline/60 block mx-auto mb-1">notifications_off</span>
                                <span>{{ __('admin.no_unread_notifications') }}</span>
                            </div>
                            @endforelse
                        </div>

                        <div class="px-4 py-2 border-t border-outline-variant/60 bg-surface-container-low/50 text-center">
                            <a href="{{ route('admin.audit.page', $room) }}" class="text-xs text-primary font-semibold hover:underline no-underline block">
                                {{ __('admin.view_all_notifications') }} →
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Fast Create Action Button -->
                <a href="{{ route('admin.campaigns.create', $room) }}" class="bg-primary hover:bg-primary-container text-on-primary px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition-colors shadow-xs no-underline">
                    <span class="material-symbols-outlined text-[16px]">bolt</span>
                    <span class="hidden sm:inline">{{ __('admin.dispatch_action') }}</span>
                </a>
                @endif
            </div>
        </header>

        <!-- Main Scrollable Canvas -->
        <main class="flex-1 p-4 sm:p-6 space-y-6">
            @yield('content')
        </main>
    </div>

    <!-- Admin Logout Confirmation Modal -->
    <x-admin.logout-modal />

    @stack('scripts')
</body>

</html>

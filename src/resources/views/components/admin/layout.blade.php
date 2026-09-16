@props([
    'title' => null,
    'active' => 'dashboard',
    'room' => null,
    'assignedRooms' => null,
])

@php
    $roomLabel = $room?->name ?? 'DrinkFlow';
    $pageTitle = $title ?: __('admin.dashboard');
    $adminUser = $adminUser ?? auth('admin')->user();
    $adminInitials = mb_strtoupper(mb_substr($adminUser?->name ?? 'AD', 0, 2));
    $adminAvatarUrl = $adminUser?->avatar_url ? route('admin.profile.avatar.show') : null;
@endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="room-slug" content="{{ $room?->slug }}">
    <title>{{ $pageTitle }} · {{ $roomLabel }} · DrinkFlow Admin</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:ital,wght@0,300..900;1,300..900&family=JetBrains+Mono:ital,wght@0,100..800;1,100..800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

    <!-- Anti-flicker Sidebar State Initialization -->
    <script>
        (function() {
            try {
                if (localStorage.getItem('df_admin_sidebar_collapsed') === 'true' && window.innerWidth >= 1024) {
                    document.documentElement.classList.add('sidebar-collapsed');
                }
            } catch(e) {}
        })();
    </script>

    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>

<body data-submit-loading-text="{{ __('global.common.loading') }}" data-room-slug="{{ $room?->slug }}" data-processing-text="{{ __('admin.processing') }}" class="bg-surface text-on-surface font-sans min-h-screen flex antialiased">
    <!-- ================= LEFT SIDEBAR ================= -->
    <aside id="admin-sidebar" class="fixed top-0 left-0 h-screen w-64 flex flex-col z-30 bg-surface-container-lowest border-r border-outline-variant -translate-x-full lg:translate-x-0">
        <div class="w-full h-full p-3.5 flex flex-col justify-between overflow-y-auto overflow-x-hidden">
            <div>
                <!-- Brand Logo & Header -->
                <div class="flex items-center gap-2 mb-5 px-1 justify-between">
                    <a href="{{ route('admin.landing') }}" class="flex items-center gap-2.5 no-underline group hover:opacity-90 transition-opacity min-w-0" title="{{ __('admin.brand_title') }}">
                        <span class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-xs shrink-0"
                              style="background: linear-gradient(135deg, #006948 0%, #047857 100%); background-color: #006948; border: 1px solid #005137; box-shadow: 0 1px 2px rgba(0,0,0,0.08);">
                            <svg class="w-4.5 h-4.5 text-white fill-current" viewBox="0 0 24 24" aria-hidden="true" style="width: 18px; height: 18px; fill: #ffffff; color: #ffffff;">
                                <path d="M20 3H4v10c0 2.21 1.79 4 4 4h6c2.21 0 4-1.79 4-4v-3h2c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 5h-2V5h2v3zM4 19h16v2H4z"/>
                            </svg>
                        </span>
                        <div class="sidebar-text min-w-0">
                            <span class="text-base font-bold text-on-surface tracking-tight block leading-tight truncate">{{ __('admin.brand_title') }}</span>
                            <span class="text-[11px] font-mono text-outline block truncate">{{ __('admin.brand_subtitle') }}</span>
                        </div>
                    </a>
                    <button type="button" class="lg:hidden w-8 h-8 flex items-center justify-center rounded text-outline hover:text-on-surface hover:bg-surface-container-low transition-colors" onclick="document.querySelector('#admin-sidebar')?.classList.toggle('-translate-x-full')">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <!-- Active Workspace Dropdown Trigger -->
                <div class="mb-5 relative" data-admin-workspace-switcher>
                    <label class="text-[10px] font-mono text-outline block mb-1 uppercase tracking-wider font-semibold sidebar-text">{{ __('admin.active_workspace') }}</label>
                    <button type="button" data-workspace-toggle aria-expanded="false" aria-controls="workspace-menu" class="sidebar-nav-link relative group w-full flex items-center justify-between px-3 py-2 bg-surface border border-outline-variant rounded hover:border-outline text-left transition-colors cursor-pointer" title="{{ $room ? $roomLabel : __('admin.select_room_title') }}">
                        <div class="flex items-center gap-2 truncate">
                            <span class="w-2.5 h-2.5 rounded-full {{ $room ? 'bg-primary' : 'bg-outline' }} inline-block shrink-0"></span>
                            <span class="text-xs font-semibold text-on-surface truncate sidebar-text">{{ $room ? $roomLabel : __('admin.select_room_title') }}</span>
                        </div>
                        <span data-workspace-chevron class="material-symbols-outlined text-[16px] text-outline transition-transform duration-200 sidebar-text">expand_more</span>
                        <div class="sidebar-tooltip pointer-events-none absolute left-full top-1/2 -translate-y-1/2 ml-3 px-2.5 py-1.5 bg-[#0b1c30] text-white text-xs font-semibold rounded-lg shadow-xl whitespace-nowrap z-50 opacity-0 group-hover:opacity-100 transition-opacity hidden">
                            {{ $room ? $roomLabel : __('admin.select_room_title') }}
                        </div>
                    </button>

                    <div id="workspace-menu" data-workspace-menu class="hidden absolute left-0 right-0 top-full mt-1 bg-surface-container-lowest border border-outline-variant rounded-lg shadow-lg z-50 py-1 max-h-56 overflow-y-auto min-w-[200px]">
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
                    <a class="sidebar-nav-link relative group flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ $active === 'dashboard' ? 'bg-secondary-container text-on-secondary-container border-l-4 border-primary font-bold' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low' }}"
                        href="{{ route('admin.dashboard.page', $room) }}" title="{{ __('admin.dashboard') }}">
                        <span class="material-symbols-outlined text-[18px] shrink-0">space_dashboard</span>
                        <span class="sidebar-text truncate">{{ __('admin.dashboard') }}</span>
                        <div class="sidebar-tooltip pointer-events-none absolute left-full top-1/2 -translate-y-1/2 ml-3 px-2.5 py-1.5 bg-[#0b1c30] text-white text-xs font-semibold rounded-lg shadow-xl whitespace-nowrap z-50 opacity-0 group-hover:opacity-100 transition-opacity hidden">
                            {{ __('admin.dashboard') }}
                        </div>
                    </a>

                    <!-- Campaigns -->
                    <a class="sidebar-nav-link relative group flex items-center justify-between px-3 py-2 rounded-lg font-medium transition-colors {{ $active === 'campaigns' ? 'bg-secondary-container text-on-secondary-container border-l-4 border-primary font-bold' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low' }}"
                        href="{{ route('admin.campaigns.page', $room) }}" title="{{ __('admin.campaigns') }}">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="material-symbols-outlined text-[18px] shrink-0">campaign</span>
                            <span class="sidebar-text truncate">{{ __('admin.campaigns') }}</span>
                        </div>
                        <span id="nav-live-badge" class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-error-container text-on-error-container font-bold hidden sidebar-text">Live</span>
                        <div class="sidebar-tooltip pointer-events-none absolute left-full top-1/2 -translate-y-1/2 ml-3 px-2.5 py-1.5 bg-[#0b1c30] text-white text-xs font-semibold rounded-lg shadow-xl whitespace-nowrap z-50 opacity-0 group-hover:opacity-100 transition-opacity hidden">
                            {{ __('admin.campaigns') }}
                        </div>
                    </a>

                    @if($hasLiveCampaign)
                        <!-- Orders -->
                        <a class="sidebar-nav-link relative group flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ $active === 'orders' ? 'bg-secondary-container text-on-secondary-container border-l-4 border-primary font-bold' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low' }}"
                            href="{{ route('admin.orders.page', $room) }}" title="{{ __('admin.orders') }}">
                            <span class="material-symbols-outlined text-[18px] shrink-0">local_shipping</span>
                            <span class="sidebar-text truncate">{{ __('admin.orders') }}</span>
                            <div class="sidebar-tooltip pointer-events-none absolute left-full top-1/2 -translate-y-1/2 ml-3 px-2.5 py-1.5 bg-[#0b1c30] text-white text-xs font-semibold rounded-lg shadow-xl whitespace-nowrap z-50 opacity-0 group-hover:opacity-100 transition-opacity hidden">
                                {{ __('admin.orders') }}
                            </div>
                        </a>
                    @endif

                    <!-- Payments & Debt -->
                    <a class="sidebar-nav-link relative group flex items-center justify-between px-3 py-2 rounded-lg font-medium transition-colors {{ in_array($active, ['debts', 'payments']) ? 'bg-secondary-container text-on-secondary-container border-l-4 border-primary font-bold' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low' }}"
                        href="{{ route('admin.debts.page', $room) }}" title="{{ __('admin.debts') }}">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="material-symbols-outlined text-[18px] shrink-0">payments</span>
                            <span class="sidebar-text truncate">{{ __('admin.debts') }}</span>
                        </div>
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 sidebar-text" title="{{ __('admin.needs_settlement') }}"></span>
                        <div class="sidebar-tooltip pointer-events-none absolute left-full top-1/2 -translate-y-1/2 ml-3 px-2.5 py-1.5 bg-[#0b1c30] text-white text-xs font-semibold rounded-lg shadow-xl whitespace-nowrap z-50 opacity-0 group-hover:opacity-100 transition-opacity hidden">
                            {{ __('admin.debts') }}
                        </div>
                    </a>

                    <!-- Users -->
                    <a class="sidebar-nav-link relative group flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ $active === 'users' ? 'bg-secondary-container text-on-secondary-container border-l-4 border-primary font-bold' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low' }}"
                        href="{{ route('admin.room-users.page', $room) }}" title="{{ __('admin.users') }}">
                        <span class="material-symbols-outlined text-[18px] shrink-0">group</span>
                        <span class="sidebar-text truncate">{{ __('admin.users') }}</span>
                        <div class="sidebar-tooltip pointer-events-none absolute left-full top-1/2 -translate-y-1/2 ml-3 px-2.5 py-1.5 bg-[#0b1c30] text-white text-xs font-semibold rounded-lg shadow-xl whitespace-nowrap z-50 opacity-0 group-hover:opacity-100 transition-opacity hidden">
                            {{ __('admin.users') }}
                        </div>
                    </a>

                    <!-- Reports -->
                    <a class="sidebar-nav-link relative group flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ $active === 'reports' ? 'bg-secondary-container text-on-secondary-container border-l-4 border-primary font-bold' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low' }}"
                        href="{{ route('admin.reports.page', $room) }}" title="{{ __('admin.reports_audit') }}">
                        <span class="material-symbols-outlined text-[18px] shrink-0">assessment</span>
                        <span class="sidebar-text truncate">{{ __('admin.reports_audit') }}</span>
                        <div class="sidebar-tooltip pointer-events-none absolute left-full top-1/2 -translate-y-1/2 ml-3 px-2.5 py-1.5 bg-[#0b1c30] text-white text-xs font-semibold rounded-lg shadow-xl whitespace-nowrap z-50 opacity-0 group-hover:opacity-100 transition-opacity hidden">
                            {{ __('admin.reports_audit') }}
                        </div>
                    </a>

                    <!-- Room Settings -->
                    <a class="sidebar-nav-link relative group flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ in_array($active, ['settings', 'notifications']) ? 'bg-secondary-container text-on-secondary-container border-l-4 border-primary font-bold' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low' }}"
                        href="{{ route('admin.settings.page', $room) }}" title="{{ __('admin.payments_settings') }}">
                        <span class="material-symbols-outlined text-[18px] shrink-0">settings</span>
                        <span class="sidebar-text truncate">{{ __('admin.payments_settings') }}</span>
                        <div class="sidebar-tooltip pointer-events-none absolute left-full top-1/2 -translate-y-1/2 ml-3 px-2.5 py-1.5 bg-[#0b1c30] text-white text-xs font-semibold rounded-lg shadow-xl whitespace-nowrap z-50 opacity-0 group-hover:opacity-100 transition-opacity hidden">
                            {{ __('admin.payments_settings') }}
                        </div>
                    </a>

                    <!-- System Diagnostics -->
                    <a class="sidebar-nav-link relative group flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ $active === 'audit' ? 'bg-secondary-container text-on-secondary-container border-l-4 border-primary font-bold' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low' }}"
                        href="{{ route('admin.audit.page', $room) }}" title="{{ __('admin.system_diagnostics') }}">
                        <span class="material-symbols-outlined text-[18px] shrink-0">health_and_safety</span>
                        <span class="sidebar-text truncate">{{ __('admin.system_diagnostics') }}</span>
                        <div class="sidebar-tooltip pointer-events-none absolute left-full top-1/2 -translate-y-1/2 ml-3 px-2.5 py-1.5 bg-[#0b1c30] text-white text-xs font-semibold rounded-lg shadow-xl whitespace-nowrap z-50 opacity-0 group-hover:opacity-100 transition-opacity hidden">
                            {{ __('admin.system_diagnostics') }}
                        </div>
                    </a>
                    @endif
                </nav>
            </div>

            <!-- Footer Action Links & Admin Info -->
            <div class="pt-4 border-t border-outline-variant space-y-2 text-xs">
                <!-- Admin Profile Info Chip -->
                <a href="{{ route('admin.profile') }}" class="admin-profile-chip flex items-center gap-2.5 p-2 rounded-lg bg-surface-container border border-outline-variant/60 relative group no-underline" title="{{ __('admin.profile_security') }}">
                    <div data-admin-avatar="sidebar" class="relative w-8 h-8 overflow-hidden rounded-full bg-secondary text-on-secondary font-mono text-xs flex items-center justify-center font-bold ring-1 ring-emerald-600/30 shrink-0 mx-auto lg:mx-0">
                        <span aria-hidden="true">{{ $adminInitials }}</span>
                        @if($adminAvatarUrl)
                            <img src="{{ $adminAvatarUrl }}" alt="{{ $adminUser?->name }}" loading="lazy" onerror="this.remove()" class="absolute inset-0 h-full w-full object-cover">
                        @endif
                    </div>
                    <div class="flex flex-col min-w-0 flex-1 sidebar-text">
                        <span class="text-xs font-semibold text-on-surface truncate leading-tight">{{ $adminUser?->name ?? __('global.common.admin') }}</span>
                        <span class="text-[10px] text-outline font-mono truncate leading-tight">{{ $adminUser?->email ?? ($adminUser?->isSuperadmin() ? __('admin.super_admin_role') : __('admin.room_dispatcher_role')) }}</span>
                    </div>
                    <div class="sidebar-tooltip pointer-events-none absolute left-full top-1/2 -translate-y-1/2 ml-3 px-2.5 py-1.5 bg-[#0b1c30] text-white text-xs font-semibold rounded-lg shadow-xl whitespace-nowrap z-50 opacity-0 group-hover:opacity-100 transition-opacity hidden">
                        {{ $adminUser?->name }} ({{ $adminUser?->email }})
                    </div>
                </a>

                <!-- Logout Button -->
                <button type="button" onclick="openAdminLogoutModal()" class="sidebar-nav-link btn-admin-logout relative group w-full flex items-center gap-2.5 px-3 py-2 text-error hover:bg-error-container/40 rounded-lg font-medium transition-colors text-left cursor-pointer" title="{{ __('admin.logout') }}">
                    <span class="material-symbols-outlined text-[18px] shrink-0">logout</span>
                    <span class="sidebar-text truncate">{{ __('admin.logout') }}</span>
                    <div class="sidebar-tooltip pointer-events-none absolute left-full top-1/2 -translate-y-1/2 ml-3 px-2.5 py-1.5 bg-[#0b1c30] text-white text-xs font-semibold rounded-lg shadow-xl whitespace-nowrap z-50 opacity-0 group-hover:opacity-100 transition-opacity hidden">
                        {{ __('admin.logout') }}
                    </div>
                </button>
            </div>
        </div>
    </aside>

    <!-- ================= MAIN CONTENT WORKSPACE ================= -->
    <div id="admin-main-wrapper" class="flex-1 lg:pl-64 flex flex-col min-w-0 min-h-screen">
        <!-- Top Navigation Bar -->
        <header class="flex justify-between items-center w-full px-6 py-2 h-14 bg-surface-container-lowest border-b border-outline-variant sticky top-0 z-20">
            <!-- Left side: Mobile menu toggle, Desktop sidebar collapse toggle & Breadcrumbs -->
            <div class="flex items-center gap-3">
                <button type="button" class="lg:hidden w-8 h-8 flex items-center justify-center rounded text-outline hover:text-on-surface hover:bg-surface-container-low transition-colors cursor-pointer"
                    onclick="document.querySelector('#admin-sidebar')?.classList.toggle('-translate-x-full')">
                    <span class="material-symbols-outlined text-[20px]">menu</span>
                </button>
                <button type="button" onclick="toggleAdminSidebar()" class="hidden lg:flex w-8 h-8 items-center justify-center rounded text-outline hover:text-on-surface hover:bg-surface-container-low transition-colors cursor-pointer" title="{{ __('admin.toggle_sidebar') }}">
                    <span class="material-symbols-outlined text-[20px] sidebar-collapse-toggle-icon">dock_to_left</span>
                </button>
                <nav class="flex items-center gap-2 text-xs text-outline font-medium">
                    <span>{{ __('global.common.admin') }}</span>
                    <span>/</span>
                    <a href="{{ route('admin.landing') }}" class="hover:text-on-surface transition-colors">Rooms</a>
                    @if(request()->routeIs('admin.profile'))
                    <span>/</span>
                    <span class="text-on-surface font-semibold">{{ __('admin.profile') }}</span>
                    @elseif($room)
                    <span>/</span>
                    <span class="text-on-surface font-semibold truncate max-w-[180px] sm:max-w-none">{{ $roomLabel }}</span>
                    @endif
                </nav>
            </div>

            <!-- Right side: Language, Notifications Dropdown, Fast Action -->
            <div class="flex items-center gap-3">
                <div class="relative" data-admin-language-switcher>
                    <button type="button"
                        data-language-toggle aria-expanded="false" aria-controls="admin-language-menu"
                        class="flex items-center gap-1.5 px-2 py-1.5 rounded-lg text-xs font-medium text-on-surface-variant hover:bg-surface-container-low transition-colors border border-outline-variant/60 cursor-pointer">
                        <span>{{ $activeLocaleMeta['flag'] }}</span>
                        <span class="hidden sm:inline font-semibold text-on-surface">{{ $activeLocaleMeta['code'] }}</span>
                        <span data-language-chevron class="material-symbols-outlined text-[16px] text-outline transition-transform duration-200">arrow_drop_down</span>
                    </button>

                    <div id="admin-language-menu" data-language-menu class="hidden absolute right-0 top-full mt-2 w-40 bg-surface-container-lowest rounded-xl shadow-xl border border-outline-variant/80 py-1.5 z-50">
                        @foreach($locales as $code => $meta)
                        <a href="{{ route('locale.switch', $code) }}" class="flex items-center justify-between px-3 py-2 text-xs text-on-surface hover:bg-primary/10 hover:text-primary transition-colors {{ $currentLocale === $code ? 'font-semibold text-primary bg-primary/5' : '' }}">
                            <span class="flex items-center gap-2">
                                <span>{{ $meta['flag'] }}</span>
                                <span>{{ $meta['name'] }}</span>
                            </span>
                            @if($currentLocale === $code)
                            <span class="material-symbols-outlined text-[16px] text-primary">check</span>
                            @endif
                        </a>
                        @endforeach
                    </div>
                </div>

                @if($room)
                <!-- Notifications Dropdown -->
                <div class="relative" data-admin-notifications>
                    <button type="button" data-notifications-toggle aria-expanded="false" aria-controls="admin-notifications-menu" class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant hover:bg-surface-container-low transition-colors relative cursor-pointer" title="{{ __('admin.notifications') }}">
                        <span class="material-symbols-outlined text-[20px]">notifications</span>
                        @if($unreadCount > 0 || $unreadNotifications->isNotEmpty())
                        <span data-unread-indicator class="absolute top-1.5 right-1.5 w-2 h-2 bg-error rounded-full ring-2 ring-surface"></span>
                        @endif
                    </button>

                    <div id="admin-notifications-menu" data-notifications-menu data-mark-all-url="{{ route('admin.notifications.read-all', $room) }}" data-empty-text="{{ __('admin.no_unread_notifications') }}" class="hidden absolute right-0 top-full mt-2 w-80 bg-surface-container-lowest border border-outline-variant rounded-xl shadow-xl z-50 py-2 overflow-hidden">
                        <div class="px-4 py-2 border-b border-outline-variant/60 flex items-center justify-between">
                            <span class="font-bold text-xs text-on-surface">{{ __('admin.unread_notifications') }}</span>
                            <div class="flex items-center gap-2">
                                @if($unreadCount > 0)
                                <span data-unread-count class="px-1.5 py-0.5 rounded-full bg-error-container text-error text-[10px] font-mono font-bold">{{ $unreadCount }}</span>
                                @endif
                                <button type="button" data-mark-all-read class="text-[10px] font-semibold text-primary hover:no-underline disabled:opacity-50">{{ __('admin.mark_all_read') }}</button>
                            </div>
                        </div>

                        <div class="max-h-64 overflow-y-auto divide-y divide-outline-variant/40">
                            @forelse($unreadNotifications as $notif)
                            @php($notificationPresentation = $notificationPresentations[$notif->getKey()] ?? ['title' => '', 'body' => '', 'icon' => 'notifications'])
                            <div data-unread-notification class="px-4 py-2.5 hover:bg-surface-container-low transition-colors">
                                <div class="flex items-start gap-2.5">
                                    <span class="w-6 h-6 rounded-full bg-primary/10 text-primary flex items-center justify-center shrink-0 mt-0.5">
                                        <span class="material-symbols-outlined text-[14px]">info</span>
                                    </span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-medium text-on-surface leading-snug">{{ $notificationPresentation['title'] }}</p>
                                        @if($notificationPresentation['body'])
                                        <p class="mt-0.5 text-[11px] text-outline leading-snug">{{ $notificationPresentation['body'] }}</p>
                                        @endif
                                        <span class="text-[10px] font-mono text-outline">{{ $notif->created_at ? $notif->created_at->diffForHumans() : 'Vừa xong' }}</span>
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
                            <a href="{{ route('admin.audit.page', $room) }}" class="text-xs text-primary font-semibold no-underline hover:no-underline block">
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
        <main class="flex-1 min-w-0 w-full p-4 sm:p-6 space-y-6">
            {{ $slot }}
        </main>
    </div>

    <!-- Admin Logout Confirmation Modal -->
    <x-admin.logout-modal />

    <!-- Admin Page Navigation & Submit Loading Overlay -->
    <x-admin.loading />

    <!-- Admin Go To Top Floating Button -->
    <x-admin.go-to-top />

    {{ $scripts ?? '' }}
    @stack('scripts')
</body>

</html>

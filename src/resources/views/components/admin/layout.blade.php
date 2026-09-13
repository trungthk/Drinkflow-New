@props([
    'title' => null,
    'active' => 'dashboard',
    'room' => null,
    'assignedRooms' => null,
])

@php
    $room = $room ?? request()->attributes->get('room') ?? request()->route('room');
    $roomLabel = $room?->name ?? 'DrinkFlow';
    $pageTitle = $title ?: __('admin.dashboard');
    $adminUser = auth('admin')->user();
    $assignedRoomsList = $assignedRooms ?? ($room ? collect([$room]) : collect());
    if ($assignedRoomsList instanceof \Illuminate\Support\Collection && $assignedRoomsList->isEmpty() && $room) {
        $assignedRoomsList = collect([$room]);
    }
@endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="room-slug" content="{{ $room?->slug }}">
    <title>{{ $pageTitle }} · {{ $roomLabel }} · DrinkFlow Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:ital,wght@0,300..900;1,300..900&family=JetBrains+Mono:ital,wght@0,100..800;1,100..800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>

<body data-room-slug="{{ $room?->slug }}" class="bg-surface text-on-surface font-sans min-h-screen flex antialiased">
    <!-- ================= LEFT SIDEBAR ================= -->
    <aside id="admin-sidebar" class="fixed top-0 left-0 h-screen w-64 flex flex-col z-30 bg-surface-container-lowest border-r border-outline-variant transition-transform duration-200 -translate-x-full lg:translate-x-0">
        <div class="w-64 h-full p-4 flex flex-col justify-between overflow-y-auto">
            <div>
                <!-- Brand Logo & Header -->
                <div class="flex items-center gap-2 mb-5 px-1 justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded bg-primary flex items-center justify-center text-on-primary shadow-sm">
                            <span class="material-symbols-outlined text-[20px]">local_bar</span>
                        </div>
                        <div>
                            <span class="text-base font-bold text-on-surface tracking-tight block leading-tight">{{ __('admin.brand_title') }}</span>
                            <span class="text-[11px] font-mono text-outline block">{{ __('admin.brand_subtitle') }}</span>
                        </div>
                    </div>
                    <button type="button" class="lg:hidden w-8 h-8 flex items-center justify-center rounded text-outline hover:text-on-surface hover:bg-surface-container-low transition-colors" onclick="document.querySelector('#admin-sidebar')?.classList.toggle('-translate-x-full')">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <!-- Active Workspace Dropdown Trigger -->
                <div class="mb-5 relative" x-data="{ open: false }">
                    <label class="text-[10px] font-mono text-outline block mb-1 uppercase tracking-wider font-semibold">{{ __('admin.active_workspace') }}</label>
                    <button type="button" @click="open = !open" class="w-full flex items-center justify-between px-3 py-2 bg-surface border border-outline-variant rounded hover:border-outline text-left transition-colors">
                        <div class="flex items-center gap-2 truncate">
                            <span class="w-2 h-2 rounded-full bg-primary inline-block shrink-0"></span>
                            <span class="text-xs font-semibold text-on-surface truncate">{{ $roomLabel }}</span>
                        </div>
                        <span class="text-[10px] text-outline ml-1">▼</span>
                    </button>

                    @if($assignedRoomsList->count() > 1 && $room)
                    <div x-show="open" @click.away="open = false" x-cloak class="absolute left-0 right-0 top-full mt-1 bg-surface-container-lowest border border-outline-variant rounded shadow-lg z-50 py-1 max-h-48 overflow-y-auto">
                        @foreach($assignedRoomsList as $assigned)
                        <a href="{{ route('admin.dashboard.page', $assigned) }}" class="flex items-center justify-between px-3 py-2 text-xs hover:bg-surface-container-low transition-colors {{ $assigned->id === $room->id ? 'font-bold text-primary bg-primary/5' : 'text-on-surface' }}">
                            <span class="truncate">{{ $assigned->name }}</span>
                            @if($assigned->id === $room->id)
                            <span class="material-symbols-outlined text-[14px] text-primary">check</span>
                            @endif
                        </a>
                        @endforeach
                    </div>
                    @endif
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
                        <span id="nav-live-badge" class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-error-container text-on-error-container font-bold hidden">Live</span>
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
                    @endif
                </nav>
            </div>

            <!-- Footer Action Links -->
            <div class="pt-4 border-t border-outline-variant space-y-1 text-xs">
                @if($room)
                <a class="flex items-center gap-3 px-3 py-2 text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low rounded font-medium transition-colors {{ $active === 'audit' ? 'bg-secondary-container text-primary font-bold' : '' }}"
                    href="{{ route('admin.audit.page', $room) }}">
                    <span class="material-symbols-outlined text-[18px]">health_and_safety</span>
                    <span>{{ __('admin.system_diagnostics') }}</span>
                </a>
                @endif
                <form method="post" action="{{ route('admin.logout') }}" class="m-0">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 text-error hover:bg-error-container/40 rounded font-medium transition-colors text-left cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">logout</span>
                        <span>{{ __('admin.logout') }}</span>
                    </button>
                </form>
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
                    <span>Admin</span>
                    <span>/</span>
                    <a href="{{ route('admin.landing') }}" class="hover:text-on-surface transition-colors">Rooms</a>
                    <span>/</span>
                    <span class="text-on-surface font-semibold truncate max-w-[180px] sm:max-w-none">{{ $roomLabel }}</span>
                </nav>
            </div>

            <!-- Right side: Realtime Socket, Actions & User Profile -->
            <div class="flex items-center gap-3">
                <!-- Socket Live Status -->
                <span id="socket-state" class="hidden sm:inline-flex items-center gap-1.5 px-2 py-1 rounded-full bg-surface-container-low text-[11px] font-mono text-outline border border-outline-variant/60">
                    <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                    <span>Socket wss</span>
                </span>

                @if($room)
                <!-- Notifications Bell -->
                <a href="{{ route('admin.manage.page', [$room, 'tab' => 'notifications']) }}" class="w-8 h-8 flex items-center justify-center rounded text-on-surface-variant hover:bg-surface-container-low transition-colors relative" title="Notifications">
                    <span class="material-symbols-outlined text-[20px]">notifications</span>
                    <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-error rounded-full"></span>
                </a>
                @endif

                <div class="h-4 w-px bg-outline-variant mx-1 hidden sm:block"></div>

                <!-- Admin Profile Avatar & Role -->
                <div class="flex items-center gap-2 pl-1">
                    <div class="w-8 h-8 rounded-full bg-secondary text-on-secondary font-mono text-xs flex items-center justify-center font-bold ring-2 ring-emerald-100 border border-outline-variant">
                        {{ mb_strtoupper(mb_substr($adminUser?->name ?? 'AD', 0, 2)) }}
                    </div>
                    <div class="text-left hidden md:block">
                        <div class="text-xs font-semibold text-on-surface leading-tight">{{ $adminUser?->name ?? 'Admin Room' }}</div>
                        <div class="text-[10px] text-outline font-mono leading-tight">Super Dispatcher</div>
                    </div>
                </div>

                @if($room)
                <!-- Fast Create Action Button -->
                <a href="{{ route('admin.campaigns.create', $room) }}" class="ml-2 bg-primary hover:bg-primary-container text-on-primary px-3 py-1.5 rounded text-xs font-semibold flex items-center gap-1.5 transition-colors shadow-sm no-underline">
                    <span class="material-symbols-outlined text-[16px]">bolt</span>
                    <span class="hidden sm:inline">{{ __('admin.dispatch_action') }}</span>
                </a>
                @endif
            </div>
        </header>

        <!-- Main Scrollable Canvas -->
        <main class="flex-1 p-4 sm:p-6 space-y-6">
            {{ $slot }}
        </main>
    </div>

    <!-- Admin Go To Top Floating Button -->
    <x-admin.go-to-top />

    {{ $scripts ?? '' }}
    @stack('scripts')
</body>

</html>

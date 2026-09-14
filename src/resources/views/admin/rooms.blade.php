@php
    $roomsCount = $rooms->count();
    $adminInitials = strtoupper(substr($admin->name ?? 'Admin', 0, 2));
    $currentLocale = app()->getLocale();
    $locales = $locales ?? \App\Constants\AppLocale::SUPPORTED;
    $activeLocaleMeta = \App\Constants\AppLocale::get($currentLocale);
@endphp
<!DOCTYPE html>
<html class="h-full" lang="{{ app()->getLocale() }}">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('admin.select_room_title') }} · DrinkFlow Admin</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    <!-- Google Fonts: JetBrains Mono & Hanken Grotesk -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect">
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Material Symbols Outlined -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

    <!-- Tailwind Engine & Custom Design Config -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#006948",
                        "primary-container": "#00855d",
                        "primary-fixed": "#85f8c4",
                        "primary-fixed-dim": "#68dba9",
                        "on-primary": "#ffffff",
                        "on-primary-container": "#f5fff7",
                        "secondary": "#565e74",
                        "secondary-container": "#dae2fd",
                        "on-secondary-container": "#5c647a",
                        "on-secondary-fixed-variant": "#3f465c",
                        "tertiary": "#006947",
                        "tertiary-container": "#00855b",
                        "tertiary-fixed": "#6ffbbe",
                        "tertiary-fixed-dim": "#4edea3",
                        "background": "#f8f9ff",
                        "surface": "#f8f9ff",
                        "surface-dim": "#cbdbf5",
                        "surface-bright": "#f8f9ff",
                        "surface-container": "#e5eeff",
                        "surface-container-low": "#eff4ff",
                        "surface-container-high": "#dce9ff",
                        "surface-container-highest": "#d3e4fe",
                        "surface-container-lowest": "#ffffff",
                        "on-surface": "#0b1c30",
                        "on-surface-variant": "#3d4a42",
                        "outline": "#6d7a72",
                        "outline-variant": "#bccac0",
                        "error": "#ba1a1a",
                        "error-container": "#ffdad6",
                        "on-error": "#ffffff",
                        "on-error-container": "#93000a"
                    },
                    fontFamily: {
                        "label-lg": ["JetBrains Mono", "monospace"],
                        "label-sm": ["JetBrains Mono", "monospace"],
                        "label-md": ["JetBrains Mono", "monospace"],
                        "headline-sm": ["Hanken Grotesk", "sans-serif"],
                        "headline-lg": ["Hanken Grotesk", "sans-serif"],
                        "display-lg": ["Hanken Grotesk", "sans-serif"],
                        "body-md": ["Hanken Grotesk", "sans-serif"],
                        "body-lg": ["Hanken Grotesk", "sans-serif"],
                        "body-sm": ["Hanken Grotesk", "sans-serif"]
                    },
                    fontSize: {
                        "label-lg": ["13px", { lineHeight: "18px", letterSpacing: "-0.01em", fontWeight: "600" }],
                        "label-sm": ["10px", { lineHeight: "14px", letterSpacing: "0.02em", fontWeight: "500" }],
                        "label-md": ["12px", { lineHeight: "16px", letterSpacing: "0em", fontWeight: "500" }],
                        "headline-sm": ["18px", { lineHeight: "24px", letterSpacing: "-0.01em", fontWeight: "600" }],
                        "headline-lg": ["24px", { lineHeight: "32px", letterSpacing: "-0.015em", fontWeight: "600" }],
                        "display-lg": ["32px", { lineHeight: "40px", letterSpacing: "-0.02em", fontWeight: "700" }],
                        "body-md": ["14px", { lineHeight: "20px", letterSpacing: "0em", fontWeight: "400" }],
                        "body-lg": ["16px", { lineHeight: "24px", letterSpacing: "0em", fontWeight: "400" }],
                        "body-sm": ["12px", { lineHeight: "16px", letterSpacing: "0.01em", fontWeight: "400" }]
                    }
                }
            }
        };
    </script>

    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 20;
            font-size: 18px;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            vertical-align: middle;
        }
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.85); }
        }
        .live-pulse {
            animation: pulse-dot 1.8s infinite ease-in-out;
        }
    </style>

    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>

<body class="bg-surface text-on-surface min-h-screen flex flex-col justify-between antialiased selection:bg-primary-container selection:text-on-primary-container">
    <!-- Ambient Backdrop Micro-Pattern -->
    <div class="fixed inset-0 pointer-events-none opacity-[0.035] bg-[radial-gradient(#006948_1px,transparent_1px)] [background-size:24px_24px]"></div>

    <!-- Header Strip -->
    <header class="relative z-10 w-full max-w-7xl mx-auto px-4 sm:px-6 pb-2 pt-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-outline-variant/40 pb-3">
            <!-- Logo & System Anchor -->
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-xl flex items-center justify-center text-white shadow-xs shrink-0"
                      style="background: linear-gradient(135deg, #006948 0%, #047857 100%); background-color: #006948; border: 1px solid #005137; box-shadow: 0 1px 2px rgba(0,0,0,0.08);">
                    <svg class="w-5 h-5 text-white fill-current" viewBox="0 0 24 24" aria-hidden="true" style="width: 22px; height: 22px; fill: #ffffff; color: #ffffff;">
                        <path d="M20 3H4v10c0 2.21 1.79 4 4 4h6c2.21 0 4-1.79 4-4v-3h2c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 5h-2V5h2v3zM4 19h16v2H4z"/>
                    </svg>
                </span>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-headline-sm text-headline-sm text-on-surface font-bold tracking-tight">DrinkFlow</span>
                        <span class="font-label-sm text-label-sm uppercase px-1.5 py-0.5 rounded bg-surface-container-highest text-on-surface-variant font-semibold">Admin Core</span>
                    </div>
                    <p class="font-label-md text-label-md text-outline">{{ __('admin.brand_subtitle') }}</p>
                </div>
            </div>

            <!-- Right Actions: Language Switcher & User Session Chip -->
            <div class="flex items-center gap-2 sm:gap-3 self-start sm:self-auto">
                <!-- Language Switcher Dropdown -->
                <div class="relative" data-admin-language-switcher>
                    <button type="button"
                            data-language-toggle aria-expanded="false" aria-controls="admin-language-menu"
                            class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl text-xs font-medium text-on-surface-variant hover:bg-surface-container-low transition-colors bg-surface-container-lowest border border-outline-variant/60 shadow-xs cursor-pointer">
                        <span>{{ $activeLocaleMeta['flag'] }}</span>
                        <span class="font-semibold text-on-surface">{{ $activeLocaleMeta['code'] }}</span>
                        <span data-language-chevron class="material-symbols-outlined text-[16px] text-outline transition-transform duration-200">arrow_drop_down</span>
                    </button>

                    <!-- Dropdown Menu -->
                    <div id="admin-language-menu" data-language-menu
                         class="hidden absolute right-0 mt-1.5 w-36 bg-surface-container-lowest rounded-xl shadow-lg border border-outline-variant/80 py-1.5 z-50">
                        @foreach($locales as $code => $meta)
                            <a href="{{ route('locale.switch', $code) }}"
                               class="flex items-center justify-between px-3 py-2 text-xs text-on-surface hover:bg-primary/10 hover:text-primary transition-colors {{ $currentLocale === $code ? 'font-semibold text-primary bg-primary/5' : '' }}">
                                <div class="flex items-center gap-2">
                                    <span>{{ $meta['flag'] }}</span>
                                    <span>{{ $meta['name'] }}</span>
                                </div>
                                @if($currentLocale === $code)
                                    <span class="material-symbols-outlined text-[16px] text-primary">check</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>

                <!-- User Session & Enterprise SSO Chip -->
                <div class="flex items-center gap-3 bg-surface-container-lowest border border-outline-variant/60 rounded-xl p-1.5 pr-4 shadow-xs">
                    <div class="w-8 h-8 rounded-lg bg-surface-container-high text-primary flex items-center justify-center font-label-md text-label-md font-bold">
                        {{ $adminInitials }}
                    </div>
                    <div class="flex flex-col text-left">
                        <div class="flex items-center gap-1.5">
                            <a href="{{ route('admin.profile') }}" class="font-headline-sm text-[13px] leading-tight font-semibold text-on-surface hover:text-primary transition-colors no-underline" title="{{ __('admin.profile_security') }}">{{ $admin->name }}</a>
                            <span class="inline-block w-1.5 h-1.5 rounded-full bg-primary" title="Active"></span>
                            <span class="font-label-sm text-label-sm text-outline">{{ $admin->role ?? __('admin.room_manager_role') }}</span>
                        </div>
                        <span class="font-label-sm text-label-sm text-outline-variant flex items-center gap-1">
                            {{ $admin->email }}
                        </span>
                    </div>
                    <div class="h-6 w-[1px] bg-outline-variant/50 mx-1"></div>
                    <button class="text-on-surface-variant hover:text-error transition-colors flex items-center gap-1 font-label-sm text-label-sm cursor-pointer"
                            title="{{ __('admin.logout') }}" type="button" onclick="openAdminLogoutModal()">
                        <span class="material-symbols-outlined text-[16px]">logout</span>
                        <span class="hidden md:inline">{{ __('admin.logout') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main View Container -->
    <main class="relative z-10 w-full max-w-7xl mx-auto px-4 sm:px-6 flex-1 py-4">
        <!-- Title & Subtitle Banner -->
        <div class="mb-4">
            <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded bg-secondary-container/70 text-on-secondary-container font-label-sm text-label-sm mb-2">
                <span class="material-symbols-outlined text-[14px]">room_preferences</span>
                <span>{{ __('admin.switch_to_rooms') }}</span>
            </div>
            <h1 class="font-display-lg text-display-lg text-on-surface font-extrabold tracking-tight">
                {{ __('admin.select_room_heading') }}
            </h1>
            <p class="font-body-md text-body-md text-on-surface-variant max-w-3xl mt-1.5">
                {{ __('admin.select_room_desc_count', ['count' => $roomsCount]) }}
            </p>
        </div>

        @if($roomsCount > 0)
            <!-- Search & Filter Operational Bar -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 mb-4">
                <!-- Search Input with perfectly aligned icon and clear button -->
                <div class="relative flex items-center flex-1 max-w-md rounded-xl border border-outline-variant/70 bg-surface-container-lowest focus-within:border-primary focus-within:ring-1 focus-within:ring-primary transition-all shadow-2xs">
                    <span class="material-symbols-outlined text-outline pl-3 text-[18px] pointer-events-none select-none shrink-0 flex items-center justify-center">search</span>
                    <input id="roomSearchInput" type="text"
                           placeholder="{{ __('admin.search_room_placeholder') }}"
                           class="w-full border-0 bg-transparent px-2.5 py-2 text-sm text-on-surface placeholder:text-outline/70 focus:ring-0 outline-none font-medium">
                    <button type="button" id="clearRoomSearchBtn"
                            class="hidden pr-3 text-outline hover:text-on-surface focus:outline-none cursor-pointer shrink-0 flex items-center justify-center transition-colors"
                            title="{{ __('admin.clear_search') }}" aria-label="{{ __('admin.clear_search') }}">
                        <span class="material-symbols-outlined text-[18px]">cancel</span>
                    </button>
                </div>

                <!-- Filter Buttons -->
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0">
                    <button type="button" data-filter="all" class="filter-btn active-filter px-3 py-1.5 rounded-lg font-label-sm text-xs font-semibold bg-primary text-on-primary shadow-xs transition-all cursor-pointer">
                        {{ __('admin.filter_room_all') }} ({{ $roomsCount }})
                    </button>
                    <button type="button" data-filter="live" class="filter-btn px-3 py-1.5 rounded-lg font-label-sm text-xs font-semibold bg-surface-container-low text-on-surface-variant hover:bg-surface-container transition-all cursor-pointer">
                        🔥 {{ __('admin.filter_room_live') }} ({{ $liveCount ?? 0 }})
                    </button>
                    <button type="button" data-filter="debt" class="filter-btn px-3 py-1.5 rounded-lg font-label-sm text-xs font-semibold bg-surface-container-low text-on-surface-variant hover:bg-surface-container transition-all cursor-pointer">
                        ⚠️ {{ __('admin.filter_room_debt') }} ({{ $debtCount ?? 0 }})
                    </button>
                    <button type="button" data-filter="idle" class="filter-btn px-3 py-1.5 rounded-lg font-label-sm text-xs font-semibold bg-surface-container-low text-on-surface-variant hover:bg-surface-container transition-all cursor-pointer">
                        {{ __('admin.filter_room_idle') }} ({{ $idleCount ?? 0 }})
                    </button>
                </div>
            </div>

            <!-- 2x2 High-Density Operational Bento Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="roomsGrid">
                @foreach($roomsData as $r)
                    <article class="room-card group bg-surface-container-lowest border border-outline-variant/70 rounded-xl hover:border-primary transition-all duration-200 hover:shadow-md flex flex-col justify-between relative overflow-hidden p-4"
                             data-room-type="{{ $r->room_type }}"
                             data-room-name="{{ strtolower($r->name . ' ' . $r->slug . ' ' . $r->description) }}">
                        <!-- Left Accent Bar -->
                        @if($r->room_type === 'live')
                            <div class="absolute top-0 left-0 w-1.5 h-full bg-primary"></div>
                        @elseif($r->room_type === 'scheduled')
                            <div class="absolute top-0 left-0 w-1.5 h-full bg-secondary-container"></div>
                        @elseif($r->room_type === 'debt')
                            <div class="absolute top-0 left-0 w-1.5 h-full bg-[#D97706]"></div>
                        @else
                            <div class="absolute top-0 left-0 w-1.5 h-full bg-outline-variant"></div>
                        @endif

                        <div>
                            <!-- Card Header & Badge -->
                            <div class="flex items-start justify-between gap-2 mb-3">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="px-2 py-0.5 rounded bg-surface-container font-label-sm text-label-sm text-on-surface-variant font-bold">
                                        #ROOM-{{ strtoupper($r->slug) }}
                                    </span>

                                    @if($r->room_type === 'live')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded font-label-sm text-label-sm font-semibold bg-[#ECFDF5] text-primary border border-[#A7F3D0]">
                                            <span class="w-2 h-2 rounded-full bg-primary live-pulse"></span>
                                            🔥 {{ __('admin.badge_live_campaign') }}
                                        </span>
                                    @elseif($r->room_type === 'scheduled')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded font-label-sm text-label-sm font-semibold bg-surface-container-high text-on-secondary-fixed-variant border border-outline-variant">
                                            <span class="material-symbols-outlined text-[14px] text-tertiary">bolt</span>
                                            ⚡ {{ __('admin.badge_upcoming_campaign', ['time' => $r->scheduled_campaign?->deadline?->format('H:i') ?? __('admin.upcoming_activation')]) }}
                                        </span>
                                    @elseif($r->room_type === 'debt')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded font-label-sm text-label-sm font-semibold bg-[#FFFBEB] text-[#D97706] border border-[#FDE68A]">
                                            <span class="material-symbols-outlined text-[14px]">warning</span>
                                            ⚠️ {{ __('admin.badge_needs_debt_audit') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded font-label-sm text-label-sm font-semibold bg-surface-container text-outline border border-outline-variant/60">
                                            <span class="w-2 h-2 rounded-full bg-outline"></span>
                                            {{ __('admin.badge_idle_ready') }}
                                        </span>
                                    @endif
                                </div>

                                <div class="flex items-center gap-1 font-label-sm text-label-sm text-outline shrink-0">
                                    <span class="material-symbols-outlined text-[15px]">shield_person</span>
                                    <span>{{ __('admin.room_manager_role') }}</span>
                                </div>
                            </div>

                            <!-- Room Title & Location -->
                            <h2 class="font-headline-lg text-headline-lg text-on-surface font-bold tracking-tight group-hover:text-primary transition-colors flex items-center gap-2">
                                <a href="{{ route('admin.dashboard.page', $r->model) }}" class="no-underline text-inherit hover:text-primary flex items-center gap-2">
                                    <span>{{ $r->name }}</span>
                                    <span class="material-symbols-outlined text-[20px] text-primary opacity-0 group-hover:opacity-100 transition-opacity">open_in_new</span>
                                </a>
                            </h2>
                            <div class="flex items-center gap-1.5 text-outline font-body-sm text-body-sm mt-1">
                                <span class="material-symbols-outlined text-[16px]">apartment</span>
                                <span>{{ $r->description ?: __('admin.default_room_desc') }}</span>
                            </div>

                            <!-- Key Metrics Grid -->
                            <div class="grid grid-cols-3 gap-3 my-4 py-3 px-3.5 bg-surface rounded-lg border border-outline-variant/40">
                                <div>
                                    <span class="font-label-sm text-label-sm text-outline block">{{ __('admin.metric_members') }}</span>
                                    <div class="flex items-baseline gap-1 mt-0.5">
                                        <span class="font-headline-sm text-[20px] font-bold text-on-surface">{{ $r->active_members_count }}</span>
                                        <span class="font-label-sm text-label-sm text-outline">{{ __('admin.status_active') }}</span>
                                    </div>
                                </div>
                                <div>
                                    @if($r->unpaid_debts_sum > 0)
                                        <span class="font-label-sm text-label-sm text-[#D97706] block">{{ __('admin.metric_unpaid_debt') }}</span>
                                        <div class="flex items-baseline gap-1 mt-0.5">
                                            <span class="font-headline-sm text-[18px] font-bold text-[#D97706]">{{ number_format($r->unpaid_debts_sum) }}₫</span>
                                        </div>
                                    @else
                                        <span class="font-label-sm text-label-sm text-outline block">{{ __('admin.metric_fund_limit') }}</span>
                                        <div class="flex items-baseline gap-1 mt-0.5">
                                            <span class="font-headline-sm text-[18px] font-bold text-primary">{{ $r->payment_account ? __('admin.vietqr_ready') : __('admin.not_configured') }}</span>
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <span class="font-label-sm text-label-sm text-outline block">{{ __('admin.metric_today_orders') }}</span>
                                    <div class="flex items-baseline gap-1 mt-0.5">
                                        <span class="font-headline-sm text-[20px] font-bold text-on-surface">{{ $r->today_orders_count }}</span>
                                        <span class="font-label-sm text-label-sm text-primary font-semibold">{{ __('admin.orders') }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Micro-affordance Banner -->
                            @if($r->room_type === 'live' && $r->active_campaign)
                                <div class="mb-4 flex items-center justify-between p-2.5 rounded bg-surface-container-low border border-outline-variant/30 text-body-sm">
                                    <div class="flex items-center gap-2 text-on-surface truncate mr-2">
                                        <span class="material-symbols-outlined text-primary text-[18px] shrink-0">coffee</span>
                                        <span class="truncate">{{ __('admin.active_campaign_open_notice', ['name' => $r->active_campaign->name, 'restaurant' => $r->active_campaign->restaurant]) }}</span>
                                    </div>
                                    <a class="font-label-sm text-label-sm text-primary font-semibold hover:underline flex items-center gap-0.5 shrink-0 no-underline"
                                       href="{{ route('admin.campaigns.page', $r->model) }}">
                                        <span>{{ __('admin.view_campaign') }}</span>
                                        <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                                    </a>
                                </div>
                            @elseif($r->room_type === 'scheduled' && $r->scheduled_campaign)
                                <div class="mb-4 flex items-center justify-between p-2.5 rounded bg-surface border border-outline-variant/30 text-body-sm">
                                    <div class="flex items-center gap-2 text-on-surface-variant truncate mr-2">
                                        <span class="material-symbols-outlined text-outline text-[18px] shrink-0">alarm</span>
                                        <span class="truncate">{{ __('admin.scheduled_campaign_notice', ['name' => $r->scheduled_campaign->name]) }}</span>
                                    </div>
                                    <span class="font-label-sm text-label-sm text-outline font-semibold shrink-0">{{ __('admin.upcoming_activation') }}</span>
                                </div>
                            @elseif($r->room_type === 'debt')
                                <div class="mb-4 flex items-center justify-between p-2.5 rounded bg-[#FEF2F2]/60 border border-[#FCA5A5]/60 text-body-sm">
                                    <div class="flex items-center gap-2 text-on-surface truncate mr-2">
                                        <span class="material-symbols-outlined text-error text-[18px] shrink-0">receipt_long</span>
                                        <span class="truncate">{{ __('admin.unpaid_debts_pending_notice', ['count' => $r->unpaid_debts_count]) }}</span>
                                    </div>
                                    <a class="font-label-sm text-label-sm text-error font-semibold hover:underline flex items-center gap-0.5 shrink-0 no-underline"
                                       href="{{ route('admin.debts.page', $r->model) }}">
                                        <span>{{ __('admin.audit_now') }}</span>
                                    </a>
                                </div>
                            @else
                                <div class="mb-4 flex items-center justify-between p-2.5 rounded bg-surface border border-outline-variant/30 text-body-sm">
                                    <div class="flex items-center gap-2 text-outline truncate mr-2">
                                        <span class="material-symbols-outlined text-[18px] shrink-0">bedtime</span>
                                        <span class="truncate">{{ __('admin.no_campaign_today') }}</span>
                                    </div>
                                    <span class="font-label-sm text-label-sm text-outline-variant font-medium shrink-0">{{ __('admin.ready_status') }}</span>
                                </div>
                            @endif
                        </div>

                        <!-- Card Actions (Footer) -->
                        <div class="pt-3 border-t border-outline-variant/40 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2 text-outline font-label-sm text-label-sm">
                                <span class="material-symbols-outlined text-[16px]">schedule</span>
                                <span>{{ __('admin.updated_time', ['time' => $r->updated_at?->diffForHumans() ?? __('admin.ready_status')]) }}</span>
                            </div>
                            <a href="{{ route('admin.dashboard.page', $r->model) }}"
                               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-primary hover:bg-[#059669] text-on-primary font-label-md text-label-md font-semibold transition shadow-xs active:scale-[0.98] no-underline">
                                <span>{{ __('admin.open_dashboard') }}</span>
                                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>

            <!-- Empty Search Results -->
            <div class="hidden text-center py-16 bg-surface-container-lowest rounded-xl border border-outline-variant/50 mt-4" id="noResults">
                <span class="material-symbols-outlined text-outline text-5xl mb-2">search_off</span>
                <h3 class="font-headline-sm text-headline-sm text-on-surface font-semibold">{{ __('admin.no_matching_rooms') }}</h3>
                <p class="font-body-md text-body-md text-outline mt-1">{{ __('admin.no_matching_rooms_desc') }}</p>
            </div>
        @else
            <!-- No Assigned Rooms State -->
            <div class="text-center py-16 bg-surface-container-lowest rounded-xl border border-outline-variant/50 mt-4">
                <span class="material-symbols-outlined text-outline text-5xl mb-2">domain_disabled</span>
                <h3 class="font-headline-sm text-headline-sm text-on-surface font-semibold">{{ __('admin.no_assigned_rooms') }}</h3>
                <p class="font-body-md text-body-md text-outline mt-1">{{ __('admin.no_assigned_rooms_contact') }}</p>
            </div>
        @endif
    </main>

    <!-- Logout Confirmation Modal -->
    <x-admin.logout-modal />

    <!-- Search, Debounce & Filter Logic -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('roomSearchInput');
            const clearBtn = document.getElementById('clearRoomSearchBtn');
            const filterButtons = document.querySelectorAll('.filter-btn');
            const cards = document.querySelectorAll('.room-card');
            const noResults = document.getElementById('noResults');

            let currentFilter = 'all';
            let currentQuery = '';

            // Debounce helper
            function debounce(fn, delay = 250) {
                let timer = null;
                return function (...args) {
                    clearTimeout(timer);
                    timer = setTimeout(() => fn.apply(this, args), delay);
                };
            }

            function applyFilter() {
                let visibleCount = 0;

                cards.forEach(card => {
                    const type = card.getAttribute('data-room-type');
                    const content = (card.getAttribute('data-room-name') || card.textContent).toLowerCase();

                    const matchesType = (currentFilter === 'all') ||
                        (currentFilter === 'live' && type === 'live') ||
                        (currentFilter === 'debt' && type === 'debt') ||
                        (currentFilter === 'idle' && (type === 'idle' || type === 'scheduled'));

                    const matchesQuery = !currentQuery || content.includes(currentQuery);

                    if (matchesType && matchesQuery) {
                        card.classList.remove('hidden');
                        visibleCount++;
                    } else {
                        card.classList.add('hidden');
                    }
                });

                if (noResults) {
                    if (visibleCount === 0 && cards.length > 0) {
                        noResults.classList.remove('hidden');
                    } else {
                        noResults.classList.add('hidden');
                    }
                }
            }

            const debouncedApplyFilter = debounce(() => {
                applyFilter();
            }, 200);

            // Filter button event listeners
            filterButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    filterButtons.forEach(b => {
                        b.classList.remove('bg-primary', 'text-on-primary', 'shadow-xs', 'active-filter');
                        b.classList.add('bg-surface-container-low', 'text-on-surface-variant');
                    });

                    btn.classList.add('bg-primary', 'text-on-primary', 'shadow-xs', 'active-filter');
                    btn.classList.remove('bg-surface-container-low', 'text-on-surface-variant');

                    currentFilter = btn.getAttribute('data-filter');
                    applyFilter();
                });
            });

            // Search input event listeners with debounce and clear button toggle
            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    currentQuery = e.target.value.trim().toLowerCase();
                    if (clearBtn) {
                        clearBtn.classList.toggle('hidden', e.target.value.length === 0);
                    }
                    debouncedApplyFilter();
                });
            }

            // Clear button handler
            if (clearBtn && searchInput) {
                clearBtn.addEventListener('click', () => {
                    searchInput.value = '';
                    currentQuery = '';
                    clearBtn.classList.add('hidden');
                    applyFilter();
                    searchInput.focus();
                });
            }
        });
    </script>
</body>

</html>

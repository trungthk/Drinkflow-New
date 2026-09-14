@props([
    'title' => null,
    'brandHero' => null,
])

@php
    $currentLocale = app()->getLocale();
    $activeLocaleMeta = $locales[$currentLocale] ?? $locales['vi'];
@endphp

<!doctype html>
<html class="h-full" lang="{{ $currentLocale }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title . ' · ' : '' }}{{ __('admin.brand_title') }} · DrinkFlow Admin</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:ital,wght@0,300..800;1,300..800&family=JetBrains+Mono:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>

<body class="h-full bg-surface text-on-surface font-sans antialiased overflow-hidden selection:bg-primary/20">
    <div class="h-screen overflow-hidden flex flex-col lg:flex-row">
        <!-- LEFT COLUMN: Brand & Operations Engine (~46% width) -->
        <aside class="lg:w-5/12 xl:w-1/2 bg-[#0a2220] text-surface-bright hidden md:flex flex-col p-6 lg:py-8 lg:px-10 relative overflow-hidden border-b lg:border-b-0 lg:border-r border-emerald-950/40 justify-between select-none shrink-0">
            <div class="absolute inset-0 opacity-[0.04] pointer-events-none bg-[radial-gradient(#4edea3_1px,transparent_1px)] [background-size:20px_20px]"></div>
            <div class="absolute -top-32 -right-32 w-96 h-96 rounded-full bg-primary/20 blur-3xl pointer-events-none"></div>

            <!-- Top Brand -->
            <div class="relative z-10">
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl flex items-center justify-center text-white shadow-lg shadow-emerald-900/30 ring-1 ring-white/20 shrink-0"
                          style="background: linear-gradient(135deg, #006948 0%, #047857 100%); background-color: #006948; border: 1px solid #005137;">
                        <svg class="w-5 h-5 text-white fill-current" viewBox="0 0 24 24" aria-hidden="true" style="width: 22px; height: 22px; fill: #ffffff; color: #ffffff;">
                            <path d="M20 3H4v10c0 2.21 1.79 4 4 4h6c2.21 0 4-1.79 4-4v-3h2c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 5h-2V5h2v3zM4 19h16v2H4z"/>
                        </svg>
                    </span>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xl font-bold tracking-tight text-white">{{ __('admin.brand_title') }}</span>
                            <span class="text-[10px] font-mono font-semibold px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 tracking-wider">{{ __('admin.engine_badge') }}</span>
                        </div>
                        <p class="text-[10px] font-mono tracking-widest text-emerald-400/80 uppercase block mt-0.5">{{ __('admin.brand_subtitle') }}</p>
                    </div>
                </div>
            </div>

            <!-- Value Props Cards / Subsystem Overview -->
            <div class="relative z-10 space-y-4 my-auto max-w-lg">
                @if(isset($brandHero) && $brandHero->isNotEmpty())
                    {{ $brandHero }}
                @else
                    <div>
                        <h1 class="text-2xl lg:text-3xl font-bold text-white leading-tight tracking-tight mb-2">
                            {{ __('admin.auth_hero_title') }}
                        </h1>
                        <p class="text-xs lg:text-sm text-slate-300/80 leading-relaxed">
                            {{ __('admin.auth_hero_desc') }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                        <div class="p-3 rounded-xl bg-white/5 border border-white/10 backdrop-blur-xs">
                            <div class="flex items-center gap-2 text-emerald-400 mb-1">
                                <span class="material-symbols-outlined text-[18px]">dinner_dining</span>
                                <strong class="text-xs font-semibold text-white">{{ __('admin.bulk_kitchen_title') }}</strong>
                            </div>
                            <p class="text-[11px] text-slate-400 leading-snug">
                                {{ __('admin.bulk_kitchen_desc') }}
                            </p>
                        </div>

                        <div class="p-3 rounded-xl bg-white/5 border border-white/10 backdrop-blur-xs">
                            <div class="flex items-center gap-2 text-emerald-400 mb-1">
                                <span class="material-symbols-outlined text-[18px]">qr_code_2</span>
                                <strong class="text-xs font-semibold text-white">{{ __('admin.vietqr_napas_title') }}</strong>
                            </div>
                            <p class="text-[11px] text-slate-400 leading-snug">
                                {{ __('admin.vietqr_napas_desc') }}
                            </p>
                        </div>

                        <div class="p-3 rounded-xl bg-white/5 border border-white/10 backdrop-blur-xs sm:col-span-2">
                            <div class="flex items-center justify-between mb-1">
                                <div class="flex items-center gap-2 text-emerald-400">
                                    <span class="material-symbols-outlined text-[18px]">hub</span>
                                    <strong class="text-xs font-semibold text-white">{{ __('admin.webhook_chatops_title') }}</strong>
                                </div>
                                <span class="text-[10px] font-mono font-bold text-emerald-300 px-2 py-0.5 rounded bg-emerald-500/20">{{ __('admin.latency_badge') }}</span>
                            </div>
                            <p class="text-[11px] text-slate-400 leading-snug">
                                {{ __('admin.webhook_chatops_desc') }}
                            </p>
                        </div>
                    </div>
                @endif
            </div>
        </aside>

        <!-- RIGHT COLUMN: Interactive Form (~54% width) -->
        <main class="lg:w-7/12 xl:w-1/2 flex flex-col p-6 lg:py-6 lg:px-12 justify-between bg-surface-container-lowest overflow-y-auto">
            <!-- Top Controls (Language Switcher + Help) -->
            <div class="flex items-center justify-between pb-3">
                <!-- Language Selector Dropdown -->
                <div class="relative" id="admin-auth-lang-selector">
                    <button type="button"
                            id="admin-auth-lang-btn"
                            aria-haspopup="true"
                            aria-expanded="false"
                            onclick="document.getElementById('admin-auth-lang-menu')?.classList.toggle('hidden')"
                            class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-medium text-[#545c72] hover:bg-[#eff4ff] transition-colors duration-150 border border-slate-200/80 hover:border-[#bccac0] cursor-pointer">
                        <span>{{ $activeLocaleMeta['flag'] }}</span>
                        <span class="font-semibold text-[#0b1c30]">{{ $activeLocaleMeta['code'] }}</span>
                        <span class="material-symbols-outlined text-[16px] text-[#545c72]">arrow_drop_down</span>
                    </button>

                    <!-- Dropdown Menu -->
                    <div id="admin-auth-lang-menu"
                         class="hidden absolute left-0 mt-1.5 w-36 bg-white rounded-xl shadow-lg border border-slate-200 py-1.5 z-50 animate-fadeIn">
                        @foreach($locales as $code => $meta)
                            <a href="{{ route('locale.switch', $code) }}"
                               class="flex items-center justify-between px-3 py-2 text-xs text-slate-700 hover:bg-emerald-50 hover:text-[#006948] transition-colors {{ $currentLocale === $code ? 'font-semibold text-[#006948] bg-emerald-50/50' : '' }}">
                                <div class="flex items-center gap-2">
                                    <span>{{ $meta['flag'] }}</span>
                                    <span>{{ $meta['name'] }}</span>
                                </div>
                                @if($currentLocale === $code)
                                    <span class="material-symbols-outlined text-[16px] text-[#006948]">check</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>

                <a class="flex items-center gap-1.5 text-xs font-semibold text-secondary hover:text-primary transition-colors no-underline" href="{{ route('contact') }}">
                    <span class="material-symbols-outlined text-[16px]">contact_support</span>
                    <span>{{ __('admin.contact_support') }}</span>
                </a>
            </div>

            <!-- Center Form Content Slot -->
            <div class="w-full max-w-md mx-auto my-auto py-2">
                {{ $slot }}
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('click', function(e) {
            const selector = document.getElementById('admin-auth-lang-selector');
            const menu = document.getElementById('admin-auth-lang-menu');
            if (selector && menu && !selector.contains(e.target)) {
                menu.classList.add('hidden');
            }
        });
    </script>

    {{ $scripts ?? '' }}
    @stack('scripts')
</body>

</html>

<!doctype html>
<html class="h-full" lang="{{ app()->getLocale() }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('admin.brand_title')) · DrinkFlow Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:ital,wght@0,300..800;1,300..800&family=JetBrains+Mono:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

    @vite(['resources/css/admin.css', 'resources/js/app.js'])
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
                    <div class="w-10 h-10 rounded-xl bg-emerald-500 flex items-center justify-center text-[#0a2220] font-bold shadow-lg shadow-emerald-900/30 ring-1 ring-white/20">
                        <span class="material-symbols-outlined text-[24px]">local_shipping</span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xl font-bold tracking-tight text-white">{{ __('admin.brand_title') }}</span>
                            <span class="text-[10px] font-mono font-semibold px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 tracking-wider">ENGINE</span>
                        </div>
                        <p class="text-[10px] font-mono tracking-widest text-emerald-400/80 uppercase block mt-0.5">{{ __('admin.brand_subtitle') }}</p>
                    </div>
                </div>
            </div>

            <!-- Value Props Cards / Subsystem Overview -->
            <div class="relative z-10 space-y-4 my-auto max-w-lg">
                @hasSection('brand-hero')
                    @yield('brand-hero')
                @else
                    <div>
                        <h1 class="text-2xl lg:text-3xl font-bold text-white leading-tight tracking-tight mb-2">
                            Quản lý chiến dịch đặt đồ uống tập trung cho doanh nghiệp
                        </h1>
                        <p class="text-xs lg:text-sm text-slate-300/80 leading-relaxed">
                            Hạ tầng số hóa quy trình dispatching F&B nội bộ, điều phối đơn số lượng lớn, đối soát số dư ví và tích hợp tự động qua hạ tầng doanh nghiệp.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                        <div class="p-3 rounded-xl bg-white/5 border border-white/10 backdrop-blur-xs">
                            <div class="flex items-center gap-2 text-emerald-400 mb-1">
                                <span class="material-symbols-outlined text-[18px]">dinner_dining</span>
                                <strong class="text-xs font-semibold text-white">Bulk Kitchen Batch</strong>
                            </div>
                            <p class="text-[11px] text-slate-400 leading-snug">
                                Tự động gom đơn từ hàng trăm nhân sự thành 1 đơn tối ưu chiết khấu và tối giản phí ship.
                            </p>
                        </div>

                        <div class="p-3 rounded-xl bg-white/5 border border-white/10 backdrop-blur-xs">
                            <div class="flex items-center gap-2 text-emerald-400 mb-1">
                                <span class="material-symbols-outlined text-[18px]">qr_code_2</span>
                                <strong class="text-xs font-semibold text-white">VietQR Napas 247</strong>
                            </div>
                            <p class="text-[11px] text-slate-400 leading-snug">
                                Chia tiền tự động từng cốc, đồng bộ biến động số dư và quản lý quỹ nợ không sót đơn vị.
                            </p>
                        </div>

                        <div class="p-3 rounded-xl bg-white/5 border border-white/10 backdrop-blur-xs sm:col-span-2">
                            <div class="flex items-center justify-between mb-1">
                                <div class="flex items-center gap-2 text-emerald-400">
                                    <span class="material-symbols-outlined text-[18px]">hub</span>
                                    <strong class="text-xs font-semibold text-white">Webhook & ChatOps Ready</strong>
                                </div>
                                <span class="text-[10px] font-mono font-bold text-emerald-300 px-2 py-0.5 rounded bg-emerald-500/20">Latency &lt; 85ms</span>
                            </div>
                            <p class="text-[11px] text-slate-400 leading-snug">
                                Đồng bộ thông báo chốt menu, nhắc chuyển khoản và phiếu giao hàng qua Slack Bots, Microsoft Teams và Telegram Channels.
                            </p>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Footer Meta -->
            <div class="relative z-10 pt-3 border-t border-white/10 text-[11px] font-mono text-slate-400 flex items-center justify-between">
                <span>Cluster: prod-hcm-edge-01</span>
                <span>Security Level 4</span>
            </div>
        </aside>

        <!-- RIGHT COLUMN: Interactive Form (~54% width) -->
        <main class="lg:w-7/12 xl:w-1/2 flex flex-col p-6 lg:py-6 lg:px-12 justify-between bg-surface-container-lowest overflow-y-auto">
            <!-- Top Controls (Language Switcher + Help) -->
            <div class="flex items-center justify-between pb-3">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-7 h-7 rounded bg-surface-container text-on-surface-variant">
                        <span class="material-symbols-outlined text-[16px]">language</span>
                    </span>
                    <div class="flex items-center gap-1.5 text-xs font-medium">
                        <a href="{{ route('locale.switch', 'vi') }}" class="px-2 py-1 rounded {{ app()->getLocale() === 'vi' ? 'bg-primary/10 text-primary font-bold' : 'text-outline hover:text-on-surface' }}">VI</a>
                        <span class="text-outline">/</span>
                        <a href="{{ route('locale.switch', 'en') }}" class="px-2 py-1 rounded {{ app()->getLocale() === 'en' ? 'bg-primary/10 text-primary font-bold' : 'text-outline hover:text-on-surface' }}">EN</a>
                        <span class="text-outline">/</span>
                        <a href="{{ route('locale.switch', 'ja') }}" class="px-2 py-1 rounded {{ app()->getLocale() === 'ja' ? 'bg-primary/10 text-primary font-bold' : 'text-outline hover:text-on-surface' }}">JA</a>
                    </div>
                </div>

                <a class="flex items-center gap-1.5 text-xs font-semibold text-secondary hover:text-primary transition-colors no-underline" href="{{ route('contact') }}">
                    <span class="material-symbols-outlined text-[16px]">contact_support</span>
                    <span>Liên hệ IT Support</span>
                </a>
            </div>

            <!-- Center Form Content Slot -->
            <div class="w-full max-w-md mx-auto my-auto py-2">
                @yield('content')
            </div>

            <!-- Bottom Copyright -->
            <div class="pt-2 border-t border-outline-variant/40 flex items-center justify-between text-xs text-outline">
                <span>© 2026 DrinkFlow Operations Platform.</span>
                <span class="font-mono text-[10px]">v2.4.0</span>
            </div>
        </main>
    </div>

    @stack('scripts')
</body>

</html>

<x-public.layout
    :title="__('terms.meta_title')"
    :description="__('terms.meta_description')"
    :ogTitle="__('terms.meta_title')"
    :ogDescription="__('terms.meta_description')"
    ogType="article"
    activeTab="terms"
    :version="$appVersion ?? 'v2.3.0'"
    :termsUrl="$termsUrl ?? url('/terms')"
    :versionsUrl="$versionsUrl ?? url('/versions')"
    :contactUrl="$contactUrl ?? route('contact')"
    :googleAuthUrl="$googleAuthUrl ?? route('auth.google')"
>
    <x-slot:head>
        <style>
            html {
                scroll-behavior: smooth;
            }
        </style>
        @if (file_exists(public_path('build/manifest.json')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </x-slot:head>

    <!-- MAIN DOCUMENTATION CONTAINER -->
    <main class="flex-1 w-full max-w-[1200px] mx-auto px-6 py-8">
        <!-- Breadcrumb & Header Section -->
        <header class="mb-8 border-b border-slate-200 pb-8">
            <!-- Breadcrumb Navigation -->
            <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-[#545c72] mb-3">
                <a class="hover:text-[#006948] transition-colors" href="{{ $landingUrl }}">{{ __('terms.breadcrumb_home') }}</a>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <span class="hover:text-[#006948] transition-colors">{{ __('terms.breadcrumb_legal') }}</span>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <span class="text-[#0F172A] font-semibold">{{ __('terms.breadcrumb_current') }}</span>
            </nav>

            <!-- Page Title -->
            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-[#0F172A] mb-3 tracking-tight">
                {{ __('terms.page_title') }}
            </h1>

            <!-- Metadata Pills Group -->
            <div class="flex flex-wrap items-center gap-2.5 mb-4">
                <span class="inline-flex items-center gap-1.5 bg-[#eff4ff] text-[#545c72] px-2.5 py-1 rounded-full text-xs font-medium border border-slate-200">
                    <span class="material-symbols-outlined text-[14px] text-[#006948]">verified</span>
                    <span>{{ __('terms.badge_version', ['version' => $version]) }}</span>
                </span>
                <span class="inline-flex items-center gap-1.5 bg-[#eff4ff] text-[#545c72] px-2.5 py-1 rounded-full text-xs font-medium border border-slate-200">
                    <span class="material-symbols-outlined text-[14px] text-[#006948]">calendar_today</span>
                    <span>{{ __('terms.badge_effective', ['date' => $effectiveDate]) }}</span>
                </span>
                <span class="inline-flex items-center gap-1.5 bg-[#ecfdf5] text-[#047857] px-2.5 py-1 rounded-full text-xs font-medium border border-[#6cf8bb]">
                    <span class="material-symbols-outlined text-[14px]" style="font-variation-settings: 'FILL' 1;">domain</span>
                    <span>{{ __('terms.badge_system') }}</span>
                </span>
            </div>

            <!-- Executive Notice Note -->
            <p class="text-sm sm:text-base text-[#475569] max-w-3xl leading-relaxed">
                {{ __('terms.notice_desc') }}
            </p>
        </header>

        <!-- 2-Column Documentation Body Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start relative">
            <!-- LEFT COLUMN: Sticky Table of Contents -->
            <aside class="hidden lg:block lg:col-span-4 sticky top-24">
                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                    <div class="flex items-center justify-between pb-3 mb-2 border-b border-slate-100">
                        <h2 class="text-base font-bold text-[#0F172A] flex items-center gap-2">
                            <span class="material-symbols-outlined text-[#006948] text-[20px]">format_list_bulleted</span>
                            <span>{{ __('terms.toc_title') }}</span>
                        </h2>
                        <span class="text-xs text-[#545c72] font-mono">{{ __('terms.toc_count') }}</span>
                    </div>

                    <nav class="space-y-1 text-sm font-medium" id="toc-nav">
                        <a class="group flex items-center justify-between px-3 py-2 rounded-lg transition-all text-[#006948] bg-[#eff4ff] font-semibold border-l-2 border-[#006948]" href="#sec-1">
                            <span class="truncate">{{ __('terms.sections.1') }}</span>
                            <span class="material-symbols-outlined text-[16px] group-hover:translate-x-0.5 transition-transform opacity-70">arrow_forward</span>
                        </a>
                        <a class="group flex items-center justify-between px-3 py-2 rounded-lg text-[#545c72] hover:text-[#0F172A] hover:bg-[#eff4ff] transition-colors" href="#sec-2">
                            <span class="truncate">{{ __('terms.sections.2') }}</span>
                            <span class="material-symbols-outlined text-[16px] opacity-40">chevron_right</span>
                        </a>
                        <a class="group flex items-center justify-between px-3 py-2 rounded-lg text-[#545c72] hover:text-[#0F172A] hover:bg-[#eff4ff] transition-colors" href="#sec-3">
                            <span class="truncate">{{ __('terms.sections.3') }}</span>
                            <span class="material-symbols-outlined text-[16px] opacity-40">chevron_right</span>
                        </a>
                        <a class="group flex items-center justify-between px-3 py-2 rounded-lg text-[#545c72] hover:text-[#0F172A] hover:bg-[#eff4ff] transition-colors" href="#sec-4">
                            <span class="truncate">{{ __('terms.sections.4') }}</span>
                            <span class="material-symbols-outlined text-[16px] opacity-40">chevron_right</span>
                        </a>
                        <a class="group flex items-center justify-between px-3 py-2 rounded-lg text-[#545c72] hover:text-[#0F172A] hover:bg-[#eff4ff] transition-colors" href="#sec-5">
                            <span class="truncate">{{ __('terms.sections.5') }}</span>
                            <span class="material-symbols-outlined text-[16px] opacity-40">chevron_right</span>
                        </a>
                        <a class="group flex items-center justify-between px-3 py-2 rounded-lg text-[#545c72] hover:text-[#0F172A] hover:bg-[#eff4ff] transition-colors" href="#sec-6">
                            <span class="truncate">{{ __('terms.sections.6') }}</span>
                            <span class="material-symbols-outlined text-[16px] opacity-40">chevron_right</span>
                        </a>
                        <a class="group flex items-center justify-between px-3 py-2 rounded-lg text-[#545c72] hover:text-[#0F172A] hover:bg-[#eff4ff] transition-colors" href="#sec-7">
                            <span class="truncate">{{ __('terms.sections.7') }}</span>
                            <span class="material-symbols-outlined text-[16px] opacity-40">chevron_right</span>
                        </a>
                        <a class="group flex items-center justify-between px-3 py-2 rounded-lg text-[#545c72] hover:text-[#0F172A] hover:bg-[#eff4ff] transition-colors" href="#sec-8">
                            <span class="truncate">{{ __('terms.sections.8') }}</span>
                            <span class="material-symbols-outlined text-[16px] opacity-40">chevron_right</span>
                        </a>
                    </nav>

                    <!-- Support & Auditing Box -->
                    <div class="mt-6 pt-4 border-t border-slate-100 bg-[#eff4ff]/60 -mx-4 -mb-4 p-4 rounded-b-xl">
                        <div class="flex items-start gap-2.5">
                            <span class="material-symbols-outlined text-[#006948] text-[18px]">verified_user</span>
                            <div class="space-y-0.5">
                                <div class="text-xs font-bold text-[#0F172A]">{{ __('terms.support_title') }}</div>
                                <p class="text-[11px] text-[#545c72] leading-relaxed">{{ __('terms.support_desc') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- RIGHT COLUMN: Main Documentation Content -->
            <div class="col-span-1 lg:col-span-8 space-y-8">
                <!-- SECTION 1: Phạm vi áp dụng & Mục đích -->
                <article class="scroll-mt-24 bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-2xs" id="sec-1">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-[#006948] text-white text-xs font-bold">1</span>
                        <h2 class="text-lg sm:text-xl font-bold text-[#0F172A]">{{ __('terms.section_titles.1') }}</h2>
                    </div>
                    <p class="text-sm sm:text-base text-[#334155] leading-relaxed mb-5">
                        {{ __('terms.content.sec1.desc') }}
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
                        <div class="p-4 bg-[#eff4ff] rounded-xl border border-slate-200/80">
                            <div class="flex items-center gap-1.5 text-[#006948] text-xs font-bold mb-1">
                                <span class="material-symbols-outlined text-[16px]">groups</span> {{ __('terms.content.sec1.card1_title') }}
                            </div>
                            <p class="text-xs text-[#545c72] leading-relaxed">{{ __('terms.content.sec1.card1_desc') }}</p>
                        </div>
                        <div class="p-4 bg-[#eff4ff] rounded-xl border border-slate-200/80">
                            <div class="flex items-center gap-1.5 text-[#006948] text-xs font-bold mb-1">
                                <span class="material-symbols-outlined text-[16px]">point_of_sale</span> {{ __('terms.content.sec1.card2_title') }}
                            </div>
                            <p class="text-xs text-[#545c72] leading-relaxed">{{ __('terms.content.sec1.card2_desc') }}</p>
                        </div>
                        <div class="p-4 bg-[#eff4ff] rounded-xl border border-slate-200/80">
                            <div class="flex items-center gap-1.5 text-[#006948] text-xs font-bold mb-1">
                                <span class="material-symbols-outlined text-[16px]">receipt_long</span> {{ __('terms.content.sec1.card3_title') }}
                            </div>
                            <p class="text-xs text-[#545c72] leading-relaxed">{{ __('terms.content.sec1.card3_desc') }}</p>
                        </div>
                    </div>
                </article>

                <!-- SECTION 2: Điều kiện sử dụng & Quyền truy cập -->
                <article class="scroll-mt-24 bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-2xs" id="sec-2">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-[#006948] text-white text-xs font-bold">2</span>
                        <h2 class="text-lg sm:text-xl font-bold text-[#0F172A]">{{ __('terms.section_titles.2') }}</h2>
                    </div>
                    <p class="text-sm sm:text-base text-[#334155] leading-relaxed mb-4">
                        {{ __('terms.content.sec2.desc') }}
                    </p>
                    <div class="p-3.5 bg-[#eff4ff] rounded-xl border border-[#bccac0]/60 flex items-center justify-between mb-4">
                        <code class="text-sm font-bold text-[#006948] font-mono">@company.com</code>
                        <span class="text-xs text-[#065F46] bg-[#ECFDF5] px-2.5 py-0.5 rounded font-medium">{{ __('terms.content.sec2.badge') }}</span>
                    </div>
                    <div class="flex items-start gap-2.5 text-xs sm:text-sm text-[#475569] bg-[#FEF2F2] border border-[#FEE2E2] p-3.5 rounded-xl">
                        <span class="material-symbols-outlined text-[#ba1a1a] text-[18px] shrink-0 mt-0.5">cancel</span>
                        <p class="leading-relaxed">{{ __('terms.content.sec2.warning') }}</p>
                    </div>
                </article>

                <!-- SECTION 3: Xác thực bảo mật Google OAuth -->
                <article class="scroll-mt-24 bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-2xs" id="sec-3">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-[#006948] text-white text-xs font-bold">3</span>
                        <h2 class="text-lg sm:text-xl font-bold text-[#0F172A]">{{ __('terms.section_titles.3') }}</h2>
                    </div>
                    <p class="text-sm sm:text-base text-[#334155] leading-relaxed mb-4">
                        {!! __('terms.content.sec3.desc') !!}
                    </p>
                    <!-- INFO CALLOUT BOX -->
                    <div class="p-4 bg-[#eff4ff] rounded-xl border-l-4 border-[#006948] shadow-2xs">
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-[#006948] text-[22px] shrink-0 mt-0.5">info</span>
                            <div>
                                <h3 class="text-sm font-bold text-[#006948] mb-1">{{ __('terms.content.sec3.callout_title') }}</h3>
                                <p class="text-xs sm:text-sm text-[#334155] leading-relaxed">
                                    {{ __('terms.content.sec3.callout_desc') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </article>

                <!-- SECTION 4: Phân định Global User & Room User -->
                <article class="scroll-mt-24 bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-2xs" id="sec-4">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-[#006948] text-white text-xs font-bold">4</span>
                        <h2 class="text-lg sm:text-xl font-bold text-[#0F172A]">{{ __('terms.section_titles.4') }}</h2>
                    </div>
                    <p class="text-sm sm:text-base text-[#334155] leading-relaxed mb-4">
                        {{ __('terms.content.sec4.desc') }}
                    </p>
                    <!-- Structured Permission Table -->
                    <div class="overflow-x-auto border border-slate-200 rounded-xl">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-[#eff4ff] border-b border-slate-200 text-xs text-[#545c72] tracking-wider uppercase font-semibold">
                                    <th class="py-3 px-4">{{ __('terms.content.sec4.th_role') }}</th>
                                    <th class="py-3 px-4">{{ __('terms.content.sec4.th_permissions') }}</th>
                                    <th class="py-3 px-4">{{ __('terms.content.sec4.th_obligations') }}</th>
                                    <th class="py-3 px-4 text-center">{{ __('terms.content.sec4.th_status') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs sm:text-sm">
                                <tr class="hover:bg-[#f8f9ff] transition-colors">
                                    <td class="py-3.5 px-4 font-semibold text-[#0F172A] whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <span class="material-symbols-outlined text-[#006948] text-[18px]">star</span>
                                            <span>{{ __('terms.content.sec4.role_host') }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 text-[#334155]">{{ __('terms.content.sec4.host_perm') }}</td>
                                    <td class="py-3.5 px-4 text-[#545c72]">{{ __('terms.content.sec4.host_obli') }}</td>
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                        <span class="inline-block px-2.5 py-0.5 rounded-full text-xs bg-[#ECFDF5] text-[#065F46] font-semibold">{{ __('terms.content.sec4.status_full') }}</span>
                                    </td>
                                </tr>
                                <tr class="hover:bg-[#f8f9ff] transition-colors">
                                    <td class="py-3.5 px-4 font-semibold text-[#0F172A] whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <span class="material-symbols-outlined text-[#545c72] text-[18px]">person</span>
                                            <span>{{ __('terms.content.sec4.role_member') }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 text-[#334155]">{{ __('terms.content.sec4.member_perm') }}</td>
                                    <td class="py-3.5 px-4 text-[#545c72]">{{ __('terms.content.sec4.member_obli') }}</td>
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                        <span class="inline-block px-2.5 py-0.5 rounded-full text-xs bg-[#eff4ff] text-[#545c72] font-semibold">{{ __('terms.content.sec4.status_standard') }}</span>
                                    </td>
                                </tr>
                                <tr class="hover:bg-[#f8f9ff] transition-colors">
                                    <td class="py-3.5 px-4 font-semibold text-[#0F172A] whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <span class="material-symbols-outlined text-[#006c49] text-[18px]">volunteer_activism</span>
                                            <span>{{ __('terms.content.sec4.role_sponsor') }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 text-[#334155]">{{ __('terms.content.sec4.sponsor_perm') }}</td>
                                    <td class="py-3.5 px-4 text-[#545c72]">{{ __('terms.content.sec4.sponsor_obli') }}</td>
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                        <span class="inline-block px-2.5 py-0.5 rounded-full text-xs bg-[#d3e4fe] text-[#0b1c30] font-semibold">{{ __('terms.content.sec4.status_subsidy') }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </article>

                <!-- SECTION 5: Quy định Order & Hủy đơn hàng -->
                <article class="scroll-mt-24 bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-2xs" id="sec-5">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-[#006948] text-white text-xs font-bold">5</span>
                        <h2 class="text-lg sm:text-xl font-bold text-[#0F172A]">{{ __('terms.section_titles.5') }}</h2>
                    </div>
                    <div class="space-y-4 text-sm sm:text-base text-[#334155] leading-relaxed">
                        <div class="flex items-start gap-3 p-3.5 bg-[#f8f9ff] rounded-xl border border-slate-200">
                            <span class="material-symbols-outlined text-[#006948] text-[20px] shrink-0 mt-0.5">timer</span>
                            <div>
                                <strong class="font-semibold text-[#0F172A]">{{ __('terms.content.sec5.lock_title') }}</strong>
                                <p class="text-xs sm:text-sm text-[#545c72] mt-0.5">{{ __('terms.content.sec5.lock_desc') }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 p-3.5 bg-[#FEF2F2] rounded-xl border border-[#FEE2E2]">
                            <span class="material-symbols-outlined text-[#ba1a1a] text-[20px] shrink-0 mt-0.5">block</span>
                            <div>
                                <strong class="font-semibold text-[#0F172A]">{{ __('terms.content.sec5.cancel_title') }}</strong>
                                <p class="text-xs sm:text-sm text-[#475569] mt-0.5">{{ __('terms.content.sec5.cancel_desc') }}</p>
                            </div>
                        </div>
                    </div>
                </article>

                <!-- SECTION 6: Biểu phí, Tách Bill & Thanh toán VietQR -->
                <article class="scroll-mt-24 bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-2xs" id="sec-6">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-[#006948] text-white text-xs font-bold">6</span>
                        <h2 class="text-lg sm:text-xl font-bold text-[#0F172A]">{{ __('terms.section_titles.6') }}</h2>
                    </div>
                    <p class="text-sm sm:text-base text-[#334155] leading-relaxed mb-4">
                        {{ __('terms.content.sec6.desc') }}
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-2">
                        <div class="p-4 bg-[#eff4ff] rounded-xl border border-slate-200">
                            <h3 class="text-sm font-semibold text-[#0F172A] mb-1 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[#006948] text-[18px]">calculate</span>
                                <span>{{ __('terms.content.sec6.fee_title') }}</span>
                            </h3>
                            <p class="text-xs sm:text-sm text-[#545c72] leading-relaxed">
                                {{ __('terms.content.sec6.fee_desc') }}
                            </p>
                        </div>
                        <div class="p-4 bg-[#eff4ff] rounded-xl border border-slate-200">
                            <h3 class="text-sm font-semibold text-[#0F172A] mb-1 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[#006948] text-[18px]">qr_code_2</span>
                                <span>{{ __('terms.content.sec6.qr_title') }}</span>
                            </h3>
                            <p class="text-xs sm:text-sm text-[#545c72] leading-relaxed">
                                {{ __('terms.content.sec6.qr_desc_prefix') }} <code class="font-mono text-[#0F172A] font-semibold bg-white px-1.5 py-0.5 rounded border border-slate-200 text-xs">[DF_ROOMID_USERID]</code>. {{ __('terms.content.sec6.qr_desc_suffix') }}
                            </p>
                        </div>
                    </div>
                </article>

                <!-- SECTION 7: Quản lý Thiết bị tin cậy & Khóa tài khoản -->
                <article class="scroll-mt-24 bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-2xs" id="sec-7">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-[#006948] text-white text-xs font-bold">7</span>
                        <h2 class="text-lg sm:text-xl font-bold text-[#0F172A]">{{ __('terms.section_titles.7') }}</h2>
                    </div>
                    <p class="text-sm sm:text-base text-[#334155] leading-relaxed mb-4">
                        {{ __('terms.content.sec7.desc') }}
                    </p>
                    <ul class="space-y-2.5 text-xs sm:text-sm text-[#334155] list-disc pl-5 marker:text-[#006948]">
                        <li>{{ __('terms.content.sec7.li1') }}</li>
                        <li>{{ __('terms.content.sec7.li2') }}</li>
                        <li>{{ __('terms.content.sec7.li3') }}</li>
                    </ul>
                </article>

                <!-- SECTION 8: Trách nhiệm của Host & Người tham gia -->
                <article class="scroll-mt-24 bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-2xs" id="sec-8">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-[#006948] text-white text-xs font-bold">8</span>
                        <h2 class="text-lg sm:text-xl font-bold text-[#0F172A]">{{ __('terms.section_titles.8') }}</h2>
                    </div>
                    <p class="text-sm sm:text-base text-[#334155] leading-relaxed mb-4">
                        {{ __('terms.content.sec8.desc') }}
                    </p>
                    <div class="border border-slate-200 rounded-xl p-4 bg-[#f8f9ff] space-y-3">
                        <div class="flex items-start gap-2.5">
                            <span class="material-symbols-outlined text-[#545c72] text-[18px] shrink-0 mt-0.5">warning</span>
                            <p class="text-xs sm:text-sm text-[#545c72] leading-relaxed">
                                <strong class="text-[#0F172A]">{{ __('terms.content.sec8.debt_title') }}</strong> {{ __('terms.content.sec8.debt_desc') }}
                            </p>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <span class="material-symbols-outlined text-[#ba1a1a] text-[18px] shrink-0 mt-0.5">gavel</span>
                            <p class="text-xs sm:text-sm text-[#545c72] leading-relaxed">
                                <strong class="text-[#0F172A]">{{ __('terms.content.sec8.spam_title') }}</strong> {{ __('terms.content.sec8.spam_desc') }}
                            </p>
                        </div>
                    </div>
                    <!-- Acceptance Confirmation Box -->
                    <div class="mt-6 pt-6 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[#006948] text-[20px]">assignment_turned_in</span>
                            <span class="text-xs sm:text-sm text-[#545c72]">{{ __('terms.approved_by') }}</span>
                        </div>
                        <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-[#eff4ff] hover:bg-[#dce9ff] text-[#0F172A] text-xs sm:text-sm font-semibold border border-slate-200 transition-colors cursor-pointer">
                            <span class="material-symbols-outlined text-[18px]">download</span>
                            <span>{{ __('terms.print_pdf') }}</span>
                        </button>
                    </div>
                </article>
            </div>
        </div>
    </main>

    <x-slot:scripts>
        <!-- Micro-interaction JS for Active Table of Contents Link Highlighting -->
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const sections = document.querySelectorAll('article[id]');
                const navLinks = document.querySelectorAll('#toc-nav a');

                function changeActiveToc() {
                    let index = sections.length;

                    while (--index && window.scrollY + 140 < sections[index].offsetTop) {}

                    navLinks.forEach((link) => {
                        link.classList.remove('text-[#006948]', 'bg-[#eff4ff]', 'font-semibold', 'border-l-2', 'border-[#006948]');
                        link.classList.add('text-[#545c72]', 'hover:text-[#0F172A]');
                        const arrow = link.querySelector('.material-symbols-outlined');
                        if (arrow) {
                            arrow.textContent = 'chevron_right';
                            arrow.classList.add('opacity-40');
                            arrow.classList.remove('opacity-70');
                        }
                    });

                    if (navLinks[index]) {
                        navLinks[index].classList.remove('text-[#545c72]', 'hover:text-[#0F172A]');
                        navLinks[index].classList.add('text-[#006948]', 'bg-[#eff4ff]', 'font-semibold', 'border-l-2', 'border-[#006948]');
                        const activeArrow = navLinks[index].querySelector('.material-symbols-outlined');
                        if (activeArrow) {
                            activeArrow.textContent = 'arrow_forward';
                            activeArrow.classList.remove('opacity-40');
                            activeArrow.classList.add('opacity-70');
                        }
                    }
                }

                changeActiveToc();
                window.addEventListener('scroll', changeActiveToc, { passive: true });
            });
        </script>
    </x-slot:scripts>
</x-public.layout>

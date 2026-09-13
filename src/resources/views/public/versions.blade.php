<x-public.layout
    :title="__('versions.meta_title', ['version' => $currentVersion->version])"
    :description="$currentVersion->summary ?? 'Lịch sử các bản cập nhật, tính năng mới và cải tiến của DrinkFlow.'"
    :ogTitle="__('versions.meta_title', ['version' => $currentVersion->version])"
    :ogDescription="$currentVersion->title"
    ogType="article"
    activeTab="versions"
    :version="$appVersion ?? 'v2.3.0'"
    :termsUrl="$termsUrl ?? url('/terms')"
    :versionsUrl="$versionsUrl ?? url('/versions')"
    :contactUrl="$contactUrl ?? route('contact')"
    :googleAuthUrl="$googleAuthUrl ?? route('auth.google')"
>
    <!-- MAIN CONTAINER -->
    <main class="flex-1 w-full max-w-[1200px] mx-auto px-4 sm:px-6 py-6 sm:py-8">
        <!-- Breadcrumb & Page Meta -->
        <div class="flex flex-col md:flex-row md:items-center justify-between pb-5 border-b border-slate-200 gap-3">
            <div class="flex items-center gap-2 text-xs text-[#545c72] flex-wrap">
                <a class="hover:text-[#006948] transition-colors" href="{{ $landingUrl }}">{{ __('versions.breadcrumb_root') }}</a>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <a class="hover:text-[#006948] transition-colors" href="{{ $versionsUrl }}">{{ __('versions.breadcrumb_history') }}</a>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <span class="text-[#0F172A] font-semibold">{{ __('versions.breadcrumb_release', ['version' => $currentVersion->version]) }}</span>
            </div>
            <div class="flex items-center gap-2 text-xs">
                <span class="text-[#545c72]">{{ __('versions.sync_status') }}:</span>
                <div class="flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-white border border-slate-200 text-[#006948] font-medium shadow-2xs">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#059669] animate-pulse"></span>
                    <span>{{ __('versions.system_online', ['version' => $appVersion]) }}</span>
                </div>
            </div>
        </div>

        <!-- MOBILE HORIZONTAL VERSION SELECTOR (Mobile only) -->
        <div class="lg:hidden mt-4 bg-white p-3 rounded-xl border border-slate-200 shadow-2xs">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-[#0F172A] flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px] text-[#006948]">history</span>
                    <span>{{ __('versions.sidebar_title') }}</span>
                </span>
                <span class="text-[11px] font-semibold text-[#006948] bg-[#eff4ff] px-2 py-0.5 rounded">{{ $versions->count() }} {{ __('versions.versions_count') }}</span>
            </div>
            <div class="flex items-center gap-2 overflow-x-auto no-scrollbar py-1" style="scrollbar-width: none; -ms-overflow-style: none;">
                @foreach ($versions as $v)
                    @php
                        $isSelected = ($v->version === $currentVersion->version);
                    @endphp
                    <a href="{{ $versionsUrl }}/{{ $v->version }}"
                       class="flex-shrink-0 px-3 py-1.5 rounded-lg text-xs font-mono font-bold transition-all {{ $isSelected ? 'bg-[#006948] text-white shadow-xs' : 'bg-slate-50 text-slate-700 hover:bg-slate-100 hover:text-[#006948] border border-slate-200/80' }}">
                        {{ $v->version }}
                    </a>
                @endforeach
            </div>
        </div>

        <!-- 2-Panel Bento Grid -->
        <div class="mt-6 grid grid-cols-12 gap-6 lg:gap-8 items-start">
            <!-- LEFT SIDEBAR PANEL: Version List (Desktop Only) -->
            <aside class="hidden lg:flex col-span-12 lg:col-span-4 bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden flex-col">
                <div class="p-4 border-b border-slate-100 bg-white">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-base font-bold text-[#0F172A] flex items-center gap-2">
                            <span class="material-symbols-outlined text-[#006948] text-[20px]">history</span>
                            <span>{{ __('versions.sidebar_title') }}</span>
                        </h2>
                        <span class="text-xs bg-[#eff4ff] text-[#006948] font-mono px-2 py-0.5 rounded font-semibold">{{ $versions->count() }} {{ __('versions.versions_count') }}</span>
                    </div>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-2.5 text-[18px] text-[#545c72]">search</span>
                        <input id="version-search-input"
                                class="w-full h-9 pl-9 pr-3 rounded-lg border border-slate-200 text-xs text-[#0F172A] placeholder:text-[#545c72] focus:outline-none focus:border-[#006948] bg-[#f8f9ff]"
                                placeholder="{{ __('versions.sidebar_search_placeholder') }}"
                                type="text">
                    </div>
                </div>

                <!-- Version Items -->
                <div id="version-list-items" class="divide-y divide-slate-100 max-h-[260px] lg:max-h-[600px] overflow-y-auto">
                    @foreach ($versions as $v)
                        @php
                            $isSelected = ($v->version === $currentVersion->version);
                            $isLatest = ($latestVersion && $v->version === $latestVersion->version);
                            $badgeType = $v->badge ?? ($isLatest ? 'Latest' : 'Feature');
                        @endphp
                        <a href="{{ $versionsUrl }}/{{ $v->version }}"
                           class="version-item block p-4 transition-colors {{ $isSelected ? 'bg-[#eff4ff] border-l-4 border-[#006948]' : 'hover:bg-slate-50' }}">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-sm font-bold {{ $isSelected ? 'text-[#006948]' : 'text-[#0F172A]' }} font-mono">{{ $v->version }}</span>
                                <span class="text-xs text-[#545c72]">{{ $v->release_date }}</span>
                            </div>
                            <div class="flex items-center gap-1.5 mb-1.5 flex-wrap">
                                @if ($isLatest)
                                    <span class="text-[11px] px-2 py-0.2 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 font-semibold">Latest</span>
                                @elseif ($badgeType === 'Maintenance')
                                    <span class="text-[11px] px-2 py-0.2 rounded-full bg-slate-100 text-slate-700 border border-slate-200 font-medium">Maintenance</span>
                                @elseif ($badgeType === 'Major')
                                    <span class="text-[11px] px-2 py-0.2 rounded-full bg-purple-50 text-purple-700 border border-purple-200 font-semibold">Major</span>
                                @else
                                    <span class="text-[11px] px-2 py-0.2 rounded-full bg-blue-50 text-blue-700 border border-blue-200 font-medium">Feature</span>
                                @endif

                                @if (!empty($v->important))
                                    <span class="text-[11px] px-2 py-0.2 rounded-full bg-amber-50 text-amber-800 border border-amber-200 font-semibold">{{ __('versions.badge_important') }}</span>
                                @endif
                            </div>
                            <p class="text-xs text-[#545c72] line-clamp-2 leading-relaxed">
                                {{ $v->title }}
                            </p>
                            @if ($isSelected)
                                <div class="mt-2 flex items-center gap-1 text-[#006948] text-xs font-semibold">
                                    <span>{{ __('versions.viewing_detail') }}</span>
                                    <span class="material-symbols-outlined text-[14px]">arrow_right_alt</span>
                                </div>
                            @endif
                        </a>
                    @endforeach
                </div>

                <!-- Sidebar Footer Action -->
                <div class="p-3 bg-white border-t border-slate-100 text-center">
                    <a class="text-xs text-[#006948] hover:underline flex items-center justify-center gap-1 font-medium" href="{{ $versionsUrl }}">
                        <span class="material-symbols-outlined text-[15px]">history</span>
                        <span>{{ __('versions.view_latest', ['version' => $latestVersion->version]) }}</span>
                    </a>
                </div>
            </aside>

            <!-- RIGHT MAIN PANEL: Version Detail -->
            <section class="col-span-12 lg:col-span-8 bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-2xs space-y-6">
                <!-- Header area -->
                <div class="pb-6 border-b border-slate-100">
                    <!-- Badges Row -->
                    <div class="flex flex-wrap items-center gap-2 mb-3">
                        @if ($latestVersion && $currentVersion->version === $latestVersion->version)
                            <span class="text-xs px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 font-semibold flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">verified</span>
                                {{ __('versions.latest_release', ['version' => $currentVersion->version]) }}
                            </span>
                        @endif
                        <span class="text-xs px-2.5 py-1 rounded-full bg-[#eff4ff] text-[#0b1c30] border border-slate-200 font-medium">
                            {{ __('versions.official_release') }}
                        </span>
                        <span class="text-xs px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 border border-slate-200 font-medium flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">shield</span>
                            {{ __('versions.security_verified') }}
                        </span>
                        @if (!empty($currentVersion->important))
                            <span class="text-xs px-2.5 py-1 rounded-full bg-amber-50 text-amber-800 border border-amber-200 font-semibold">
                                {{ __('versions.important_update') }}
                            </span>
                        @endif
                    </div>

                    <!-- Main Title -->
                    <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-[#0F172A] tracking-tight">
                        DrinkFlow {{ $currentVersion->version }} - {{ $currentVersion->title }}
                    </h1>

                    <!-- Metadata Bar -->
                    <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-[#545c72] bg-[#eff4ff] p-3 rounded-xl border border-slate-200">
                        <div class="flex items-center gap-1">
                            <span class="material-symbols-outlined text-[15px]">calendar_today</span>
                            <span>{{ __('versions.meta_released_date') }}: <strong class="text-[#0F172A]">{{ $currentVersion->release_date }}</strong></span>
                        </div>
                        <span>•</span>
                        <div class="flex items-center gap-1">
                            <span class="material-symbols-outlined text-[15px]">group</span>
                            <span>{{ __('versions.meta_author') }}: <strong class="text-[#0F172A]">{{ $currentVersion->author ?? 'DrinkFlow Core Team' }}</strong></span>
                        </div>
                    </div>

                    <!-- Executive Summary -->
                    @if (!empty($currentVersion->summary))
                        <div class="mt-4 p-4 rounded-xl bg-[#f8f9ff] border-l-4 border-[#006948] text-xs sm:text-sm text-[#334155] leading-relaxed">
                            {{ $currentVersion->summary }}
                        </div>
                    @endif
                </div>

                <!-- Categorized Change Log Sections -->
                <div class="space-y-6">
                    <!-- Section 1: ✨ New Features -->
                    @if (!empty($currentVersion->features) && count($currentVersion->features) > 0)
                        <div class="border border-slate-200 rounded-xl p-5 bg-white shadow-2xs">
                            <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center border border-emerald-200">
                                        <span class="material-symbols-outlined text-[18px]">auto_awesome</span>
                                    </span>
                                    <h3 class="text-base font-bold text-[#0F172A]">
                                        {{ __('versions.tab_features') }}
                                    </h3>
                                </div>
                                <span class="text-xs px-2 py-0.5 rounded bg-emerald-50 text-emerald-800 font-semibold">{{ count($currentVersion->features) }} {{ __('versions.items_count') }}</span>
                            </div>
                            <ul class="space-y-3.5">
                                @foreach ($currentVersion->features as $item)
                                    <li class="flex items-start gap-3">
                                        <span class="w-2 h-2 rounded-full bg-[#006948] mt-2 flex-shrink-0"></span>
                                        <div>
                                            <h4 class="text-xs sm:text-sm font-semibold text-[#0F172A]">
                                                {{ $item->title }}
                                            </h4>
                                            <p class="text-xs text-[#545c72] mt-0.5 leading-relaxed">
                                                {{ $item->description }}
                                            </p>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Section 2: 🔧 Improvements -->
                    @if (!empty($currentVersion->improvements) && count($currentVersion->improvements) > 0)
                        <div class="border border-slate-200 rounded-xl p-5 bg-white shadow-2xs">
                            <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-7 h-7 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center border border-blue-200">
                                        <span class="material-symbols-outlined text-[18px]">build</span>
                                    </span>
                                    <h3 class="text-base font-bold text-[#0F172A]">
                                        {{ __('versions.tab_improvements') }}
                                    </h3>
                                </div>
                                <span class="text-xs px-2 py-0.5 rounded bg-blue-50 text-blue-800 font-semibold">{{ count($currentVersion->improvements) }} {{ __('versions.items_count') }}</span>
                            </div>
                            <ul class="space-y-3.5">
                                @foreach ($currentVersion->improvements as $item)
                                    <li class="flex items-start gap-3">
                                        <span class="w-2 h-2 rounded-full bg-blue-600 mt-2 flex-shrink-0"></span>
                                        <div>
                                            <h4 class="text-xs sm:text-sm font-semibold text-[#0F172A]">
                                                {{ $item->title }}
                                            </h4>
                                            <p class="text-xs text-[#545c72] mt-0.5 leading-relaxed">
                                                {{ $item->description }}
                                            </p>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Section 3: 🐛 Bug Fixes -->
                    @if (!empty($currentVersion->bugfixes) && count($currentVersion->bugfixes) > 0)
                        <div class="border border-slate-200 rounded-xl p-5 bg-white shadow-2xs">
                            <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-7 h-7 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center border border-amber-200">
                                        <span class="material-symbols-outlined text-[18px]">bug_report</span>
                                    </span>
                                    <h3 class="text-base font-bold text-[#0F172A]">
                                        {{ __('versions.tab_bugfixes') }}
                                    </h3>
                                </div>
                                <span class="text-xs px-2 py-0.5 rounded bg-amber-50 text-amber-800 font-semibold">{{ count($currentVersion->bugfixes) }} {{ __('versions.items_count') }}</span>
                            </div>
                            <ul class="space-y-3.5">
                                @foreach ($currentVersion->bugfixes as $item)
                                    <li class="flex items-start gap-3">
                                        <span class="w-2 h-2 rounded-full bg-amber-600 mt-2 flex-shrink-0"></span>
                                        <div>
                                            <h4 class="text-xs sm:text-sm font-semibold text-[#0F172A]">
                                                {{ $item->title }}
                                            </h4>
                                            <p class="text-xs text-[#545c72] mt-0.5 leading-relaxed">
                                                {{ $item->description }}
                                            </p>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Section 4: 🔐 Security & Governance -->
                    @if (!empty($currentVersion->security) && count($currentVersion->security) > 0)
                        <div class="border border-slate-200 rounded-xl p-5 bg-white shadow-2xs">
                            <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-7 h-7 rounded-lg bg-purple-50 text-purple-700 flex items-center justify-center border border-purple-200">
                                        <span class="material-symbols-outlined text-[18px]">lock</span>
                                    </span>
                                    <h3 class="text-base font-bold text-[#0F172A]">
                                        {{ __('versions.tab_security') }}
                                    </h3>
                                </div>
                                <span class="text-xs px-2 py-0.5 rounded bg-purple-50 text-purple-800 font-semibold">{{ count($currentVersion->security) }} {{ __('versions.items_count') }}</span>
                            </div>
                            <ul class="space-y-3.5">
                                @foreach ($currentVersion->security as $item)
                                    <li class="flex items-start gap-3">
                                        <span class="w-2 h-2 rounded-full bg-purple-600 mt-2 flex-shrink-0"></span>
                                        <div>
                                            <h4 class="text-xs sm:text-sm font-semibold text-[#0F172A]">
                                                {{ $item->title }}
                                            </h4>
                                            <p class="text-xs text-[#545c72] mt-0.5 leading-relaxed">
                                                {{ $item->description }}
                                            </p>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Additional / Custom Changelog Text if exists -->
                    @if (!empty($currentVersion->changelog))
                        <div class="border border-slate-200 rounded-xl p-5 bg-white shadow-2xs">
                            <h3 class="text-base font-bold text-[#0F172A] mb-3 flex items-center gap-2">
                                <span class="material-symbols-outlined text-[#006948] text-[20px]">notes</span>
                                <span>{{ __('versions.changelog_notes') }}</span>
                            </h3>
                            <div class="prose prose-sm max-w-none text-xs sm:text-sm text-[#334155] leading-relaxed whitespace-pre-line bg-[#f8f9ff] p-4 rounded-xl border border-slate-200">
                                {{ $currentVersion->changelog }}
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Footer Version Navigation Card -->
                <div class="mt-8 pt-6 border-t border-slate-200 grid grid-cols-1 md:grid-cols-2 gap-4">
                    @if ($prevVersion)
                        <a href="{{ $versionsUrl }}/{{ $prevVersion->version }}"
                           class="p-4 rounded-xl border border-slate-200 hover:border-[#006948] hover:bg-[#eff4ff] transition-all group flex items-center justify-between">
                            <div class="flex flex-col">
                                <span class="text-xs text-[#545c72] group-hover:text-[#006948] font-medium">← {{ __('versions.nav_prev') }}</span>
                                <span class="text-sm font-bold text-[#0F172A] font-mono">{{ $prevVersion->version }} ({{ $prevVersion->release_date }})</span>
                            </div>
                            <span class="material-symbols-outlined text-[#545c72] group-hover:text-[#006948] text-[20px]">arrow_back</span>
                        </a>
                    @else
                        <div class="p-4 rounded-xl border border-dashed border-slate-200 bg-[#f8f9ff] flex items-center justify-between opacity-75">
                            <div class="flex flex-col">
                                <span class="text-xs text-[#545c72]">{{ __('versions.nav_prev') }}</span>
                                <span class="text-xs text-[#545c72] font-medium">{{ __('versions.no_prev_version') }}</span>
                            </div>
                            <span class="material-symbols-outlined text-slate-400 text-[20px]">first_page</span>
                        </div>
                    @endif

                    @if ($nextVersion)
                        <a href="{{ $versionsUrl }}/{{ $nextVersion->version }}"
                           class="p-4 rounded-xl border border-slate-200 hover:border-[#006948] hover:bg-[#eff4ff] transition-all group flex items-center justify-between text-right">
                            <div class="flex flex-col ml-auto">
                                <span class="text-xs text-[#545c72] group-hover:text-[#006948] font-medium">{{ __('versions.nav_next') }} →</span>
                                <span class="text-sm font-bold text-[#0F172A] font-mono">{{ $nextVersion->version }} ({{ $nextVersion->release_date }})</span>
                            </div>
                            <span class="material-symbols-outlined text-[#545c72] group-hover:text-[#006948] text-[20px] ml-3">arrow_forward</span>
                        </a>
                    @else
                        <div class="p-4 rounded-xl border border-dashed border-slate-200 bg-[#f8f9ff] flex items-center justify-between opacity-75">
                            <div class="flex flex-col">
                                <span class="text-xs text-[#545c72]">{{ __('versions.nav_next') }}</span>
                                <span class="text-xs text-[#047857] font-semibold">{{ __('versions.no_next_version') }}</span>
                            </div>
                            <span class="material-symbols-outlined text-[#059669] text-[20px]">check_circle</span>
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </main>
</x-public.layout>

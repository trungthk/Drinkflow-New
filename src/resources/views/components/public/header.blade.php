@props([
    'activeTab' => 'about',
    'version' => null,
    'termsUrl' => null,
    'versionsUrl' => null,
    'contactUrl' => null,
    'googleAuthUrl' => null,
])

@php
    $currentLocale = app()->getLocale();
    $activeLocaleMeta = $locales[$currentLocale] ?? $locales['vi'];
@endphp

<!-- TOP NAVBAR (Shared Component) -->
<header class="bg-surface-container-lowest border-b border-outline-variant/50 sticky top-0 z-40 bg-white/95 backdrop-blur-md">
    <div class="w-full max-w-[1200px] mx-auto px-6 h-16 flex items-center justify-between">
        <!-- Brand Logo -->
        <div class="flex items-center gap-8">
            <a class="text-headline-md font-headline-md font-semibold text-on-surface flex items-center gap-2 tracking-tight text-[#0F172A] hover:opacity-90 transition-opacity" href="{{ route('landing') }}">
                <span class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-sm shrink-0"
                      style="background: linear-gradient(135deg, #006948 0%, #047857 100%); background-color: #006948; border: 1px solid #005137;">
                    <svg class="w-4.5 h-4.5 text-white fill-current" viewBox="0 0 24 24" aria-hidden="true" style="width: 18px; height: 18px; fill: #ffffff; color: #ffffff;">
                        <path d="M20 3H4v10c0 2.21 1.79 4 4 4h6c2.21 0 4-1.79 4-4v-3h2c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 5h-2V5h2v3zM4 19h16v2H4z"/>
                    </svg>
                </span>
                <span class="font-bold text-lg text-[#0F172A]">DrinkFlow</span>
            </a>
            <!-- Desktop Navigation Links -->
            <nav class="hidden md:flex items-center gap-6 pt-1" aria-label="Public Navigation">
                <a class="{{ $activeTab === 'about' ? 'text-[#006948] border-b-2 border-[#006948] font-semibold' : 'text-[#545c72] hover:text-[#0b1c30]' }} pb-1 transition-colors text-sm font-medium" href="{{ route('landing') }}">
                    {{ __('public.header.about') }}
                </a>
                <a class="{{ $activeTab === 'terms' ? 'text-[#006948] border-b-2 border-[#006948] font-semibold' : 'text-[#545c72] hover:text-[#0b1c30]' }} pb-1 transition-colors text-sm font-medium" href="{{ $termsUrl }}">
                    {{ __('public.header.terms') }}
                </a>
                <a class="{{ $activeTab === 'versions' ? 'text-[#006948] border-b-2 border-[#006948] font-semibold' : 'text-[#545c72] hover:text-[#0b1c30]' }} pb-1 transition-colors text-sm font-medium" href="{{ $versionsUrl }}">
                    {{ __('public.header.versions') }}
                </a>
                <a class="{{ $activeTab === 'contact' ? 'text-[#006948] border-b-2 border-[#006948] font-semibold' : 'text-[#545c72] hover:text-[#0b1c30]' }} pb-1 transition-colors text-sm font-medium" href="{{ $contactUrl }}">
                    {{ __('public.header.contact') }}
                </a>
            </nav>
        </div>

        <!-- Trailing Actions Cluster -->
        <div class="flex items-center gap-2 sm:gap-3">
            <!-- Language Selector Dropdown -->
            <div class="relative" id="public-lang-selector">
                <button type="button"
                        id="public-lang-btn"
                        aria-haspopup="true"
                        aria-expanded="false"
                        class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-medium text-[#545c72] hover:bg-[#eff4ff] transition-colors duration-150 border border-slate-200/80 hover:border-[#bccac0] cursor-pointer">
                    <span>{{ $activeLocaleMeta['flag'] }}</span>
                    <span class="font-semibold text-[#0b1c30]">{{ $activeLocaleMeta['code'] }}</span>
                    <span class="material-symbols-outlined text-[16px] text-[#545c72]">arrow_drop_down</span>
                </button>

                <!-- Dropdown Menu -->
                <div id="public-lang-menu"
                     class="hidden absolute right-0 mt-1.5 w-36 bg-white rounded-xl shadow-lg border border-slate-200 py-1.5 z-50 animate-fadeIn">
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

            <div class="hidden sm:block h-4 w-px bg-slate-200 mx-0.5"></div>

            <!-- Primary CTA Button (Desktop & Tablet) -->
            <div class="hidden sm:flex items-center">
                @auth('web')
                    <a href="{{ route('user.me.dashboard') }}" class="bg-[#059669] hover:bg-[#047857] text-white text-xs sm:text-sm font-medium px-3.5 sm:px-4 py-2 rounded-[6px] transition-all active:scale-[0.98] duration-100 flex items-center gap-1.5 shadow-sm cursor-pointer whitespace-nowrap">
                        <span>{{ __('public.header.get_started') }}</span>
                        <span class="material-symbols-outlined text-[16px] sm:text-[18px]">arrow_forward</span>
                    </a>
                @else
                    <button type="button" class="btn-google-sso bg-[#059669] hover:bg-[#047857] text-white text-xs sm:text-sm font-medium px-3.5 sm:px-4 py-2 rounded-[6px] transition-all active:scale-[0.98] duration-100 flex items-center gap-1.5 shadow-sm cursor-pointer whitespace-nowrap">
                        <span>{{ __('public.header.get_started') }}</span>
                        <span class="material-symbols-outlined text-[16px] sm:text-[18px]">arrow_forward</span>
                    </button>
                @endauth
            </div>

            <!-- Mobile Hamburger Menu Button (Mobile Only) -->
            <button type="button"
                    id="mobile-nav-toggle"
                    aria-label="Toggle Navigation Menu"
                    class="md:hidden p-2 text-slate-600 hover:text-[#006948] hover:bg-slate-100 rounded-lg transition-colors cursor-pointer flex items-center justify-center">
                <span id="mobile-nav-icon" class="material-symbols-outlined text-[24px]">menu</span>
            </button>
        </div>
    </div>

    <!-- Mobile Navigation Drawer -->
    <div id="mobile-nav-menu" class="hidden md:hidden border-t border-slate-200 bg-white/98 backdrop-blur-md px-6 py-4 shadow-lg animate-fadeIn">
        <nav class="flex flex-col space-y-2" aria-label="Mobile Navigation">
            <a class="flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ $activeTab === 'about' ? 'text-[#006948] bg-emerald-50/80 font-semibold' : 'text-[#545c72] hover:bg-slate-50 hover:text-[#0b1c30]' }}" href="{{ route('landing') }}">
                <span>{{ __('public.header.about') }}</span>
                <span class="material-symbols-outlined text-[18px] opacity-60">info</span>
            </a>
            <a class="flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ $activeTab === 'terms' ? 'text-[#006948] bg-emerald-50/80 font-semibold' : 'text-[#545c72] hover:bg-slate-50 hover:text-[#0b1c30]' }}" href="{{ $termsUrl }}">
                <span>{{ __('public.header.terms') }}</span>
                <span class="material-symbols-outlined text-[18px] opacity-60">gavel</span>
            </a>
            <a class="flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ $activeTab === 'versions' ? 'text-[#006948] bg-emerald-50/80 font-semibold' : 'text-[#545c72] hover:bg-slate-50 hover:text-[#0b1c30]' }}" href="{{ $versionsUrl }}">
                <span>{{ __('public.header.versions') }}</span>
                <span class="material-symbols-outlined text-[18px] opacity-60">history</span>
            </a>
            <a class="flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ $activeTab === 'contact' ? 'text-[#006948] bg-emerald-50/80 font-semibold' : 'text-[#545c72] hover:bg-slate-50 hover:text-[#0b1c30]' }}" href="{{ $contactUrl }}">
                <span>{{ __('public.header.contact') }}</span>
                <span class="material-symbols-outlined text-[18px] opacity-60">support_agent</span>
            </a>
            <div class="pt-3 border-t border-slate-100">
                @auth('web')
                    <a href="{{ route('user.me.dashboard') }}" class="w-full bg-[#059669] hover:bg-[#047857] text-white text-sm font-semibold py-2.5 px-4 rounded-lg flex items-center justify-center gap-2 shadow-sm transition-colors">
                        <span>{{ __('public.header.get_started') }}</span>
                        <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                    </a>
                @else
                    <button type="button" class="btn-google-sso w-full bg-[#059669] hover:bg-[#047857] text-white text-sm font-semibold py-2.5 px-4 rounded-lg flex items-center justify-center gap-2 shadow-sm transition-colors cursor-pointer">
                        <span>{{ __('public.header.get_started') }}</span>
                        <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                    </button>
                @endauth
            </div>
        </nav>
    </div>
</header>

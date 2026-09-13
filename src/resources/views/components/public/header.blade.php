@props([
    'activeTab' => 'about',
    'version' => 'v2.3.0',
    'termsUrl' => url('/terms'),
    'versionsUrl' => url('/versions'),
    'contactUrl' => route('contact'),
    'googleAuthUrl' => route('auth.google'),
])

@php
    $currentLocale = app()->getLocale();
    $locales = [
        'vi' => ['name' => 'Tiếng Việt', 'flag' => '🇻🇳', 'code' => 'VN'],
        'en' => ['name' => 'English', 'flag' => '🇬🇧', 'code' => 'EN'],
        'ja' => ['name' => '日本語', 'flag' => '🇯🇵', 'code' => 'JA'],
    ];
    $activeLocaleMeta = $locales[$currentLocale] ?? $locales['vi'];
@endphp

<!-- TOP NAVBAR (Shared Component) -->
<header class="bg-surface-container-lowest border-b border-outline-variant/50 sticky top-0 z-40 bg-white/95 backdrop-blur-md">
    <div class="w-full max-w-[1200px] mx-auto px-6 h-16 flex items-center justify-between">
        <!-- Brand Logo -->
        <div class="flex items-center gap-8">
            <a class="text-headline-md font-headline-md font-semibold text-on-surface flex items-center gap-2 tracking-tight text-[#0F172A] hover:opacity-90 transition-opacity" href="{{ route('landing') }}">
                <span class="w-8 h-8 rounded-lg bg-[#006948] flex items-center justify-center text-white shadow-sm">
                    <span class="material-symbols-outlined text-[20px]" data-icon="local_cafe">local_cafe</span>
                </span>
                <span class="font-bold text-lg">DrinkFlow</span>
            </a>
            <!-- Desktop Navigation Links -->
            <nav class="hidden md:flex items-center gap-6 pt-1" aria-label="Public Navigation">
                <a class="{{ $activeTab === 'about' ? 'text-[#006948] border-b-2 border-[#006948] font-semibold' : 'text-[#545c72] hover:text-[#0b1c30]' }} pb-1 transition-colors text-sm font-medium" href="{{ route('landing') }}#about">
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
        <div class="flex items-center gap-3">
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

            <div class="h-4 w-px bg-slate-200 mx-1"></div>

            <!-- Primary CTA Button -->
            @auth('web')
                <a href="{{ route('user.me.dashboard') }}" class="bg-[#059669] hover:bg-[#047857] text-white text-sm font-medium px-4 py-2 rounded-[6px] transition-all active:scale-[0.98] duration-100 flex items-center gap-1.5 shadow-sm cursor-pointer">
                    <span>{{ __('public.header.get_started') }}</span>
                    <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                </a>
            @else
                <button type="button" class="btn-google-sso bg-[#059669] hover:bg-[#047857] text-white text-sm font-medium px-4 py-2 rounded-[6px] transition-all active:scale-[0.98] duration-100 flex items-center gap-1.5 shadow-sm cursor-pointer">
                    <span>{{ __('public.header.get_started') }}</span>
                    <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                </button>
            @endauth
        </div>
    </div>
</header>

<script>
    (function() {
        const langBtn = document.getElementById('public-lang-btn');
        const langMenu = document.getElementById('public-lang-menu');
        const container = document.getElementById('public-lang-selector');

        if (!langBtn || !langMenu) return;

        langBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            const isOpen = !langMenu.classList.contains('hidden');
            if (isOpen) {
                langMenu.classList.add('hidden');
                langBtn.setAttribute('aria-expanded', 'false');
            } else {
                langMenu.classList.remove('hidden');
                langBtn.setAttribute('aria-expanded', 'true');
            }
        });

        document.addEventListener('click', function(e) {
            if (container && !container.contains(e.target)) {
                langMenu.classList.add('hidden');
                langBtn.setAttribute('aria-expanded', 'false');
            }
        });
    })();
</script>

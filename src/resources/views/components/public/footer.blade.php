@props([
    'activeTab' => 'about',
    'version' => \App\Models\Version::getLatestVersionString(),
    'termsUrl' => url('/terms'),
    'versionsUrl' => url('/versions'),
    'contactUrl' => route('contact'),
])

@php
    $menuItems = [
        'about' => [
            'label' => __('public.footer.about'),
            'url' => route('landing'),
            'icon' => 'info',
        ],
        'terms' => [
            'label' => __('public.footer.terms'),
            'url' => $termsUrl,
            'icon' => 'gavel',
        ],
        'versions' => [
            'label' => __('public.footer.versions'),
            'url' => $versionsUrl,
            'icon' => 'history',
        ],
        'contact' => [
            'label' => __('public.footer.contact'),
            'url' => $contactUrl,
            'icon' => 'support_agent',
        ],
    ];

    $currentTab = $activeTab ?? 'about';
    if (!array_key_exists($currentTab, $menuItems)) {
        $currentTab = 'about';
    }
    $activeItem = $menuItems[$currentTab];
@endphp

<!-- FOOTER (Shared Component with Active Dropdown) -->
<footer class="bg-white border-t border-slate-200 mt-16">
    <div class="w-full max-w-[1200px] mx-auto px-6 py-8 flex flex-col md:flex-row items-center justify-between gap-4">
        <!-- Brand & Version & Copyright -->
        <div class="flex flex-col sm:flex-row items-center gap-3 text-center sm:text-left">
            <span class="text-base font-semibold text-[#0F172A] flex items-center gap-2">
                <span class="w-6 h-6 rounded-md bg-[#006948] border border-[#005137] ring-1 ring-emerald-500/20 flex items-center justify-center text-white shadow-2xs">
                    <span class="material-symbols-outlined text-[14px]">local_cafe</span>
                </span>
                DrinkFlow
            </span>
            <span class="hidden sm:inline text-slate-300">|</span>
            <span class="text-xs sm:text-sm text-[#545c72]">
                {{ __('public.footer.copyright', ['year' => date('Y')]) }}
            </span>
            <a href="{{ $versionsUrl }}/{{ $version }}" class="font-mono text-xs text-[#006948] bg-[#eff4ff] hover:bg-[#dce9ff] transition-colors px-2 py-0.5 rounded font-medium">
                {{ __('public.footer.version', ['version' => $version]) }}
            </a>
        </div>

        <!-- Footer Navigation Dropdown -->
        <div class="relative" id="footer-nav-dropdown">
            <button type="button"
                    id="footer-nav-btn"
                    aria-haspopup="true"
                    aria-expanded="false"
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-slate-200/90 bg-white hover:bg-slate-50 text-xs sm:text-sm font-medium text-[#0b1c30] shadow-2xs hover:border-[#006948] transition-colors cursor-pointer">
                <span class="material-symbols-outlined text-[18px] text-[#006948]">{{ $activeItem['icon'] }}</span>
                <span class="font-semibold">{{ $activeItem['label'] }}</span>
                <span class="material-symbols-outlined text-[18px] text-[#545c72] transition-transform duration-200" id="footer-nav-arrow">expand_more</span>
            </button>

            <!-- Dropdown Menu -->
            <div id="footer-nav-menu"
                 class="hidden absolute bottom-full mb-2 right-0 sm:right-0 w-52 bg-white rounded-xl shadow-xl border border-slate-200 py-1.5 z-30 animate-fadeIn">
                @foreach($menuItems as $key => $item)
                    <a href="{{ $item['url'] }}"
                       class="flex items-center justify-between px-3.5 py-2 text-xs sm:text-sm text-slate-700 hover:bg-emerald-50 hover:text-[#006948] transition-colors {{ $currentTab === $key ? 'font-semibold text-[#006948] bg-emerald-50/70' : '' }}">
                        <div class="flex items-center gap-2.5">
                            <span class="material-symbols-outlined text-[18px] {{ $currentTab === $key ? 'text-[#006948]' : 'text-slate-400' }}">{{ $item['icon'] }}</span>
                            <span>{{ $item['label'] }}</span>
                        </div>
                        @if($currentTab === $key)
                            <span class="material-symbols-outlined text-[16px] text-[#006948]">check</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</footer>

@props([
    'version' => 'v2.3.0',
    'termsUrl' => url('/terms'),
    'versionsUrl' => url('/versions'),
    'contactUrl' => route('contact'),
])

<!-- FOOTER (Shared Component) -->
<footer class="bg-white border-t border-slate-200 mt-16">
    <div class="w-full max-w-[1200px] mx-auto px-6 py-8 flex flex-col md:flex-row items-center justify-between gap-4">
        <!-- Brand & Version & Copyright -->
        <div class="flex flex-col sm:flex-row items-center gap-3 text-center sm:text-left">
            <span class="text-base font-semibold text-[#0F172A] flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[18px] text-[#006948]">local_cafe</span>
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
        <!-- Footer Navigation Links -->
        <nav class="flex flex-wrap items-center justify-center gap-6 text-xs sm:text-sm font-medium" aria-label="Footer Navigation">
            <a class="text-[#545c72] hover:text-[#006948] transition-colors duration-150" href="{{ route('landing') }}">{{ __('public.footer.about') }}</a>
            <a class="text-[#545c72] hover:text-[#006948] transition-colors duration-150" href="{{ $termsUrl }}">{{ __('public.footer.terms') }}</a>
            <a class="text-[#545c72] hover:text-[#006948] transition-colors duration-150" href="{{ $versionsUrl }}">{{ __('public.footer.versions') }}</a>
            <a class="text-[#545c72] hover:text-[#006948] transition-colors duration-150" href="{{ $contactUrl }}">{{ __('public.footer.contact') }}</a>
        </nav>
    </div>
</footer>

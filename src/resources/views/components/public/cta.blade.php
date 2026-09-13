@props([
    'googleAuthUrl' => route('auth.google'),
])

<!-- SECTION: FINAL HIGH-CONTRAST CTA SECTION -->
<section class="bg-[#006948] text-white rounded-2xl p-8 sm:p-12 text-center flex flex-col items-center justify-center shadow-md relative overflow-hidden">
    <!-- Subtle Decorative Background Circles -->
    <div class="absolute -top-24 -left-24 w-64 h-64 bg-white/5 rounded-full blur-2xl pointer-events-none"></div>
    <div class="absolute -bottom-24 -right-24 w-64 h-64 bg-emerald-400/10 rounded-full blur-2xl pointer-events-none"></div>

    <div class="max-w-2xl mx-auto space-y-4 relative z-10">
        <span class="text-[#6ffbbe] font-mono text-xs uppercase tracking-widest font-semibold">{{ __('public.cta.eyebrow') }}</span>
        <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-white tracking-tight">{{ __('public.cta.title') }}</h2>
        <p class="text-emerald-100 text-sm sm:text-base leading-relaxed">
            {{ __('public.cta.subtitle') }}
        </p>
        <div class="pt-4 flex flex-wrap items-center justify-center gap-4">
            @auth('web')
                <a href="{{ route('user.me.dashboard') }}" class="bg-white hover:bg-[#F8FAFC] text-[#006948] text-sm font-semibold px-6 py-3 rounded-[6px] shadow-sm transition-all active:scale-[0.98] duration-100 flex items-center gap-2 cursor-pointer">
                    <span>{{ __('public.cta.btn_primary') }}</span>
                    <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                </a>
            @else
                <button type="button" class="btn-google-sso bg-white hover:bg-[#F8FAFC] text-[#006948] text-sm font-semibold px-6 py-3 rounded-[6px] shadow-sm transition-all active:scale-[0.98] duration-100 flex items-center gap-2 cursor-pointer">
                    <span>{{ __('public.cta.btn_primary') }}</span>
                    <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                </button>
            @endauth
            <button type="button" class="open-video-btn border border-white/40 hover:bg-white/10 text-white text-sm font-medium px-5 py-3 rounded-[6px] transition-colors flex items-center gap-2 cursor-pointer">
                <span class="material-symbols-outlined text-[18px] text-emerald-300">play_circle</span>
                <span>{{ __('public.cta.btn_video') }}</span>
            </button>
        </div>
    </div>
</section>

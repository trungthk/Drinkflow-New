<!-- SECTION: GOOGLE SINGLE SIGN-ON HIGHLIGHT BOX -->
<section class="bg-white border border-[#E2E8F0] rounded-2xl p-6 sm:p-8 shadow-2xs">
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div class="flex items-start gap-4 sm:gap-6">
            <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl bg-[#eff4ff] flex items-center justify-center flex-shrink-0 border border-[#bccac0]/50 text-[#006948]">
                <span class="material-symbols-outlined text-[28px] sm:text-[32px]">shield</span>
            </div>
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="bg-[#ECFDF5] text-[#065F46] text-xs px-2.5 py-0.5 rounded font-medium">{{ __('public.google_sso.badge') }}</span>
                    <span class="text-[#545c72] text-xs font-mono">{{ __('public.google_sso.tech_badge') }}</span>
                </div>
                <h3 class="text-lg sm:text-xl font-bold text-[#0F172A]">{{ __('public.google_sso.title') }}</h3>
                <p class="text-xs sm:text-sm text-[#545c72] max-w-3xl leading-relaxed">
                    {{ __('public.google_sso.description') }}
                </p>
            </div>
        </div>
        <div class="flex-shrink-0 w-full md:w-auto">
            @auth('web')
                <a href="{{ route('user.me.dashboard') }}" class="w-full md:w-auto bg-white border border-[#CBD5E1] hover:bg-[#F8FAFC] text-[#0b1c30] text-sm font-medium px-4 py-2.5 rounded-[6px] flex items-center justify-center gap-2 transition-colors shadow-2xs cursor-pointer">
                    <span class="material-symbols-outlined text-[18px] text-[#059669]">domain</span>
                    <span>{{ __('public.google_sso.btn_dashboard') }}</span>
                </a>
            @else
                <button type="button" class="btn-google-sso w-full md:w-auto bg-white border border-[#CBD5E1] hover:bg-[#F8FAFC] text-[#0b1c30] text-sm font-medium px-4 py-2.5 rounded-[6px] flex items-center justify-center gap-2 transition-colors shadow-2xs cursor-pointer">
                    <span class="material-symbols-outlined text-[18px] text-[#059669]">domain</span>
                    <span>{{ __('public.google_sso.btn_connect') }}</span>
                </button>
            @endauth
        </div>
    </div>
</section>

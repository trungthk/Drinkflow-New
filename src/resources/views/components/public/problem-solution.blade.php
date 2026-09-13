<!-- SECTION: PROBLEM VS SOLUTION (12-Column Grid / 2 Col Cards) -->
<section class="border-t border-[#E2E8F0] pt-12">
    <div class="text-center max-w-2xl mx-auto mb-10">
        <span class="text-[#006948] text-xs uppercase tracking-wider font-semibold">{{ __('public.problem_solution.eyebrow') }}</span>
        <h2 class="text-2xl sm:text-3xl text-[#0F172A] mt-1 font-bold">{{ __('public.problem_solution.title') }}</h2>
        <p class="text-[#545c72] text-sm sm:text-base mt-2">{{ __('public.problem_solution.subtitle') }}</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Card: Traditional Problem -->
        <div class="bg-white border border-[#FEE2E2] rounded-xl p-6 shadow-sm">
            <div class="flex items-center gap-3 pb-4 mb-4 border-b border-[#FEE2E2]">
                <span class="w-8 h-8 rounded-full bg-[#FEF2F2] flex items-center justify-center text-[#ba1a1a] font-bold text-sm">✕</span>
                <div>
                    <h3 class="text-base font-semibold text-[#0F172A]">{{ __('public.problem_solution.traditional_title') }}</h3>
                    <p class="text-xs text-[#545c72]">{{ __('public.problem_solution.traditional_subtitle') }}</p>
                </div>
            </div>
            <ul class="space-y-4">
                <li class="flex items-start gap-3">
                    <span class="text-[#ba1a1a] font-bold mt-0.5">❌</span>
                    <span class="text-sm text-[#334155]">{{ __('public.problem_solution.traditional_p1') }}</span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="text-[#ba1a1a] font-bold mt-0.5">❌</span>
                    <span class="text-sm text-[#334155]">{{ __('public.problem_solution.traditional_p2') }}</span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="text-[#ba1a1a] font-bold mt-0.5">❌</span>
                    <span class="text-sm text-[#334155]">{{ __('public.problem_solution.traditional_p3') }}</span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="text-[#ba1a1a] font-bold mt-0.5">❌</span>
                    <span class="text-sm text-[#334155]">{{ __('public.problem_solution.traditional_p4') }}</span>
                </li>
            </ul>
        </div>

        <!-- Card: DrinkFlow Solution -->
        <div class="bg-white border border-[#006948]/30 rounded-xl p-6 shadow-sm ring-1 ring-[#006948]/10">
            <div class="flex items-center gap-3 pb-4 mb-4 border-b border-[#bccac0]/40">
                <span class="w-8 h-8 rounded-full bg-[#ECFDF5] flex items-center justify-center text-[#059669] font-bold text-sm">✓</span>
                <div>
                    <h3 class="text-base font-semibold text-[#006948]">{{ __('public.problem_solution.drinkflow_title') }}</h3>
                    <p class="text-xs text-[#545c72]">{{ __('public.problem_solution.drinkflow_subtitle') }}</p>
                </div>
            </div>
            <ul class="space-y-4">
                <li class="flex items-start gap-3">
                    <span class="text-[#059669] font-bold mt-0.5">✅</span>
                    <span class="text-sm text-[#0F172A] font-medium">{{ __('public.problem_solution.drinkflow_s1') }}</span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="text-[#059669] font-bold mt-0.5">✅</span>
                    <span class="text-sm text-[#0F172A] font-medium">{{ __('public.problem_solution.drinkflow_s2') }}</span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="text-[#059669] font-bold mt-0.5">✅</span>
                    <span class="text-sm text-[#0F172A] font-medium">{{ __('public.problem_solution.drinkflow_s3') }}</span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="text-[#059669] font-bold mt-0.5">✅</span>
                    <span class="text-sm text-[#0F172A] font-medium">{{ __('public.problem_solution.drinkflow_s4') }}</span>
                </li>
            </ul>
        </div>
    </div>
</section>

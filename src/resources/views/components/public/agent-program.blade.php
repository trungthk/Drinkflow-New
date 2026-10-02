{{-- Chương trình Đại lý: giới thiệu quyền lợi và lối vào form đăng ký (/admin/register). --}}
<section id="agent-program" class="pt-4">
    <div class="rounded-2xl border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-emerald-100/60 p-6 sm:p-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
            <div class="lg:col-span-7 space-y-4">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-100 text-[#006948] text-xs font-semibold border border-emerald-200">
                    <span class="material-symbols-outlined text-[15px]">storefront</span>
                    {{ __('public.agent_program.eyebrow') }}
                </span>
                <h2 class="text-2xl sm:text-3xl font-bold text-[#0b1c30] tracking-tight">{{ __('public.agent_program.title') }}</h2>
                <p class="text-sm sm:text-base text-[#475569] leading-relaxed">{{ __('public.agent_program.subtitle') }}</p>

                <ul class="space-y-2.5 pt-1">
                    @foreach (['b1', 'b2', 'b3'] as $benefit)
                        <li class="flex items-start gap-2.5 text-sm text-[#0b1c30]">
                            <span class="material-symbols-outlined text-[18px] text-[#006948] shrink-0 mt-0.5">check_circle</span>
                            <span>{{ __('public.agent_program.'.$benefit) }}</span>
                        </li>
                    @endforeach
                </ul>

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 pt-2">
                    <a href="{{ route('admin.register.page') }}"
                        class="justify-center bg-[#059669] hover:bg-[#047857] text-white text-sm font-semibold px-5 py-2.5 rounded-[6px] transition-all active:scale-[0.98] duration-100 shadow-sm flex items-center gap-2 cursor-pointer">
                        <span>{{ __('public.agent_program.cta_register') }}</span>
                        <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                    </a>
                    <a href="{{ url('/guides/agent') }}"
                        class="justify-center border border-[#006948]/30 hover:bg-white text-[#006948] text-sm font-medium px-5 py-2.5 rounded-[6px] transition-colors flex items-center gap-2 cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">menu_book</span>
                        <span>{{ __('public.agent_program.cta_guide') }}</span>
                    </a>
                </div>
                <p class="text-xs text-[#64748b] pt-1">{{ __('public.agent_program.note') }}</p>
            </div>

            <div class="lg:col-span-5">
                <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm space-y-4">
                    <ol class="space-y-4">
                        @foreach (['step1', 'step2', 'step3'] as $index => $step)
                            <li class="flex items-start gap-3">
                                <span class="w-7 h-7 rounded-lg bg-emerald-50 text-[#006948] flex items-center justify-center text-xs font-bold shrink-0 border border-emerald-100">{{ $index + 1 }}</span>
                                <span class="text-sm text-[#0b1c30]">{{ __('public.agent_program.'.$step) }}</span>
                            </li>
                        @endforeach
                    </ol>
                    <p class="text-xs text-[#64748b] border-t border-slate-100 pt-3">{{ __('public.agent_program.steps_note') }}</p>
                </div>
            </div>
        </div>
    </div>
</section>

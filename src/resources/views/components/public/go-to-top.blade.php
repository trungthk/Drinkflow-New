<!-- FLOATING ACTION BUTTONS (Contact & Go To Top) -->
<div class="fixed bottom-6 right-6 z-40 flex flex-col items-center gap-3 select-none">
    <!-- Quick Contact Floating Button (Redirects to /contact) -->
    <a href="{{ route('contact') }}"
       aria-label="{{ __('public.contact_support') }}"
       title="{{ __('public.contact_support') }}"
       class="w-11 h-11 rounded-full bg-white hover:bg-emerald-50 text-[#006948] border border-slate-200/90 shadow-lg flex items-center justify-center transition-all duration-200 hover:scale-110 active:scale-95 group focus:outline-none focus:ring-2 focus:ring-[#006948] focus:ring-offset-2">
        <span class="material-symbols-outlined text-[22px] transition-transform duration-200 group-hover:scale-110">support_agent</span>
    </a>

    <!-- Go To Top Floating Button -->
    <button id="go-to-top-btn"
            type="button"
            aria-label="{{ __('public.go_to_top') }}"
            title="{{ __('public.go_to_top') }}"
            class="w-11 h-11 rounded-full bg-[#006948] hover:bg-[#047857] text-white shadow-lg flex items-center justify-center cursor-pointer transition-all duration-300 opacity-0 pointer-events-none translate-y-3 hover:scale-105 active:scale-95 focus:outline-none focus:ring-2 focus:ring-[#006948] focus:ring-offset-2">
        <span class="material-symbols-outlined text-[20px]">arrow_upward</span>
    </button>
</div>

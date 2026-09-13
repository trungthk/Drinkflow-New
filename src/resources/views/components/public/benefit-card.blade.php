@props([
    'icon' => 'touch_app',
    'title' => '',
    'description' => '',
])

<div class="bg-white border border-[#E2E8F0] rounded-xl p-5 shadow-2xs hover:border-[#006948]/50 hover:shadow-sm transition-all duration-200 group">
    <div class="w-10 h-10 rounded-lg bg-[#eff4ff] flex items-center justify-center text-[#006948] mb-3 group-hover:bg-[#006948] group-hover:text-white transition-colors duration-200">
        <span class="material-symbols-outlined text-[24px]">{{ $icon }}</span>
    </div>
    <h3 class="text-base font-semibold text-[#0b1c30] mb-2">{{ $title }}</h3>
    <p class="text-xs sm:text-sm text-[#545c72] leading-relaxed">{{ $description }}</p>
</div>

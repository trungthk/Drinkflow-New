@props([
    'icon' => 'campaign',
    'title' => '',
    'description' => '',
])

<div class="bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-2xs hover:border-[#006948]/40 hover:shadow-sm transition-all duration-200 group">
    <div class="flex items-center gap-3 mb-3">
        <span class="w-9 h-9 rounded-lg bg-[#eff4ff] flex items-center justify-center text-[#006948] group-hover:bg-[#006948] group-hover:text-white transition-colors duration-200">
            <span class="material-symbols-outlined text-[22px]">{{ $icon }}</span>
        </span>
        <h3 class="text-base font-semibold text-[#0F172A]">{{ $title }}</h3>
    </div>
    <p class="text-xs sm:text-sm text-[#545c72] leading-relaxed">{{ $description }}</p>
</div>

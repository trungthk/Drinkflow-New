<!-- Global User Loading Page & Submit Overlay Component -->
<div id="global-page-loading"
     class="fixed inset-0 z-[9999] hidden items-center justify-center p-4 bg-white/75 backdrop-blur-xs transition-opacity duration-200 opacity-0 pointer-events-none"
     role="status"
     aria-live="polite"
     aria-label="{{ __('global.common.loading') }}">
  <div class="flex flex-col items-center gap-2.5 select-none transform scale-95 transition-all duration-200" id="global-loading-card">
    <!-- Gentle Minimal Brand Spinner -->
    <div class="relative w-10 h-10 flex items-center justify-center">
      <div class="w-10 h-10 rounded-full border-2 border-emerald-100 border-t-[#006948] animate-spin"></div>
      <span class="material-symbols-outlined text-[18px] text-[#006948] absolute animate-pulse">local_cafe</span>
    </div>

    <!-- Minimal, Soft Single Line Text -->
    <p class="text-xs font-medium text-slate-500 tracking-wide" id="global-loading-title" data-submit-title="{{ __('global.common.loading') }}">
      {{ __('global.common.loading') }}
    </p>
  </div>
</div>

<!-- Public Loading Page & Submit Overlay Component -->
<div id="public-page-loading"
     class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-white/75 backdrop-blur-xs transition-opacity duration-200 opacity-100"
     role="status"
     aria-live="polite"
     aria-label="{{ __('public.loading.processing') }}">
  <div class="flex flex-col items-center gap-2.5 select-none transform scale-100 transition-all duration-200" id="public-loading-card">
    <!-- Gentle Minimal Brand Spinner -->
    <div class="relative w-10 h-10 flex items-center justify-center">
      <div class="w-10 h-10 rounded-full border-2 border-emerald-100 border-t-[#006948] animate-spin"></div>
      <span class="material-symbols-outlined text-[18px] text-[#006948] absolute animate-pulse">local_cafe</span>
    </div>

    <!-- Minimal, Soft Single Line Text -->
    <p class="text-xs font-medium text-slate-500 tracking-wide"
       id="public-loading-title"
       data-default-title="{{ __('public.loading.processing') }}"
       data-google-title="{{ __('public.loading.connecting_google') }}"
       data-submit-title="{{ __('public.loading.submitting') }}">
      {{ __('public.loading.processing') }}
    </p>
  </div>
</div>

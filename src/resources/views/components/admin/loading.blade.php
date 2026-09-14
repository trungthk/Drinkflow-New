<div id="admin-page-loading" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-surface/80 backdrop-blur-xs opacity-0 pointer-events-none transition-opacity duration-200" role="status" aria-live="polite" aria-label="{{ __('admin.processing') }}">
    <div id="admin-loading-card" class="flex flex-col items-center gap-3 scale-95 transition-transform duration-200">
        <div class="relative flex h-11 w-11 items-center justify-center"><span class="h-11 w-11 rounded-full border-2 border-primary/20 border-t-primary animate-spin"></span><span class="material-symbols-outlined absolute text-primary text-[19px]">local_cafe</span></div>
        <span id="admin-loading-title" class="text-xs font-semibold text-on-surface-variant">{{ __('admin.processing') }}</span>
    </div>
</div>

@props([
    'id',
    'icon' => 'info',
    'title',
    'description' => null,
    'maxWidth' => 'max-w-lg',
])

<div id="{{ $id }}" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4 backdrop-blur-xs"
     role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title" data-modal>
    <div class="absolute inset-0" data-modal-close></div>
    <div class="relative z-10 w-full {{ $maxWidth }} bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 shadow-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-start justify-between pb-3 border-b border-outline-variant mb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary border border-primary/20 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-[22px]">{{ $icon }}</span>
                </div>
                <div>
                    <h3 class="font-bold text-base text-on-surface" id="{{ $id }}-title">{{ $title }}</h3>
                    @if($description)
                        <p class="text-xs text-outline mt-0.5">{{ $description }}</p>
                    @endif
                </div>
            </div>
            <button type="button" class="p-1 rounded-lg text-outline hover:text-on-surface hover:bg-surface-container transition-colors cursor-pointer" data-modal-close aria-label="{{ __('superadmin.common.cancel') }}">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>
        <div id="{{ $id }}-error" class="hidden mb-4 rounded-xl p-3 bg-error-container/60 border border-error/30 text-error text-xs leading-relaxed"></div>
        {{ $slot }}
    </div>
</div>

{{-- Logout confirmation for the superadmin console, opened by the topbar button via [data-modal-open="superadmin-logout-modal"]. --}}
<x-superadmin.modal id="superadmin-logout-modal" icon="logout" :title="__('admin.logout_modal_title')" max-width="max-w-md">
    <p class="text-xs text-on-surface-variant leading-relaxed mb-4">{{ __('admin.logout_modal_desc') }}</p>
    <form method="POST" action="{{ route('admin.logout') }}" class="flex items-center justify-end gap-2.5 pt-2 border-t border-outline-variant">
        @csrf
        <button type="button" class="px-4 py-2 rounded-lg bg-surface-container hover:bg-surface-container-high text-on-surface text-xs font-semibold transition-colors cursor-pointer" data-modal-close>
            {{ __('admin.logout_modal_cancel') }}
        </button>
        <button type="submit" class="px-4 py-2 rounded-lg bg-error hover:opacity-90 text-white text-xs font-semibold transition-colors flex items-center gap-1.5 shadow-xs cursor-pointer">
            <span class="material-symbols-outlined text-[16px]">logout</span>{{ __('admin.logout_modal_confirm') }}
        </button>
    </form>
</x-superadmin.modal>

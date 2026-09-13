<!-- Global Logout Confirmation Modal -->
<div id="logout-confirm-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs transition-opacity duration-200" role="dialog" aria-modal="true" aria-labelledby="logout-modal-title">
  <div class="w-full max-w-md bg-white rounded-2xl shadow-xl border border-slate-200/80 overflow-hidden transform transition-all duration-200 scale-95 opacity-0" id="logout-modal-content">
    <div class="p-6">
      <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center mb-4">
        <span class="material-symbols-outlined text-[24px]">logout</span>
      </div>
      <h3 class="text-lg font-bold text-slate-900 tracking-tight" id="logout-modal-title">{{ __('global.modal.logout_title') }}</h3>
      <p class="text-sm text-slate-500 mt-2 leading-relaxed">
        {{ __('global.modal.logout_desc') }}
      </p>
    </div>
    <div class="px-6 py-4 bg-slate-50/80 border-t border-slate-100 flex items-center justify-end gap-3">
      <button type="button" onclick="window.closeLogoutModal ? window.closeLogoutModal() : null" class="px-4 py-2 text-sm font-medium text-slate-700 hover:text-slate-900 hover:bg-slate-200/60 rounded-xl transition-colors cursor-pointer">
        {{ __('global.modal.cancel') }}
      </button>
      <form id="global-logout-form" action="{{ route('logout') }}" method="POST" class="m-0 p-0">
        @csrf
        <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-xl shadow-xs transition-colors cursor-pointer flex items-center gap-1.5">
          <span class="material-symbols-outlined text-[16px]">logout</span>
          <span>{{ __('global.modal.confirm_logout') }}</span>
        </button>
      </form>
    </div>
  </div>
</div>

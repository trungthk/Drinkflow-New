{{-- Join-a-room modal (same flow as the dashboard "join by link" form: POST user.me.rooms.join). --}}
@php
    $joinModalHasError = old('_from') === 'join-room-modal' && $errors->has('room_url');
@endphp

<div id="join-room-modal"
     data-auto-open="{{ $joinModalHasError ? 'true' : 'false' }}"
     role="dialog" aria-modal="true" aria-labelledby="join-room-modal-title"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm">
    <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl sm:p-7">
        <button type="button" data-join-room-close aria-label="{{ __('global.join_modal.close') }}"
                class="absolute right-4 top-4 cursor-pointer rounded-lg p-1.5 text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-900">
            <span class="material-symbols-outlined text-[20px]">close</span>
        </button>

        <div class="mb-5 flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#006948] text-white shadow-sm">
                <span class="material-symbols-outlined text-[22px]">meeting_room</span>
            </div>
            <div>
                <h3 id="join-room-modal-title" class="text-lg font-bold tracking-tight text-slate-900">{{ __('global.join_modal.title') }}</h3>
                <p class="text-xs text-slate-500">{{ __('global.join_modal.subtitle') }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('user.me.rooms.join') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="_from" value="join-room-modal">
            <div>
                <label for="join-room-modal-url" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('global.dashboard.join_by_url') }}</label>
                <input id="join-room-modal-url" name="room_url" type="url" required
                       value="{{ old('_from') === 'join-room-modal' ? old('room_url') : '' }}"
                       placeholder="{{ __('global.dashboard.join_placeholder') }}"
                       class="h-11 w-full rounded-xl border bg-slate-50/70 px-4 text-sm text-slate-900 placeholder:text-slate-400 focus:border-[#006948] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#006948]/20 {{ $joinModalHasError ? 'border-red-500' : 'border-slate-200' }}">
                @if($joinModalHasError)
                    <p class="mt-2 flex items-center gap-1 text-xs font-medium text-red-600">
                        <span class="material-symbols-outlined text-[14px]">error</span>
                        {{ $errors->first('room_url') }}
                    </p>
                @endif
            </div>

            <div class="flex items-center justify-end gap-2 pt-1">
                <button type="button" data-join-room-close
                        class="cursor-pointer rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-semibold text-slate-700 transition-colors hover:bg-slate-50">
                    {{ __('global.join_modal.cancel') }}
                </button>
                <button type="submit"
                        class="flex cursor-pointer items-center gap-2 rounded-xl bg-[#006948] px-5 py-2.5 text-xs font-semibold text-white shadow-xs transition-colors hover:bg-[#005137]">
                    <span class="material-symbols-outlined text-[18px]">login</span>
                    <span>{{ __('global.dashboard.join_button') }}</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('join-room-modal');
        if (!modal || modal.dataset.bound) return;
        modal.dataset.bound = 'true';

        const open = () => {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
            document.getElementById('join-room-modal-url')?.focus();
        };
        const close = () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
        };

        document.querySelectorAll('[data-join-room-open]').forEach((button) => button.addEventListener('click', open));
        modal.querySelectorAll('[data-join-room-close]').forEach((button) => button.addEventListener('click', close));
        modal.addEventListener('click', (event) => { if (event.target === modal) close(); });
        document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !modal.classList.contains('hidden')) close(); });

        if (modal.dataset.autoOpen === 'true') open();
    })();
</script>

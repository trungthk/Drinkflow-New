@props([
    'room' => null,
])

<div class="fixed inset-0 z-[9999] flex h-screen w-screen min-h-full min-w-full items-center justify-center overflow-y-auto p-4 bg-slate-900/60 backdrop-blur-md">
  <div class="w-full max-w-[560px] bg-white shadow-2xl border border-rose-200/80 rounded-2xl p-6 sm:p-8 relative overflow-hidden my-auto animate-fadeIn">
    <!-- Red Top Accent Strip -->
    <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-rose-500 via-rose-600 to-rose-400"></div>

    <!-- Header & Restricted Badge -->
    <div class="flex flex-col items-center text-center">
      <div class="w-16 h-16 rounded-2xl bg-rose-50 border border-rose-100 flex items-center justify-center text-rose-600 mb-3 shadow-xs">
        <span class="material-symbols-outlined text-[32px]">shield_person</span>
      </div>
      <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-50 text-rose-700 font-mono text-[11px] font-semibold mb-2 border border-rose-200/70">
        <span class="w-1.5 h-1.5 rounded-full bg-rose-600 animate-pulse"></span>
        <span>{{ __('room.blocked.badge', ['name' => strtoupper($room->name ?? 'ROOM')]) }}</span>
      </div>
      <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
        {{ __('room.blocked.title', ['name' => $room->name ?? __('global.common.room')]) }}
      </h1>
      <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-md">
        {{ __('room.blocked.subtitle') }}
      </p>
    </div>

    <!-- Reason Box -->
    <div class="mt-6 bg-slate-50/80 border border-slate-200/80 rounded-xl p-4 text-left text-xs text-slate-600 space-y-3">
      <div class="flex items-start gap-2.5">
        <span class="material-symbols-outlined text-rose-600 text-[18px] shrink-0 mt-0.5">error</span>
        <div>
          <span class="font-bold text-slate-900 block">{{ __('room.blocked.reason_title') }}</span>
          <p class="mt-0.5 text-slate-600 leading-relaxed">
            {{ __('room.blocked.reason_desc', ['name' => $room->name ?? __('global.common.room')]) }}
          </p>
        </div>
      </div>
      <div class="h-px bg-slate-200/70 w-full"></div>
      <div class="space-y-1.5 pl-6 text-slate-600 text-[11px]">
        <div class="flex items-center gap-2">
          <span class="w-1.5 h-1.5 rounded-full bg-slate-400 shrink-0"></span>
          <span>{{ __('room.blocked.restriction_1') }}</span>
        </div>
        <div class="flex items-center gap-2">
          <span class="w-1.5 h-1.5 rounded-full bg-slate-400 shrink-0"></span>
          <span>{{ __('room.blocked.restriction_2', ['name' => $room->name ?? __('global.common.room')]) }}</span>
        </div>
        <div class="flex items-center gap-2">
          <span class="w-1.5 h-1.5 rounded-full bg-slate-400 shrink-0"></span>
          <span>{{ __('room.blocked.restriction_3') }}</span>
        </div>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="mt-6 flex flex-col gap-2.5">
      <a class="w-full flex items-center justify-center gap-2 bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs py-2.5 px-4 rounded-xl shadow-xs transition-colors" href="{{ route('user.me.rooms') }}">
        <span class="material-symbols-outlined text-[18px]">meeting_room</span>
        <span>{{ __('room.blocked.back_to_my_rooms') }}</span>
      </a>
    </div>
  </div>
</div>

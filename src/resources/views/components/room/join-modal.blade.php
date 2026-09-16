@props([
    'room' => null,
    'user' => null,
    'suggestedCode' => null,
])

@php
    $user = $user ?? request()->attributes->get('global_user') ?? auth('web')->user();
    $suggestedCode = $suggestedCode ?? strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $user->name ?? 'MEM'), 0, 8));
    $roomName = $room->name ?? __('global.common.room');
    $roomNameToken = '__ROOM_NAME__';
    $roomJoinTitleParts = explode($roomNameToken, __('room.join.title', ['name' => $roomNameToken]), 2);
@endphp

<div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-950/55 p-3 backdrop-blur-sm sm:p-6"
  style="margin: 0;" role="dialog" aria-modal="true" aria-labelledby="room-join-title">
  <div data-room-join-confirmation
    class="relative my-auto w-full max-w-lg overflow-hidden rounded-[1.75rem] border border-white/70 bg-white shadow-[0_24px_80px_-24px_rgba(15,23,42,0.5)] animate-fadeIn">
    <div class="relative overflow-hidden border-b border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-teal-50 px-5 pb-5 pt-6 sm:px-6">
      <div class="absolute -right-12 -top-16 h-40 w-40 rounded-full bg-emerald-200/35 blur-2xl" aria-hidden="true"></div>
      <div class="relative flex items-start gap-4">
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#006948] text-white shadow-lg shadow-emerald-900/15">
          <span class="material-symbols-outlined text-[26px]" aria-hidden="true">corporate_fare</span>
        </div>
        <div class="min-w-0 flex-1">
          <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-white/80 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.12em] text-emerald-800">
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span>
            {{ __('room.join.badge') }}
          </span>
          <h1 id="room-join-title" class="mt-2 text-xl font-bold tracking-tight text-slate-950 sm:text-2xl">
            {{ $roomJoinTitleParts[0] }}<span data-room-name class="text-[#006948]">{{ $roomName }}</span>{{ $roomJoinTitleParts[1] ?? '' }}
          </h1>
          <div class="mt-1 text-xs text-slate-500">
            <span>{{ $room->description ?: __('room.join.default_description') }}</span>
          </div>
        </div>
      </div>
    </div>

    <div class="space-y-4 p-5 sm:p-6">
      <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50/80 p-3">
        <div class="relative shrink-0">
          <img class="h-11 w-11 rounded-xl border border-white object-cover shadow-sm ring-1 ring-slate-200"
            src="{{ $user->avatar_url ?: asset('images/default-avatar.svg') }}"
            alt="{{ __('room.join.account_avatar_alt', ['name' => $user->name ?? __('global.common.user')]) }}"
            loading="lazy"
            onerror="this.onerror=null;this.src='{{ asset('images/default-avatar.svg') }}';">
          <span class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full border-2 border-white bg-emerald-500"
            title="{{ __('room.join.ready_badge') }}"></span>
        </div>
        <div class="min-w-0 flex-1">
          <p class="truncate text-sm font-bold text-slate-900">{{ $user->name ?? __('global.common.user') }}</p>
          <p class="truncate text-xs text-slate-500">{{ $user->email ?? '' }}</p>
        </div>
        <div class="shrink-0 rounded-xl border border-emerald-200 bg-white px-3 py-2 text-right shadow-sm">
          <span class="block text-[9px] font-bold uppercase tracking-wider text-slate-400">{{ __('room.header.user_code') }}</span>
          <strong class="mt-0.5 block font-mono text-xs tracking-wide text-[#006948]">{{ $suggestedCode }}</strong>
        </div>
      </div>

      <div class="flex items-start gap-2.5 rounded-2xl bg-emerald-50/70 px-3.5 py-3 text-xs leading-relaxed text-slate-600">
        <span class="material-symbols-outlined mt-0.5 shrink-0 text-[18px] text-[#006948]" aria-hidden="true">verified_user</span>
        <p>{{ __('room.join.intro_desc', ['name' => $roomName, 'code' => $suggestedCode]) }}</p>
      </div>

      <form method="POST" action="{{ route('user.rooms.join', $room->slug) }}" data-loading-form="true"
        class="grid grid-cols-1 gap-2.5 pt-1 sm:grid-cols-[1fr_auto]">
        @csrf
        <input type="hidden" name="user_code" value="{{ $suggestedCode }}">
        <button type="submit"
          class="inline-flex h-11 min-w-0 items-center justify-center gap-2 rounded-xl bg-[#006948] px-5 text-xs font-bold text-white shadow-md shadow-emerald-900/15 transition-all hover:-translate-y-0.5 hover:bg-[#005137] hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:pointer-events-none disabled:opacity-70">
          <span class="material-symbols-outlined text-[18px]" aria-hidden="true">group_add</span>
          <span class="truncate">{{ __('room.join.button') }}</span>
        </button>
        <a class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-600 transition-colors hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-2"
          href="{{ route('user.me.rooms') }}">
          <span class="material-symbols-outlined mr-1.5 text-[17px]" aria-hidden="true">arrow_back</span>
          {{ __('global.common.back') }}
        </a>
      </form>
    </div>
  </div>
</div>

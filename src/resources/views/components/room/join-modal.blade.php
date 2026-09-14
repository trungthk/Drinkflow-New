@props([
    'room' => null,
    'user' => null,
    'suggestedCode' => null,
])

@php
    $user = $user ?? request()->attributes->get('global_user') ?? auth('web')->user();
    $suggestedCode = $suggestedCode ?? strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $user->name ?? 'MEM'), 0, 8));
@endphp

<div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md overflow-y-auto">
  <div class="w-full max-w-[580px] bg-white rounded-2xl shadow-2xl p-6 sm:p-8 relative border border-slate-200/80 my-auto animate-fadeIn">
    <!-- Modal Header -->
    <div class="flex flex-col items-center text-center">
      <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-800 text-xs font-semibold uppercase tracking-wider mb-3 border border-emerald-200/70">
        <span class="w-2 h-2 rounded-full bg-[#006948] animate-pulse"></span>
        <span>{{ __('room.join.badge') }}</span>
      </div>
      <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-[#006948] border-2 border-emerald-200 ring-1 ring-emerald-500/20 flex items-center justify-center mb-2 shadow-xs">
        <span class="material-symbols-outlined text-[32px]">corporate_fare</span>
      </div>
      <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
        {{ __('room.join.title', ['name' => $room->name ?? 'Room']) }}
      </h1>
      <p class="text-xs sm:text-sm text-slate-500 mt-1 flex items-center justify-center gap-1.5 flex-wrap">
        <span>{{ $room->description ?? 'Không gian đặt đồ uống nội bộ' }}</span>
        @if($room->timezone)
          <span class="text-slate-300">•</span>
          <span>{{ $room->timezone }}</span>
        @endif
      </p>
    </div>

    <!-- User Identity Card -->
    <div class="mt-6 p-4 bg-slate-50/80 rounded-xl border border-slate-200/70 flex items-center gap-3.5">
      <div class="relative shrink-0">
        <img class="w-13 h-13 rounded-full object-cover border-2 border-white ring-2 ring-emerald-600/30 shadow-xs" 
             src="{{ $user->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode($user->name ?? 'User').'&background=006948&color=ffffff&bold=true' }}" 
             alt="{{ $user->name ?? 'User' }}"
             loading="lazy">
        <span class="absolute bottom-0 right-0 w-3.5 h-3.5 bg-emerald-500 rounded-full ring-2 ring-white" title="{{ __('room.join.ready_badge') }}"></span>
      </div>
      <div class="flex flex-col min-w-0 flex-1">
        <div class="flex items-center justify-between gap-2">
          <span class="font-bold text-sm text-slate-900 truncate">{{ $user->name ?? 'User' }}</span>
          <span class="shrink-0 px-2 py-0.5 rounded-md bg-slate-200 text-slate-700 font-mono text-xs font-semibold">{{ $suggestedCode }}</span>
        </div>
        <span class="text-xs text-slate-500 truncate">{{ $user->email ?? '' }}</span>
        <span class="text-[11px] text-[#006948] font-medium truncate mt-0.5">{{ __('room.join.role_title', ['id' => $user->id ?? '1']) }}</span>
      </div>
    </div>

    <!-- Info Explanation Box -->
    <div class="mt-3.5 p-3.5 rounded-xl bg-emerald-50/50 border border-emerald-100 flex items-start gap-2.5 text-slate-600 text-xs leading-relaxed">
      <span class="material-symbols-outlined text-[#006948] text-[18px] shrink-0 mt-0.5">info</span>
      <p>{{ __('room.join.intro_desc', ['name' => $room->name ?? 'Room', 'code' => $suggestedCode]) }}</p>
    </div>

    <!-- Actions Form -->
    <form method="POST" action="{{ route('user.rooms.join', $room->slug) }}" class="mt-6 flex flex-col gap-2.5">
      @csrf
      <input type="hidden" name="user_code" value="{{ $suggestedCode }}">
      <button type="submit" class="w-full h-11 inline-flex items-center justify-center gap-2 rounded-xl bg-[#006948] hover:bg-[#005137] text-white font-semibold text-xs transition-colors shadow-xs cursor-pointer">
        <span class="material-symbols-outlined text-[18px]">group_add</span>
        <span>{{ __('room.join.button', ['name' => $room->name ?? 'Room']) }}</span>
      </button>
      <a class="inline-flex items-center justify-center py-2 text-xs text-slate-500 hover:text-slate-900 transition-colors text-center font-medium" href="{{ route('user.me.rooms') }}">
        {{ __('room.join.back_to_my_rooms') }}
      </a>
    </form>

  </div>
</div>

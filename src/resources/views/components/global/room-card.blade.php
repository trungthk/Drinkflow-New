@props(['item', 'showLiveStatus' => false])
<!-- CARD: Active Room -->
<div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs hover:border-[#006948]/60 hover:shadow-md transition-all flex flex-col justify-between group">
  <div>
    <!-- Card Header -->
    <div class="flex items-start justify-between gap-3 mb-4">
      <div class="w-10 h-10 rounded-lg bg-emerald-50 border border-emerald-100 flex items-center justify-center text-[#006948]">
        <span class="material-symbols-outlined text-[24px]">groups</span>
      </div>
      <!-- Badge: Đang hoạt động -->
      <div class="flex flex-col items-end gap-1.5 text-right">
      <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#ECFDF5] text-[#006948] border border-[#006948]/20">
        <span class="w-1.5 h-1.5 rounded-full bg-[#006948] animate-pulse"></span>
        <span>{{ $showLiveStatus && ($item->is_live ?? false) ? __('global.dashboard.room_live') : __('global.rooms.badge_active') }}</span>
      </span>
      @if($showLiveStatus && ($item->is_live ?? false) && $item->live_deadline)
        <span class="inline-flex items-center gap-1 text-xs text-slate-500">
          <span class="material-symbols-outlined text-[14px]" aria-hidden="true">schedule</span>
          {{ __('global.dashboard.room_order_deadline', ['time' => $item->live_deadline]) }}
        </span>
      @endif
      </div>
    </div>

    <!-- Title & ID -->
    <h2 class="text-base font-bold text-slate-900 group-hover:text-[#006948] transition-colors line-clamp-1" title="{{ $item->room_name }}">
      {{ $item->room_name }}
    </h2>
    <p class="text-xs text-slate-400 mt-0.5 font-mono uppercase">{{ $item->room_id_display }}</p>

    <!-- Metrics Matrix -->
    <div class="mt-5 pt-4 border-t border-slate-100 grid grid-cols-2 gap-4">
      <div>
        <span class="text-xs text-slate-400 block">{{ __('global.rooms.joined_date') }}</span>
        <span class="text-xs font-medium text-slate-800 mt-0.5 block">{{ $item->joined_at_formatted }}</span>
      </div>
      <div>
        <span class="text-xs text-slate-400 block">{{ __('global.rooms.orders_placed') }}</span>
        <span class="text-xs font-semibold text-slate-800 mt-0.5 block">{{ __('global.rooms.orders_count', ['count' => $item->orders_count]) }}</span>
      </div>
      <div class="col-span-2 bg-slate-50/70 p-2.5 rounded-lg border border-slate-100 flex items-center justify-between">
        <span class="text-xs text-slate-600 font-medium">{{ __('global.rooms.total_spent') }}</span>
        <span class="text-sm font-bold text-[#006948] tabular-nums">{{ $item->total_spent_formatted }}</span>
      </div>
    </div>
  </div>

  <!-- Card Action CTA -->
  <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between gap-3">
    <span class="text-xs text-slate-400 flex items-center gap-1">
      <span class="material-symbols-outlined text-[16px]">schedule</span>
      <span>{{ $item->last_order_time ? __('global.rooms.last_ordered', ['time' => $item->last_order_time]) : __('global.rooms.no_orders_yet') }}</span>
    </span>
    <a href="{{ $item->dashboard_url }}" class="h-[36px] px-4 bg-[#006948] hover:bg-[#005137] text-white rounded-lg text-xs font-semibold transition-colors flex items-center gap-1.5 shadow-2xs">
      <span>{{ __('global.rooms.enter_room') }}</span>
      <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
    </a>
  </div>
</div>

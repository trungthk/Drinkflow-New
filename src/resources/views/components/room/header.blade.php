@props([
    'room' => null,
    'roomUser' => null,
    'user' => null,
    'activeTab' => 'overview',
    'breadcrumbs' => [],
    'activeCampaign' => null,
    'unreadNotificationsCount' => 0,
    'userRooms' => collect(),
])

@php
    $user = $user ?? request()->attributes->get('global_user') ?? auth('web')->user();
    $roomUser = $roomUser ?? request()->attributes->get('room_user');
    $currentLocale = app()->getLocale();
    $activeLocaleMeta = $locales[$currentLocale] ?? $locales['vi'];
@endphp

<header class="sticky top-0 w-full z-40 bg-white border-b border-slate-200/80 shadow-2xs" x-data="{ showRoomDropdown: false, showLangDropdown: false }">
  <!-- Tier 1: Main Global Header Bar -->
  <div class="h-14 border-b border-slate-100 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 h-full flex items-center justify-between gap-4">
      <!-- Left Brand & Room Selector -->
      <div class="flex items-center gap-3">
        <a class="flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-900 transition-colors py-1 px-2 rounded-lg hover:bg-slate-50 group" href="{{ route('user.me.dashboard') }}">
          <span class="material-symbols-outlined text-[18px] group-hover:-translate-x-0.5 transition-transform text-[#006948]">arrow_back</span>
          <span>{{ __('room.header.back_to_portal') }}</span>
        </a>
        <span class="h-4 w-px bg-slate-200 hidden sm:block"></span>
        
        <!-- Room Selector Dropdown -->
        <div class="relative">
          <button @click="showRoomDropdown = !showRoomDropdown" 
                  @click.outside="showRoomDropdown = false"
                  type="button" 
                  class="flex items-center gap-2 px-2.5 py-1 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200/70 text-slate-800 transition-all cursor-pointer">
            <span class="w-6 h-6 rounded-lg bg-emerald-50 text-[#006948] border border-emerald-300 ring-1 ring-emerald-500/20 flex items-center justify-center font-bold text-xs shrink-0 shadow-2xs">
              <span class="material-symbols-outlined text-[15px]">corporate_fare</span>
            </span>
            <span class="font-bold text-xs sm:text-sm text-slate-900 truncate max-w-[140px] sm:max-w-[200px]">{{ $room->name ?? 'Room' }}</span>
            <span class="material-symbols-outlined text-[16px] text-slate-400" :class="{ 'rotate-180': showRoomDropdown }">expand_more</span>
          </button>

          <!-- Room Switch Dropdown Menu -->
          <div x-show="showRoomDropdown" 
               x-cloak
               x-transition:enter="transition ease-out duration-100"
               x-transition:enter-start="opacity-0 scale-95"
               x-transition:enter-end="opacity-100 scale-100"
               class="absolute left-0 mt-1.5 w-64 bg-white border border-slate-200 rounded-2xl shadow-xl py-2 z-50 overflow-hidden">
            <div class="px-3 py-1.5 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
              {{ __('room.header.switch_room') }}
            </div>
            @if(isset($userRooms) && count($userRooms) > 0)
              <div class="max-h-56 overflow-y-auto divide-y divide-slate-50">
                @foreach($userRooms as $r)
                  <a href="{{ route('user.dashboard', $r->slug ?? $r['slug']) }}" 
                     class="flex items-center justify-between px-3 py-2 text-xs text-slate-700 hover:bg-emerald-50/60 hover:text-[#006948] transition-colors {{ ($room && ($r->id ?? $r['id']) == $room->id) ? 'bg-emerald-50 text-[#006948] font-semibold' : '' }}">
                    <span class="truncate">{{ $r->name ?? $r['name'] }}</span>
                    @if($room && ($r->id ?? $r['id']) == $room->id)
                      <span class="material-symbols-outlined text-[16px] text-[#006948]">check</span>
                    @endif
                  </a>
                @endforeach
              </div>
            @endif
            <div class="pt-1.5 mt-1 border-t border-slate-100 px-2">
              <a href="{{ route('user.me.rooms') }}" class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-medium text-slate-600 hover:text-[#006948] hover:bg-slate-50 transition-colors">
                <span class="material-symbols-outlined text-[15px]">meeting_room</span>
                <span>{{ __('global.header.rooms') }}</span>
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Controls: Language, Notifications, User Code & Profile -->
      <div class="flex items-center gap-1 sm:gap-2 shrink-0">
        <!-- Language Switcher -->
        <div class="relative shrink-0">
          <button @click="showLangDropdown = !showLangDropdown" 
                  @click.outside="showLangDropdown = false"
                  type="button" 
          class="flex items-center gap-1 sm:gap-1.5 px-2 sm:px-2.5 py-1 sm:py-1.5 rounded-lg sm:rounded-xl text-[11px] sm:text-xs font-medium text-slate-600 hover:bg-slate-50 transition-colors duration-150 border border-slate-200 shadow-2xs cursor-pointer shrink-0"
          title="Ngôn ngữ / Language">
        <span class="text-xs sm:text-sm leading-none">{{ $activeLocaleMeta['flag'] }}</span>
        <span class="font-semibold text-slate-800 text-[11px] sm:text-xs leading-none">{{ $activeLocaleMeta['code'] }}</span>
        <span class="material-symbols-outlined text-[14px] sm:text-[16px] text-slate-400">arrow_drop_down</span>
          </button>
          <div x-show="showLangDropdown" 
               x-cloak
               x-transition
               class="absolute right-0 mt-1.5 w-36 bg-white border border-slate-200 rounded-xl shadow-lg py-1 z-50">
            @foreach($locales as $code => $info)
              <a href="{{ route('locale.switch', $code) }}" class="flex items-center justify-between px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-[#006948] transition-colors {{ $currentLocale === $code ? 'bg-emerald-50 text-[#006948] font-semibold' : '' }}">
                <span class="flex items-center gap-1.5">
                  <span>{{ $info['flag'] }}</span>
                  <span>{{ $info['name'] }}</span>
                </span>
                @if($currentLocale === $code)
                  <span class="material-symbols-outlined text-[14px]">check</span>
                @endif
              </a>
            @endforeach
          </div>
        </div>

        <!-- Notifications Icon -->
        <a href="{{ $room ? route('user.rooms.notifications', $room->slug) : '#' }}" 
           class="relative w-8 h-8 sm:w-9 sm:h-9 flex items-center justify-center rounded-lg sm:rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 hover:text-slate-900 transition-colors shadow-2xs cursor-pointer shrink-0"
          title="{{ __('room.header.notifications') }}" aria-label="{{ __('room.header.notifications') }}">
          <span class="material-symbols-outlined text-[17px] sm:text-[19px]">notifications</span>
          @if($unreadNotificationsCount > 0)
            <span data-user-notification-badge class="absolute -top-1 -right-1 min-w-[16px] h-4 px-1 rounded-full bg-rose-600 text-white text-[10px] font-bold flex items-center justify-center ring-2 ring-white animate-pulse">
              {{ $unreadNotificationsCount > 9 ? '9+' : $unreadNotificationsCount }}
            </span>
          @endif
        </a>

        <!-- User Profile Pill -->
        <a href="{{ $room ? route('user.rooms.profile', $room->slug) : '#' }}" 
            class="relative flex items-center gap-1 sm:gap-2 pl-1 sm:pl-2 shrink-0 rounded-xl hover:bg-slate-100/60 transition-colors"
           title="{{ __('global.header.profile_menu') }}" aria-label="{{ __('global.header.profile_menu') }}">
          <img class="w-8 h-8 sm:w-9 sm:h-9 rounded-full object-cover border-2 border-white ring-2 ring-[#006948]/30 shadow-xs"
               src="{{ $user->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode($user->name ?? 'User').'&background=006948&color=ffffff&bold=true' }}" 
               alt="{{ $user->name ?? 'User' }}"
               loading="lazy">
          <span class="hidden lg:flex flex-col text-left">
            <span class="text-xs font-semibold text-slate-800 truncate max-w-[130px]">
              {{ $user->name ?? 'User' }}
            </span>
            @if($roomUser && $roomUser->user_code)
              <span class="text-[10px] font-mono font-bold text-[#006948] truncate max-w-[130px]" title="{{ __('room.header.user_code') }}">
                {{ $roomUser->user_code }}
              </span>
            @endif
          </span>
        </a>
      </div>
    </div>
  </div>

  <!-- Tier 2: Breadcrumb & Countdown Sub-Bar -->
  <div class="h-9 bg-slate-50/80 border-b border-slate-100 text-xs text-slate-500">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 h-full flex items-center justify-between gap-3">
      <!-- Breadcrumbs -->
      <div class="flex items-center gap-1.5 truncate">
        <a class="hover:text-slate-900 transition-colors flex items-center gap-1" href="{{ route('user.me.dashboard') }}">
          <span class="material-symbols-outlined text-[14px]">home</span>
          <span>{{ __('room.header.breadcrumb_home') }}</span>
        </a>
        <span class="material-symbols-outlined text-[13px] text-slate-400">chevron_right</span>
        <a class="hover:text-slate-900 transition-colors font-medium truncate" href="{{ $room ? route('user.dashboard', $room->slug) : '#' }}">
          {{ $room->name ?? 'Room' }}
        </a>
        @if(!empty($breadcrumbs))
          @foreach($breadcrumbs as $crumb)
            <span class="material-symbols-outlined text-[13px] text-slate-400">chevron_right</span>
            @if(isset($crumb['url']) && $crumb['url'])
              <a class="hover:text-slate-900 transition-colors truncate" href="{{ $crumb['url'] }}">{{ $crumb['title'] }}</a>
            @else
              <span class="text-slate-800 font-semibold truncate">{{ $crumb['title'] }}</span>
            @endif
          @endforeach
        @endif
      </div>

      <!-- Live Countdown Badge -->
      <div class="shrink-0">
        @if($activeCampaign)
          <div class="flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-rose-50 text-rose-700 border border-rose-200/70 font-mono text-[11px] font-semibold">
            <span class="w-1.5 h-1.5 rounded-full bg-rose-600 animate-pulse"></span>
            <span>{{ __('room.header.countdown_prefix') }} {{ $activeCampaign['time_remaining'] ?? '14:22' }}</span>
          </div>
        @else
          <div class="flex items-center gap-1 text-[11px] text-slate-400">
            <span class="material-symbols-outlined text-[14px]">bedtime</span>
            <span>{{ __('room.header.no_active_campaign') }}</span>
          </div>
        @endif
      </div>
    </div>
  </div>

  <!-- Tier 3: Navigation Tabs Bar -->
  <div class="h-11 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 h-full flex items-center">
      <nav class="flex items-center h-full gap-1 overflow-x-auto no-scrollbar">
        <!-- Tab 1: Tổng quan -->
        <a href="{{ $room ? route('user.dashboard', $room->slug) : '#' }}" 
           class="h-full px-3.5 inline-flex items-center gap-1.5 text-xs font-semibold transition-all border-b-2 whitespace-nowrap {{ $activeTab === 'overview' ? 'border-[#006948] text-[#006948] bg-emerald-50/40' : 'border-transparent text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
          <span class="material-symbols-outlined text-[16px]">dashboard</span>
          <span>{{ __('room.nav.overview') }}</span>
        </a>

        <!-- Tab 2: Chiến dịch & Menu -->
        <a href="{{ $room ? route('user.campaigns.index', $room->slug) : '#' }}" 
           class="h-full px-3.5 inline-flex items-center gap-1.5 text-xs font-semibold transition-all border-b-2 whitespace-nowrap {{ $activeTab === 'campaigns' ? 'border-[#006948] text-[#006948] bg-emerald-50/40' : 'border-transparent text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
          <span class="material-symbols-outlined text-[16px]">restaurant_menu</span>
          <span>{{ __('room.nav.campaign_menu') }}</span>
        </a>

        <!-- Tab 3: Đơn hàng của tôi -->
        <a href="{{ $room ? route('user.orders.index', $room->slug) : '#' }}" 
           class="h-full px-3.5 inline-flex items-center gap-1.5 text-xs font-semibold transition-all border-b-2 whitespace-nowrap {{ $activeTab === 'orders' ? 'border-[#006948] text-[#006948] bg-emerald-50/40' : 'border-transparent text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
          <span class="material-symbols-outlined text-[16px]">receipt_long</span>
          <span>{{ __('room.nav.my_orders') }}</span>
        </a>

        <!-- Tab 4: Thanh toán & Nợ -->
        <a href="{{ $room ? route('user.debts.index', $room->slug) : '#' }}" 
           class="h-full px-3.5 inline-flex items-center gap-1.5 text-xs font-semibold transition-all border-b-2 whitespace-nowrap {{ $activeTab === 'debts' ? 'border-[#006948] text-[#006948] bg-emerald-50/40' : 'border-transparent text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
          <span class="material-symbols-outlined text-[16px]">account_balance_wallet</span>
          <span>{{ __('room.nav.payment_debts') }}</span>
        </a>

        <!-- Tab 5: Thống kê Room -->
        <a href="{{ $room ? route('user.analytics.room', $room->slug) : '#' }}" 
           class="h-full px-3.5 inline-flex items-center gap-1.5 text-xs font-semibold transition-all border-b-2 whitespace-nowrap {{ $activeTab === 'analytics' ? 'border-[#006948] text-[#006948] bg-emerald-50/40' : 'border-transparent text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
          <span class="material-symbols-outlined text-[16px]">bar_chart</span>
          <span>{{ __('room.nav.room_analytics') }}</span>
        </a>

        <!-- Tab 6: Thông báo -->
        <a href="{{ $room ? route('user.rooms.notifications', $room->slug) : '#' }}" 
           class="h-full px-3.5 inline-flex items-center gap-1.5 text-xs font-semibold transition-all border-b-2 whitespace-nowrap {{ $activeTab === 'notifications' ? 'border-[#006948] text-[#006948] bg-emerald-50/40' : 'border-transparent text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
          <span class="material-symbols-outlined text-[16px]">notifications</span>
          <span>{{ __('room.nav.notifications') }}</span>
        </a>

        <!-- Tab 7: Hồ sơ -->
        <a href="{{ $room ? route('user.rooms.profile', $room->slug) : '#' }}" 
           class="h-full px-3.5 inline-flex items-center gap-1.5 text-xs font-semibold transition-all border-b-2 whitespace-nowrap {{ $activeTab === 'profile' ? 'border-[#006948] text-[#006948] bg-emerald-50/40' : 'border-transparent text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
          <span class="material-symbols-outlined text-[16px]">person</span>
          <span>{{ __('room.nav.profile') }}</span>
        </a>
      </nav>
    </div>
  </div>
</header>

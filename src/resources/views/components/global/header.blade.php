@props([
    'user',
    'activeTab' => 'overview',
    'breadcrumbs' => [],
    'unreadNotificationsCount' => 0,
    'notifications' => collect(),
])

@php
    $tabClasses = function(bool $isActive) {
        return $isActive
            ? 'border-b-2 border-[#006948] text-[#006948] text-sm font-semibold pb-3 whitespace-nowrap flex items-center gap-1.5'
            : 'text-slate-600 hover:text-slate-900 font-medium text-sm pb-3 transition-colors whitespace-nowrap border-b-2 border-transparent hover:border-slate-300 flex items-center gap-1.5';
    };

    $currentLocale = app()->getLocale();
    $activeLocaleMeta = $locales[$currentLocale] ?? $locales['vi'];

    $hasRooms = $user ? $user->roomUsers()->exists() : false;
@endphp

<!-- Top Navigation Bar (2 Tầng chuẩn hệ thống) -->
<header class="bg-white/95 backdrop-blur-md border-b border-slate-200/80 shadow-2xs w-full sticky top-0 z-30">
  <div class="w-full max-w-[1200px] mx-auto px-6 flex flex-col">
    <!-- Tầng 1: Upper Section (Logo, Actions, Profile SSO) -->
    <div class="flex items-center justify-between h-16 gap-6">
      <!-- Left: Logo -->
      <div class="flex items-center gap-8 flex-1">
        <a class="flex items-center gap-2.5 group" href="{{ route('user.me.dashboard') }}">
          <span class="w-8 h-8 rounded-xl bg-gradient-to-br from-[#006948] to-[#047857] flex items-center justify-center text-white shadow-xs transition-transform duration-150 group-hover:scale-105">
            <span class="material-symbols-outlined text-[19px]">local_cafe</span>
          </span>
          <span class="text-xl font-bold text-[#006948] tracking-tight">DrinkFlow</span>
        </a>
      </div>

      <!-- Right: Actions & User Dropdown -->
      <div class="flex items-center gap-2.5">
        <!-- Language Selector Dropdown (Kế bên notification button) -->
        <div class="relative" id="global-lang-selector">
          <button type="button"
                  id="global-lang-btn"
                  aria-haspopup="true"
                  aria-expanded="false"
                  class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl text-xs font-medium text-slate-600 hover:bg-slate-50 transition-colors duration-150 border border-slate-200 shadow-2xs cursor-pointer"
                  title="Ngôn ngữ / Language">
            <span>{{ $activeLocaleMeta['flag'] }}</span>
            <span class="font-semibold text-slate-800">{{ $activeLocaleMeta['code'] }}</span>
            <span class="material-symbols-outlined text-[16px] text-slate-400">arrow_drop_down</span>
          </button>

          <!-- Language Dropdown Menu -->
          <div id="global-lang-menu"
               class="hidden absolute right-0 top-12 w-36 bg-white rounded-xl shadow-xl border border-slate-200/80 py-1.5 z-50">
            @foreach($locales as $code => $meta)
              <a href="{{ route('locale.switch', $code) }}"
                 class="flex items-center justify-between px-3 py-2 text-xs text-slate-700 hover:bg-emerald-50 hover:text-[#006948] transition-colors {{ $currentLocale === $code ? 'font-semibold text-[#006948] bg-emerald-50/50' : '' }}">
                <div class="flex items-center gap-2">
                  <span>{{ $meta['flag'] }}</span>
                  <span>{{ $meta['name'] }}</span>
                </div>
                @if($currentLocale === $code)
                  <span class="material-symbols-outlined text-[16px] text-[#006948]">check</span>
                @endif
              </a>
            @endforeach
          </div>
        </div>

        <!-- Notification Action -->
        <div class="relative">
          <button class="w-9 h-9 flex items-center justify-center rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 hover:text-slate-900 transition-colors shadow-2xs cursor-pointer" id="global-notification-btn" title="{{ __('global.header.notifications') }}" aria-label="{{ __('global.header.notifications') }}">
            <span class="material-symbols-outlined text-[19px]">notifications</span>
          </button>
          @if($unreadNotificationsCount > 0)
            <span class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-white text-[10px] font-bold pointer-events-none ring-2 ring-white" id="global-notif-badge">
              {{ $unreadNotificationsCount }}
            </span>
          @endif

          <!-- Notification Dropdown Menu -->
          <div class="hidden absolute right-0 top-12 z-50 w-80 sm:w-96 bg-white rounded-2xl shadow-xl border border-slate-200/80 overflow-hidden" id="global-notification-dropdown">
            <div class="p-3.5 px-4 bg-slate-50/70 border-b border-slate-100 flex items-center justify-between">
              <div class="flex items-center gap-2">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('global.header.notifications') }}</h3>
                @if($unreadNotificationsCount > 0)
                  <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold border border-emerald-200/60">{{ __('global.header.new_badge', ['count' => $unreadNotificationsCount]) }}</span>
                @endif
              </div>
              <button class="text-xs font-medium text-[#006948] hover:text-[#047857] transition-colors cursor-pointer"
                      id="global-mark-all-read-btn"
                      data-read-text="{{ __('global.header.all_read') }}">
                {{ __('global.header.mark_all_read') }}
              </button>
            </div>
            <div class="divide-y divide-slate-100 max-h-[380px] overflow-y-auto">
              @forelse($notifications as $notif)
                <div class="p-3.5 flex items-start gap-3 hover:bg-slate-50/80 transition-colors {{ is_null($notif->read_at) ? 'bg-emerald-50/20' : '' }}">
                  <span class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0 mt-0.5 border border-emerald-100">
                    <span class="material-symbols-outlined text-[17px]">
                      {{ $notif->type === 'campaign.created' ? 'local_fire_department' : ($notif->type === 'order.status' ? 'check_circle' : 'notifications') }}
                    </span>
                  </span>
                  <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-slate-800">{{ $notif->title ?? 'Thông báo từ DrinkFlow' }}</p>
                    <p class="text-xs text-slate-500 mt-0.5 line-clamp-2">{{ $notif->body ?? '' }}</p>
                    <span class="text-[11px] text-slate-400 block mt-1 flex items-center gap-1">
                      <span class="material-symbols-outlined text-[12px]">schedule</span> {{ $notif->created_at->diffForHumans() }}
                    </span>
                  </div>
                  @if(is_null($notif->read_at))
                    <span class="w-2 h-2 rounded-full bg-emerald-600 shrink-0 mt-2"></span>
                  @endif
                </div>
              @empty
                <div class="p-6 text-center text-slate-500 text-xs">
                  <span class="material-symbols-outlined text-slate-300 text-[26px] mb-1.5 block">notifications_off</span>
                  {{ __('global.header.no_notifications') }}
                </div>
              @endforelse
            </div>
            <div class="p-2.5 bg-slate-50/60 border-t border-slate-100 text-center">
              <a class="text-xs font-medium text-[#006948] hover:text-[#047857] inline-flex items-center gap-1" href="{{ route('user.me.notifications') }}">
                {{ __('global.header.view_all_notifications') }}
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
              </a>
            </div>
          </div>
        </div>

        <!-- Help Action -->
        <a class="w-9 h-9 flex items-center justify-center rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 hover:text-slate-900 transition-colors shadow-2xs" href="{{ route('user.me.feedback') }}" title="{{ __('global.header.help_support') }}" aria-label="{{ __('global.header.help_support') }}">
          <span class="material-symbols-outlined text-[19px]">help</span>
        </a>

        <!-- User Avatar & Dropdown Anchor -->
        <div class="relative flex items-center gap-2 pl-2 border-l border-slate-200" id="global-user-menu-wrapper">
          <button class="flex items-center gap-2 focus:outline-none cursor-pointer rounded-xl p-1 hover:bg-slate-100/60 transition-colors" id="global-user-menu-btn" title="{{ __('global.header.profile_menu') }}" aria-label="{{ __('global.header.profile_menu') }}" aria-haspopup="true" aria-expanded="false">
            <div class="relative">
              <img alt="Avatar {{ $user->name }}" class="w-9 h-9 rounded-full object-cover ring-2 ring-emerald-500/20" src="{{ $user->avatar_url }}" onerror="this.onerror=null;this.src='{{ asset('images/default-avatar.svg') }}'">
              <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-500 rounded-full ring-2 ring-white" title="Google Workspace Verified"></span>
            </div>
            <div class="hidden lg:flex flex-col text-left">
              <div class="flex items-center gap-1">
                <span class="text-xs font-semibold text-slate-800 truncate max-w-[130px]">{{ $user->name }}</span>
                <span class="material-symbols-outlined text-emerald-600 text-[13px]" style="font-variation-settings: 'FILL' 1;">verified</span>
              </div>
              <span class="text-[11px] text-slate-500 truncate max-w-[150px]">{{ $user->email }}</span>
            </div>
            <span class="material-symbols-outlined text-slate-400 text-[18px] transition-transform duration-200" id="global-user-chevron">expand_more</span>
          </button>

          <!-- User Dropdown Menu -->
          <div class="hidden absolute right-0 top-12 z-50 w-72 bg-white rounded-2xl shadow-xl border border-slate-200/80 overflow-hidden" id="global-user-dropdown">
            <div class="p-4 bg-slate-50/70 border-b border-slate-100">
              <div class="flex items-center gap-3">
                <img alt="Avatar {{ $user->name }}" class="w-10 h-10 rounded-full object-cover ring-2 ring-emerald-500/20 shrink-0" src="{{ $user->avatar_url }}" onerror="this.onerror=null;this.src='{{ asset('images/default-avatar.svg') }}'">
                <div class="min-w-0 flex-1">
                  <div class="flex items-center gap-1.5">
                    <p class="text-sm font-bold text-slate-900 truncate">{{ $user->name }}</p>
                    <span class="inline-flex items-center gap-0.5 px-1.5 py-0.2 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold">{{ __('global.header.sso_badge') }}</span>
                  </div>
                  <p class="text-[11px] text-slate-400 truncate mt-0.5">{{ $user->email }}</p>
                </div>
              </div>
            </div>
            <div class="p-2 divide-y divide-slate-100">
              <div class="space-y-0.5 pb-2">
                <a class="flex items-center gap-2.5 px-3 py-2 text-xs font-medium {{ $activeTab === 'profile' ? 'text-[#006948] bg-emerald-50/60 font-semibold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-50' }} rounded-xl transition-colors" href="{{ route('user.me.profile') }}">
                  <span class="material-symbols-outlined text-[17px] {{ $activeTab === 'profile' ? 'text-[#006948]' : 'text-slate-400' }}">account_circle</span>
                  <span class="flex-1">{{ __('global.header.profile_taste') }}</span>
                  @if($activeTab === 'profile')
                    <span class="w-1.5 h-1.5 rounded-full bg-[#006948]"></span>
                  @endif
                </a>
                @if($hasRooms)
                  <a class="flex items-center gap-2.5 px-3 py-2 text-xs font-medium {{ $activeTab === 'payments' ? 'text-[#006948] bg-emerald-50/60 font-semibold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-50' }} rounded-xl transition-colors" href="{{ route('user.me.payments') }}">
                    <span class="material-symbols-outlined text-[17px] {{ $activeTab === 'payments' ? 'text-[#006948]' : 'text-slate-400' }}">account_balance_wallet</span>
                    <span>{{ __('global.header.wallet_payments') }}</span>
                  </a>
                  <a class="flex items-center gap-2.5 px-3 py-2 text-xs font-medium {{ $activeTab === 'statistics' ? 'text-[#006948] bg-emerald-50/60 font-semibold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-50' }} rounded-xl transition-colors" href="{{ route('user.me.statistics') }}">
                    <span class="material-symbols-outlined text-[17px] {{ $activeTab === 'statistics' ? 'text-[#006948]' : 'text-slate-400' }}">bar_chart</span>
                    <span>{{ __('global.header.spend_stats') }}</span>
                  </a>
                @endif
                <a class="flex items-center gap-2.5 px-3 py-2 text-xs font-medium {{ $activeTab === 'devices' ? 'text-[#006948] bg-emerald-50/60 font-semibold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-50' }} rounded-xl transition-colors" href="{{ route('user.me.devices') }}">
                  <span class="material-symbols-outlined text-[17px] {{ $activeTab === 'devices' ? 'text-[#006948]' : 'text-slate-400' }}">shield</span>
                  <span>{{ __('global.header.security_devices') }}</span>
                </a>
                <a class="flex items-center gap-2.5 px-3 py-2 text-xs font-medium {{ $activeTab === 'notifications' ? 'text-[#006948] bg-emerald-50/60 font-semibold' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-50' }} rounded-xl transition-colors" href="{{ route('user.me.notifications') }}">
                  <span class="material-symbols-outlined text-[17px] {{ $activeTab === 'notifications' ? 'text-[#006948]' : 'text-slate-400' }}">tune</span>
                  <span>{{ __('global.header.notif_settings') }}</span>
                </a>
              </div>
              <div class="pt-2">
                <button type="button" onclick="openLogoutModal()" class="w-full flex items-center gap-2.5 px-3 py-2 text-xs font-medium text-rose-600 hover:bg-rose-50 rounded-xl transition-colors text-left cursor-pointer">
                  <span class="material-symbols-outlined text-[17px] text-rose-500">logout</span>
                  <span>{{ __('global.header.logout') }}</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tầng 2: Navigation Links & Breadcrumb Trail -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pt-2">
      <nav class="flex items-center gap-6 overflow-x-auto no-scrollbar" aria-label="Điều hướng chính">
        <!-- Luôn hiển thị Tổng quan -->
        <a class="{{ $tabClasses($activeTab === 'overview') }}" href="{{ route('user.me.dashboard') }}">
          <span class="material-symbols-outlined text-[17px]">dashboard</span>
          {{ __('global.header.overview') }}
        </a>

        <!-- Chỉ hiển thị khi đã tham gia ít nhất 1 Room -->
        @if($hasRooms)
          <a class="{{ $tabClasses($activeTab === 'rooms') }}" href="{{ route('user.me.rooms') }}">
            {{ __('global.header.my_rooms') }}
          </a>
          <a class="{{ $tabClasses($activeTab === 'orders') }}" href="{{ route('user.me.orders') }}">
            {{ __('global.header.order_history') }}
          </a>
          <a class="{{ $tabClasses($activeTab === 'payments') }}" href="{{ route('user.me.payments') }}">
            {{ __('global.header.payments') }}
          </a>
          <a class="{{ $tabClasses($activeTab === 'statistics') }}" href="{{ route('user.me.statistics') }}">
            {{ __('global.header.statistics') }}
          </a>
        @endif

        <!-- Luôn hiển thị Đánh giá -->
        <a class="{{ $tabClasses($activeTab === 'feedback') }}" href="{{ route('user.me.feedback') }}">
          {{ __('global.header.feedback') }}
        </a>
      </nav>

      <!-- Breadcrumbs Trail -->
      @if(!empty($breadcrumbs))
        <div class="hidden lg:flex items-center gap-1.5 text-xs text-slate-400 pb-2.5">
          @foreach($breadcrumbs as $index => $crumb)
            @if(!$loop->first)
              <span class="material-symbols-outlined text-[13px]">chevron_right</span>
            @endif
            @if(!empty($crumb['url']) && !$loop->last)
              <a href="{{ $crumb['url'] }}" class="hover:text-slate-700 transition-colors">{{ $crumb['label'] }}</a>
            @else
              <span class="{{ $loop->last ? 'text-[#006948] font-semibold' : '' }}">{{ $crumb['label'] }}</span>
            @endif
          @endforeach
        </div>
      @endif
    </div>
  </div>
</header>

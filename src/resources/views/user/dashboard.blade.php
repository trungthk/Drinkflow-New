<x-room.layout
  :title="'DrinkFlow - ' . __('room.dashboard.page_title') . ' - ' . ($room->name ?? 'Room')"
  :room="$room"
  :room-user="$roomUser"
  :user="$user"
  :active-tab="'overview'"
  :active-campaign="$activeCampaign"
  :unread-notifications-count="$unreadNotificationsCount ?? 0"
  :user-rooms="$userRooms ?? collect()"
>
  <!-- Main Canvas Space -->
  <div class="space-y-5" x-data="{ showAnnouncement: true }">

    <!-- 1. Top Announcement Card -->
    <div x-show="showAnnouncement" x-cloak class="w-full bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4 flex items-start sm:items-center justify-between gap-3 relative overflow-hidden transition-all">
      <div class="absolute left-0 top-0 bottom-0 w-1 bg-amber-500"></div>
      <div class="flex items-start sm:items-center gap-3 pl-1 min-w-0">
        <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-700 border border-amber-200/70 shrink-0 flex items-center justify-center">
          <span class="material-symbols-outlined text-[18px]">campaign</span>
        </div>
        <p class="text-xs sm:text-sm text-slate-800 truncate leading-relaxed">
          <span class="font-bold text-amber-800">{{ __('room.dashboard.announcement_label') }}</span>
          {{ __('room.dashboard.announcement_default', ['name' => $room->name]) }}
        </p>
      </div>
      <div class="flex items-center gap-1 shrink-0">
        <button type="button" @click="showAnnouncement = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer" title="{{ __('room.dashboard.close_announcement') }}">
          <span class="material-symbols-outlined text-[18px]">close</span>
        </button>
      </div>
    </div>

    <!-- 2. Unpaid Debt Warning Banner -->
    @if($unpaidDebts > 0)
      <div class="w-full bg-rose-50 border border-rose-200/80 text-rose-900 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition-all">
        <div class="flex items-center gap-3 min-w-0">
          <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-700 border border-rose-200 shrink-0 flex items-center justify-center">
            <span class="material-symbols-outlined text-[22px]">warning</span>
          </div>
          <div>
            <div class="flex flex-wrap items-baseline gap-2">
              <span class="font-bold text-xs sm:text-sm text-rose-950">{{ __('room.dashboard.debt_warning_title') }}</span>
              <span class="font-bold font-mono text-base sm:text-lg text-rose-700">{{ number_format($unpaidDebts, 0, ',', '.') }}đ</span>
            </div>
            <span class="text-xs text-rose-700">{{ __('room.dashboard.debt_warning_hint') }}</span>
          </div>
        </div>
        <a href="{{ route('user.debts.index', $room->slug) }}" class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs transition-colors shadow-2xs shrink-0 cursor-pointer">
          <span class="material-symbols-outlined text-[17px]">qr_code_2</span>
          <span>{{ __('room.dashboard.debt_warning_btn') }}</span>
        </a>
      </div>
    @endif

    <!-- 3. Main Grid Layout: Left Column (Active Campaign) & Right Column (Overview Summary) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
      
      <!-- Left Column: 7 Cols -->
      <div class="lg:col-span-7 space-y-5">
        @if($activeCampaign)
          <!-- Active Campaign Hero Card -->
          <div class="bg-white border border-slate-200/80 rounded-2xl shadow-xs p-6 relative overflow-hidden">
            <!-- Background Ambient Glow -->
            <div class="absolute -right-16 -top-16 w-56 h-56 rounded-full bg-emerald-50 pointer-events-none blur-2xl"></div>

            <div class="relative z-10 space-y-5">
              <!-- Campaign Header Info -->
              <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-3.5">
                  <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-[#006948] border border-emerald-100 flex items-center justify-center shrink-0 shadow-2xs">
                    <span class="material-symbols-outlined text-[28px]">local_cafe</span>
                  </div>
                  <div>
                    <div class="flex items-center gap-1.5">
                      <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold uppercase tracking-wider border border-emerald-200/70">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
                        {{ __('room.dashboard.active_campaign_badge') }}
                      </span>
                    </div>
                    <h2 class="text-base sm:text-lg font-bold text-slate-900 tracking-tight mt-0.5">{{ $activeCampaign['name'] }}</h2>
                    <p class="text-xs text-slate-500 flex items-center gap-1 mt-0.5">
                      <span class="material-symbols-outlined text-[15px] text-[#006948]">storefront</span>
                      <span class="font-semibold text-slate-800">{{ $activeCampaign['restaurant'] }}</span>
                      <span class="text-slate-300">•</span>
                      <span>{{ __('room.dashboard.created_by', ['name' => $activeCampaign['creator_name']]) }}</span>
                    </p>
                  </div>
                </div>

                <!-- Countdown Timer Pill -->
                <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-rose-50 text-rose-700 border border-rose-200/80 font-mono text-xs font-bold shrink-0">
                  <span class="material-symbols-outlined text-[16px] animate-pulse">schedule</span>
                  <span>{{ $activeCampaign['time_remaining'] }}</span>
                </div>
              </div>

              <!-- Sponsor Budget Info Box -->
              <div class="bg-slate-50/80 border border-slate-100 rounded-xl p-4 space-y-2">
                <div class="flex items-center justify-between text-xs">
                  <div class="flex items-center gap-1.5 text-slate-600">
                    <span class="material-symbols-outlined text-[17px] text-[#006948]">savings</span>
                    <span class="font-medium">{{ __('room.dashboard.sponsor_budget_title') }}</span>
                    <span class="font-bold font-mono text-slate-900">{{ number_format($activeCampaign['sponsor_budget'], 0, ',', '.') }}đ</span>
                  </div>
                  <div class="text-xs font-semibold text-[#006948]">
                    {{ __('room.dashboard.sponsor_remaining', ['amount' => number_format($activeCampaign['sponsor_remaining'], 0, ',', '.') . 'đ']) }}
                  </div>
                </div>
                <!-- Progress Bar -->
                <div class="w-full bg-slate-200 h-2 rounded-full overflow-hidden">
                  <div class="bg-[#006948] h-full rounded-full transition-all duration-300" style="width: {{ $activeCampaign['sponsor_percent'] }}%"></div>
                </div>
                <div class="flex items-center justify-between text-[11px] text-slate-400">
                  <span>{{ __('room.dashboard.sponsor_used', ['amount' => number_format($activeCampaign['sponsor_used'], 0, ',', '.') . 'đ', 'percent' => $activeCampaign['sponsor_percent']]) }}</span>
                  <span>{{ __('room.dashboard.sponsor_limit', ['amount' => '20.000đ']) }}</span>
                </div>
              </div>

              <!-- Enter Campaign CTA Button -->
              <a href="{{ route('user.campaigns.index', $room->slug) }}" class="w-full h-11 bg-[#006948] hover:bg-[#005137] text-white rounded-xl text-xs font-semibold flex items-center justify-center gap-2 transition-colors shadow-xs cursor-pointer">
                <span class="material-symbols-outlined text-[18px]">restaurant_menu</span>
                <span>{{ __('room.dashboard.enter_campaign') }}</span>
              </a>
            </div>
          </div>

          <!-- Recommended Drinks Grid -->
          @if(isset($activeCampaign['recommended_items']) && count($activeCampaign['recommended_items']) > 0)
            <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs">
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3.5 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[#006948] text-[18px]">auto_awesome</span>
                {{ __('room.dashboard.recommended_drinks') }}
              </h3>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach($activeCampaign['recommended_items'] as $item)
                  <div class="p-3.5 rounded-xl border border-slate-200/80 bg-slate-50/40 hover:bg-white hover:border-emerald-300 transition-all flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0">
                      <div class="w-10 h-10 rounded-lg bg-emerald-50 text-[#006948] border border-emerald-100 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-[20px]">local_cafe</span>
                      </div>
                      <div class="min-w-0">
                        <h4 class="text-xs font-bold text-slate-900 truncate">{{ $item->name }}</h4>
                        <span class="text-[11px] font-mono font-semibold text-slate-500">{{ number_format($item->base_price, 0, ',', '.') }}đ</span>
                      </div>
                    </div>
                    <a href="{{ route('user.campaigns.index', $room->slug) }}" class="px-3 py-1 rounded-lg bg-[#006948] hover:bg-[#005137] text-white text-xs font-semibold transition-colors shadow-2xs shrink-0 cursor-pointer">
                      {{ __('room.dashboard.select_drink') }}
                    </a>
                  </div>
                @endforeach
              </div>
            </div>
          @endif
        @else
          <!-- No Active Campaign State -->
          <div class="bg-white border border-slate-200/80 rounded-2xl p-8 shadow-xs text-center">
            <div class="w-16 h-16 rounded-2xl bg-slate-50 text-slate-400 border border-slate-200 mx-auto flex items-center justify-center mb-3">
              <span class="material-symbols-outlined text-[32px]">bedtime</span>
            </div>
            <h3 class="text-base font-bold text-slate-900">{{ __('room.dashboard.no_active_campaign_title') }}</h3>
            <p class="text-xs text-slate-500 max-w-md mx-auto mt-1 leading-relaxed">
              {{ __('room.dashboard.no_active_campaign_desc') }}
            </p>
          </div>
        @endif
      </div>

      <!-- Right Column: 5 Cols -->
      <div class="lg:col-span-5 space-y-5">
        
        <!-- Live Pool Metric Card -->
        @if($activeCampaign)
          <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
              <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[#006948] text-[20px]">group</span>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-700">{{ __('room.dashboard.participant_progress', ['active' => $activeCampaign['ordered_members'], 'total' => $activeCampaign['total_members']]) }}</span>
              </div>
            </div>
            <div class="flex items-center justify-between bg-slate-50 p-3.5 rounded-xl border border-slate-100">
              <span class="text-xs text-slate-500 font-medium">{{ __('room.dashboard.total_pool_value', ['amount' => '']) }}</span>
              <span class="text-base font-bold font-mono text-[#006948]">{{ number_format($activeCampaign['total_pool_value'], 0, ',', '.') }}đ</span>
            </div>
          </div>
        @endif

        <!-- Recent Personal Orders in this Room -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-3">
            <div class="flex items-center gap-2">
              <span class="material-symbols-outlined text-[#006948] text-[20px]">history</span>
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">{{ __('room.orders.page_title') }}</h3>
            </div>
            <a href="{{ route('user.orders.index', $room->slug) }}" class="text-xs text-[#006948] hover:underline font-semibold">
              {{ __('global.dashboard.full_history') }} →
            </a>
          </div>

          @if(isset($userRecentOrders) && count($userRecentOrders) > 0)
            <div class="divide-y divide-slate-100">
              @foreach($userRecentOrders as $order)
                <div class="py-3 flex items-center justify-between gap-3">
                  <div class="min-w-0">
                    <span class="font-mono text-xs font-bold text-slate-900 block truncate">#{{ $order->id }}</span>
                    <span class="text-[11px] text-slate-400 block mt-0.5">{{ $order->created_at->format('H:i, d/m') }}</span>
                  </div>
                  <div class="text-right shrink-0">
                    <span class="text-xs font-bold font-mono text-slate-900 block">{{ number_format($order->final_amount, 0, ',', '.') }}đ</span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full {{ $order->status?->value === 'completed' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }} font-medium inline-block mt-0.5">
                      {{ $order->status?->value ?? 'pending' }}
                    </span>
                  </div>
                </div>
              @endforeach
            </div>
          @else
            <div class="py-6 text-center text-slate-400 text-xs">
              <span class="material-symbols-outlined text-[28px] text-slate-300 mb-1 block">receipt_long</span>
              {{ __('room.orders.no_orders_title') }}
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</x-room.layout>

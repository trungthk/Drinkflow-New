<x-room.layout
  :title="'DrinkFlow - ' . __('room.dashboard.page_title') . ' - ' . ($room->name ?? __('global.common.room'))"
  :room="$room"
  :room-user="$roomUser"
  :user="$user"
  :active-tab="'overview'"
  :unread-notifications-count="$unreadNotificationsCount ?? 0"
  :user-rooms="$userRooms ?? collect()"
>
  <!-- Main Canvas Space -->
  <div class="space-y-4" x-data="{ showAnnouncement: true }">

    <!-- 1. Top Announcement Card -->
    <div x-show="showAnnouncement" x-cloak class="w-full bg-white rounded-xl border border-slate-200/80 shadow-2xs p-3 sm:p-3.5 flex items-start sm:items-center justify-between gap-3 relative overflow-hidden transition-all">
      <div class="absolute left-0 top-0 bottom-0 w-1 bg-amber-500"></div>
      <div class="flex items-start sm:items-center gap-2.5 pl-1 min-w-0">
        <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-700 border border-amber-200/70 shrink-0 flex items-center justify-center">
          <span class="material-symbols-outlined text-[16px]">campaign</span>
        </div>
        <p class="text-xs text-slate-800 truncate leading-relaxed">
          <span class="font-bold text-amber-800">{{ __('room.dashboard.announcement_label') }}</span>
          {{ __('room.dashboard.announcement_default', ['name' => $room->name]) }}
        </p>
      </div>
      <div class="flex items-center gap-1 shrink-0">
        <button type="button" @click="showAnnouncement = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer" title="{{ __('room.dashboard.close_announcement') }}">
          <span class="material-symbols-outlined text-[16px]">close</span>
        </button>
      </div>
    </div>

    <!-- 2. Unpaid Debt Warning Banner -->
    @if($unpaidDebts > 0)
      <div class="w-full bg-rose-50 border border-rose-200/80 text-rose-900 rounded-xl p-3 sm:p-3.5 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 transition-all">
        <div class="flex items-center gap-2.5 min-w-0">
          <div class="w-8 h-8 rounded-lg bg-rose-100 text-rose-700 border border-rose-200 shrink-0 flex items-center justify-center">
            <span class="material-symbols-outlined text-[18px]">warning</span>
          </div>
          <div>
            <div class="flex flex-wrap items-baseline gap-2">
              <span class="font-bold text-xs text-rose-950">{{ __('room.dashboard.debt_warning_title') }}</span>
              <span class="font-bold font-mono text-sm sm:text-base text-rose-700">{{ \App\Support\Helpers\FormatHelper::formatCurrency($unpaidDebts) }}</span>
            </div>
            <span class="text-[11px] text-rose-700">{{ __('room.dashboard.debt_warning_hint') }}</span>
          </div>
        </div>
        <a href="{{ route('user.debts.index', $room->slug) }}" class="inline-flex items-center justify-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs transition-colors shadow-2xs shrink-0 cursor-pointer">
          <span class="material-symbols-outlined text-[16px]">qr_code_2</span>
          <span>{{ __('room.dashboard.debt_warning_btn') }}</span>
        </a>
      </div>
    @endif

    <!-- 3. Main Grid Layout: Left Column (Active Campaign) & Right Column (Overview Summary) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
      
      <!-- Live Campaign and Top Items -->
      <div class="lg:col-span-12">
        @if($activeCampaign)
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-stretch">
          <!-- Top 5 Live Items -->
          <section class="lg:col-span-5 bg-white border border-slate-200/80 rounded-xl p-4 shadow-2xs flex flex-col justify-between h-full">
            <div>
              <div class="flex items-center gap-2 pb-2.5 border-b border-slate-100 mb-2.5">
                <span class="material-symbols-outlined text-[#006948] text-[18px]" aria-hidden="true">leaderboard</span>
                <h3 class="text-[11px] font-bold uppercase tracking-wider text-slate-700">{{ __('room.dashboard.top_live_items') }}</h3>
              </div>
              @forelse($activeCampaign['popular_items'] as $index => $item)
                <div class="flex items-center justify-between gap-3 py-2 {{ $loop->last ? '' : 'border-b border-slate-100' }}">
                  <div class="flex items-center gap-2.5 min-w-0">
                    <x-room.rank-medal :rank="$index + 1" />
                    <span class="text-xs font-semibold text-slate-800 truncate">{{ $item['name'] }}</span>
                  </div>
                  <span class="text-xs font-semibold text-[#006948] whitespace-nowrap">({{ $item['quantity'] }})</span>
                </div>
              @empty
                <div class="py-10 flex flex-col items-center justify-center text-center gap-2 text-slate-400 my-auto">
                  <span class="material-symbols-outlined text-[26px] text-slate-300" aria-hidden="true">no_meals</span>
                  <p class="text-xs">{{ __('room.dashboard.no_live_item_data') }}</p>
                </div>
              @endforelse
            </div>
          </section>

          <!-- Active Campaign Hero Card -->
          <div class="lg:col-span-7 bg-white border border-slate-200/80 rounded-xl shadow-2xs p-4 sm:p-5 relative overflow-hidden flex flex-col justify-between h-full">
            <!-- Background Ambient Glow -->
            <div class="absolute -right-16 -top-16 w-56 h-56 rounded-full bg-emerald-50 pointer-events-none blur-2xl"></div>

            <div class="relative z-10 space-y-4">
              <!-- Campaign Header Info -->
              <div class="flex flex-col-reverse items-start gap-3 sm:flex-row sm:justify-between">
                <div class="flex w-full min-w-0 flex-1 items-start gap-3">
                  <div class="w-10 h-10 rounded-xl bg-emerald-50 text-[#006948] border border-emerald-100 flex items-center justify-center shrink-0 shadow-2xs">
                    <span class="material-symbols-outlined text-[20px]">local_cafe</span>
                  </div>
                  <div class="min-w-0">
                    <div class="flex items-center gap-1.5">
                      <span class="inline-flex items-center gap-1 whitespace-nowrap px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold uppercase tracking-wider border border-emerald-200/70">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
                        {{ __('room.dashboard.active_campaign_badge') }}
                      </span>
                    </div>
                    <h2 class="text-sm sm:text-base font-bold text-slate-900 tracking-tight mt-0.5 break-words">{{ $activeCampaign['name'] }}</h2>
                    <p class="text-[11px] text-slate-500 flex flex-wrap items-center gap-x-1 gap-y-0.5 mt-0.5">
                      <span class="material-symbols-outlined text-[13px] text-[#006948]">storefront</span>
                      <span class="font-semibold text-slate-800">{{ $activeCampaign['restaurant'] }}</span>
                      <span class="text-slate-300">•</span>
                      <span>{{ __('room.dashboard.created_by', ['name' => $activeCampaign['creator_name']]) }}</span>
                    </p>
                  </div>
                </div>

                <!-- Countdown Timer Pill -->
                @php
                  $dashTimeRemaining = $activeCampaign['time_remaining'] ?? '';
                  $dashHasExpired = ($activeCampaign['has_expired'] ?? false) || $dashTimeRemaining === '00:00' || $dashTimeRemaining === '00:00:00' || empty($dashTimeRemaining);
                @endphp
                <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg {{ $dashHasExpired ? 'bg-slate-100 text-slate-600 border border-slate-200' : 'bg-rose-50 text-rose-700 border border-rose-200/80' }} font-mono text-xs font-bold shrink-0 whitespace-nowrap">
                  <span class="material-symbols-outlined text-[14px] {{ $dashHasExpired ? '' : 'animate-pulse' }}">schedule</span>
                  <span>{{ $dashHasExpired ? __('room.header.countdown_closed') : $dashTimeRemaining }}</span>
                </div>
              </div>

              <!-- Sponsor Budget Info Box -->
              <div class="bg-slate-50/80 border border-slate-100 rounded-lg p-3 space-y-1.5">
                <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 text-xs">
                  <div class="flex flex-wrap items-center gap-1.5 text-slate-600">
                    <span class="material-symbols-outlined text-[15px] text-[#006948]">savings</span>
                    <span class="font-medium">{{ __('room.dashboard.sponsor_budget_title') }}</span>
                    <span class="font-bold font-mono text-slate-900">{{ \App\Support\Helpers\FormatHelper::formatCurrency($activeCampaign['sponsor_budget']) }}</span>
                  </div>
                  <div class="text-[11px] font-semibold text-[#006948]">
                    {{ __('room.dashboard.sponsor_remaining', ['amount' => \App\Support\Helpers\FormatHelper::formatCurrency($activeCampaign['sponsor_remaining'])]) }}
                  </div>
                </div>
                <!-- Progress Bar -->
                <div class="w-full bg-slate-200 h-1.5 rounded-full overflow-hidden">
                  <div class="bg-[#006948] h-full rounded-full transition-all duration-300" style="width: {{ $activeCampaign['sponsor_percent'] }}%"></div>
                </div>
                <div class="flex items-center justify-between text-[10px] text-slate-400">
                  <span>{{ __('room.dashboard.sponsor_used', ['amount' => \App\Support\Helpers\FormatHelper::formatCurrency($activeCampaign['sponsor_used']), 'percent' => $activeCampaign['sponsor_percent']]) }}</span>
                  <span>{{ __('room.dashboard.sponsor_limit', ['amount' => '20.000đ']) }}</span>
                </div>
              </div>

              <!-- Campaign CTA Buttons -->
              <div class="grid grid-cols-1 {{ ($activeCampaign['has_ordered'] ?? false) ? 'sm:grid-cols-2' : '' }} gap-2">
                <a href="{{ route('user.campaigns.index', $room->slug) }}" class="h-9 bg-[#006948] hover:bg-[#005137] text-white rounded-lg text-xs font-semibold flex items-center justify-center gap-1.5 transition-colors shadow-2xs cursor-pointer">
                  <span class="material-symbols-outlined text-[16px]">restaurant_menu</span>
                  <span>{{ __('room.dashboard.enter_campaign') }}</span>
                </a>
                {{-- Only members who already ordered in this campaign see their placed orders. --}}
                @if($activeCampaign['has_ordered'] ?? false)
                  <a href="{{ route('user.orders.index', $room->slug) }}" class="h-9 bg-white hover:bg-slate-50 border border-[#006948] text-[#006948] rounded-lg text-xs font-semibold flex items-center justify-center gap-1.5 transition-colors cursor-pointer">
                    <span class="material-symbols-outlined text-[16px]">receipt_long</span>
                    <span>{{ __('room.dashboard.placed_orders') }}</span>
                  </a>
                @endif
              </div>
            </div>
          </div>
          </div>

        @else
          <!-- No Active Campaign State -->
          <div class="bg-white border border-slate-200/80 rounded-xl p-6 shadow-2xs text-center lg:min-h-64">
            <div class="w-12 h-12 rounded-xl bg-slate-50 text-slate-400 border border-slate-200 mx-auto flex items-center justify-center mb-2.5">
              <span class="material-symbols-outlined text-[24px]">bedtime</span>
            </div>
            <h3 class="text-sm font-bold text-slate-900">{{ __('room.dashboard.no_active_campaign_title') }}</h3>
            <p class="text-xs text-slate-500 max-w-md mx-auto mt-1 leading-relaxed">
              {{ __('room.dashboard.no_active_campaign_desc') }}
            </p>
          </div>
        @endif
      </div>

      <!-- 4. Sponsor Leaderboard & Weekly Trend Charts -->
      <div class="lg:col-span-12 grid grid-cols-1 lg:grid-cols-12 gap-4"
        data-room-dashboard-charts
        data-top-sponsors="{{ \Illuminate\Support\Js::from($topSponsors ?? []) }}"
        data-weekly-trend="{{ \Illuminate\Support\Js::from($weeklyItemTrend ?? []) }}"
        data-chart-labels="{{ \Illuminate\Support\Js::from([
          'items' => __('room.dashboard.chart_items_label'),
          'value' => __('room.dashboard.chart_value_label'),
          'sponsoredCampaigns' => __('room.dashboard.sponsored_campaigns_count'),
          'noSponsorData' => __('room.dashboard.no_sponsor_data'),
          'noTrendData' => __('room.dashboard.no_weekly_trend_data'),
        ]) }}"
      >
        <!-- Top Sponsors Bar Chart -->
        <section class="lg:col-span-5 bg-white border border-slate-200/80 rounded-xl p-4 shadow-2xs flex flex-col">
          <div class="flex items-center gap-2 pb-2.5 border-b border-slate-100 mb-2.5">
            <span class="material-symbols-outlined text-[#006948] text-[18px]" aria-hidden="true">military_tech</span>
            <div class="min-w-0">
              <h3 class="text-[11px] font-bold uppercase tracking-wider text-slate-700">{{ __('room.dashboard.top_sponsors_title') }}</h3>
              <p class="text-[10px] text-slate-400 truncate">{{ __('room.dashboard.top_sponsors_subtitle') }}</p>
            </div>
          </div>
          <div id="top-sponsors-chart" class="flex-1 min-h-[200px]"></div>
        </section>

        <!-- Weekly Items & Value Combo Chart -->
        <section class="lg:col-span-7 bg-white border border-slate-200/80 rounded-xl p-4 shadow-2xs flex flex-col">
          <div class="flex items-center justify-between gap-2 pb-2.5 border-b border-slate-100 mb-2.5">
            <div class="flex items-center gap-2 min-w-0">
              <span class="material-symbols-outlined text-[#006948] text-[18px]" aria-hidden="true">show_chart</span>
              <div class="min-w-0">
                <h3 class="text-[11px] font-bold uppercase tracking-wider text-slate-700">{{ __('room.dashboard.weekly_trend_title') }}</h3>
                <p class="text-[10px] text-slate-400 truncate">{{ __('room.dashboard.weekly_trend_subtitle') }}</p>
              </div>
            </div>
            <div class="flex items-center gap-3 text-[10px] text-slate-500 shrink-0">
              <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-[#006948]"></span>{{ __('room.dashboard.chart_items_label') }}</span>
              <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>{{ __('room.dashboard.chart_value_label') }}</span>
            </div>
          </div>
          <div id="weekly-trend-chart" class="flex-1 min-h-[200px]"></div>
        </section>
      </div>

    </div>
  </div>
</x-room.layout>

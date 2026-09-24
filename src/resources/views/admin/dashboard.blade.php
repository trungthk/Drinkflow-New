<x-admin.layout :title="__('admin.dashboard')" active="dashboard" :room="$room">
    <div
        data-admin-dashboard
        data-dashboard-url="{{ route('admin.dashboard', $room) }}"
        data-token-url="{{ route('admin.socket-token', $room) }}"
        data-realtime-url="{{ rtrim(config('services.realtime.public_url', 'http://localhost:3001'), '/') }}"
        data-time-expired-text="{{ __('admin.time_expired') }}"
        data-no-deadline-text="{{ __('admin.no_deadline_set') }}"
        data-opened-at-text="{{ __('admin.dashboard_opened_at') }}"
        data-today-text="{{ __('admin.dashboard_today') }}"
        data-across-members-text="{{ __('admin.across_members', ['count' => ':count']) }}"
        data-pending-users-text="{{ __('admin.pending_users', ['count' => ':count']) }}"
        data-store-label-text="{{ __('admin.dashboard_store_label') }}"
        data-room-fund-text="{{ __('admin.dashboard_room_fund') }}"
        data-live-campaign-text="{{ __('admin.live_campaign_info') }}"
        data-secondary-campaign-text="{{ __('admin.dashboard_secondary_campaign') }}"
        data-chart-label-campaigns="{{ __('admin.chart_tooltip_campaigns') }}"
        data-chart-label-spending="{{ __('admin.chart_tooltip_spending') }}"
        data-chart-label-orders="{{ __('admin.chart_tooltip_orders') }}"
        data-chart-peak-label="{{ __('admin.chart_peak_label') }}"
        data-chart-no-data-text="{{ __('admin.no_weekly_data') }}"
        data-chart-summary-template="{{ __('admin.weekly_total_summary', ['campaigns' => ':campaigns', 'amount' => ':amount']) }}"
        class="space-y-6"
    >
        <!-- Page Header & Actions -->
        <div class="flex flex-wrap items-center justify-between gap-4 pb-2 border-b border-outline-variant/40">
            <div>
                <h1 class="text-2xl font-bold text-on-surface tracking-tight">{{ __('admin.dashboard') }} · {{ $room->name }}</h1>
            </div>
        </div>

        <!-- 5 Summary Metrics Grid -->
        <section id="metrics-grid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3.5">
            <!-- Metric 1: Active Rooms -->
            <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-4 flex flex-col justify-between shadow-2xs">
                <div class="flex items-center justify-between text-outline mb-1">
                    <span class="text-[10px] font-mono uppercase tracking-wider font-semibold">{{ __('admin.active_rooms') }}</span>
                    <span class="material-symbols-outlined text-[18px]">meeting_room</span>
                </div>
                <div id="metric-active-rooms" class="text-2xl font-bold text-on-surface">{{ $activeRoomsCount ?? 1 }}</div>
                <div id="metric-rooms-hint" class="text-[11px] text-outline mt-1 font-mono">{{ __('admin.across_members', ['count' => $activeRoomUsers ?? $room->roomUsers()->where('status', 'active')->count()]) }}</div>
            </div>

            <!-- Metric 2: Live Campaigns -->
            <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-4 flex flex-col justify-between shadow-2xs">
                <div class="flex items-center justify-between text-outline mb-1">
                    <span class="text-[10px] font-mono uppercase tracking-wider font-semibold">{{ __('admin.live_campaigns') }}</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] whitespace-nowrap shrink-0 bg-error-container text-on-error-container font-bold">{{ __('admin.active_now') }}</span>
                </div>
                <div class="text-2xl font-bold text-error flex items-baseline gap-1.5">
                    <span id="metric-live-campaigns">{{ $activeCampaignsCount ?? 0 }}</span>
                    <span class="text-xs text-outline font-normal">{{ __('admin.campaign_runs_unit') }}</span>
                </div>
                <div id="metric-campaigns-hint" class="text-[11px] text-error mt-1 font-semibold flex items-center gap-1 font-mono">
                    <span class="w-1.5 h-1.5 rounded-full bg-error status-dot-pulse"></span>
                    <span id="metric-campaign-closing-text">{{ __('admin.closing_in', ['time' => '--:--']) }}</span>
                </div>
            </div>

            <!-- Metric 3: Today's Orders -->
            <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-4 flex flex-col justify-between shadow-2xs">
                <div class="flex items-center justify-between text-outline mb-1">
                    <span class="text-[10px] font-mono uppercase tracking-wider font-semibold">{{ __('admin.todays_orders') }}</span>
                    <span class="material-symbols-outlined text-[18px]">shopping_bag</span>
                </div>
                <div id="metric-orders-today" class="text-2xl font-bold text-on-surface">{{ $ordersTodayCount ?? 0 }}</div>
                <div id="metric-orders-growth" class="text-[11px] text-primary mt-1 font-semibold flex items-center gap-0.5 font-mono">
                    <span class="material-symbols-outlined text-[14px]">trending_up</span>
                    <span id="metric-orders-growth-val">+{{ $ordersGrowth ?? 0 }}% {{ __('admin.vs_yesterday') }}</span>
                </div>
            </div>

            <!-- Metric 4: Total Value Today -->
            <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-4 flex flex-col justify-between shadow-2xs">
                <div class="flex items-center justify-between text-outline mb-1">
                    <span class="text-[10px] font-mono uppercase tracking-wider font-semibold">{{ __('admin.total_value_today') }}</span>
                    <span class="material-symbols-outlined text-[18px]">attach_money</span>
                </div>
                <div id="metric-total-value" class="text-2xl font-bold text-on-surface truncate">{{ \App\Support\Helpers\FormatHelper::formatCurrency($todayTotalValue ?? 0) }}</div>
                <div id="metric-sponsors-val" class="text-[11px] text-outline mt-1 font-mono">{{ __('admin.sponsors_today', ['amount' => \App\Support\Helpers\FormatHelper::formatCurrency($todaySponsorValue ?? 0)]) }}</div>
            </div>

            <!-- Metric 5: Unpaid Debt -->
            <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-4 flex flex-col justify-between shadow-2xs">
                <div class="flex items-center justify-between text-outline mb-1">
                    <span class="text-[10px] font-mono uppercase tracking-wider font-semibold">{{ __('admin.unpaid_debt') }}</span>
                    <span class="px-1.5 py-0.5 rounded text-[9px] font-mono bg-amber-100 text-amber-800 font-bold border border-amber-200">{{ __('admin.needs_settlement') }}</span>
                </div>
                <div id="metric-unpaid-debt" class="text-2xl font-bold text-amber-700 truncate">{{ \App\Support\Helpers\FormatHelper::formatCurrency($outstandingDebtsTotal ?? 0) }}</div>
                <div id="metric-debt-users" class="text-[11px] text-amber-700 mt-1 font-semibold font-mono">{{ __('admin.pending_users', ['count' => $pendingDebtUsersCount ?? 0]) }}</div>
            </div>
        </section>

        <!-- Weekly Trend Chart Section -->
        <section class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 shadow-sm space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-outline-variant/60">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px] text-primary">monitoring</span>
                        <h2 class="text-base font-bold text-on-surface tracking-tight">{{ __('admin.weekly_trend_title') }}</h2>
                    </div>
                    <p class="text-xs text-outline mt-0.5">{{ __('admin.weekly_trend_desc') }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-4">
                    <div class="px-2.5 py-1 bg-surface-container-low rounded border border-outline-variant/60 text-xs font-mono font-semibold text-primary flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                        <span id="chart-summary-badge">{{ __('admin.weekly_total_summary', ['campaigns' => $totalWeekCampaigns ?? 0, 'amount' => \App\Support\Helpers\FormatHelper::formatCurrency($totalWeekSpending ?? 0)]) }}</span>
                    </div>
                </div>
            </div>

            <!-- SVG Trend Chart Container -->
            <div class="relative w-full overflow-x-auto">
                <div class="min-w-[680px]">
                    <div class="flex justify-between items-center text-[10px] font-mono text-outline px-1 pb-1">
                        <span>{{ __('admin.axis_left_campaigns') }}</span>
                        <span>{{ __('admin.axis_right_spending') }}</span>
                    </div>
                    <div class="relative h-60 w-full" id="svg-chart-wrapper">
                        <!-- Dynamic SVG chart injected by script -->
                        <div class="h-full flex items-center justify-center text-outline text-xs font-mono">{{ __('admin.no_weekly_data') }}</div>
                    </div>
                    <div id="chart-day-labels" class="relative h-10 pt-2 border-t border-outline-variant/60 ml-[45px] mr-[45px]"></div>
                </div>
            </div>
            <div id="chart-legend" class="flex flex-wrap items-center justify-center gap-4 text-xs font-mono">
                <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded bg-primary inline-block"></span>
                    <span class="text-on-surface-variant">{{ __('admin.campaign_count_bar') }}</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-3 h-1 bg-[#2563eb] rounded-full inline-block"></span>
                    <span class="w-2 h-2 rounded-full border-2 border-[#2563eb] bg-white inline-block -ml-2"></span>
                    <span class="text-on-surface-variant">{{ __('admin.spending_vnd_line') }}</span>
                </div>
            </div>
        </section>

        <!-- Campaign Control Panels (Shown only when active campaign exists) -->
        <div id="campaign-panels-container" class="grid grid-cols-1 lg:grid-cols-3 gap-4 {{ ($activeCampaign ?? null) ? '' : 'hidden' }}">
            <!-- Primary Live Campaign Card -->
            <div id="hero-campaign-card" class="{{ ($secondaryCampaign ?? null) ? 'lg:col-span-2' : 'lg:col-span-3' }} bg-surface-container-lowest border-2 border-primary/40 rounded-xl p-5 flex flex-col justify-between relative overflow-hidden shadow-sm">
                <div class="absolute top-0 left-0 right-0 h-1 bg-primary"></div>
                <div>
                    <!-- Header Row -->
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                        <div class="flex items-center gap-2">
                            @if ($activeCampaign ?? null)
                                <x-admin.campaign-status-badge :campaign="$activeCampaign" />
                            @else
                                <span class="flex items-center gap-1.5 px-2.5 py-0.5 bg-emerald-50 text-emerald-700 rounded-full text-[11px] font-mono font-bold border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 status-dot-pulse"></span>
                                    {{ __('admin.live_now') }}
                                </span>
                            @endif
                            <h3 id="hero-campaign-title" class="text-lg font-bold text-on-surface">{{ $activeCampaign?->name ?? __('admin.loading_campaign') }}</h3>
                            <span id="hero-campaign-code" class="text-xs font-mono font-code text-outline">{{ $activeCampaign?->code ?? 'N/A' }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-xs font-mono text-outline">
                            <span id="hero-campaign-time">{{ __('admin.ready') }}</span>
                        </div>
                    </div>

                    <!-- Countdown Timer & Participation Banner -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 p-3 bg-surface rounded-lg border border-outline-variant mb-4">
                        <!-- Digital Countdown -->
                        <div class="flex flex-col justify-center border-r-0 md:border-r border-outline-variant pr-2">
                            <span class="text-[10px] font-mono text-outline uppercase font-semibold">{{ __('admin.time_remaining_lock') }}</span>
                            <div id="hero-timer" class="text-2xl font-mono font-bold text-error tracking-widest mt-0.5">{{ $activeCampaign?->deadline?->isPast() ? __('admin.time_expired') : '--:--:--' }}</div>
                        </div>
                        <!-- Participation Progress -->
                        <div class="flex flex-col justify-center border-r-0 md:border-r border-outline-variant pr-2">
                            <div class="flex justify-between text-xs font-mono mb-1">
                                <span class="text-outline">{{ __('admin.orders_placed') }}</span>
                                <span id="hero-participation-text" class="font-bold text-on-surface">0 / {{ $room->roomUsers()->where('status', 'active')->count() }}</span>
                            </div>
                            <div class="w-full bg-surface-container h-2.5 rounded-full overflow-hidden">
                                <div id="hero-progress-bar" class="bg-primary h-full rounded-full transition-all duration-500" style="width: 0%"></div>
                            </div>
                        </div>
                        <!-- Net Summary -->
                        <div class="flex flex-col justify-center pl-1">
                            <span class="text-[10px] font-mono text-outline uppercase font-semibold">{{ __('admin.net_payable') }}</span>
                            <span id="hero-net-payable" class="text-lg font-bold text-primary">0đ</span>
                            <span id="hero-gross-subtotal" class="text-[10px] font-mono text-outline">{{ __('admin.gross_subtotal') }}: 0đ</span>
                        </div>
                    </div>

                    <!-- Multi-Sponsor Contribution Row -->
                    <div class="bg-surface-container-low p-2.5 rounded border border-outline-variant/60 flex flex-wrap items-center justify-between text-xs gap-2 mb-4">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[16px] text-tertiary">volunteer_activism</span>
                            <span class="text-on-surface-variant font-medium">{{ __('admin.multi_sponsor_title') }}</span>
                            <strong id="hero-sponsor-total" class="font-bold text-tertiary font-mono">-0đ</strong>
                        </div>
                        <div id="hero-sponsor-names" class="flex items-center gap-3 text-outline text-[11px] font-mono">
                            <span>{{ __('admin.loading_sponsors') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Bottom Action Buttons -->
                <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-outline-variant">
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('admin.orders.page', $room) }}" class="bg-primary hover:bg-primary-container text-on-primary px-4 py-2 rounded-lg text-xs font-semibold transition-colors flex items-center gap-1.5 shadow-xs no-underline">
                            <span class="material-symbols-outlined text-[16px]">checklist</span>
                            <span>{{ __('admin.view_orders_adjust') }}</span>
                        </a>
                        <a data-adjust-campaign-link
                            href="{{ ($activeCampaign ?? null) ? route('admin.campaigns.info', [$room, $activeCampaign]) : route('admin.campaigns.page', $room) }}"
                            class="bg-surface-container-low hover:bg-surface-container border border-outline-variant text-on-surface px-4 py-2 rounded-lg text-xs font-semibold transition-colors flex items-center gap-1.5 shadow-xs no-underline">
                            <span class="material-symbols-outlined text-[16px]">tune</span>
                            <span>{{ __('admin.adjust_campaign') }}</span>
                        </a>
                    </div>
                    <button type="button" id="btn-open-close-modal" data-close-campaign-open data-campaign-id="{{ ($activeCampaign ?? null)?->id }}" class="text-error hover:bg-error-container/60 border border-error/30 rounded-lg px-3 py-2 text-xs font-semibold transition-colors flex items-center gap-1 cursor-pointer">
                        <span class="material-symbols-outlined text-[16px]">lock_clock</span>
                        <span>{{ __('admin.close_campaign_early') }}</span>
                    </button>
                </div>
            </div>

            <!-- Secondary Live Campaign Quick Card -->
            <div id="secondary-campaign-card" class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 flex flex-col justify-between shadow-2xs {{ ($secondaryCampaign ?? null) ? '' : 'hidden' }}">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="flex items-center gap-1 text-[11px] font-mono text-primary font-bold">
                            <span class="w-2 h-2 rounded-full bg-primary"></span>
                            {{ __('admin.running_secondary') }}
                        </span>
                        <span id="sec-campaign-code" class="text-xs font-mono font-code text-outline">{{ $secondaryCampaign?->code ?? 'N/A' }}</span>
                    </div>
                    <h3 id="sec-campaign-title" class="text-base font-bold text-on-surface mb-1 truncate">{{ $secondaryCampaign?->name ?? __('admin.no_secondary_campaign') }}</h3>
                    <p id="sec-campaign-vendor" class="text-xs text-outline mb-4 font-mono">{{ $secondaryCampaign?->restaurant ?? '—' }}</p>
                    <div class="space-y-3 p-3 bg-surface rounded-lg border border-outline-variant mb-4 font-mono text-xs">
                        <div class="flex justify-between items-center">
                            <span class="text-outline">{{ __('admin.deadline_label') }}</span>
                            <span id="sec-campaign-deadline" class="text-on-surface font-bold">--:--</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-outline">{{ __('admin.orders_count_label') }}</span>
                            <span id="sec-campaign-orders" class="text-on-surface font-semibold">0</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-outline">{{ __('admin.subtotal_label') }}</span>
                            <span id="sec-campaign-subtotal" class="text-primary font-bold">0đ</span>
                        </div>
                    </div>
                </div>
                <div class="pt-2 border-t border-outline-variant flex items-center justify-between">
                    <span id="sec-campaign-arrival" class="text-[11px] font-mono text-outline">{{ __('admin.target_arrival', ['time' => '--:--']) }}</span>
                    <a href="{{ route('admin.campaigns.page', $room) }}" class="bg-surface-container-low hover:bg-surface-container border border-outline-variant text-on-surface px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors flex items-center gap-1 no-underline">
                        <span>{{ __('admin.manage') }}</span>
                        <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Live Stream Activity & Orders Table -->
        <div id="orders-stream-container" class="grid grid-cols-1 {{ count($liveOrders ?? []) > 0 ? 'xl:grid-cols-[1.6fr_.9fr]' : '' }} gap-4">
            <!-- Left: Recent Orders Table -->
            <section id="recent-orders-section" class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 shadow-2xs space-y-4 {{ count($liveOrders ?? []) > 0 ? '' : 'hidden' }}">
                <div class="flex items-center justify-between pb-3 border-b border-outline-variant">
                    <div>
                        <h3 class="text-base font-bold text-on-surface">{{ __('admin.recent_orders_title') }}</h3>
                        <p class="text-xs text-outline mt-0.5">{{ __('admin.recent_orders_desc') }}</p>
                    </div>
                    <a href="{{ route('admin.orders.page', $room) }}" class="text-xs font-semibold text-primary hover:underline flex items-center gap-1">
                        <span>{{ __('admin.open_orders_list') }}</span>
                        <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="table-colgroup w-full min-w-[36rem] table-fixed text-xs text-left">
                        <colgroup>
                            <col class="w-28">
                            <col class="w-40">
                            <col>
                            <col class="w-32">
                            <col class="w-32">
                        </colgroup>
                        <thead>
                            <tr class="border-b border-outline-variant/60 text-outline uppercase font-mono text-[10px]">
                                <th class="py-2.5 px-3">{{ __('admin.order_code') }}</th>
                                <th class="py-2.5 px-3">{{ __('admin.placed_by') }}</th>
                                <th class="py-2.5 px-3">{{ __('admin.campaigns') }}</th>
                                <th class="py-2.5 px-3 text-right">{{ __('admin.net_payable') }}</th>
                                <th class="py-2.5 px-3 text-center">{{ __('admin.order_status') }}</th>
                            </tr>
                        </thead>
                        <tbody id="orders-tbody" class="divide-y divide-outline-variant/30">
                            @if(isset($liveOrders) && $liveOrders instanceof \Illuminate\Support\Collection)
                                @foreach($liveOrders as $order)
                                    <tr class="hover:bg-surface-container-low/50 transition-colors">
                                        <td class="py-2.5 px-3 font-mono font-code text-outline">#{{ $order->code }}</td>
                                        <td class="py-2.5 px-3 font-semibold text-on-surface">{{ $order->roomUser?->globalUser?->name ?? $order->roomUser?->display_name ?? __('global.common.member') }}</td>
                                        <td class="py-2.5 px-3 text-outline">{{ $order->campaign?->name ?? __('global.common.campaign') }}</td>
                                        <td class="py-2.5 px-3 text-right font-mono font-bold text-on-surface">{{ \App\Support\Helpers\FormatHelper::formatCurrency($order->final_amount ?? 0) }}</td>
                                        <td class="py-2.5 px-3 text-center">
                                            <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-mono font-semibold {{ ($order->status instanceof \BackedEnum ? $order->status->value : $order->status) === 'confirmed' ? 'bg-emerald-50 text-emerald-700' : (($order->status instanceof \BackedEnum ? $order->status->value : $order->status) === 'cancelled' ? 'bg-rose-50 text-rose-700' : 'bg-blue-50 text-blue-700') }}">
                                                {{ $order->status instanceof \BackedEnum ? $order->status->value : ($order->status ?? 'pending') }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </section>

            @if(false)
            <!-- Right: Realtime Stream Feed & VietQR Reconciliation -->
            <div id="side-stream-container" class="{{ count($liveOrders ?? []) > 0 ? 'space-y-4' : 'grid grid-cols-1 md:grid-cols-2 gap-4 space-y-0' }}">
                <!-- VietQR Card -->
                <section class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 shadow-2xs">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-[20px]">qr_code_2</span>
                            <h3 class="text-sm font-bold text-on-surface">{{ __('admin.room_vietqr_account') }}</h3>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-mono bg-emerald-50 text-emerald-700 font-bold border border-emerald-200">Auto Split Bill</span>
                    </div>
                    <div class="flex items-center gap-3 p-3 rounded-lg bg-surface-container-low border border-outline-variant/60">
                        <span class="material-symbols-outlined text-2xl text-slate-700 p-2 bg-white rounded border border-outline-variant">account_balance</span>
                        <div>
                            <div id="payment-bank-name" class="text-xs font-bold text-on-surface">
                                {{ $activePaymentAccount ? ($activePaymentAccount->bank_name ?: $activePaymentAccount->bank_code) . ' (' . ($activePaymentAccount->account_name ?: 'Quỹ phòng') . ')' : __('admin.not_configured') }}
                            </div>
                            <div id="payment-account-masked" class="text-[11px] font-mono text-outline mt-0.5">
                                {{ $activePaymentAccount ? $activePaymentAccount->account_number_masked : '•••• •••• ••••' }}
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('admin.settings.page', $room) }}" class="mt-3 block w-full text-center py-2 bg-surface-container hover:bg-surface-container-high text-on-surface rounded-lg text-xs font-semibold transition-colors no-underline">
                        {{ __('admin.payments_settings') }}
                    </a>
                </section>

                <!-- Realtime Activity Log -->
                <section class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 shadow-2xs">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <h3 class="text-sm font-bold text-on-surface">{{ __('admin.live_event_stream') }}</h3>
                        </div>
                        <span class="text-[10px] font-mono text-outline">{{ __('admin.socket_room_stream') }}</span>
                    </div>
                    <div id="activity-stream" class="space-y-2 max-h-56 overflow-y-auto">
                        <div class="p-2.5 rounded bg-surface-container-low text-xs flex items-center justify-between">
                            <span class="text-on-surface-variant font-medium">{{ __('admin.initializing_socket') }}</span>
                            <span class="text-[10px] font-mono text-outline">{{ __('admin.ready') }}</span>
                        </div>
                    </div>
                </section>
            </div>
            @endif
        </div>

        <!-- Close Campaign Modal (shared with the campaign info page) -->
        <x-admin.close-campaign-modal :room="$room" />
    </div>
</x-admin.layout>

<x-admin.layout :title="$campaign->name . ' · ' . __('admin.live_now')" active="campaigns" :room="$room">
@php
    $summaryText = "🛒 " . $campaign->name . "\n" .
        $campaign->restaurant . "\n" .
        "-----------------------------------\n" .
        $aggregatedItems->map(fn($agg) => "• " . $agg['quantity'] . "x " . $agg['name'] . ($agg['size'] ? " (" . $agg['size'] . ")" : "") . " - " . number_format($agg['total_amount'], 0, ',', '.') . "đ" . ($agg['notes']->isNotEmpty() ? "\n   Note: " . $agg['notes']->join(", ") : ""))->join("\n") .
        "\n-----------------------------------\n" .
        __('admin.gross_subtotal') . ": " . number_format($campaign->orders->sum('subtotal'), 0, ',', '.') . " ₫";
@endphp
<div class="max-w-[1600px] mx-auto space-y-6" x-data="liveCampaignComponent({
    roomSlug: '{{ $room->slug }}',
    campaignId: {{ $campaign->id }},
    deadline: '{{ $campaign->deadline ?? '' }}',
    totalUsers: {{ $totalUsersCount }},
    orderedUsers: {{ $orderedUsersCount }},
    declinedUsers: {{ $declinedUsersCount }},
    pendingUsers: {{ $pendingUsersCount }},
    summaryText: {{ json_encode($summaryText) }}
})">
    <!-- Breadcrumb & Top Bar -->
    <div class="flex items-center justify-between gap-4 pb-2 border-b border-outline-variant/60">
        <div class="flex items-center gap-2 text-xs font-mono text-outline">
            <a href="{{ route('admin.dashboard.page', $room) }}" class="hover:text-on-surface">Admin</a>
            <span>/</span>
            <a href="{{ route('admin.campaigns.page', $room) }}" class="hover:text-on-surface">{{ __('admin.campaigns') }}</a>
            <span>/</span>
            <span class="text-on-surface font-semibold">#CMP-{{ $campaign->id }} - {{ __('admin.live_control_center') }}</span>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.campaigns.show', [$room, $campaign, 'view' => 'detail']) }}" class="px-3 py-1.5 rounded-lg border border-outline-variant text-xs font-semibold text-on-surface hover:bg-surface-container-low transition-colors flex items-center gap-1.5 no-underline">
                <span class="material-symbols-outlined text-[16px]">receipt_long</span>
                <span>{{ __('admin.view_settlement_page') }}</span>
            </a>
        </div>
    </div>

    <!-- SECTION 1: Page Header & Live Countdown Action Strip -->
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 shadow-sm space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-outline-variant">
            <div class="space-y-1.5">
                <div class="flex items-center gap-2.5 flex-wrap">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-error-container text-error border border-error text-[11px] font-mono font-bold tracking-wide">
                        <span class="w-2 h-2 rounded-full bg-error animate-pulse"></span>
                        {{ __('admin.live_now') }}
                    </span>
                    <h1 class="text-xl sm:text-2xl font-bold text-on-surface tracking-tight">{{ $campaign->name }}</h1>
                    <span class="text-xs bg-surface-container px-2 py-0.5 rounded font-mono font-medium text-secondary">#CMP-{{ $campaign->id }}</span>
                    <span class="text-xs bg-surface-container-low border border-outline-variant px-2 py-0.5 rounded text-on-surface-variant font-medium">{{ $campaign->restaurant }}</span>
                </div>
                <div class="flex items-center gap-4 text-xs text-on-surface-variant flex-wrap">
                    <span class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-primary text-[16px]">schedule</span>
                        {{ __('admin.created_time_label') }} <strong class="text-on-surface font-semibold font-mono">{{ $campaign->created_at->format('H:i, d/m/Y') }}</strong>
                    </span>
                    <span class="text-outline-variant">•</span>
                    <span class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-error text-[16px]">lock_clock</span>
                        {{ __('admin.deadline_label') }} <strong class="text-error font-bold font-mono">{{ $campaign->deadline ? \Carbon\Carbon::parse($campaign->deadline)->format('H:i d/m/Y') : __('admin.unlimited_time') }}</strong>
                    </span>
                    <span class="text-outline-variant">•</span>
                    <span class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-secondary text-[16px]">groups</span>
                        {{ __('admin.room_label') }} <strong class="text-on-surface font-medium">{{ $room->name }}</strong>
                    </span>
                </div>
            </div>

            <!-- Action Buttons Strip -->
            <div class="flex items-center gap-2 flex-wrap lg:justify-end">
                <!-- Countdown Box -->
                <div class="flex items-center gap-2 px-3 py-1.5 bg-surface-container rounded-lg border border-outline-variant shadow-inner">
                    <span class="material-symbols-outlined text-error text-[18px]">timer</span>
                    <div class="flex flex-col">
                        <span class="text-[9px] uppercase tracking-wider text-outline font-semibold leading-tight">{{ __('admin.time_left_label') }}</span>
                        <span class="text-sm font-bold text-error tracking-widest font-mono leading-none" x-text="countdownText">00:00:00</span>
                    </div>
                </div>

                <!-- Extend Time Dropdown -->
                <div class="relative" x-data="{ open: false }">
                    <button type="button" @click="open = !open" class="px-3 py-2 bg-surface-container-lowest text-primary border border-primary/40 hover:border-primary hover:bg-surface-container-low rounded-lg text-xs font-semibold transition-colors flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px]">more_time</span>
                        <span>{{ __('admin.extend_time_btn') }}</span>
                        <span class="material-symbols-outlined text-[14px]">arrow_drop_down</span>
                    </button>
                    <div x-show="open" @click.away="open = false" x-cloak class="absolute right-0 top-full mt-1.5 w-44 bg-surface-container-lowest border border-outline-variant rounded-lg shadow-lg z-30 py-1 flex flex-col text-xs">
                        <button type="button" @click="extendDeadline(10); open = false" class="px-3 py-2 text-left hover:bg-surface-container-low flex items-center justify-between text-on-surface">
                            <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-primary text-[14px]">schedule</span>{{ __('admin.extend_10m') }}</span>
                        </button>
                        <button type="button" @click="extendDeadline(30); open = false" class="px-3 py-2 text-left hover:bg-surface-container-low flex items-center justify-between text-on-surface">
                            <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-primary text-[14px]">schedule</span>{{ __('admin.extend_30m') }}</span>
                        </button>
                        <button type="button" @click="extendDeadline(60); open = false" class="px-3 py-2 text-left hover:bg-surface-container-low flex items-center justify-between text-on-surface">
                            <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-primary text-[14px]">schedule</span>{{ __('admin.extend_60m') }}</span>
                        </button>
                    </div>
                </div>

                <!-- Close & Finalize Button -->
                <button type="button" @click="openCloseModal = true" class="px-3.5 py-2 bg-primary text-on-primary rounded-lg text-xs font-semibold hover:bg-primary-container transition-colors flex items-center gap-1.5 shadow-sm">
                    <span class="material-symbols-outlined text-[16px]">task_alt</span>
                    <span>{{ __('admin.close_and_finalize_btn') }}</span>
                </button>

                <!-- Cancel Campaign Button -->
                <button type="button" @click="cancelCampaign()" class="px-3 py-2 bg-error-container/60 text-error border border-error/40 rounded-lg text-xs font-semibold hover:bg-error hover:text-on-error transition-colors flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">cancel</span>
                    <span>{{ __('admin.cancel_campaign_btn') }}</span>
                </button>
            </div>
        </div>

        <!-- Participation Progress Bar Strip -->
        <div class="flex flex-col lg:flex-row items-center justify-between gap-4 pt-1">
            <div class="flex items-center gap-5 flex-wrap w-full lg:w-auto text-xs">
                <div class="flex items-baseline gap-2">
                    <span class="text-outline">{{ __('admin.th_user_member') }}:</span>
                    <span class="font-bold text-on-surface font-mono" x-text="roomTotalUsers">{{ $totalUsersCount }}</span>
                </div>
                <div class="h-3.5 w-px bg-outline-variant hidden sm:block"></div>
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-primary inline-block"></span>
                    <span class="text-on-surface">{{ __('admin.ordered_legend', ['count' => $orderedUsersCount, 'percent' => round(($orderedUsersCount / max(1, $totalUsersCount)) * 100, 1)]) }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-secondary inline-block"></span>
                    <span class="text-on-surface-variant">{{ __('admin.declined_legend', ['count' => $declinedUsersCount, 'percent' => round(($declinedUsersCount / max(1, $totalUsersCount)) * 100, 1)]) }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-error inline-block"></span>
                    <span class="text-error">{{ __('admin.pending_legend', ['count' => $pendingUsersCount, 'percent' => round(($pendingUsersCount / max(1, $totalUsersCount)) * 100, 1)]) }}</span>
                </div>
            </div>

            <div class="flex items-center gap-4 w-full lg:w-auto justify-between lg:justify-end">
                <div class="w-48 sm:w-60 h-2.5 bg-surface-container rounded-full overflow-hidden flex border border-outline-variant">
                    <div class="h-full bg-primary transition-all duration-500" :style="'width: ' + orderedPct + '%'"></div>
                    <div class="h-full bg-secondary transition-all duration-500" :style="'width: ' + declinedPct + '%'"></div>
                    <div class="h-full bg-error transition-all duration-500" :style="'width: ' + pendingPct + '%'"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 2: Two-Column Live Operational Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
        <!-- Box 1: Kitchen Batch & Popular Items -->
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 shadow-sm flex flex-col justify-between h-full space-y-4">
            <div class="space-y-3">
                <div class="flex items-center justify-between pb-2.5 border-b border-outline-variant">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">kitchen</span>
                        <h3 class="text-sm font-bold text-on-surface uppercase tracking-wider">{{ __('admin.kitchen_batch_title') }}</h3>
                    </div>
                </div>

                <div class="divide-y divide-surface-container max-h-64 overflow-y-auto pr-1">
                    @forelse($aggregatedItems as $index => $agg)
                    <div class="py-2.5 flex items-center justify-between text-xs">
                        <div class="flex items-center gap-3">
                            <span class="w-5 h-5 rounded-full bg-primary/10 text-primary font-mono font-bold flex items-center justify-center text-[11px]">{{ $loop->iteration }}</span>
                            <div>
                                <div class="font-bold text-on-surface">{{ $agg['name'] }} @if($agg['size']) <span class="text-outline font-normal">({{ $agg['size'] }})</span> @endif</div>
                                @if($agg['notes']->isNotEmpty())
                                <div class="text-[10px] text-outline truncate max-w-xs italic">{{ $agg['notes']->join(', ') }}</div>
                                @endif
                            </div>
                        </div>
                        <div class="text-right font-mono">
                            <span class="font-bold text-primary block">SL: {{ $agg['quantity'] }}</span>
                            <span class="text-[11px] text-outline">{{ number_format($agg['total_amount'], 0, ',', '.') }} ₫</span>
                        </div>
                    </div>
                    @empty
                    <div class="py-8 text-center text-xs text-outline italic">{{ __('admin.no_orders_found') }}</div>
                    @endforelse
                </div>
            </div>

            <button type="button" @click="copyOrderSummary()" class="w-full py-2.5 px-3 bg-primary/10 border border-primary/40 text-primary hover:bg-primary/20 rounded-lg text-xs font-semibold flex items-center justify-center gap-2 transition-colors">
                <span class="material-symbols-outlined text-[16px]">content_copy</span>
                <span>{{ __('admin.copy_kitchen_summary') }}</span>
            </button>
        </div>

        <!-- Box 2: Financial Calculation & Split -->
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 shadow-sm flex flex-col justify-between h-full space-y-4">
            <div class="space-y-3">
                <div class="flex items-center justify-between pb-2.5 border-b border-outline-variant">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">calculate</span>
                        <h3 class="text-sm font-bold text-on-surface uppercase tracking-wider">{{ __('admin.financial_summary') }}</h3>
                    </div>
                </div>

                <div class="space-y-2 text-xs">
                    <div class="flex justify-between items-center text-outline">
                        <span>{{ __('admin.gross_subtotal') }}:</span>
                        <span class="font-mono font-bold text-on-surface">{{ number_format($campaign->orders->sum('subtotal'), 0, ',', '.') }} ₫</span>
                    </div>
                    <div class="flex justify-between items-center text-outline">
                        <span class="flex items-center gap-1">{{ __('admin.share_shipping_fee') }}:</span>
                        <span class="font-mono font-bold text-error">+{{ number_format($campaign->delivery_fee ?? 0, 0, ',', '.') }} ₫</span>
                    </div>
                    <div class="flex justify-between items-center text-outline">
                        <span>{{ __('admin.voucher_discount') }}:</span>
                        <span class="font-mono font-bold text-emerald-600">-{{ number_format($campaign->discount ?? 0, 0, ',', '.') }} ₫</span>
                    </div>
                    @if($campaign->sponsor_name)
                    <div class="bg-surface-container-low p-2.5 rounded-lg border border-outline-variant space-y-1">
                        <div class="flex justify-between items-center text-primary font-bold">
                            <span>{{ $campaign->sponsor_name }}:</span>
                            <span class="font-mono">-{{ number_format(min($campaign->max_budget ?? 99999999, $campaign->orders->sum('total_amount')), 0, ',', '.') }} ₫</span>
                        </div>
                    </div>
                    @endif
                    <div class="flex justify-between items-center pt-2 border-t border-outline-variant font-bold text-sm">
                        <span class="text-on-surface">{{ __('admin.net_payable') }}:</span>
                        <span class="font-mono text-primary text-base">{{ number_format($campaign->orders->sum('total_amount'), 0, ',', '.') }} ₫</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 pt-3 border-t border-outline-variant text-xs">
                <div class="p-3 rounded-lg bg-surface-container border border-outline-variant">
                    <span class="text-outline block">{{ __('admin.total_member_collected') }}:</span>
                    <span class="font-mono font-bold text-primary text-sm block mt-0.5">{{ number_format($campaign->orders->where('status', 'paid')->sum('total_amount'), 0, ',', '.') }} ₫</span>
                </div>
                <div class="p-3 rounded-lg bg-surface-container border border-outline-variant">
                    <span class="text-outline block">{{ __('admin.member_debt_remaining') }}:</span>
                    <span class="font-mono font-bold text-error text-sm block mt-0.5">{{ number_format($campaign->orders->whereNotIn('status', ['paid', 'cancelled'])->sum('total_amount'), 0, ',', '.') }} ₫</span>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 3: Real-time Incoming Orders Table -->
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-sm">
        <div class="p-4 border-b border-outline-variant flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">format_list_bulleted</span>
                <h2 class="text-sm font-bold text-on-surface uppercase tracking-wider">{{ __('admin.orders') }}</h2>
                <span class="bg-primary text-on-primary text-[11px] font-mono px-2 py-0.5 rounded font-bold">{{ __('admin.orders_unit', ['count' => $campaign->orders->whereNotIn('status', ['cancelled'])->count()]) }}</span>
            </div>

            <div class="flex items-center gap-3 w-full sm:w-auto">
                <input type="text" x-model="searchQuery" placeholder="{{ __('admin.search_orders_placeholder') }}" class="px-3 py-1.5 text-xs bg-surface border border-outline-variant rounded-lg focus:outline-none focus:border-primary w-full sm:w-64">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-surface border-b border-outline-variant text-[11px] font-mono text-outline uppercase tracking-wider">
                        <th class="py-2.5 px-3">{{ __('admin.th_order_code_user') }}</th>
                        <th class="py-2.5 px-3">{{ __('admin.placed_by') }}</th>
                        <th class="py-2.5 px-3">{{ __('admin.th_items_detail') }}</th>
                        <th class="py-2.5 px-3 text-right">{{ __('admin.subtotal_label_short') }}</th>
                        <th class="py-2.5 px-3 text-right">{{ __('admin.net_payable') }}</th>
                        <th class="py-2.5 px-3 text-center">{{ __('admin.th_status') }}</th>
                        <th class="py-2.5 px-3 text-right">{{ __('admin.th_actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-container text-on-surface">
                    @forelse($campaign->orders as $order)
                    <tr class="hover:bg-surface-container-low/50 transition-colors" x-show="matchesSearch('{{ addslashes($order->roomUser?->user?->name ?? 'Guest') }} {{ addslashes($order->items->pluck('item_name')->join(' ')) }}')">
                        <td class="py-2.5 px-3 whitespace-nowrap">
                            <span class="font-bold text-on-surface block font-mono">#ORD-{{ $order->id }}</span>
                            <span class="text-[10px] text-outline font-mono">{{ $order->created_at->format('H:i') }}</span>
                        </td>
                        <td class="py-2.5 px-3 whitespace-nowrap">
                            <span class="font-bold block text-on-surface">{{ $order->roomUser?->user?->name ?? 'Member #' . $order->room_user_id }}</span>
                            <span class="text-[10px] text-secondary font-mono">{{ $order->roomUser?->user?->email ?? '' }}</span>
                        </td>
                        <td class="py-2.5 px-3">
                            <div class="space-y-0.5">
                                @foreach($order->items as $item)
                                <div class="font-medium text-on-surface">
                                    {{ $item->quantity }}x {{ $item->item_name }} @if($item->size_name) <span class="text-outline">({{ $item->size_name }})</span> @endif
                                </div>
                                @if($item->note)
                                <div class="text-[10px] text-outline italic">{{ $item->note }}</div>
                                @endif
                                @endforeach
                            </div>
                        </td>
                        <td class="py-2.5 px-3 text-right font-mono text-outline">
                            {{ number_format($order->subtotal, 0, ',', '.') }} ₫
                        </td>
                        <td class="py-2.5 px-3 text-right font-mono font-bold text-on-surface">
                            {{ number_format($order->total_amount, 0, ',', '.') }} ₫
                        </td>
                        <td class="py-2.5 px-3 text-center whitespace-nowrap">
                            @if($order->status === 'paid')
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-mono font-bold">
                                <span class="material-symbols-outlined text-[12px]">check_circle</span>
                                {{ __('admin.status_paid') }}
                            </span>
                            @elseif($order->status === 'cancelled')
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-slate-100 text-slate-500 border border-slate-200 text-[10px] font-mono font-semibold">
                                {{ __('admin.status_cancelled') }}
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-amber-50 text-amber-800 border border-amber-200 text-[10px] font-mono font-bold">
                                <span class="material-symbols-outlined text-[12px]">hourglass_top</span>
                                {{ __('admin.status_pending') }}
                            </span>
                            @endif
                        </td>
                        <td class="py-2.5 px-3 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-1.5">
                                <button type="button" @click="openPriceModal({{ $order->toJson() }})" class="px-2 py-1 text-[11px] font-semibold border border-outline-variant hover:border-primary hover:text-primary rounded transition-colors">
                                    {{ __('admin.adjust_price_btn') }}
                                </button>
                                @if($order->status !== 'cancelled')
                                <button type="button" @click="cancelOrder({{ $order->id }})" class="px-2 py-1 text-[11px] font-semibold border border-outline-variant hover:border-error hover:bg-error-container hover:text-error rounded text-outline transition-colors">
                                    {{ __('admin.cancel_order_btn') }}
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-outline italic">{{ __('admin.no_orders_found') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Close Campaign Confirmation Modal -->
    <div x-show="openCloseModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl max-w-lg w-full shadow-2xl p-6 space-y-4" @click.away="openCloseModal = false">
            <div class="flex items-center justify-between border-b border-outline-variant pb-3">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-[22px]">task_alt</span>
                    <h3 class="font-bold text-sm text-on-surface uppercase">{{ __('admin.close_and_settle') }}</h3>
                </div>
                <button type="button" @click="openCloseModal = false" class="text-outline hover:text-on-surface"><span class="material-symbols-outlined">close</span></button>
            </div>

            <p class="text-xs text-on-surface-variant leading-relaxed">
                {{ __('admin.close_early_confirm') }}
            </p>

            <!-- Lý do đóng chiến dịch (Reason / Suggestions) -->
            <div class="space-y-2">
                <label class="block text-xs font-semibold text-on-surface">{{ __('admin.close_reason_label') }}</label>
                
                <!-- Gợi ý nhanh nguyên nhân (Quick Reason Chips) -->
                <div class="flex flex-wrap gap-1.5">
                    <button type="button" @click="closeReason = '{{ __('admin.close_reason_opt_time_up') }}'"
                            :class="closeReason === '{{ __('admin.close_reason_opt_time_up') }}' ? 'bg-primary text-on-primary border-primary' : 'bg-surface-container-low text-on-surface-variant border-outline-variant hover:border-primary/50'"
                            class="px-2.5 py-1 rounded-lg text-[11px] font-medium border transition-colors cursor-pointer">
                        ⏰ {{ __('admin.close_reason_opt_time_up') }}
                    </button>
                    <button type="button" @click="closeReason = '{{ __('admin.close_reason_opt_quota_reached') }}'"
                            :class="closeReason === '{{ __('admin.close_reason_opt_quota_reached') }}' ? 'bg-primary text-on-primary border-primary' : 'bg-surface-container-low text-on-surface-variant border-outline-variant hover:border-primary/50'"
                            class="px-2.5 py-1 rounded-lg text-[11px] font-medium border transition-colors cursor-pointer">
                        🎯 {{ __('admin.close_reason_opt_quota_reached') }}
                    </button>
                    <button type="button" @click="closeReason = '{{ __('admin.close_reason_opt_store_cutoff') }}'"
                            :class="closeReason === '{{ __('admin.close_reason_opt_store_cutoff') }}' ? 'bg-primary text-on-primary border-primary' : 'bg-surface-container-low text-on-surface-variant border-outline-variant hover:border-primary/50'"
                            class="px-2.5 py-1 rounded-lg text-[11px] font-medium border transition-colors cursor-pointer">
                        🏪 {{ __('admin.close_reason_opt_store_cutoff') }}
                    </button>
                    <button type="button" @click="closeReason = '{{ __('admin.close_reason_opt_driver_arrived') }}'"
                            :class="closeReason === '{{ __('admin.close_reason_opt_driver_arrived') }}' ? 'bg-primary text-on-primary border-primary' : 'bg-surface-container-low text-on-surface-variant border-outline-variant hover:border-primary/50'"
                            class="px-2.5 py-1 rounded-lg text-[11px] font-medium border transition-colors cursor-pointer">
                        🛵 {{ __('admin.close_reason_opt_driver_arrived') }}
                    </button>
                    <button type="button" @click="closeReason = '{{ __('admin.close_reason_opt_other') }}'"
                            :class="closeReason === '{{ __('admin.close_reason_opt_other') }}' ? 'bg-primary text-on-primary border-primary' : 'bg-surface-container-low text-on-surface-variant border-outline-variant hover:border-primary/50'"
                            class="px-2.5 py-1 rounded-lg text-[11px] font-medium border transition-colors cursor-pointer">
                        📝 {{ __('admin.close_reason_opt_other') }}
                    </button>
                </div>

                <!-- Input nhập lý do chi tiết -->
                <input type="text" x-model="closeReason" placeholder="{{ __('admin.close_reason_placeholder') }}"
                       class="w-full h-9 px-3 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface focus:border-primary focus:ring-1 focus:ring-primary transition-all">
            </div>

            <!-- Cho phép ghi nhận công nợ tự động -->
            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" id="allowDebtCheck" x-model="allowDebt" class="rounded border-outline-variant text-primary focus:ring-primary cursor-pointer">
                <label for="allowDebtCheck" class="text-xs text-on-surface font-medium cursor-pointer">{{ __('admin.auto_create_debt_record') }}</label>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-outline-variant">
                <button type="button" @click="openCloseModal = false" class="px-4 py-2 border border-outline-variant rounded-lg text-xs font-semibold hover:bg-surface-container-low cursor-pointer">{{ __('admin.cancel') }}</button>
                <button type="button" @click="confirmCloseCampaign()" class="px-4 py-2 bg-primary hover:bg-primary-container text-on-primary rounded-lg text-xs font-semibold shadow-xs cursor-pointer">
                    {{ __('admin.confirm_close_now') }}
                </button>
            </div>
        </div>
    </div>
</div>
</x-admin.layout>

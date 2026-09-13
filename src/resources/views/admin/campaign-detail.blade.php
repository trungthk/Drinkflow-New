<x-admin.layout :title="__('admin.brand_title') . ' · ' . $campaign->name" active="campaigns" :room="$room">
    <!-- Breadcrumbs -->
    <nav class="flex items-center gap-1.5 text-xs text-outline select-none pb-2 border-b border-outline-variant/40">
        <a href="{{ route('admin.landing') }}" class="hover:text-on-surface transition-colors no-underline text-outline">Admin</a>
        <span class="text-outline-variant">/</span>
        <a href="{{ route('admin.dashboard.page', $room) }}" class="hover:text-on-surface transition-colors no-underline text-outline">{{ $room->name }}</a>
        <span class="text-outline-variant">/</span>
        <a href="{{ route('admin.manage.page', [$room, 'tab' => 'campaigns']) }}" class="hover:text-on-surface transition-colors no-underline text-outline">{{ __('admin.campaigns') }}</a>
        <span class="text-outline-variant">/</span>
        <span class="text-primary font-semibold">#CMP-{{ $campaign->id }} · {{ $campaign->name }}</span>
    </nav>

    <!-- SECTION 1: STORE & CAMPAIGN BANNER (2/3 & 1/3 SPLIT LAYOUT) -->
    <section class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 shadow-xs">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-center">
            <!-- Left Column: 2/3 Width - Campaign Info & Participation -->
            <div class="lg:col-span-2 space-y-3.5">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full {{ $campaign->status?->value === 'active' ? 'bg-error-container border border-error/30 text-on-error-container' : 'bg-emerald-50 border border-emerald-200 text-emerald-800' }} font-mono text-xs font-semibold">
                        <span class="w-2 h-2 rounded-full {{ $campaign->status?->value === 'active' ? 'bg-error status-dot-pulse' : 'bg-emerald-600' }}"></span>
                        {{ strtoupper($campaign->status?->value ?? 'CLOSED') }}
                    </span>
                    <span class="text-xs text-outline bg-surface-container-low border border-outline-variant px-2 py-0.5 rounded font-mono">
                        #CMP-{{ $campaign->id }} • {{ $campaign->restaurant }}
                    </span>
                    <span class="text-xs text-on-surface-variant bg-surface-container-lowest border border-outline-variant px-2 py-0.5 rounded">
                        Room: <strong>{{ $room->name }}</strong>
                    </span>
                </div>

                <h1 class="text-2xl lg:text-3xl font-bold text-on-surface tracking-tight">
                    {{ $campaign->name }} · {{ $campaign->restaurant }}
                </h1>

                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-outline">
                    <span class="flex items-center gap-1">
                        <span class="material-symbols-outlined text-[16px] text-amber-600">schedule</span>
                        Deadline: <strong class="text-on-surface font-mono">{{ $campaign->deadline?->format('H:i d/m/Y') ?? '11:15' }}</strong>
                    </span>
                    <span class="text-outline-variant">•</span>
                    <span class="flex items-center gap-1">
                        <span class="material-symbols-outlined text-[16px] text-primary">account_circle</span>
                        Dispatcher: <span class="text-on-surface font-medium">{{ auth('admin')->user()?->name ?? 'Lead Dispatcher' }}</span>
                    </span>
                </div>

                <!-- Room Participation Banner -->
                @php
                    $totalUsers = max(1, $totalUsersCount ?? 1);
                    $orderedCount = $orderedUsersCount ?? $orders->count();
                    $declinedCount = $declinedUsersCount ?? 0;
                    $pendingCount = max(0, $totalUsers - $orderedCount - $declinedCount);
                    $orderedPercent = round(($orderedCount / $totalUsers) * 100, 1);
                    $declinedPercent = round(($declinedCount / $totalUsers) * 100, 1);
                    $pendingPercent = max(0, 100 - $orderedPercent - $declinedPercent);
                @endphp
                <div class="pt-3 border-t border-outline-variant/40 bg-surface-container-low p-3.5 rounded-lg border border-outline-variant/60 flex flex-col gap-2.5">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-md bg-emerald-50 border border-emerald-200 flex items-center justify-center text-primary">
                                <span class="material-symbols-outlined text-[18px]">pie_chart</span>
                            </div>
                            <div>
                                <span class="font-semibold text-on-surface text-xs sm:text-sm">{{ __('admin.room_participation_rate') }}</span>
                                <span class="text-xs text-outline ml-2 font-mono">({{ $orderedCount }}/{{ $totalUsers }} {{ __('admin.members') }})</span>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-50 border border-emerald-200 text-primary text-xs font-semibold font-mono">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                            {{ __('admin.participating') }}: {{ $orderedPercent }}%
                        </span>
                    </div>

                    <div class="space-y-1.5">
                        <div class="h-2.5 w-full bg-surface-container-highest rounded-full overflow-hidden flex shadow-inner">
                            <div class="bg-primary h-full transition-all duration-300" style="width: {{ $orderedPercent }}%;" title="{{ __('admin.ordered') }}: {{ $orderedCount }} ({{ $orderedPercent }}%)"></div>
                            <div class="bg-slate-400 h-full transition-all duration-300" style="width: {{ $declinedPercent }}%;" title="{{ __('admin.declined') }}: {{ $declinedCount }} ({{ $declinedPercent }}%)"></div>
                            <div class="bg-amber-400 h-full transition-all duration-300" style="width: {{ $pendingPercent }}%;" title="{{ __('admin.no_response') }}: {{ $pendingCount }} ({{ $pendingPercent }}%)"></div>
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-2 text-xs pt-0.5 font-mono">
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="flex items-center gap-1.5 text-primary font-semibold">
                                    <span class="w-2 h-2 rounded-full bg-primary"></span>
                                    {{ $orderedCount }} {{ __('admin.ordered') }} ({{ $orderedPercent }}%)
                                </span>
                                <span class="flex items-center gap-1.5 text-outline">
                                    <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                                    {{ $declinedCount }} {{ __('admin.declined') }} ({{ $declinedPercent }}%)
                                </span>
                                <span class="flex items-center gap-1.5 text-amber-700">
                                    <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                                    {{ $pendingCount }} {{ __('admin.no_response') }} ({{ $pendingPercent }}%)
                                </span>
                            </div>
                            <span class="text-outline">{{ $room->name }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: 1/3 Width - Vertical Stacked Action Buttons -->
            <div class="lg:col-span-1 flex flex-col gap-2.5 justify-center lg:border-l lg:border-outline-variant/40 lg:pl-6">
                <button
                    type="button"
                    id="btn-copy-items"
                    data-restaurant="{{ $campaign->restaurant }}"
                    data-summary="{{ json_encode($aggregatedItems) }}"
                    class="w-full h-10 px-4 bg-primary hover:bg-primary-container text-on-primary rounded text-xs font-semibold flex items-center justify-center gap-2 shadow-sm transition-colors cursor-pointer active:scale-[0.99]"
                >
                    <span class="material-symbols-outlined text-[18px]">content_copy</span>
                    <span>{{ __('admin.copy_order_summary') }}</span>
                </button>
                <button
                    type="button"
                    id="btn-export-statement"
                    data-campaign-id="{{ $campaign->id }}"
                    data-orders="{{ json_encode($orders->map(fn($o) => [
                        'member' => $o->roomUser?->display_name ?? 'Member',
                        'code' => $o->roomUser?->user_code ?? '',
                        'items_count' => $o->items->count(),
                        'subtotal' => $o->subtotal,
                        'sponsor_amount' => $o->sponsor_amount,
                        'final_amount' => $o->final_amount,
                        'status' => $o->status->value
                    ])) }}"
                    class="w-full h-10 px-4 bg-surface-container-lowest border border-outline-variant hover:bg-surface-container-low text-on-surface rounded text-xs font-semibold flex items-center justify-center gap-2 transition-colors shadow-xs cursor-pointer"
                >
                    <span class="material-symbols-outlined text-[18px] text-primary">file_download</span>
                    <span>{{ __('admin.export_settlement_csv') }}</span>
                </button>
                <a href="{{ route('admin.manage.page', [$room, 'tab' => 'campaigns']) }}" class="w-full h-10 px-4 bg-surface-container-lowest border border-outline-variant hover:bg-surface-container-low text-outline hover:text-on-surface rounded text-xs font-semibold flex items-center justify-center gap-2 transition-colors shadow-xs no-underline">
                    <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                    <span>{{ __('admin.back_to_campaigns') }}</span>
                </a>
            </div>
        </div>
    </section>

    <!-- SECTION 2: 2-COLUMN GRID (FINANCIAL SETTLEMENT + NEXT ACTIONS) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-stretch">
        <!-- COLUMN 1: FINANCIAL SETTLEMENT SUMMARY -->
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl shadow-xs overflow-hidden flex flex-col justify-between">
            <div>
                <div class="p-4 border-b border-outline-variant/60 bg-surface-container-low flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[20px]">calculate</span>
                        <h3 class="text-sm font-bold text-on-surface">{{ __('admin.financial_settlement_summary') }}</h3>
                    </div>
                    <span class="text-xs bg-emerald-50 text-primary border border-emerald-200 px-2.5 py-0.5 rounded-full font-bold font-mono">
                        {{ __('admin.bill_matched_100') }}
                    </span>
                </div>

                <div class="p-5 space-y-3.5 text-xs">
                    <!-- Subtotal section -->
                    <div class="space-y-1.5">
                        <div class="flex justify-between items-center text-outline">
                            <span>{{ __('admin.original_subtotal') }}:</span>
                            <span class="font-mono text-on-surface font-semibold">{{ number_format($grossSubtotal ?? 0, 0, ',', '.') }} ₫</span>
                        </div>
                        <div class="flex justify-between items-center text-outline">
                            <span class="flex items-center gap-1">
                                <span>{{ __('admin.delivery_fee_extra') }}:</span>
                                <span class="material-symbols-outlined text-[14px] text-outline" title="{{ __('admin.delivery_fee_extra') }}">help_outline</span>
                            </span>
                            <span class="font-mono text-amber-700 font-semibold">+{{ number_format($campaign->delivery_fee ?? 0, 0, ',', '.') }} ₫</span>
                        </div>
                        <div class="flex justify-between items-center pt-1 font-bold text-on-surface border-t border-outline-variant/40">
                            <span>{{ __('admin.gross_total') }}:</span>
                            <span class="font-mono text-sm text-on-surface">{{ number_format(($grossSubtotal ?? 0) + ($campaign->delivery_fee ?? 0), 0, ',', '.') }} ₫</span>
                        </div>
                    </div>

                    <!-- Subsidy section -->
                    <div class="space-y-2 bg-surface-container-low p-3 rounded-lg border border-outline-variant/60">
                        <div class="flex justify-between items-center font-bold text-primary">
                            <span class="flex items-center gap-1">
                                <span class="material-symbols-outlined text-[16px]">redeem</span>
                                {{ __('admin.multi_sponsor_subsidy') }}:
                            </span>
                            <span class="font-mono">-{{ number_format($sponsorSubsidy ?? 0, 0, ',', '.') }} ₫</span>
                        </div>
                        @if($campaign->sponsor_name)
                        <div class="pl-3 space-y-1 text-[11px] text-outline border-l-2 border-primary/40 font-mono">
                            <div class="flex justify-between">
                                <span>{{ $campaign->sponsor_name }}:</span>
                                <span class="text-on-surface">{{ number_format($sponsorSubsidy ?? 0, 0, ',', '.') }} ₫</span>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Net User Payables -->
                    <div class="flex justify-between items-center font-bold text-on-surface bg-secondary-container/40 p-3 rounded-lg">
                        <span>{{ __('admin.net_user_payables') }}:</span>
                        <span class="font-mono text-primary text-sm">{{ number_format($netPayables ?? 0, 0, ',', '.') }} ₫</span>
                    </div>

                    <!-- Realized vs Debt Breakdown -->
                    <div class="space-y-2 pt-1">
                        <div class="flex justify-between items-center p-2 rounded bg-emerald-50 border border-emerald-200">
                            <div class="flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[16px] text-primary">check_circle</span>
                                <span class="text-primary font-semibold">{{ __('admin.paid_via_vietqr_fund') }}:</span>
                            </div>
                            <span class="font-mono font-bold text-primary">{{ number_format($paidViaQr ?? 0, 0, ',', '.') }} ₫</span>
                        </div>
                        <div class="flex justify-between items-center p-2 rounded bg-amber-50 border border-amber-200">
                            <div class="flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[16px] text-amber-700">pending_actions</span>
                                <span class="text-amber-800 font-semibold">{{ __('admin.member_debt_incurred') }}:</span>
                            </div>
                            <span class="font-mono font-bold text-amber-800">{{ number_format($memberDebt ?? 0, 0, ',', '.') }} ₫</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-3 bg-surface-container-low border-t border-outline-variant/60 flex items-center justify-between text-xs text-outline">
                <span class="uppercase font-mono">{{ __('admin.settlement_status') }}:</span>
                <span class="font-mono font-bold text-primary flex items-center gap-1">
                    <span class="material-symbols-outlined text-[16px]">verified</span>
                    {{ __('admin.synced_with_debt_ledger') }}
                </span>
            </div>
        </div>

        <!-- COLUMN 2: DISPATCH & NEXT ACTIONS CHECKLIST -->
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl shadow-xs overflow-hidden flex flex-col justify-between">
            <div>
                <div class="p-4 border-b border-outline-variant/60 bg-surface-container-low flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[20px]">checklist</span>
                        <h3 class="text-sm font-bold text-on-surface">{{ __('admin.next_actions_dispatch') }}</h3>
                    </div>
                    <span class="text-xs bg-primary/10 text-primary px-2.5 py-0.5 rounded-full font-mono font-semibold">
                        {{ __('admin.ops_workflow') }}
                    </span>
                </div>

                <div class="p-5 space-y-3 text-xs">
                    <ul class="space-y-3">
                        <li class="flex items-start gap-2.5 text-on-surface">
                            <span class="material-symbols-outlined text-[18px] text-primary shrink-0">check_box</span>
                            <span class="line-through text-outline">{{ __('admin.step_locked_orders') }}</span>
                        </li>
                        <li class="flex items-start gap-2.5 text-on-surface">
                            <span class="material-symbols-outlined text-[18px] text-primary shrink-0">check_box</span>
                            <span class="line-through text-outline">{{ __('admin.step_synced_prices') }}</span>
                        </li>
                        <li class="flex items-start gap-2.5 text-on-surface">
                            <span class="material-symbols-outlined text-[18px] text-primary shrink-0">check_box</span>
                            <span class="line-through text-outline">{{ __('admin.step_transferred_debts') }}</span>
                        </li>
                        <li class="flex items-start gap-2.5 text-on-surface font-semibold bg-emerald-50 p-2.5 rounded-lg border border-emerald-200">
                            <span class="material-symbols-outlined text-[18px] text-primary shrink-0 animate-pulse">radio_button_unchecked</span>
                            <div>
                                <span>{{ __('admin.step_receive_from_shipper') }}</span>
                                <span class="text-[11px] block text-primary font-normal mt-0.5">{{ __('admin.estimated_delivery') }}: {{ $campaign->deadline?->format('H:i') ?? '11:15' }}</span>
                            </div>
                        </li>
                        <li class="flex items-start gap-2.5 text-outline p-1">
                            <span class="material-symbols-outlined text-[18px] text-outline-variant shrink-0">check_box_outline_blank</span>
                            <span>{{ __('admin.step_distribute_drinks') }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="p-4 bg-surface-container-low border-t border-outline-variant/60">
                <button type="button" class="w-full py-2.5 px-4 bg-primary hover:bg-primary-container text-on-primary rounded text-xs font-semibold flex items-center justify-center gap-2 shadow-sm transition-colors cursor-pointer" onclick="alert('{{ addslashes(__('admin.pantry_notification_sent')) }}')">
                    <span class="material-symbols-outlined text-[18px]">campaign</span>
                    <span>{{ __('admin.send_pantry_notification') }}</span>
                </button>
            </div>
        </div>
    </div>

    <!-- SECTION 3: TABBED INTERFACE FOR ORDER AGGREGATION & SETTLEMENT LEDGER -->
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl shadow-xs overflow-hidden" x-data="{ activeTab: 'aggregated' }">
        <!-- Tabs Navigation Header -->
        <div class="border-b border-outline-variant/60 bg-surface-container-low px-4 pt-3 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <button type="button" @click="activeTab = 'aggregated'"
                    :class="activeTab === 'aggregated' ? 'border-primary text-primary font-bold bg-surface-container-lowest' : 'border-transparent text-outline hover:text-on-surface'"
                    class="flex items-center gap-2 px-4 py-2.5 border-b-2 rounded-t-lg text-xs transition-colors cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                    <span>{{ __('admin.aggregated_items_list') }}</span>
                    <span class="px-2 py-0.5 rounded bg-emerald-50 text-primary border border-emerald-200 font-mono text-[11px] font-semibold">{{ count($aggregatedItems ?? []) }} {{ __('admin.item_groups') }}</span>
                </button>

                <button type="button" @click="activeTab = 'ledger'"
                    :class="activeTab === 'ledger' ? 'border-primary text-primary font-bold bg-surface-container-lowest' : 'border-transparent text-outline hover:text-on-surface'"
                    class="flex items-center gap-2 px-4 py-2.5 border-b-2 rounded-t-lg text-xs transition-colors cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">account_balance_wallet</span>
                    <span>{{ __('admin.participant_settlement_ledger') }}</span>
                    <span class="px-2 py-0.5 rounded bg-surface-container text-on-surface-variant border border-outline-variant font-mono text-[11px] font-semibold">{{ __('admin.orders_count_badge', ['count' => $orders->count()]) }}</span>
                </button>
            </div>
        </div>

        <!-- TAB 1: AGGREGATED ORDER LIST -->
        <div x-show="activeTab === 'aggregated'" class="p-4 space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-3 pb-2">
                <div>
                    <h3 class="text-sm font-bold text-on-surface">{{ __('admin.aggregated_items_for_store') }} {{ $campaign->restaurant }}</h3>
                    <p class="text-xs text-outline">{{ __('admin.aggregated_items_desc') }}</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="h-9 bg-surface-container-low border-b border-outline-variant text-outline uppercase font-mono tracking-wider">
                            <th class="px-4 py-2 w-12 text-center">{{ __('admin.order_no') }}</th>
                            <th class="px-4 py-2">{{ __('admin.item_name_customization') }}</th>
                            <th class="px-4 py-2 w-32 text-center">{{ __('admin.quantity') }}</th>
                            <th class="px-4 py-2 w-32 text-right">{{ __('admin.unit_price') }}</th>
                            <th class="px-4 py-2 w-36 text-right">{{ __('admin.total_amount') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/40">
                        @forelse($aggregatedItems as $index => $item)
                            <tr class="hover:bg-surface-container-low/50 transition-colors">
                                <td class="px-4 py-3 text-center font-mono text-outline">{{ $index + 1 }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-on-surface">{{ $item['name'] }} {{ $item['size'] ? '('.$item['size'].')' : '' }}</div>
                                    @if(!empty($item['notes']) && $item['notes']->isNotEmpty())
                                        <div class="text-[11px] text-outline mt-0.5">{{ __('admin.notes') }}: {{ $item['notes']->unique()->join(' • ') }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-block px-2.5 py-0.5 bg-emerald-50 border border-emerald-200 text-primary font-mono font-bold rounded">
                                        {{ $item['quantity'] }} {{ __('admin.portions') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-outline">{{ number_format($item['unit_price'], 0, ',', '.') }} ₫</td>
                                <td class="px-4 py-3 text-right font-mono font-bold text-on-surface">{{ number_format($item['total_amount'], 0, ',', '.') }} ₫</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-outline">{{ __('admin.no_items_ordered') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="bg-surface-container-low border-t-2 border-outline-variant font-bold text-xs font-mono">
                            <td class="px-4 py-3 text-on-surface" colspan="2">{{ __('admin.total_menu_items') }}</td>
                            <td class="px-4 py-3 text-center text-primary">{{ collect($aggregatedItems)->sum('quantity') }} {{ __('admin.items_unit') }}</td>
                            <td class="px-4 py-3 text-right text-outline"></td>
                            <td class="px-4 py-3 text-right text-primary text-sm">{{ number_format(collect($aggregatedItems)->sum('total_amount'), 0, ',', '.') }} ₫</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- TAB 2: PARTICIPANT SETTLEMENT LEDGER -->
        <div x-show="activeTab === 'ledger'" class="p-4 space-y-3" style="display: none;">
            <div class="flex flex-wrap items-center justify-between gap-3 pb-2">
                <div>
                    <h3 class="text-sm font-bold text-on-surface">{{ __('admin.order_allocation_debt_detail') }}</h3>
                    <p class="text-xs text-outline">{{ __('admin.order_allocation_debt_desc') }}</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="h-9 bg-surface-container-low border-b border-outline-variant text-outline uppercase font-mono tracking-wider">
                            <th class="px-4 py-2">{{ __('admin.member') }}</th>
                            <th class="px-4 py-2">{{ __('admin.items_ordered') }}</th>
                            <th class="px-3 py-2 text-right">{{ __('admin.gross_bill') }}</th>
                            <th class="px-3 py-2 text-right">{{ __('admin.subsidy') }}</th>
                            <th class="px-3 py-2 text-right">{{ __('admin.payable') }}</th>
                            <th class="px-4 py-2 text-center w-28">{{ __('admin.status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/40">
                        @forelse($orders as $order)
                            @php
                                $roomUser = $order->roomUser;
                                $globalUser = $roomUser?->globalUser;
                            @endphp
                            <tr class="hover:bg-surface-container-low/50 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-7 h-7 rounded-full bg-secondary text-on-secondary font-mono text-[11px] font-bold flex items-center justify-center">
                                            {{ mb_strtoupper(mb_substr($roomUser?->display_name ?? 'U', 0, 2)) }}
                                        </div>
                                        <div>
                                            <div class="font-semibold text-on-surface">{{ $roomUser?->display_name ?? 'Member' }}</div>
                                            <div class="text-[10px] font-mono text-outline">{{ $roomUser?->user_code ?? '' }} • {{ $globalUser?->email ?? '' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-on-surface-variant">
                                    <span class="font-semibold text-on-surface">{{ $order->items->count() }} {{ __('admin.items_unit') }}:</span>
                                    {{ $order->items->pluck('item_name')->join(', ') }}
                                </td>
                                <td class="px-3 py-3 text-right font-mono text-outline">{{ number_format($order->subtotal, 0, ',', '.') }} ₫</td>
                                <td class="px-3 py-3 text-right font-mono text-primary font-semibold">-{{ number_format($order->sponsor_amount, 0, ',', '.') }} ₫</td>
                                <td class="px-3 py-3 text-right font-mono font-bold text-on-surface">{{ number_format($order->final_amount, 0, ',', '.') }} ₫</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-block px-2.5 py-0.5 rounded font-mono text-[10px] font-bold {{ $order->status->value === 'completed' ? 'bg-emerald-50 text-primary border border-emerald-200' : 'bg-amber-50 text-amber-800 border border-amber-200' }}">
                                        {{ strtoupper($order->status->value) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-outline">{{ __('admin.no_orders_in_campaign') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin.layout>

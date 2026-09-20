<x-admin.layout :title="__('admin.reports_analytics_title')" active="reports" :room="$room">
    <!-- Header & Action Ribbon -->
    <div class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-outline-variant/40">
        <div>
            <h1 class="text-2xl font-bold text-on-surface tracking-tight">{{ __('admin.reports_analytics_title') }}</h1>
        </div>
        <div class="flex items-center gap-3">
            <x-admin.date-range-filter id="report-date-range" />
            <button type="button" onclick="exportReportCSV()" class="px-3.5 py-2 bg-surface-container hover:bg-surface-container-high border border-outline-variant text-on-surface rounded text-xs font-semibold flex items-center gap-1.5 shadow-xs transition-colors">
                <span class="material-symbols-outlined text-[16px] text-primary">download</span>
                <span>{{ __('admin.export_excel_csv') }}</span>
            </button>
        </div>
    </div>

    @php
        $reportI18n = [
            'member' => __('admin.report_col_member'),
            'debtCount' => __('admin.report_col_debt_count'),
            'totalOriginal' => __('admin.total_debt_amount'),
            'totalPaid' => __('admin.total_debt_paid'),
            'totalRemaining' => __('admin.total_debt_remaining'),
            'status' => __('admin.report_col_status'),
            'statusCleared' => __('admin.report_status_cleared'),
            'statusOwing' => __('admin.report_status_owing'),
            'rank' => __('admin.report_col_rank'),
            'sponsor' => __('admin.report_col_sponsor'),
            'sponsoredOrders' => __('admin.report_col_sponsored_orders'),
            'totalSponsored' => __('admin.report_col_total_sponsored'),
            'ordersPlaced' => __('admin.th_orders_placed_count'),
            'totalSpent' => __('admin.report_col_total_spent'),
            'noDrinksTitle' => __('admin.report_no_drinks_found'),
            'noDrinksDesc' => __('admin.report_no_drinks_found_desc'),
            'noStoresTitle' => __('admin.report_no_stores_found'),
            'noStoresDesc' => __('admin.report_no_stores_found_desc'),
            'noDebtsTitle' => __('admin.report_no_debts_found'),
            'noDebtsDesc' => __('admin.report_no_debts_found_desc'),
            'noSponsorsTitle' => __('admin.no_sponsors_found'),
            'noSponsorsDesc' => __('admin.no_sponsors_found_desc'),
            'noUsersTitle' => __('admin.no_users_analytics_found'),
            'noUsersDesc' => __('admin.no_users_analytics_found_desc'),
        ];
    @endphp
    <!-- 5 Report Tabs Navigation -->
    <div id="report-tabs" data-i18n="{{ json_encode($reportI18n, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) }}" class="flex items-center gap-2 border-b border-outline-variant overflow-x-auto text-xs font-semibold">
        <button type="button" onclick="switchReportTab('campaigns')" id="rtab-campaigns" class="rtab flex items-center gap-2 px-3.5 py-2.5 border-b-2 border-primary text-primary font-bold transition-colors whitespace-nowrap">
            <span class="material-symbols-outlined text-[18px]">analytics</span>
            <span>{{ __('admin.tab_campaigns_analytics') }}</span>
        </button>
        <button type="button" onclick="switchReportTab('products')" id="rtab-products" class="rtab flex items-center gap-2 px-3.5 py-2.5 border-b-2 border-transparent text-outline hover:text-on-surface transition-colors whitespace-nowrap">
            <span class="material-symbols-outlined text-[18px]">local_cafe</span>
            <span>{{ __('admin.tab_products_analytics') }}</span>
        </button>
        <button type="button" onclick="switchReportTab('debts')" id="rtab-debts" class="rtab flex items-center gap-2 px-3.5 py-2.5 border-b-2 border-transparent text-outline hover:text-on-surface transition-colors whitespace-nowrap">
            <span class="material-symbols-outlined text-[18px]">account_balance_wallet</span>
            <span>{{ __('admin.tab_debts_analytics') }}</span>
        </button>
        <button type="button" onclick="switchReportTab('sponsors')" id="rtab-sponsors" class="rtab flex items-center gap-2 px-3.5 py-2.5 border-b-2 border-transparent text-outline hover:text-on-surface transition-colors whitespace-nowrap">
            <span class="material-symbols-outlined text-[18px]">volunteer_activism</span>
            <span>{{ __('admin.tab_sponsors_analytics') }}</span>
        </button>
        <button type="button" onclick="switchReportTab('users')" id="rtab-users" class="rtab flex items-center gap-2 px-3.5 py-2.5 border-b-2 border-transparent text-outline hover:text-on-surface transition-colors whitespace-nowrap">
            <span class="material-symbols-outlined text-[18px]">group</span>
            <span>{{ __('admin.tab_users_analytics') }}</span>
        </button>
    </div>

    <!-- 4 Dynamic KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-2">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 shadow-xs">
            <div class="flex items-center justify-between text-outline">
                <span class="text-[11px] font-mono uppercase tracking-wider font-semibold">{{ __('admin.kpi_total_campaigns') }}</span>
                <span class="material-symbols-outlined text-[20px] text-primary">campaign</span>
            </div>
            <div class="text-2xl font-bold font-mono text-on-surface mt-2" id="kpi-campaigns">{{ $stats['campaign_count'] ?? 0 }}</div>
            <div class="text-[11px] text-emerald-700 mt-1">{{ __('admin.selected_period_desc') }}</div>
        </div>

        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 shadow-xs">
            <div class="flex items-center justify-between text-outline">
                <span class="text-[11px] font-mono uppercase tracking-wider font-semibold">{{ __('admin.kpi_total_orders_placed') }}</span>
                <span class="material-symbols-outlined text-[20px] text-secondary">shopping_cart_checkout</span>
            </div>
            <div class="text-2xl font-bold font-mono text-on-surface mt-2" id="kpi-orders">{{ $stats['order_count'] ?? 0 }}</div>
            <div class="text-[11px] text-outline mt-1">{{ __('admin.on_time_completed_desc') }}</div>
        </div>

        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 shadow-xs">
            <div class="flex items-center justify-between text-outline">
                <span class="text-[11px] font-mono uppercase tracking-wider font-semibold">{{ __('admin.kpi_total_store_spending') }}</span>
                <span class="material-symbols-outlined text-[20px] text-primary">receipt_long</span>
            </div>
            <div class="text-2xl font-bold font-mono text-primary mt-2" id="kpi-spending">{{ \App\Support\Helpers\FormatHelper::formatCurrency((float) ($stats['spending'] ?? 0)) }}</div>
            <div class="text-[11px] text-outline mt-1">{{ __('admin.actual_invoice_desc') }}</div>
        </div>

        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 shadow-xs">
            <div class="flex items-center justify-between text-outline">
                <span class="text-[11px] font-mono uppercase tracking-wider font-semibold">{{ __('admin.kpi_sponsor_fund_subsidies') }}</span>
                <span class="material-symbols-outlined text-[20px] text-emerald-600">savings</span>
            </div>
            <div class="text-2xl font-bold font-mono text-emerald-600 mt-2" id="kpi-sponsor">{{ \App\Support\Helpers\FormatHelper::formatCurrency((float) ($stats['sponsor_amount'] ?? 0)) }}</div>
            <div class="text-[11px] text-emerald-700 mt-1">{{ __('admin.sponsor_for_members_desc') }}</div>
        </div>
    </div>

    <!-- Tab 1: Campaign Analytics Panel -->
    <div id="panel-campaigns" class="report-panel space-y-6">
        <!-- Visual Participation Ratio Component -->
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 shadow-xs">
            <div class="flex flex-col md:flex-row md:items-center justify-between pb-3 border-b border-outline-variant gap-2">
                <div>
                    <h2 class="font-bold text-sm text-on-surface">{{ __('admin.participation_ratio_heading') }}</h2>
                    <p class="text-[11px] text-outline">{{ __('admin.total_room_members_label') }} {{ $roomMembersCount ?? 30 }}</p>
                </div>
                <div class="flex items-center gap-3 text-[11px] font-semibold">
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-primary inline-block"></span> {{ __('admin.filter_active') }}</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-secondary inline-block"></span> {{ __('admin.filter_closed') }}</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-amber-500 inline-block"></span> {{ __('admin.status_pending') }}</span>
                </div>
            </div>

            <div class="mt-4 space-y-3.5 text-xs">
                @forelse($campaigns->take(5) as $c)
                    @php
                        $ordersCount = (int) $c->orders_count;
                        $pct = $roomMembersCount > 0 ? min(100, round(($ordersCount / $roomMembersCount) * 100, 1)) : 0;
                    @endphp
                    <div>
                        <div class="flex justify-between items-center mb-1 text-on-surface">
                            <span class="font-bold text-xs">{{ $c->name ?? $c->title }} ({{ $c->restaurant }})</span>
                            <span class="font-mono text-outline">{{ $ordersCount }} ({{ $pct }}%)</span>
                        </div>
                        <div class="w-full h-3.5 bg-surface-container rounded-sm flex overflow-hidden">
                            <div class="bg-primary hover:opacity-90 transition-all" style="width: {{ $pct }}%" title="{{ $ordersCount }}"></div>
                            <div class="bg-secondary/40 hover:opacity-90 transition-all" style="width: {{ max(0, 10 - $pct/10) }}%"></div>
                            <div class="bg-amber-500 hover:opacity-90 transition-all" style="width: {{ max(0, 100 - $pct - 5) }}%"></div>
                        </div>
                    </div>
                @empty
                    <x-admin.empty-state icon="campaign" :title="__('admin.no_campaigns_analytics_found')" :description="__('admin.no_campaigns_analytics_found_desc')" />
                @endforelse
            </div>
        </div>
    </div>

    <!-- Tab 2: Product Analytics Panel -->
    <div id="panel-products" class="report-panel space-y-6 hidden">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 shadow-xs">
                <h3 class="font-bold text-sm text-on-surface mb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-[18px]">local_cafe</span>
                    <span>{{ __('admin.top_5_drinks') }}</span>
                </h3>
                <div id="top-drinks-list" class="space-y-2.5 text-xs">
                    <!-- Loaded dynamically -->
                </div>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 shadow-xs">
                <h3 class="font-bold text-sm text-on-surface mb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-[18px]">storefront</span>
                    <span>{{ __('admin.top_5_stores') }}</span>
                </h3>
                <div id="top-stores-list" class="space-y-2.5 text-xs">
                    <!-- Loaded dynamically -->
                </div>
            </div>
        </div>
    </div>

    <!-- Tab 3: Debt Analytics Panel (Công nợ theo từng User) -->
    <div id="panel-debts" class="report-panel space-y-6 hidden">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-outline-variant">
                <div>
                    <h3 class="font-bold text-sm text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[18px]">account_balance_wallet</span>
                        <span>{{ __('admin.debts_analytics_title') }}</span>
                    </h3>
                    <p class="text-[11px] text-outline mt-0.5">{{ __('admin.debts_analytics_desc') }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-1.5 px-3 py-1.5 bg-surface-container-low rounded-lg border border-outline-variant text-xs">
                        <span class="text-outline">{{ __('admin.total_debt_remaining') }}:</span>
                        <span class="font-mono font-bold text-error" id="debt-summary-remaining">{{ \App\Support\Helpers\FormatHelper::formatCurrency((float) ($stats['debt'] ?? 0)) }}</span>
                    </div>
                </div>
            </div>

            <div id="debts-users-list">
                <!-- Loaded dynamically -->
            </div>
        </div>
    </div>

    <!-- Tab 4: Sponsor Analytics Panel -->
    <div id="panel-sponsors" class="report-panel space-y-6 hidden">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 shadow-xs space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-outline-variant">
                <div>
                    <h3 class="font-bold text-sm text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[18px]">volunteer_activism</span>
                        <span>{{ __('admin.sponsor_leaderboard') }}</span>
                    </h3>
                    <p class="text-[11px] text-outline mt-0.5">{{ __('admin.multi_sponsor_title') }}</p>
                </div>
                <div class="px-3 py-1 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 rounded-lg text-xs font-mono font-bold" id="sponsor-subtotal-text">
                    {{ \App\Support\Helpers\FormatHelper::formatCurrency((float) ($stats['sponsor_amount'] ?? 0)) }}
                </div>
            </div>
            
            <div id="sponsors-leaderboard-list">
                <!-- Loaded dynamically -->
            </div>
        </div>
    </div>

    <!-- Tab 5: User Analytics Panel -->
    <div id="panel-users" class="report-panel space-y-6 hidden">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 shadow-xs space-y-4">
            <div class="pb-3 border-b border-outline-variant">
                <h3 class="font-bold text-sm text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-[18px]">group</span>
                    <span>{{ __('admin.user_reliability') }}</span>
                </h3>
                <p class="text-[11px] text-outline mt-0.5">{{ __('admin.users_directory') }}</p>
            </div>

            <div id="users-analytics-list">
                <!-- Loaded dynamically -->
            </div>
        </div>
    </div>
</x-admin.layout>

<x-global.layout 
    :title="'DrinkFlow - ' . __('global.statistics.page_title')" 
    :user="$user"
    activeTab="statistics" 
    :breadcrumbs="$breadcrumbs"
    :unreadNotificationsCount="$unreadNotificationsCount ?? 0"
    :notifications="$notifications ?? collect()"
>
<div 
    x-data="{
        showHelpModal: false,
        timeRange: 'month'
    }" 
    class="space-y-6"
>
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 pb-4 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('global.statistics.title') }}</h1>
            <p class="text-sm text-slate-600 mt-1">{{ __('global.statistics.subtitle') }}</p>
        </div>

        <div class="flex items-center gap-3">
            <div class="relative inline-block">
                <select 
                    x-model="timeRange"
                    class="h-[38px] appearance-none pl-3 pr-8 rounded-xl border border-slate-200 bg-white text-slate-800 text-xs font-medium focus:border-[#006948] focus:ring-1 focus:ring-[#006948] focus:outline-none cursor-pointer shadow-2xs"
                >
                    <option value="month" selected>{{ __('global.statistics.range_this_month', ['date' => date('m/Y')]) }}</option>
                    <option value="3months">{{ __('global.statistics.range_3months') }}</option>
                    <option value="6months">{{ __('global.statistics.range_6months') }}</option>
                    <option value="year">{{ __('global.statistics.range_year', ['year' => date('Y')]) }}</option>
                </select>
                <span class="material-symbols-outlined absolute right-2.5 top-2.5 pointer-events-none text-slate-400 text-[18px]">expand_more</span>
            </div>

            @if($totalOrders === 0)
                <button type="button" disabled class="h-[38px] px-4 rounded-xl border border-slate-200 bg-slate-50 text-slate-400 text-xs font-semibold flex items-center gap-2 cursor-not-allowed">
                    <span class="material-symbols-outlined text-[18px]">file_download</span>
                    <span>{{ __('global.statistics.export_excel') }}</span>
                </button>
            @else
                <a href="{{ route('user.me.statistics.export') }}" class="h-[38px] px-4 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold flex items-center gap-2 transition-colors shadow-2xs">
                    <span class="material-symbols-outlined text-[#006948] text-[18px]">file_download</span>
                    <span>{{ __('global.statistics.export_excel') }}</span>
                </a>
            @endif
        </div>
    </div>

    <!-- Metric Summary Grid (4 Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Tổng đơn đã đặt -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-2xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs text-slate-500 font-medium">{{ __('global.statistics.total_orders') }}</span>
                <span class="w-8 h-8 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-center text-[#006948]">
                    <span class="material-symbols-outlined text-[19px]">receipt_long</span>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-3xl font-bold text-slate-900 tabular-nums">
                    {{ $totalOrders }} <span class="text-base font-normal text-slate-400">{{ __('global.statistics.orders_unit') }}</span>
                </div>
                <div class="mt-2 flex items-center gap-1.5 text-xs">
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[11px] bg-emerald-50 text-emerald-800 font-semibold border border-emerald-200">
                        <span class="material-symbols-outlined text-[13px] mr-0.5">trending_up</span>
                        +3 {{ __('global.statistics.orders_unit') }}
                    </span>
                    <span class="text-slate-400">{{ __('global.statistics.vs_last_month') }}</span>
                </div>
            </div>
        </div>

        <!-- Card 2: Tổng chi tiêu cá nhân -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-2xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs text-slate-500 font-medium">{{ __('global.statistics.total_spent') }}</span>
                <span class="w-8 h-8 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-center text-[#006948]">
                    <span class="material-symbols-outlined text-[19px]">account_balance_wallet</span>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-3xl font-bold text-slate-900 tabular-nums">
                    {{ \App\Support\Helpers\FormatHelper::formatCurrency($totalSpent) }}
                </div>
                <div class="mt-2 text-xs text-slate-400 flex items-center gap-1">
                    <span class="material-symbols-outlined text-[14px]">calculate</span>
                    <span>{{ __('global.statistics.avg_per_order', ['amount' => \App\Support\Helpers\FormatHelper::formatCurrency($avgSpent)]) }}</span>
                </div>
            </div>
        </div>

        <!-- Card 3: Tổng tài trợ đã nhận (Sponsor) -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-2xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs text-slate-500 font-medium">{{ __('global.statistics.total_sponsor') }}</span>
                <span class="w-8 h-8 rounded-xl bg-emerald-50 border border-emerald-200/80 flex items-center justify-center text-[#006948]">
                    <span class="material-symbols-outlined text-[19px]">loyalty</span>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-3xl font-bold text-[#006948] tabular-nums">
                    {{ \App\Support\Helpers\FormatHelper::formatCurrency($totalSponsor) }}
                </div>
                <div class="mt-2 flex items-center gap-1.5">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] bg-emerald-50 text-emerald-800 font-semibold border border-emerald-200">
                        {{ __('global.statistics.saved_percent', ['percent' => $sponsorPercent]) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Card 4: Món & Topping ưa chuộng -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-2xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs text-slate-500 font-medium">{{ __('global.statistics.favorite_item') }}</span>
                <span class="w-8 h-8 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-center text-[#006948]">
                    <span class="material-symbols-outlined text-[19px]">favorite</span>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-base font-bold text-slate-900 truncate" title="{{ $favoriteItem }}">
                    {{ $favoriteItem }}
                </div>
                <div class="mt-2 text-xs text-slate-500 flex items-center justify-between">
                    <span class="font-medium text-slate-700">{{ __('global.statistics.orders_count', ['count' => $favoriteItemOrders]) }}</span>
                    @if($favoriteItemOrders > 0)
                        <span class="text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full text-[11px] font-semibold border border-emerald-200">{{ __('global.statistics.most_ordered_badge') }}</span>
                    @else
                        <span class="text-slate-400 bg-slate-50 px-2 py-0.5 rounded-full text-[11px] border border-slate-200">{{ __('global.statistics.no_data') }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Data Visualization Row 1 (2/3 + 1/3) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
        <!-- Left Card (2/3): Biểu đồ xu hướng chi tiêu theo tuần -->
        <div class="lg:col-span-8 bg-white border border-slate-200 rounded-2xl p-6 shadow-2xs flex flex-col justify-between space-y-6">
            <div>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">{{ __('global.statistics.weekly_chart_title') }}</h2>
                        <p class="text-xs text-slate-500 mt-0.5">{{ __('global.statistics.weekly_chart_subtitle') }}</p>
                    </div>

                    <!-- Legend -->
                    <div class="flex items-center gap-4 text-xs">
                        <div class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded bg-[#006948]"></span>
                            <span class="text-slate-700 font-medium">{{ __('global.statistics.legend_spent') }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded bg-slate-400"></span>
                            <span class="text-slate-700 font-medium">{{ __('global.statistics.legend_sponsor') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Visualization Canvas with Bars -->
                <div class="pt-6 pb-2">
                    <div class="h-56 flex items-end justify-between gap-6 px-4 border-b border-slate-200 relative">
                        <!-- Grid background reference lines -->
                        <div class="absolute inset-0 flex flex-col justify-between pointer-events-none opacity-40">
                            <div class="border-b border-dashed border-slate-200 w-full h-0"></div>
                            <div class="border-b border-dashed border-slate-200 w-full h-0"></div>
                            <div class="border-b border-dashed border-slate-200 w-full h-0"></div>
                            <div class="border-b border-dashed border-slate-200 w-full h-0"></div>
                        </div>

                        @foreach($weeklyStats as $w)
                            <div class="flex-1 flex flex-col items-center gap-2 z-10 group">
                                <div class="opacity-0 group-hover:opacity-100 transition-opacity bg-slate-900 text-white px-2.5 py-1 rounded-lg text-[10px] whitespace-nowrap shadow-md">
                                    {{ __('global.statistics.spent_tooltip', ['spent' => \App\Support\Helpers\FormatHelper::formatCurrency($w['spent']), 'sponsor' => \App\Support\Helpers\FormatHelper::formatCurrency($w['sponsor'])]) }}
                                </div>
                                <div class="w-full flex items-end justify-center gap-2 h-40">
                                    <div class="w-7 sm:w-10 bg-[#006948] rounded-t-md transition-all hover:brightness-110 shadow-xs" style="height: {{ $w['height_spent'] }}%;"></div>
                                    <div class="w-7 sm:w-10 bg-slate-400 rounded-t-md transition-all hover:brightness-110 shadow-xs" style="height: {{ $w['height_sponsor'] }}%;"></div>
                                </div>
                                <span class="text-xs text-slate-500 pt-2 font-medium">{{ $w['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Metric Footer Note -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                <span>{{ __('global.statistics.realtime_data_note') }}</span>
                <span class="font-semibold text-slate-800">{{ $peakWeekText }}</span>
            </div>
        </div>

        <!-- Right Card (1/3): Cơ cấu chi tiêu theo Danh mục -->
        <div class="lg:col-span-4 bg-white border border-slate-200 rounded-2xl p-6 shadow-2xs flex flex-col justify-between space-y-6">
            <div>
                <h2 class="text-base font-bold text-slate-900">{{ __('global.statistics.category_chart_title') }}</h2>
                <p class="text-xs text-slate-500 mt-0.5">{{ __('global.statistics.category_chart_subtitle') }}</p>

                @if(!empty($categoryStats))
                    <!-- Visual Stacked Bar -->
                    <div class="mt-6">
                        <div class="h-3 w-full rounded-full flex overflow-hidden bg-slate-100">
                            @foreach($categoryStats as $c)
                                <div class="h-full" style="width: {{ $c['percent'] }}%; background-color: {{ $c['color'] }};" title="{{ $c['name'] }}: {{ $c['percent'] }}%"></div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Breakdown List -->
                    <div class="mt-6 space-y-4 text-xs">
                        @foreach($categoryStats as $c)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $c['color'] }};"></span>
                                    <span class="text-slate-800 font-medium truncate">{{ $c['name'] }}</span>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="text-slate-900 font-semibold tabular-nums">{{ __('global.statistics.orders_count', ['count' => $c['quantity']]) }}</span>
                                    <span class="text-[11px] text-slate-400 block">{{ __('global.statistics.percent_total', ['percent' => $c['percent']]) }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-10 text-center text-slate-400 text-xs">
                        <span class="material-symbols-outlined text-[28px] text-slate-300 block mb-1">donut_large</span>
                        {{ __('global.statistics.no_category_data') }}
                    </div>
                @endif
            </div>

            <div class="pt-4 border-t border-slate-100 -mx-6 -mb-6 p-4 bg-slate-50 rounded-b-2xl flex items-center justify-between text-xs">
                <span class="text-slate-500 font-medium">{{ __('global.statistics.net_spent_total') }}</span>
                <span class="text-base font-bold text-slate-900 tabular-nums">{{ \App\Support\Helpers\FormatHelper::formatCurrency($totalSpent) }}</span>
            </div>
        </div>
    </div>

    <!-- Data Visualization Row 2 (1/2 + 1/2 Ranked Tables) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
        <!-- Left Card (1/2): Top Quán & Cửa hàng đặt nhiều nhất -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-2xs">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h2 class="text-base font-bold text-slate-900">{{ __('global.statistics.top_restaurants_title') }}</h2>
                    <p class="text-xs text-slate-500 mt-0.5">{{ __('global.statistics.top_restaurants_subtitle') }}</p>
                </div>
                <span class="text-xs text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md font-medium">T{{ date('m/Y') }}</span>
            </div>

            <div class="mt-2 overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="h-10 border-b border-slate-100 text-[11px] text-slate-400 uppercase tracking-wider">
                            <th class="py-2 pl-2">{{ __('global.statistics.th_brand') }}</th>
                            <th class="py-2 text-center">{{ __('global.statistics.th_order_count') }}</th>
                            <th class="py-2 text-right pr-2">{{ __('global.statistics.th_total_spent') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($topRestaurants as $res)
                            <tr class="h-12 hover:bg-slate-50/70 transition-colors">
                                <td class="py-2 pl-2 flex items-center gap-2.5">
                                    <span class="w-5 h-5 rounded-full {{ $loop->first ? 'bg-[#006948] text-white' : 'bg-slate-100 text-slate-700' }} text-[11px] font-bold flex items-center justify-center shrink-0">
                                        {{ $res['rank'] ?? $loop->iteration }}
                                    </span>
                                    <div class="min-w-0">
                                        <div class="font-medium text-slate-800 truncate">{{ $res['name'] }}</div>
                                        <div class="text-[11px] text-slate-400 truncate">{{ $res['sub_title'] }}</div>
                                    </div>
                                </td>
                                <td class="py-2 text-center font-medium tabular-nums text-slate-600">{{ __('global.statistics.orders_count', ['count' => $res['order_count']]) }}</td>
                                <td class="py-2 text-right pr-2 font-semibold text-slate-900 tabular-nums">{{ \App\Support\Helpers\FormatHelper::formatCurrency($res['spent']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-8 text-center text-slate-400 text-xs">
                                    <span class="material-symbols-outlined text-slate-300 text-[24px] mb-1 block">storefront</span>
                                    {{ __('global.statistics.empty_restaurants') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right Card (1/2): Chi tiêu theo từng Room / Phòng ban -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-2xs">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h2 class="text-base font-bold text-slate-900">{{ __('global.statistics.room_spending_title') }}</h2>
                    <p class="text-xs text-slate-500 mt-0.5">{{ __('global.statistics.room_spending_subtitle') }}</p>
                </div>
                <span class="material-symbols-outlined text-slate-400 text-lg">groups</span>
            </div>

            <div class="mt-2 overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="h-10 border-b border-slate-100 text-[11px] text-slate-400 uppercase tracking-wider">
                            <th class="py-2 pl-2">{{ __('global.statistics.th_room_group') }}</th>
                            <th class="py-2 text-center">{{ __('global.statistics.th_orders') }}</th>
                            <th class="py-2 text-right pr-2">{{ __('global.statistics.th_spent_sponsor') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($roomStats as $rStat)
                            <tr class="h-14 hover:bg-slate-50/70 transition-colors">
                                <td class="py-2 pl-2">
                                    <div class="font-medium text-slate-800 flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full {{ $loop->first ? 'bg-[#006948]' : ($loop->iteration === 2 ? 'bg-slate-400' : 'bg-amber-400') }}"></span>
                                        {{ $rStat['name'] }}
                                    </div>
                                    <div class="text-[11px] text-slate-400 pl-3.5">{{ $rStat['location'] }}</div>
                                </td>
                                <td class="py-2 text-center font-medium tabular-nums text-slate-600">{{ __('global.statistics.orders_count', ['count' => $rStat['order_count']]) }}</td>
                                <td class="py-2 text-right pr-2">
                                    <span class="font-semibold text-slate-900 block tabular-nums">{{ \App\Support\Helpers\FormatHelper::formatCurrency($rStat['spent']) }}</span>
                                    @if($rStat['sponsor'] > 0)
                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] bg-emerald-50 text-emerald-800 font-semibold border border-emerald-200">
                                            Sponsor: {{ \App\Support\Helpers\FormatHelper::formatCurrency($rStat['sponsor']) }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] bg-slate-100 text-slate-500 font-medium">
                                            {{ __('global.statistics.no_sponsor') }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-8 text-center text-slate-400 text-xs">
                                    <span class="material-symbols-outlined text-slate-300 text-[24px] mb-1 block">meeting_room</span>
                                    {{ __('global.statistics.empty_rooms') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
</x-global.layout>

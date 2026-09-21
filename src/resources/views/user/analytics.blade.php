<x-room.layout :room="$room" :room-user="$roomUser" :user-rooms="$userRooms" :unread-notifications-count="$unreadNotificationsCount" :active-tab="'analytics'" :title="__('room.analytics.page_title')">
    @php
        $metrics = [
            ['label' => __('room.analytics.orders_placed'), 'icon' => 'receipt_long', 'value' => $totalOrders, 'detail' => __('room.analytics.orders_unit', ['cups' => $totalCups]) . ' · ' . __('room.analytics.participation_rate', ['percent' => $participationRate])],
            ['label' => __('room.analytics.total_spent'), 'icon' => 'payments', 'value' => \App\Support\Helpers\FormatHelper::formatCurrency($totalAmount), 'detail' => __('room.analytics.weekly_avg', ['amount' => \App\Support\Helpers\FormatHelper::formatCurrency($weeklyAverage)])],
            ['label' => __('room.analytics.sponsor_received'), 'icon' => 'redeem', 'value' => \App\Support\Helpers\FormatHelper::formatCurrency($sponsorReceived), 'detail' => __('room.analytics.savings_percent', ['percent' => $savingsPercent])],
        ];
    @endphp
    <div class="space-y-4">
        <section class="rounded-xl border border-slate-200/80 bg-white p-4 sm:p-4.5 shadow-2xs">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                <div class="flex items-start gap-2.5">
                    <span class="w-9 h-9 shrink-0 rounded-lg bg-emerald-50 text-[#006948] flex items-center justify-center">
                        <span class="material-symbols-outlined text-[20px]" aria-hidden="true">monitoring</span>
                    </span>
                    <div>
                        <h1 class="text-sm sm:text-base font-bold text-slate-900 tracking-tight">{{ __('room.analytics.page_title') }}</h1>
                        <p class="text-[11px] text-slate-400 mt-1 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[13px]" aria-hidden="true">date_range</span>
                            {{ $periodStart->format('d/m/Y') }} – {{ $periodEnd->format('d/m/Y') }}
                        </p>
                    </div>
                </div>
                <nav class="flex self-start lg:self-auto items-center gap-1 rounded-lg bg-slate-100 border border-slate-200/70 p-0.5" aria-label="{{ __('room.analytics.period_filter') }}">
                    @foreach(\App\Enums\AnalyticsPeriod::cases() as $option)
                        {{-- "Whole year" filter is hidden for now; drop this line to show it again. --}}
                        @continue($option === \App\Enums\AnalyticsPeriod::Year)
                        <a href="{{ route('user.analytics.room', ['room' => $room->slug, 'period' => $option->value]) }}"
                           @if($period === $option) aria-current="page" @endif
                           class="px-2.5 sm:px-3 py-1 rounded-md text-xs font-semibold whitespace-nowrap transition-colors {{ $period === $option ? 'bg-[#006948] text-white shadow-2xs' : 'text-slate-500 hover:text-slate-900 hover:bg-white/60' }}">
                            {{ __('room.analytics.filter_' . $option->value) }}
                        </a>
                    @endforeach
                </nav>
            </div>
        </section>
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
            @foreach($metrics as $metric)
                <section class="rounded-xl border border-slate-200/80 bg-white p-3.5 sm:p-4 shadow-2xs flex flex-col justify-between">
                    <div class="flex items-center justify-between gap-2">
                        <h2 class="text-xs font-semibold text-slate-500">{{ $metric['label'] }}</h2>
                        <span class="material-symbols-outlined text-[#006948] text-[18px]" aria-hidden="true">{{ $metric['icon'] }}</span>
                    </div>
                    <div class="mt-2.5">
                        <p class="text-lg sm:text-xl font-bold font-mono text-slate-900 tabular-nums break-words">{{ $metric['value'] }}</p>
                        <p class="text-[11px] text-slate-500 mt-1">{{ $metric['detail'] }}</p>
                    </div>
                </section>
            @endforeach
            <section class="rounded-xl border border-emerald-200/70 bg-emerald-50/50 p-3.5 sm:p-4 shadow-2xs flex flex-col justify-between">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-xs font-semibold text-slate-500">{{ __('room.analytics.favorite_drink') }}</h2>
                    <span class="material-symbols-outlined text-[#006948] text-[18px]" aria-hidden="true">stars</span>
                </div>
                <div class="mt-2.5">
                    <p class="text-sm sm:text-base font-bold text-[#006948] break-words">{{ $topItems->first()['name'] ?? __('room.analytics.no_favorite_item') }}</p>
                    <p class="text-[11px] text-slate-500 mt-1">{{ __('room.analytics.ordered_times', ['count' => $topItems->first()['quantity'] ?? 0]) }}</p>
                </div>
            </section>
        </div>
        <section class="rounded-xl border border-slate-200/80 bg-white shadow-2xs overflow-hidden">
            <div class="p-3 sm:p-3.5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div class="flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[#006948] text-[18px]" aria-hidden="true">leaderboard</span>
                    <h2 class="text-xs sm:text-sm font-bold text-slate-900">{{ __('room.analytics.popular_in_room') }}</h2>
                </div>
                <span class="text-[11px] font-medium text-slate-400">{{ __('room.analytics.filter_' . $period->value) }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-[10px] sm:text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <tr>
                            <th scope="col" class="py-2.5 px-3 sm:px-4 whitespace-nowrap">{{ __('room.analytics.table_rank') }}</th>
                            <th scope="col" class="py-2.5 px-3 whitespace-nowrap">{{ __('room.analytics.table_item_name') }}</th>
                            <th scope="col" class="py-2.5 px-3 text-right whitespace-nowrap">{{ __('room.analytics.table_quantity') }}</th>
                            <th scope="col" class="py-2.5 px-3 sm:px-4 text-right whitespace-nowrap">{{ __('room.analytics.table_total_amount') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($topItems as $index => $item)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-2.5 px-3 sm:px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full {{ $index === 0 ? 'bg-amber-100 text-amber-800' : ($index === 1 ? 'bg-slate-100 text-slate-700' : ($index === 2 ? 'bg-orange-100 text-orange-800' : 'text-slate-400')) }} text-[11px] font-bold">
                                        {{ $index + 1 }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 font-semibold text-slate-900 whitespace-nowrap">{{ $item['name'] }}</td>
                                <td class="py-2.5 px-3 text-right text-slate-600 font-mono whitespace-nowrap">{{ $item['quantity'] }}</td>
                                <td class="py-2.5 px-3 sm:px-4 text-right font-semibold text-slate-900 font-mono whitespace-nowrap">{{ \App\Support\Helpers\FormatHelper::formatCurrency($item['total_amount']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-slate-400 text-xs">
                                    <span class="material-symbols-outlined text-[28px] text-slate-300 block mb-1" aria-hidden="true">inbox</span>
                                    <p class="font-medium text-slate-600">{{ __('room.analytics.no_data') }}</p>
                                    <p class="text-[11px] text-slate-400">{{ __('room.analytics.no_data_period') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-room.layout>

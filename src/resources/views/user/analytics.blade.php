<x-room.layout :room="$room" :room-user="$roomUser" :active-campaign="$activeCampaign" :user-rooms="$userRooms" :unread-notifications-count="$unreadNotificationsCount" :active-tab="'analytics'" :title="__('room.analytics.page_title')">
    @php
        $metrics = [
            ['label' => __('room.analytics.orders_placed'), 'icon' => 'receipt_long', 'value' => $totalOrders, 'detail' => __('room.analytics.orders_unit', ['cups' => $totalCups]) . ' · ' . __('room.analytics.participation_rate', ['percent' => $participationRate])],
            ['label' => __('room.analytics.total_spent'), 'icon' => 'payments', 'value' => \App\Support\Helpers\FormatHelper::formatCurrency($totalAmount), 'detail' => __('room.analytics.weekly_avg', ['amount' => \App\Support\Helpers\FormatHelper::formatCurrency($weeklyAverage)])],
            ['label' => __('room.analytics.sponsor_received'), 'icon' => 'redeem', 'value' => \App\Support\Helpers\FormatHelper::formatCurrency($sponsorReceived), 'detail' => __('room.analytics.savings_percent', ['percent' => $savingsPercent])],
        ];
    @endphp
    <div class="space-y-6">
        <section class="rounded-2xl border border-slate-200/80 bg-white p-5 sm:p-6 shadow-xs">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5">
                <div class="flex items-start gap-3">
                    <span class="w-11 h-11 shrink-0 rounded-xl bg-emerald-50 text-[#006948] flex items-center justify-center">
                        <span class="material-symbols-outlined text-[24px]" aria-hidden="true">monitoring</span>
                    </span>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">{{ __('room.analytics.page_title') }}</h1>
                        <p class="text-sm text-slate-500 mt-1">{{ $room->name }} · {{ __('room.analytics.for_user') }} {{ $user->name }}</p>
                        <p class="text-xs text-slate-400 mt-2 flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[15px]" aria-hidden="true">date_range</span>
                            {{ $periodStart->format('d/m/Y') }} – {{ $periodEnd->format('d/m/Y') }}
                        </p>
                    </div>
                </div>
                <nav class="flex self-start lg:self-auto items-center gap-1 rounded-xl bg-slate-100 border border-slate-200/70 p-1" aria-label="{{ __('room.analytics.period_filter') }}">
                    @foreach(\App\Enums\AnalyticsPeriod::cases() as $option)
                        <a href="{{ route('user.analytics.room', ['room' => $room->slug, 'period' => $option->value]) }}"
                           @if($period === $option) aria-current="page" @endif
                           class="px-3 sm:px-4 py-2 rounded-lg text-xs sm:text-sm font-semibold whitespace-nowrap transition-colors {{ $period === $option ? 'bg-[#006948] text-white shadow-xs' : 'text-slate-500 hover:text-slate-900 hover:bg-white/60' }}">
                            {{ __('room.analytics.filter_' . $option->value) }}
                        </a>
                    @endforeach
                </nav>
            </div>
        </section>
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            @foreach($metrics as $metric)
                <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-xs font-semibold text-slate-500">{{ $metric['label'] }}</h2>
                        <span class="material-symbols-outlined text-[#006948] text-[22px]" aria-hidden="true">{{ $metric['icon'] }}</span>
                    </div>
                    <p class="mt-4 text-2xl sm:text-3xl font-bold text-slate-900 tabular-nums break-words">{{ $metric['value'] }}</p>
                    <p class="text-xs text-slate-500 mt-3">{{ $metric['detail'] }}</p>
                </section>
            @endforeach
            <section class="rounded-2xl border border-emerald-200/70 bg-emerald-50/50 p-5 shadow-xs">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-xs font-semibold text-slate-500">{{ __('room.analytics.favorite_drink') }}</h2>
                    <span class="material-symbols-outlined text-[#006948] text-[22px]" aria-hidden="true">stars</span>
                </div>
                <p class="mt-4 text-lg font-bold text-[#006948] break-words">{{ $topItems->first()['name'] ?? __('room.analytics.no_favorite_item') }}</p>
                <p class="text-xs text-slate-500 mt-3">{{ __('room.analytics.ordered_times', ['count' => $topItems->first()['quantity'] ?? 0]) }}</p>
            </section>
        </div>
        <section class="rounded-2xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
            <div class="p-5 sm:p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#006948] text-[22px]" aria-hidden="true">leaderboard</span>
                    <h2 class="text-base font-bold text-slate-900">{{ __('room.analytics.popular_in_room') }}</h2>
                </div>
                <span class="text-xs font-medium text-slate-400">{{ __('room.analytics.filter_' . $period->value) }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold text-slate-500">
                        <tr>
                            <th scope="col" class="py-3 px-5 sm:px-6">{{ __('room.analytics.table_rank') }}</th>
                            <th scope="col" class="py-3 px-4">{{ __('room.analytics.table_item_name') }}</th>
                            <th scope="col" class="py-3 px-4 text-right whitespace-nowrap">{{ __('room.analytics.table_quantity') }}</th>
                            <th scope="col" class="py-3 px-5 sm:px-6 text-right whitespace-nowrap">{{ __('room.analytics.table_total_amount') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($topItems as $index => $item)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-4 px-5 sm:px-6 font-semibold text-slate-400">#{{ $index + 1 }}</td>
                                <td class="py-4 px-4">
                                    <div class="flex items-center gap-2.5">
                                        <span class="w-8 h-8 shrink-0 rounded-lg bg-emerald-50 text-[#006948] flex items-center justify-center">
                                            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">local_cafe</span>
                                        </span>
                                        <span class="font-semibold text-slate-800">{{ $item['name'] }}</span>
                                    </div>
                                </td>
                                <td class="py-4 px-4 text-right tabular-nums whitespace-nowrap">{{ __('room.analytics.cups_count', ['count' => $item['quantity']]) }}</td>
                                <td class="py-4 px-5 sm:px-6 text-right font-semibold text-[#006948] tabular-nums whitespace-nowrap">{{ \App\Support\Helpers\FormatHelper::formatCurrency($item['total_amount']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-14 sm:py-20">
                                    <div class="flex flex-col items-center justify-center gap-3 text-center">
                                        <span class="w-16 h-16 rounded-2xl bg-slate-50 border border-slate-100 text-slate-300 flex items-center justify-center">
                                            <span class="material-symbols-outlined text-[34px]" aria-hidden="true">bar_chart</span>
                                        </span>
                                        <p class="text-sm font-semibold text-slate-600">{{ __('room.analytics.no_data') }}</p>
                                        <p class="text-xs text-slate-400">{{ __('room.analytics.no_data_period') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-room.layout>

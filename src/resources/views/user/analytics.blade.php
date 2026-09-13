<x-room.layout :room="$room" :room-user="$roomUser" :active-campaign="$activeCampaign" :user-rooms="$userRooms" :unread-notifications-count="$unreadNotificationsCount" current-nav="thong-ke-room" :title="__('room.analytics.page_title')">
    <div class="flex flex-col w-full gap-space-lg">
        <!-- Top Title & Filter Bar -->
        <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-space-md pb-space-sm">
            <div class="flex flex-col gap-space-xs">
                <div class="flex items-center gap-space-xs">
                    <span class="px-2 py-0.5 rounded bg-primary-fixed text-on-primary-fixed font-label-sm text-label-sm font-semibold">Báo cáo nội bộ</span>
                    <span class="font-body-sm text-body-sm text-on-surface-variant">{{ __('room.analytics.updated_at', ['time' => now()->format('H:i')]) }}</span>
                </div>
                <h1 class="font-display-lg text-display-lg text-on-surface tracking-tight">{{ __('room.analytics.page_title') }} {{ $room->name }}</h1>
                <p class="font-body-md text-body-md text-on-surface-variant">{{ __('room.analytics.subtitle') }} dành cho <span class="font-semibold text-on-surface">{{ $user->name }}</span>.</p>
            </div>
            <div class="flex items-center gap-space-sm self-start lg:self-auto">
                <div class="flex items-center bg-surface-container rounded-xl p-1 border border-outline-variant/30">
                    <button class="px-3 py-1 rounded-lg font-label-sm text-label-sm bg-surface-container-lowest text-on-surface shadow-sm font-bold" type="button">{{ __('room.analytics.filter_month') }}</button>
                    <button class="px-3 py-1 rounded-lg font-label-sm text-label-sm text-on-surface-variant hover:text-on-surface transition-all" type="button">{{ __('room.analytics.filter_quarter') }}</button>
                    <button class="px-3 py-1 rounded-lg font-label-sm text-label-sm text-on-surface-variant hover:text-on-surface transition-all" type="button">{{ __('room.analytics.filter_year') }}</button>
                </div>
            </div>
        </div>

        <!-- 4 Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-space-md">
            <!-- Card 1: Tổng đơn -->
            <div class="bg-surface-container-lowest p-space-md rounded-2xl shadow-sm border border-outline-variant/30 flex flex-col justify-between relative overflow-hidden group hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between">
                    <span class="font-label-md text-label-md text-on-surface-variant font-medium">{{ __('room.analytics.orders_placed') }}</span>
                    <div class="w-9 h-9 rounded-xl bg-surface-container-low flex items-center justify-center text-primary">
                        <span class="material-symbols-outlined text-[20px]">local_cafe</span>
                    </div>
                </div>
                <div class="mt-space-md">
                    <div class="flex items-baseline gap-space-xs">
                        <span class="font-display-lg text-display-lg text-on-surface font-bold tracking-tight">{{ $totalOrders }}</span>
                        <span class="font-headline-sm text-headline-sm text-on-surface-variant">đơn ({{ $totalCups }} ly)</span>
                    </div>
                    <div class="mt-space-xs flex items-center gap-1.5 text-primary font-label-sm text-label-sm font-semibold">
                        <span class="material-symbols-outlined text-[14px]">check_circle</span>
                        <span>{{ __('room.analytics.participation_rate', ['percent' => $participationRate]) }}</span>
                    </div>
                </div>
                <div class="w-full bg-surface-container h-1.5 rounded-full mt-space-md overflow-hidden">
                    <div class="bg-primary h-full rounded-full transition-all duration-500" style="width: {{ min(100, $participationRate) }}%;"></div>
                </div>
            </div>

            <!-- Card 2: Tổng chi tiêu -->
            <div class="bg-surface-container-lowest p-space-md rounded-2xl shadow-sm border border-outline-variant/30 flex flex-col justify-between relative overflow-hidden group hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between">
                    <span class="font-label-md text-label-md text-on-surface-variant font-medium">{{ __('room.analytics.total_spent') }}</span>
                    <div class="w-9 h-9 rounded-xl bg-surface-container-low flex items-center justify-center text-primary">
                        <span class="material-symbols-outlined text-[20px]">payments</span>
                    </div>
                </div>
                <div class="mt-space-md">
                    <div class="flex items-baseline gap-space-xs">
                        <span class="font-display-lg text-display-lg text-on-surface font-bold tracking-tight">{{ number_format($totalAmount, 0, ',', '.') }}</span>
                        <span class="font-headline-sm text-headline-sm text-on-surface-variant">đ</span>
                    </div>
                    <div class="mt-space-xs flex items-center gap-1.5 text-on-surface-variant font-label-sm text-label-sm">
                        <span class="material-symbols-outlined text-[14px]">trending_up</span>
                        <span>{{ __('room.analytics.weekly_avg', ['amount' => number_format($weeklyAverage, 0, ',', '.') . 'đ']) }}</span>
                    </div>
                </div>
                <div class="w-full bg-surface-container h-1.5 rounded-full mt-space-md overflow-hidden">
                    <div class="bg-secondary h-full rounded-full transition-all duration-500" style="width: 65%;"></div>
                </div>
            </div>

            <!-- Card 3: Sponsor nhận được -->
            <div class="bg-surface-container-lowest p-space-md rounded-2xl shadow-sm border border-outline-variant/30 flex flex-col justify-between relative overflow-hidden group hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between">
                    <span class="font-label-md text-label-md text-on-surface-variant font-medium">Sponsor đã nhận từ Room</span>
                    <div class="w-9 h-9 rounded-xl bg-primary-fixed text-on-primary-fixed flex items-center justify-center">
                        <span class="material-symbols-outlined text-[20px]">redeem</span>
                    </div>
                </div>
                <div class="mt-space-md">
                    <div class="flex items-baseline gap-space-xs">
                        <span class="font-display-lg text-display-lg text-primary font-bold tracking-tight">{{ number_format($sponsorReceived > 0 ? $sponsorReceived : (int)($totalAmount * 0.25), 0, ',', '.') }}</span>
                        <span class="font-headline-sm text-headline-sm text-primary">đ</span>
                    </div>
                    <div class="mt-space-xs flex items-center gap-1.5 text-primary font-label-sm text-label-sm font-semibold">
                        <span class="material-symbols-outlined text-[14px]">savings</span>
                        <span>Tiết kiệm ~25% chi phí</span>
                    </div>
                </div>
                <div class="w-full bg-surface-container h-1.5 rounded-full mt-space-md overflow-hidden">
                    <div class="bg-primary-container h-full rounded-full transition-all duration-500" style="width: 25%;"></div>
                </div>
            </div>

            <!-- Card 4: Món ruột -->
            <div class="bg-surface-container-lowest p-space-md rounded-2xl shadow-sm border border-outline-variant/30 flex flex-col justify-between relative overflow-hidden group hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between">
                    <span class="font-label-md text-label-md text-on-surface-variant font-medium">Món yêu thích</span>
                    <div class="w-9 h-9 rounded-xl bg-surface-container-low flex items-center justify-center text-tertiary">
                        <span class="material-symbols-outlined text-[20px]">stars</span>
                    </div>
                </div>
                <div class="mt-space-md">
                    <div class="flex items-baseline gap-space-xs truncate">
                        <span class="font-headline-lg text-headline-lg text-on-surface font-bold truncate">
                            {{ $topItems->first()['name'] ?? 'Cà phê sữa đá' }}
                        </span>
                    </div>
                    <div class="mt-space-xs flex items-center gap-1.5 text-on-surface-variant font-label-sm text-label-sm">
                        <span class="material-symbols-outlined text-[14px]">repeat</span>
                        <span>Đã order {{ $topItems->first()['quantity'] ?? 1 }} lần</span>
                    </div>
                </div>
                <div class="w-full bg-surface-container h-1.5 rounded-full mt-space-md overflow-hidden">
                    <div class="bg-tertiary h-full rounded-full transition-all duration-500" style="width: 80%;"></div>
                </div>
            </div>
        </div>

        <!-- Top Popular Items Table -->
        <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-outline-variant/30 overflow-hidden">
            <div class="p-space-md border-b border-outline-variant/20 flex items-center justify-between">
                <div class="flex items-center gap-space-sm">
                    <span class="material-symbols-outlined text-primary text-[20px]">leaderboard</span>
                    <h3 class="font-headline-sm text-headline-sm text-on-surface font-bold">{{ __('room.analytics.popular_in_room') }}</h3>
                </div>
            </div>

            <div class="w-full overflow-x-auto">
                <table class="w-full text-left font-body-sm text-body-sm">
                    <thead>
                        <tr class="bg-surface-container-low text-on-surface-variant font-label-sm text-label-sm uppercase tracking-wider border-b border-outline-variant/20">
                            <th class="py-3 px-space-md">Hạng</th>
                            <th class="py-3 px-space-sm">Tên đồ uống</th>
                            <th class="py-3 px-space-sm text-right">Số lượng đã đặt</th>
                            <th class="py-3 px-space-sm text-right">Tổng chi tiêu</th>
                        </tr>
                    </thead>
                    <tbody class="text-on-surface divide-y divide-outline-variant/20">
                        @forelse($topItems as $index => $item)
                            <tr class="hover:bg-surface-container-low/50 transition-colors">
                                <td class="py-4 px-space-md font-bold {{ $index === 0 ? 'text-tertiary' : ($index === 1 ? 'text-secondary' : 'text-on-surface-variant') }}">
                                    #{{ $index + 1 }}
                                </td>
                                <td class="py-4 px-space-sm font-semibold text-on-surface flex items-center gap-2">
                                    <span class="w-8 h-8 rounded-lg bg-surface-container-high flex items-center justify-center text-primary">
                                        <span class="material-symbols-outlined text-[18px]">local_cafe</span>
                                    </span>
                                    <span>{{ $item['name'] }}</span>
                                </td>
                                <td class="py-4 px-space-sm text-right font-tabular-nums font-bold text-on-surface">{{ $item['quantity'] }} ly</td>
                                <td class="py-4 px-space-sm text-right font-tabular-nums font-bold text-primary">{{ number_format($item['total_amount'], 0, ',', '.') }}đ</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-on-surface-variant font-body-sm">
                                    Chưa có dữ liệu thống kê đồ uống nào.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-room.layout>

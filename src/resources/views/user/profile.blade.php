<x-room.layout :room="$room" :room-user="$roomUser" :active-campaign="$activeCampaign" :user-rooms="$userRooms" :unread-notifications-count="$unreadNotificationsCount" :active-tab="'profile'" :title="__('room.profile.page_title')">
    @php
        $memberStatus = $roomUser->status instanceof \App\Enums\RoomUserStatus
            ? $roomUser->status->value
            : (string) $roomUser->status;
        $memberStatusLabel = __('room.profile.status_' . $memberStatus);
    @endphp
    <div class="flex flex-col w-full gap-space-lg">
        <!-- Profile Header Card -->
        <section class="w-full bg-surface-container-lowest rounded-2xl shadow-sm border border-outline-variant/30 overflow-hidden p-space-md lg:p-space-lg relative">
            <div class="absolute top-0 right-0 w-96 h-96 bg-primary/5 rounded-full blur-3xl pointer-events-none -mr-20 -mt-20"></div>
            <div class="flex flex-col lg:flex-row gap-space-lg items-start justify-between relative z-10">
                <!-- User Info & Avatar -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-space-md w-full lg:w-auto">
                    <div class="relative shrink-0">
                        <img class="w-24 h-24 lg:w-28 lg:h-28 rounded-2xl object-cover shadow-sm border-2 border-[#006948]/30 ring-4 ring-emerald-500/10"
                             src="{{ $user->avatar_url ?? 'https://lh3.googleusercontent.com/aida-public/AB6AXuDABv8Oe8xyc5YFfx-Urh180Ei9oWDlXncJYYMhGsQyXKi9hH-Ozqz3OugY2_1YBVNW7gx3_8lQ0e663-MZrk9sfuwQNx_hfyyQtK2Zhj_zZGIVtA4PdjFBpNhgR9tn9snH3UYWVQ68_CKNQt5duVHzjZFHBqTbF8GWsCP5QSCLqXnCkE_RM9NLeqxpc7hKb0xusaVGpsBgdlLGILxnD3Fq8gdCU6OgF-qluxXmwytHivLwPF5jc5JUug' }}"
                             alt="{{ $user->name }}"
                             loading="lazy" onerror="this.onerror=null;this.src='{{ asset('images/default-avatar.svg') }}';"/>
                    </div>
                    <div class="flex flex-col gap-space-xs">
                        <div class="flex flex-wrap items-center gap-space-xs">
                            <h1 class="font-headline-lg text-headline-lg text-on-surface font-bold">{{ $user->name }}</h1>
                            <span class="px-2 py-0.5 rounded bg-primary-fixed text-on-primary-fixed font-label-sm text-label-sm flex items-center gap-1 font-semibold">
                                <span class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse"></span> {{ $memberStatusLabel }}
                            </span>
                        </div>
                        <p class="font-label-md text-label-md text-on-surface-variant flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px] text-primary">terminal</span>
                            <span>{{ $roomUser->role === 'admin' ? __('room.profile.role_admin') : __('room.profile.role_member') }} • {{ $room->name }}</span>
                        </p>
                        <div class="flex flex-wrap items-center gap-x-space-md gap-y-1 mt-1 text-on-surface-variant font-body-sm text-body-sm">
                            <span class="flex items-center gap-1">
                                <span class="material-symbols-outlined text-[15px]">mail</span> {{ $user->email }}
                            </span>
                            <span class="flex items-center gap-1">
                                <span class="material-symbols-outlined text-[15px]">badge</span> {{ __('room.profile.member_code_prefix') }}: {{ $roomUser->user_code }}
                            </span>
                            <span class="flex items-center gap-1">
                                <span class="material-symbols-outlined text-[15px]">calendar_today</span> {{ __('room.profile.joined_date_prefix') }}: {{ $roomUser->created_at?->format('d/m/Y') }}
                            </span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Quick Stats Metric Band -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-space-md mt-space-lg pt-space-md border-t border-outline-variant/20">
                <!-- Metric 1 -->
                <div class="bg-surface-container-low rounded-xl p-space-md flex items-center gap-space-md">
                    <div class="w-11 h-11 rounded-xl bg-surface-container flex items-center justify-center text-primary shrink-0">
                        <span class="material-symbols-outlined text-[24px]">receipt_long</span>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">{{ __('room.profile.total_orders') }}</span>
                        <div class="flex items-baseline gap-1">
                            <span class="font-headline-md text-headline-md font-tabular-nums text-on-surface font-bold">{{ $totalOrders }}</span>
                            <span class="font-body-sm text-body-sm text-on-surface-variant">{{ __('room.profile.orders_unit') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Metric 2 -->
                <div class="bg-surface-container-low rounded-xl p-space-md flex items-center gap-space-md">
                    <div class="w-11 h-11 rounded-xl bg-primary-fixed text-on-primary-fixed-variant flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-[24px]">account_balance_wallet</span>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">{{ __('room.profile.total_debt') }}</span>
                        <div class="flex items-baseline gap-1">
                            <span class="font-headline-md text-headline-md font-tabular-nums text-primary font-bold">{{ \App\Support\Helpers\FormatHelper::formatCurrency($totalDebt) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Metric 3 -->
                <div class="bg-surface-container-low rounded-xl p-space-md flex items-center gap-space-md">
                    <div class="w-11 h-11 rounded-xl bg-surface-container flex items-center justify-center text-primary shrink-0">
                        <span class="material-symbols-outlined text-[24px]">verified_user</span>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">{{ __('room.profile.total_spent') }}</span>
                        <div class="flex items-baseline gap-1">
                            <span class="font-headline-md text-headline-md font-tabular-nums text-on-surface font-bold">{{ number_format($totalSpent, 0, ',', '.') }}₫</span>
                            <span class="font-label-sm text-label-sm text-secondary font-semibold">{{ __('room.profile.accumulated') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Profile Details Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-space-lg items-stretch">
            <!-- Left 7 cols: Favorite items & Room specs -->
            <div class="lg:col-span-7 flex flex-col gap-space-md">
                <div class="bg-surface-container-lowest rounded-2xl p-space-lg shadow-sm border border-outline-variant/30 flex flex-col flex-1 min-h-[300px] gap-space-md">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-space-sm">
                            <span class="material-symbols-outlined text-primary text-[20px]">stars</span>
                            <h3 class="font-headline-sm text-headline-sm text-on-surface font-bold">{{ __('room.profile.favorite_items') }}</h3>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 content-start flex-1 gap-2">
                        @forelse($favoriteItems as $fav)
                            <div class="bg-surface-container-low rounded-lg p-2.5 flex items-center justify-between gap-2 min-w-0">
                                <div class="flex items-center gap-2 min-w-0">
                                    <div class="w-8 h-8 rounded-lg bg-surface-container-high flex items-center justify-center text-primary shrink-0">
                                        <span class="material-symbols-outlined text-[17px]">local_cafe</span>
                                    </div>
                                    <span class="text-sm leading-5 font-semibold text-on-surface truncate">{{ $fav['name'] }}</span>
                                </div>
                                <span class="px-2 py-0.5 rounded-full bg-primary-fixed text-on-primary-fixed text-[10px] leading-4 font-bold whitespace-nowrap shrink-0">
                                    {{ __('room.profile.ordered_times', ['count' => $fav['count']]) }}
                                </span>
                            </div>
                        @empty
                            <div class="sm:col-span-2 p-6 text-center text-on-surface-variant font-body-sm flex flex-col flex-1 items-center justify-center gap-3">
                                <span class="w-14 h-14 rounded-2xl bg-surface-container-low text-primary/60 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[32px]" aria-hidden="true">local_cafe</span>
                                </span>
                                <p>{{ __('room.profile.no_favorite_drinks') }}</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Right 5 cols: Department info -->
            <div class="lg:col-span-5 flex flex-col gap-space-md">
                <div class="bg-surface-container-lowest rounded-2xl p-space-lg shadow-sm border border-outline-variant/30 flex flex-col flex-1 min-h-[300px] gap-space-md">
                    <div class="flex items-center gap-space-sm">
                        <span class="material-symbols-outlined text-primary text-[20px]">domain</span>
                        <h3 class="font-headline-sm text-headline-sm text-on-surface font-bold">{{ __('room.profile.contact_info') }}</h3>
                    </div>

                    <div class="flex flex-col gap-3">
                        <div class="flex justify-between items-center py-2 border-b border-outline-variant/20 text-body-sm">
                            <span class="text-on-surface-variant">{{ __('room.profile.department_label') }}</span>
                            <span class="font-bold text-on-surface">{{ $room->name }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-outline-variant/20 text-body-sm">
                            <span class="text-on-surface-variant">{{ __('room.profile.room_code_label') }}</span>
                            <span class="font-bold text-primary">{{ $room->code ?? $room->slug }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-outline-variant/20 text-body-sm">
                            <span class="text-on-surface-variant">{{ __('room.profile.last_activity_label') }}</span>
                            <span class="font-bold text-on-surface">{{ $roomUser->last_active_at?->format('d/m/Y H:i') ?? __('room.profile.no_activity') }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-outline-variant/20 text-body-sm">
                            <span class="text-on-surface-variant">{{ __('room.profile.member_status_label') }}</span>
                            <span class="font-bold text-primary">{{ $memberStatusLabel }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-room.layout>

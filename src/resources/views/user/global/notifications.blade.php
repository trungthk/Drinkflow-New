<x-global.layout 
    :title="'DrinkFlow - ' . __('global.notifications.page_title')" 
    :user="$user"
    activeTab="notifications" 
    :breadcrumbs="$breadcrumbs"
    :unreadNotificationsCount="$unreadCount"
>
<div class="space-y-6">
    <!-- Status Flash Notification -->
    @if(session('status'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between shadow-2xs">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-emerald-600 text-[20px]">check_circle</span>
                <span>{{ session('status') }}</span>
            </div>
            <button @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>
    @endif

    <!-- Page Header Area -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('global.notifications.title') }}</h1>
            <p class="text-sm text-slate-600 mt-0.5">{{ __('global.notifications.subtitle') }}</p>
        </div>
        <div class="flex items-center gap-2 self-start md:self-auto">
            <form action="{{ route('user.me.notifications.read-all') }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition-colors shadow-2xs cursor-pointer">
                    <span class="material-symbols-outlined text-[16px] text-slate-400">done_all</span>
                    <span>{{ __('global.notifications.mark_all_read') }}</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="bg-white border border-slate-200 rounded-2xl p-2 shadow-2xs flex flex-col md:flex-row items-center justify-between gap-3">
        <div class="flex items-center gap-1.5 overflow-x-auto w-full md:w-auto pb-1 md:pb-0 scrollbar-none">
            <a 
                href="{{ route('user.me.notifications', ['tab' => 'all']) }}" 
                class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap flex items-center gap-1.5 transition-colors {{ $tab === 'all' ? 'bg-[#006948] text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}"
            >
                <span>{{ __('global.notifications.tab_all') }}</span>
                <span class="px-1.5 py-0.2 rounded-full text-[11px] {{ $tab === 'all' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $allCount }}</span>
            </a>

            <a 
                href="{{ route('user.me.notifications', ['tab' => 'unread']) }}" 
                class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap flex items-center gap-1.5 transition-colors {{ $tab === 'unread' ? 'bg-[#006948] text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}"
            >
                <span>{{ __('global.notifications.tab_unread') }}</span>
                <span class="px-1.5 py-0.2 rounded-full text-[11px] {{ $tab === 'unread' ? 'bg-white/20 text-white' : 'bg-rose-100 text-rose-700 font-bold' }}">{{ $unreadCount }}</span>
            </a>

            <a 
                href="{{ route('user.me.notifications', ['tab' => 'room_order']) }}" 
                class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap flex items-center gap-1.5 transition-colors {{ $tab === 'room_order' ? 'bg-[#006948] text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}"
            >
                <span>{{ __('global.notifications.tab_room_order') }}</span>
                <span class="px-1.5 py-0.2 rounded-full text-[11px] {{ $tab === 'room_order' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $roomOrderCount }}</span>
            </a>

            <a 
                href="{{ route('user.me.notifications', ['tab' => 'payment']) }}" 
                class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap flex items-center gap-1.5 transition-colors {{ $tab === 'payment' ? 'bg-[#006948] text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}"
            >
                <span>{{ __('global.notifications.tab_payment') }}</span>
                <span class="px-1.5 py-0.2 rounded-full text-[11px] {{ $tab === 'payment' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $paymentCount }}</span>
            </a>

            <a 
                href="{{ route('user.me.notifications', ['tab' => 'security']) }}" 
                class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap flex items-center gap-1.5 transition-colors {{ $tab === 'security' ? 'bg-[#006948] text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}"
            >
                <span>{{ __('global.notifications.tab_security') }}</span>
                <span class="px-1.5 py-0.2 rounded-full text-[11px] {{ $tab === 'security' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $securityCount }}</span>
            </a>
        </div>

        <div class="flex items-center gap-2 w-full md:w-auto justify-end text-xs text-slate-500">
            <span>{{ __('global.notifications.sort_time') }}</span>
        </div>
    </div>

    <!-- Notification Cards List -->
    <div class="flex flex-col gap-3">
        @if($notifications->total() > 0)
            @foreach($notifications as $notif)
                @php
                    $isUnread = is_null($notif->read_at);
                    $icon = match($notif->type) {
                        'campaign.created' => 'local_mall',
                        'order.status' => 'restaurant',
                        'payment.confirmed' => 'qr_code_2',
                        'payment.due', 'debt.reminder' => 'payments',
                        'room.invite' => 'group_add',
                        'security.alert', 'device.new' => 'security',
                        default => 'notifications'
                    };
                @endphp
                <div class="bg-white border border-slate-200 hover:border-slate-300 rounded-2xl p-4 transition-all duration-150 shadow-2xs relative overflow-hidden flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 {{ $isUnread ? 'bg-emerald-50/10' : 'opacity-90' }}">
                    @if($isUnread)
                        <div class="absolute left-0 top-0 bottom-0 w-1 bg-[#006948]"></div>
                    @endif
                    <div class="flex items-start gap-3.5 flex-1 min-w-0">
                        <div class="w-10 h-10 rounded-xl bg-slate-50 border border-slate-200/80 text-[#006948] flex items-center justify-center shrink-0 mt-0.5">
                            <span class="material-symbols-outlined text-[22px]">{{ $icon }}</span>
                        </div>
                        <div class="space-y-1 min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-semibold text-slate-900">{{ $notif->title }}</span>
                                @if($isUnread)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">{{ __('global.notifications.badge_new') }}</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.2 rounded-full text-[10px] font-medium bg-slate-100 text-slate-500">{{ __('global.notifications.badge_read') }}</span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-600 leading-relaxed">{{ $notif->body }}</p>
                            <div class="flex items-center gap-4 text-xs text-slate-400 pt-0.5">
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[13px]">schedule</span>
                                    {{ $notif->created_at->diffForHumans() }}
                                </span>
                            </div>
                        </div>
                    </div>

                    @if($isUnread)
                        <div class="flex items-center gap-2 self-end sm:self-center shrink-0">
                            <form action="{{ route('user.notifications.read', $notif->id) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="px-3 py-1 rounded-xl text-xs font-medium text-[#006948] hover:bg-emerald-50 transition-colors">
                                    {{ __('global.notifications.mark_as_read') }}
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            @endforeach

            <!-- Pagination -->
            <div class="pt-2">
                {{ $notifications->links() }}
            </div>
        @else
            <div class="bg-white border border-slate-200 rounded-2xl p-12 text-center shadow-2xs">
                <div class="w-16 h-16 rounded-2xl bg-slate-50 border border-slate-200/80 text-slate-400 flex items-center justify-center mx-auto mb-4">
                    <span class="material-symbols-outlined text-[32px]">notifications_off</span>
                </div>
                <h3 class="text-base font-semibold text-slate-800">{{ __('global.notifications.empty_title') }}</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">{{ __('global.notifications.empty_desc') }}</p>
            </div>
        @endif
    </div>

    <!-- MODAL: Cấu hình thông báo -->
    @if (false)
    <div 
        x-show="showConfigModal"
        x-cloak
        style="display: none;"
        :class="{ 'flex': showConfigModal, 'hidden': !showConfigModal }"
        class="fixed inset-0 z-50 items-center justify-center bg-black/40 backdrop-blur-sm p-4"
        @click.self="showConfigModal = false"
        @keydown.escape.window="showConfigModal = false"
    >
        <div class="bg-white border border-slate-200 rounded-2xl max-w-md w-full p-6 shadow-xl relative">
            <button @click="showConfigModal = false" class="absolute top-4 right-4 text-slate-400 hover:text-slate-700 p-1 rounded-lg hover:bg-slate-100 transition-colors" aria-label="{{ __('global.common.close') }}">
                <span class="material-symbols-outlined text-xl">close</span>
            </button>

            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-emerald-50 text-[#006948] flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-xl">tune</span>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900">{{ __('global.notifications.modal_config_title') }}</h3>
                    <p class="text-xs text-slate-500">{{ __('global.notifications.modal_config_subtitle') }}</p>
                </div>
            </div>

            <div class="space-y-3 mb-6 text-xs text-slate-600">
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between">
                    <div>
                        <p class="font-semibold text-slate-800">{{ __('global.notifications.config_campaign') }}</p>
                        <p class="text-slate-500 text-[11px] mt-0.5">{{ __('global.notifications.config_campaign_desc') }}</p>
                    </div>
                    <span class="text-emerald-700 font-bold">{{ __('global.notifications.state_enabled') }}</span>
                </div>
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between">
                    <div>
                        <p class="font-semibold text-slate-800">{{ __('global.notifications.config_payment') }}</p>
                        <p class="text-slate-500 text-[11px] mt-0.5">{{ __('global.notifications.config_payment_desc') }}</p>
                    </div>
                    <span class="text-emerald-700 font-bold">{{ __('global.notifications.state_enabled') }}</span>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="button" @click="showConfigModal = false" class="px-4 py-2 rounded-xl bg-[#006948] text-white hover:bg-[#005137] text-xs font-semibold transition-colors">
                    {{ __('global.notifications.understood_close') }}
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
</x-global.layout>

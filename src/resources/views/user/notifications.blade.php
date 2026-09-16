<x-room.layout :room="$room" :room-user="$roomUser" :active-campaign="$activeCampaign" :user-rooms="$userRooms" :unread-notifications-count="$unreadNotificationsCount" :active-tab="'notifications'" :title="__('room.notifications.page_title')">
    <div class="flex flex-col w-full gap-6" x-data="{
        currentTab: 'all',
        unreadCount: {{ $unreadCount }},
        notifications: {{ Js::from($notifications) }},
        singleReadUrl: {{ Js::from(route('user.notifications.read', ['notification' => '__notification__'])) }},
        updateHeaderBadge(count) {
            document.querySelectorAll('[data-user-notification-badge]').forEach(badge => {
                badge.classList.toggle('hidden', count <= 0);
                badge.classList.toggle('flex', count > 0);
                badge.textContent = count > 9 ? '9+' : String(count);
            });
        },
        async markAllRead() {
            try {
                const response = await fetch('{{ route('user.me.notifications.read-all') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                });
                if (!response.ok) throw new Error('Unable to mark notifications as read.');
                const payload = await response.json();
                this.notifications.forEach(n => n.is_read = true);
                this.unreadCount = Number(payload.unread_count ?? 0);
                this.updateHeaderBadge(this.unreadCount);
            } catch (e) {
                console.error(e);
            }
        },
        async markSingleRead(id) {
            try {
                const response = await fetch(this.singleReadUrl.replace('__notification__', String(id)), {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                });
                if (!response.ok) throw new Error('Unable to mark notification as read.');
                const payload = await response.json();
                const notif = this.notifications.find(n => n.id === id);
                if (notif && !notif.is_read) {
                    notif.is_read = true;
                    this.unreadCount = Math.max(0, this.unreadCount - 1);
                    this.updateHeaderBadge(Number(payload.unread_count ?? this.unreadCount));
                }
            } catch (e) {
                console.error(e);
            }
        },
        get filteredNotifications() {
            if (this.currentTab === 'all') return this.notifications;
            return this.notifications.filter(n => n.category === this.currentTab);
        }
    }">
        <!-- Top Hero Header & Control Bar -->
        <section class="w-full">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                <!-- Title & Live Counter Tag -->
                <div class="flex flex-col gap-0.5">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-sm sm:text-base font-bold text-slate-900 tracking-tight">{{ __('room.notifications.page_title') }}</h1>
                        <span class="unread-count-badge inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-rose-50 text-rose-700 border border-rose-200/70 text-[11px] font-semibold" x-show="unreadCount > 0">
                            <span class="w-1.5 h-1.5 rounded-full bg-error animate-pulse"></span>
                            <span x-text="unreadCount + ' ' + '{{ __('room.notifications.unread_badge_suffix') }}'"></span>
                        </span>
                    </div>
                    <p class="text-xs text-slate-600">
                        {{ __('room.notifications.subtitle') }}
                    </p>
                </div>

                <!-- Action Button -->
                <div class="flex items-center self-start lg:self-center shrink-0">
                    <button class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition-colors border border-slate-200 cursor-pointer"
                            @click="markAllRead()"
                            :disabled="unreadCount === 0"
                            :class="{'opacity-50 cursor-not-allowed': unreadCount === 0}">
                        <span class="material-symbols-outlined text-[16px] text-primary">done_all</span>
                        <span>{{ __('room.notifications.mark_all_read') }}</span>
                    </button>
                </div>
            </div>

            <!-- Smart Filter Horizontal Bar -->
            <div class="mt-3 p-1 rounded-xl bg-slate-50 flex items-center gap-1 overflow-x-auto border border-slate-200/80 no-scrollbar">
                <button class="shrink-0 whitespace-nowrap px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer flex items-center gap-1.5"
                        :class="currentTab === 'all' ? 'bg-primary text-white shadow-2xs font-bold' : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface'"
                        @click="currentTab = 'all'">
                    <span class="material-symbols-outlined text-[15px]">inbox</span>
                    <span>{{ __('room.notifications.filter_all') }}</span>
                    <span class="shrink-0 text-[10px] px-1.5 py-0.2 rounded-full font-bold" :class="currentTab === 'all' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-600'" x-text="notifications.length"></span>
                </button>
                <button class="shrink-0 whitespace-nowrap px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer flex items-center gap-1.5"
                        :class="currentTab === 'campaigns' ? 'bg-primary text-white shadow-2xs font-bold' : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface'"
                        @click="currentTab = 'campaigns'">
                    <span class="material-symbols-outlined text-[15px]">local_cafe</span>
                    <span>{{ __('room.notifications.filter_campaigns') }}</span>
                    <span class="shrink-0 text-[10px] px-1.5 py-0.2 rounded-full font-bold" :class="currentTab === 'campaigns' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-600'" x-text="notifications.filter(n => n.category === 'campaigns').length"></span>
                </button>
                <button class="shrink-0 whitespace-nowrap px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer flex items-center gap-1.5"
                        :class="currentTab === 'orders' ? 'bg-primary text-white shadow-2xs font-bold' : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface'"
                        @click="currentTab = 'orders'">
                    <span class="material-symbols-outlined text-[15px]">delivery_dining</span>
                    <span>{{ __('room.notifications.filter_orders') }}</span>
                    <span class="shrink-0 text-[10px] px-1.5 py-0.2 rounded-full font-bold" :class="currentTab === 'orders' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-600'" x-text="notifications.filter(n => n.category === 'orders').length"></span>
                </button>
                <button class="shrink-0 whitespace-nowrap px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer flex items-center gap-1.5"
                        :class="currentTab === 'debts' ? 'bg-primary text-white shadow-2xs font-bold' : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface'"
                        @click="currentTab = 'debts'">
                    <span class="material-symbols-outlined text-[15px]">qr_code_2</span>
                    <span>{{ __('room.notifications.filter_debts') }}</span>
                    <span class="shrink-0 text-[10px] px-1.5 py-0.2 rounded-full font-bold" :class="currentTab === 'debts' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-600'" x-text="notifications.filter(n => n.category === 'debts').length"></span>
                </button>
            </div>
        </section>

        <!-- Notifications Feed List -->
        <div class="flex flex-col gap-2.5">
            <template x-for="item in filteredNotifications" :key="item.id">
                <div class="p-3 sm:p-3.5 rounded-xl transition-all border border-outline-variant/30 flex items-start gap-3 group"
                     :class="item.is_read ? 'bg-surface-container-lowest/60 opacity-85' : 'bg-surface-container-lowest shadow-2xs ring-1 ring-primary/20'">
                    
                    <!-- Icon -->
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                         :class="item.category === 'campaigns' ? 'bg-primary-fixed text-on-primary-fixed' : (item.category === 'orders' ? 'bg-secondary-container text-on-secondary-container' : 'bg-tertiary-fixed text-on-tertiary-fixed')">
                        <span class="material-symbols-outlined text-[17px]" x-text="item.category === 'campaigns' ? 'local_cafe' : (item.category === 'orders' ? 'delivery_dining' : 'payments')"></span>
                    </div>

                    <!-- Content -->
                    <div class="flex-1 flex flex-col gap-0.5 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <h4 class="text-xs text-on-surface font-bold truncate" x-text="item.title"></h4>
                            <div class="flex items-center gap-1.5 shrink-0">
                                <span class="text-[11px] text-on-surface-variant" x-text="item.created_at_formatted"></span>
                                <span class="w-1.5 h-1.5 rounded-full bg-error" x-show="!item.is_read"></span>
                            </div>
                        </div>
                        <p class="text-xs text-on-surface-variant" x-text="item.body"></p>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-1 shrink-0 self-center" x-show="!item.is_read">
                        <button class="p-1 rounded-md hover:bg-surface-container text-on-surface-variant hover:text-primary transition-colors cursor-pointer"
                                @click="markSingleRead(item.id)"
                                title="{{ __('room.notifications.mark_read') }}">
                            <span class="material-symbols-outlined text-[16px]">check</span>
                        </button>
                    </div>
                </div>
            </template>

            <template x-if="filteredNotifications.length === 0">
                <div class="p-8 sm:p-10 text-center bg-white rounded-xl shadow-2xs border border-slate-200/80 flex flex-col items-center justify-center">
                    <div class="w-11 h-11 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 mb-2.5">
                        <span class="material-symbols-outlined text-[24px]">notifications_off</span>
                    </div>
                    <h3 class="text-sm font-bold text-slate-900 mb-1">{{ __('room.notifications.empty_title') }}</h3>
                    <p class="text-xs text-slate-500 max-w-sm">{{ __('room.notifications.empty_desc') }}</p>
                </div>
            </template>
        </div>
    </div>
</x-room.layout>

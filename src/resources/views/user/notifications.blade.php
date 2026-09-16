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
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <!-- Title & Live Counter Tag -->
                <div class="flex flex-col gap-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">{{ __('room.notifications.page_title') }}</h1>
                        <span class="unread-count-badge inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-rose-50 text-rose-700 border border-rose-200/70 text-xs font-semibold" x-show="unreadCount > 0">
                            <span class="w-1.5 h-1.5 rounded-full bg-error animate-pulse"></span>
                            <span x-text="unreadCount + ' ' + '{{ __('room.notifications.unread_badge_suffix') }}'"></span>
                        </span>
                    </div>
                    <p class="text-sm text-slate-600">
                        {{ __('room.notifications.subtitle') }}
                    </p>
                </div>

                <!-- Action Button -->
                <div class="flex items-center self-start lg:self-center shrink-0">
                    <button class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition-colors border border-slate-200 cursor-pointer"
                            @click="markAllRead()"
                            :disabled="unreadCount === 0"
                            :class="{'opacity-50 cursor-not-allowed': unreadCount === 0}">
                        <span class="material-symbols-outlined text-[18px] text-primary">done_all</span>
                        <span>{{ __('room.notifications.mark_all_read') }}</span>
                    </button>
                </div>
            </div>

            <!-- Smart Filter Horizontal Bar -->
            <div class="mt-4 p-1.5 rounded-xl bg-slate-50 flex items-center gap-1 overflow-x-auto border border-slate-200/80 no-scrollbar">
                <button class="shrink-0 whitespace-nowrap px-3 py-2 rounded-lg text-xs font-semibold transition-colors cursor-pointer flex items-center gap-1.5"
                        :class="currentTab === 'all' ? 'bg-primary text-white shadow-sm font-bold' : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface'"
                        @click="currentTab = 'all'">
                    <span class="material-symbols-outlined text-[16px]">inbox</span>
                    <span>{{ __('room.notifications.filter_all') }}</span>
                    <span class="shrink-0 text-[11px] px-1.5 py-0.5 rounded-full" :class="currentTab === 'all' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-600'" x-text="notifications.length"></span>
                </button>
                <button class="shrink-0 whitespace-nowrap px-3 py-2 rounded-lg text-xs font-semibold transition-colors cursor-pointer flex items-center gap-1.5"
                        :class="currentTab === 'campaigns' ? 'bg-primary text-white shadow-sm font-bold' : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface'"
                        @click="currentTab = 'campaigns'">
                    <span class="material-symbols-outlined text-[16px]">local_cafe</span>
                    <span>{{ __('room.notifications.filter_campaigns') }}</span>
                    <span class="shrink-0 text-[11px] px-1.5 py-0.5 rounded-full" :class="currentTab === 'campaigns' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-600'" x-text="notifications.filter(n => n.category === 'campaigns').length"></span>
                </button>
                <button class="shrink-0 whitespace-nowrap px-3 py-2 rounded-lg text-xs font-semibold transition-colors cursor-pointer flex items-center gap-1.5"
                        :class="currentTab === 'orders' ? 'bg-primary text-white shadow-sm font-bold' : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface'"
                        @click="currentTab = 'orders'">
                    <span class="material-symbols-outlined text-[16px]">delivery_dining</span>
                    <span>{{ __('room.notifications.filter_orders') }}</span>
                    <span class="shrink-0 text-[11px] px-1.5 py-0.5 rounded-full" :class="currentTab === 'orders' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-600'" x-text="notifications.filter(n => n.category === 'orders').length"></span>
                </button>
                <button class="shrink-0 whitespace-nowrap px-3 py-2 rounded-lg text-xs font-semibold transition-colors cursor-pointer flex items-center gap-1.5"
                        :class="currentTab === 'debts' ? 'bg-primary text-white shadow-sm font-bold' : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface'"
                        @click="currentTab = 'debts'">
                    <span class="material-symbols-outlined text-[16px]">qr_code_2</span>
                    <span>{{ __('room.notifications.filter_debts') }}</span>
                    <span class="shrink-0 text-[11px] px-1.5 py-0.5 rounded-full" :class="currentTab === 'debts' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-600'" x-text="notifications.filter(n => n.category === 'debts').length"></span>
                </button>
            </div>
        </section>

        <!-- Notifications Feed List -->
        <div class="flex flex-col gap-3">
            <template x-for="item in filteredNotifications" :key="item.id">
                <div class="p-space-md rounded-xl transition-all border border-outline-variant/30 flex items-start gap-space-md group"
                     :class="item.is_read ? 'bg-surface-container-lowest/60 opacity-85' : 'bg-surface-container-lowest shadow-sm ring-1 ring-primary/20'">
                    
                    <!-- Icon -->
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                         :class="item.category === 'campaigns' ? 'bg-primary-fixed text-on-primary-fixed' : (item.category === 'orders' ? 'bg-secondary-container text-on-secondary-container' : 'bg-tertiary-fixed text-on-tertiary-fixed')">
                        <span class="material-symbols-outlined text-[20px]" x-text="item.category === 'campaigns' ? 'local_cafe' : (item.category === 'orders' ? 'delivery_dining' : 'payments')"></span>
                    </div>

                    <!-- Content -->
                    <div class="flex-1 flex flex-col gap-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <h4 class="font-headline-sm text-headline-sm text-on-surface font-bold truncate" x-text="item.title"></h4>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="font-body-sm text-body-sm text-on-surface-variant" x-text="item.created_at_formatted"></span>
                                <span class="w-2 h-2 rounded-full bg-error" x-show="!item.is_read"></span>
                            </div>
                        </div>
                        <p class="font-body-md text-body-md text-on-surface-variant" x-text="item.body"></p>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-1 shrink-0 self-center" x-show="!item.is_read">
                        <button class="p-1.5 rounded-lg hover:bg-surface-container text-on-surface-variant hover:text-primary transition-colors cursor-pointer"
                                @click="markSingleRead(item.id)"
                                title="{{ __('room.notifications.mark_read') }}">
                            <span class="material-symbols-outlined text-[18px]">check</span>
                        </button>
                    </div>
                </div>
            </template>

            <template x-if="filteredNotifications.length === 0">
                <div class="min-h-59 p-8 sm:p-10 text-center bg-white rounded-2xl shadow-xs border border-slate-200/80 flex flex-col items-center justify-center">
                    <div class="w-14 h-14 rounded-full bg-indigo-100 flex items-center justify-center text-slate-600 mb-3">
                        <span class="material-symbols-outlined text-4xl">notifications_off</span>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mb-1.5">{{ __('room.notifications.empty_title') }}</h3>
                    <p class="text-sm text-slate-600 max-w-sm">{{ __('room.notifications.empty_desc') }}</p>
                </div>
            </template>
        </div>
    </div>
</x-room.layout>

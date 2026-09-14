<x-room.layout :room="$room" :room-user="$roomUser" :active-campaign="$activeCampaign" :user-rooms="$userRooms" :unread-notifications-count="$unreadNotificationsCount" :active-tab="'notifications'" :title="__('room.notifications.page_title')">
    <div class="flex flex-col w-full gap-space-lg" x-data="{
        currentTab: 'all',
        unreadCount: {{ $unreadCount }},
        notifications: {{ Js::from($notifications) }},
        async markAllRead() {
            try {
                await fetch('{{ route('user.me.notifications.read-all') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                this.notifications.forEach(n => n.is_read = true);
                this.unreadCount = 0;
            } catch (e) {
                console.error(e);
            }
        },
        async markSingleRead(id) {
            try {
                await fetch('/notifications/' + id + '/read', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const notif = this.notifications.find(n => n.id === id);
                if (notif && !notif.is_read) {
                    notif.is_read = true;
                    this.unreadCount = Math.max(0, this.unreadCount - 1);
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
        <section class="w-full pb-space-xs">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-space-md">
                <!-- Title & Live Counter Tag -->
                <div class="flex flex-col gap-space-xs">
                    <div class="flex items-center gap-space-sm flex-wrap">
                        <h1 class="font-display-lg text-display-lg text-on-surface tracking-tight">{{ __('room.notifications.page_title') }}</h1>
                        <span class="unread-count-badge inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-error-container text-on-error-container font-label-sm text-label-sm font-semibold" x-show="unreadCount > 0">
                            <span class="w-1.5 h-1.5 rounded-full bg-error animate-pulse"></span>
                            <span x-text="unreadCount + ' ' + '{{ __('room.notifications.unread_badge_suffix') }}'"></span>
                        </span>
                        <span class="font-label-sm text-label-sm text-on-surface-variant bg-surface-container-high px-2 py-0.5 rounded">
                            {{ $room->name }} Workspace
                        </span>
                    </div>
                    <p class="font-body-md text-body-md text-on-surface-variant">
                        {{ __('room.notifications.subtitle') }}
                    </p>
                </div>

                <!-- Action Button -->
                <div class="flex items-center gap-space-sm self-start lg:self-center shrink-0">
                    <button class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-surface-container-low hover:bg-surface-container text-on-surface font-label-md text-label-md shadow-sm transition-colors border border-outline-variant/40 cursor-pointer"
                            @click="markAllRead()"
                            :disabled="unreadCount === 0"
                            :class="{'opacity-50 cursor-not-allowed': unreadCount === 0}">
                        <span class="material-symbols-outlined text-[18px] text-primary">done_all</span>
                        <span>{{ __('room.notifications.mark_all_read') }}</span>
                    </button>
                </div>
            </div>

            <!-- Smart Filter Horizontal Bar -->
            <div class="mt-space-md p-1.5 rounded-xl bg-surface-container-low flex items-center gap-1 overflow-x-auto border border-outline-variant/20">
                <button class="whitespace-nowrap px-space-md py-2 rounded-lg font-label-md text-label-md transition-colors cursor-pointer flex items-center gap-1.5"
                        :class="currentTab === 'all' ? 'bg-primary text-on-primary shadow-sm font-bold' : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface'"
                        @click="currentTab = 'all'">
                    <span class="material-symbols-outlined text-[16px]">inbox</span>
                    <span>{{ __('room.notifications.filter_all') }}</span>
                    <span class="text-[11px] px-1.5 py-0.2 rounded-full" :class="currentTab === 'all' ? 'bg-white/20' : 'bg-surface-container-highest text-on-surface-variant'" x-text="notifications.length"></span>
                </button>
                <button class="whitespace-nowrap px-space-md py-2 rounded-lg font-label-md text-label-md transition-colors cursor-pointer flex items-center gap-1.5"
                        :class="currentTab === 'campaigns' ? 'bg-primary text-on-primary shadow-sm font-bold' : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface'"
                        @click="currentTab = 'campaigns'">
                    <span class="material-symbols-outlined text-[16px]">local_cafe</span>
                    <span>{{ __('room.notifications.filter_campaigns') }}</span>
                    <span class="text-[11px] px-1.5 py-0.2 rounded-full" :class="currentTab === 'campaigns' ? 'bg-white/20' : 'bg-surface-container-highest text-on-surface-variant'" x-text="notifications.filter(n => n.category === 'campaigns').length"></span>
                </button>
                <button class="whitespace-nowrap px-space-md py-2 rounded-lg font-label-md text-label-md transition-colors cursor-pointer flex items-center gap-1.5"
                        :class="currentTab === 'orders' ? 'bg-primary text-on-primary shadow-sm font-bold' : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface'"
                        @click="currentTab = 'orders'">
                    <span class="material-symbols-outlined text-[16px]">delivery_dining</span>
                    <span>{{ __('room.notifications.filter_orders') }}</span>
                    <span class="text-[11px] px-1.5 py-0.2 rounded-full" :class="currentTab === 'orders' ? 'bg-white/20' : 'bg-surface-container-highest text-on-surface-variant'" x-text="notifications.filter(n => n.category === 'orders').length"></span>
                </button>
                <button class="whitespace-nowrap px-space-md py-2 rounded-lg font-label-md text-label-md transition-colors cursor-pointer flex items-center gap-1.5"
                        :class="currentTab === 'debts' ? 'bg-primary text-on-primary shadow-sm font-bold' : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface'"
                        @click="currentTab = 'debts'">
                    <span class="material-symbols-outlined text-[16px]">qr_code_2</span>
                    <span>{{ __('room.notifications.filter_debts') }}</span>
                    <span class="text-[11px] px-1.5 py-0.2 rounded-full" :class="currentTab === 'debts' ? 'bg-white/20' : 'bg-surface-container-highest text-on-surface-variant'" x-text="notifications.filter(n => n.category === 'debts').length"></span>
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
                <div class="p-12 text-center bg-surface-container-lowest rounded-2xl shadow-sm border border-outline-variant/30 flex flex-col items-center justify-center">
                    <div class="w-16 h-16 rounded-full bg-surface-container-high flex items-center justify-center text-on-surface-variant mb-4">
                        <span class="material-symbols-outlined text-4xl">notifications_off</span>
                    </div>
                    <h3 class="text-xl font-bold text-on-surface mb-2">{{ __('room.notifications.empty_title') }}</h3>
                    <p class="text-on-surface-variant max-w-sm">{{ __('room.notifications.empty_desc') }}</p>
                </div>
            </template>
        </div>
    </div>
</x-room.layout>

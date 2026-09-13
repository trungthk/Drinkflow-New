<x-admin.layout :title="__('admin.room_settings_title')" active="settings" :room="$room">
    <!-- Header & Action Ribbon -->
    <div class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-outline-variant/40">
        <div>
            <div class="flex items-center gap-2 text-xs font-mono text-outline mb-1">
                <a href="{{ route('admin.dashboard.page', $room) }}" class="hover:text-primary transition-colors">Admin</a>
                <span>/</span>
                <span>Rooms</span>
                <span>/</span>
                <span class="text-on-surface font-semibold">{{ $room->name }}</span>
                <span>/</span>
                <span class="text-primary font-bold">{{ __('admin.payments_settings') }}</span>
            </div>
            <h1 class="text-2xl font-bold text-on-surface tracking-tight">{{ __('admin.room_settings_title') }}</h1>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.notification-channels.page', $room) }}" class="px-3.5 py-2 bg-surface-container hover:bg-surface-container-high border border-outline-variant text-on-surface rounded text-xs font-semibold flex items-center gap-1.5 shadow-xs transition-colors no-underline">
                <span class="material-symbols-outlined text-[16px] text-primary">notifications</span>
                <span>{{ __('admin.webhook_channel_btn') }}</span>
            </a>
            <button type="submit" form="room-settings-form" class="px-4 py-2 bg-primary hover:bg-primary/90 text-on-primary rounded text-xs font-bold flex items-center gap-1.5 shadow-sm transition-all">
                <span class="material-symbols-outlined text-[18px]">save</span>
                <span>{{ __('admin.save_changes_btn') }}</span>
            </button>
        </div>
    </div>

    <!-- Notice Notification Banner -->
    <div id="notice" class="hidden mb-4 rounded-xl px-4 py-3 text-xs font-medium"></div>

    <form id="room-settings-form" data-loading-form="true" class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Left Column: Template & Schedule -->
            <div class="space-y-6">
                <!-- Section 1: Template Tên Chiến Dịch Mặc Định -->
                <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-outline-variant">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded bg-blue-50 text-blue-700 flex items-center justify-center">
                                <span class="material-symbols-outlined text-[18px]">edit_note</span>
                            </div>
                            <div>
                                <h3 class="font-bold text-sm text-on-surface">{{ __('admin.default_campaign_title') }}</h3>
                                <p class="text-[11px] text-outline">{{ __('admin.campaign_syntax_template') }}</p>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 bg-blue-50 border border-blue-200 text-blue-700 text-[10px] font-bold rounded">Dynamic Tags</span>
                    </div>

                    <div>
                        <label class="block font-semibold text-xs text-on-surface mb-1.5">{{ __('admin.campaign_syntax_template') }}:</label>
                        <input type="text" id="set-template" value="{{ $settings['campaign_title_template'] ?? '['.$room->name.'] Trà chiều & Cafe {date}' }}" class="w-full h-10 px-3 bg-surface border border-outline-variant rounded text-xs font-bold text-on-surface focus:border-primary focus:ring-1 focus:ring-primary">
                        
                        <div class="flex flex-wrap items-center gap-1.5 pt-2">
                            <span class="text-[11px] text-outline">{{ __('admin.supported_variables') }}</span>
                            <button type="button" onclick="insertTag('{date}')" class="px-2 py-0.5 bg-surface-container hover:bg-surface-container-high rounded border border-outline-variant font-mono text-[11px] text-on-surface font-semibold">{date}</button>
                            <button type="button" onclick="insertTag('{time}')" class="px-2 py-0.5 bg-surface-container hover:bg-surface-container-high rounded border border-outline-variant font-mono text-[11px] text-on-surface font-semibold">{time}</button>
                            <button type="button" onclick="insertTag('{day_of_week}')" class="px-2 py-0.5 bg-surface-container hover:bg-surface-container-high rounded border border-outline-variant font-mono text-[11px] text-on-surface font-semibold">{day_of_week}</button>
                            <button type="button" onclick="insertTag('{creator_name}')" class="px-2 py-0.5 bg-surface-container hover:bg-surface-container-high rounded border border-outline-variant font-mono text-[11px] text-on-surface font-semibold">{creator_name}</button>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Thời Gian Order Mặc Định -->
                <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-outline-variant">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded bg-emerald-50 text-emerald-700 flex items-center justify-center">
                                <span class="material-symbols-outlined text-[18px]">schedule</span>
                            </div>
                            <div>
                                <h3 class="font-bold text-sm text-on-surface">{{ __('admin.default_order_window_title') }}</h3>
                                <p class="text-[11px] text-outline">{{ __('admin.default_order_window_desc') }}</p>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 bg-emerald-50 border border-emerald-200 text-emerald-700 text-[10px] font-bold rounded">Auto-Lock</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <div>
                            <label class="block font-semibold text-on-surface mb-1">{{ __('admin.default_start_time') }}:</label>
                            <input type="time" id="set-start-time" value="{{ $settings['default_start_time'] ?? '10:00' }}" class="w-full h-9 px-3 bg-surface border border-outline-variant rounded text-on-surface font-semibold">
                        </div>
                        <div>
                            <label class="block font-semibold text-on-surface mb-1">{{ __('admin.default_lock_time') }}:</label>
                            <input type="time" id="set-end-time" value="{{ $settings['default_end_time'] ?? '10:45' }}" class="w-full h-9 px-3 bg-surface border border-outline-variant rounded text-on-surface font-semibold">
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-xs text-on-surface mb-1">{{ __('admin.pre_lock_warning') }}:</label>
                        <div class="relative max-w-xs">
                            <input type="number" id="set-warning-minutes" value="{{ $settings['auto_close_warning_minutes'] ?? 15 }}" min="1" max="60" class="w-full h-9 pl-3 pr-16 bg-surface border border-outline-variant rounded text-xs font-bold text-on-surface">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[11px] text-outline font-semibold">{{ __('admin.minutes_unit') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Budget & Debt Policy -->
            <div class="space-y-6">
                <!-- Section 3: Ngân Sách Tối Đa Mỗi Chiến Dịch -->
                <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-outline-variant">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded bg-amber-50 text-amber-700 flex items-center justify-center">
                                <span class="material-symbols-outlined text-[18px]">payments</span>
                            </div>
                            <div>
                                <h3 class="font-bold text-sm text-on-surface">{{ __('admin.max_budget_ceiling') }}</h3>
                                <p class="text-[11px] text-outline">{{ __('admin.spending_debt_policy_desc') }}</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-xs text-on-surface mb-1">{{ __('admin.max_budget_ceiling') }} (₫):</label>
                        <div class="relative">
                            <input type="number" id="set-max-budget" value="{{ $settings['max_campaign_budget'] ?? 2000000 }}" class="w-full h-10 pl-3 pr-16 bg-surface border border-outline-variant rounded font-mono font-bold text-sm text-primary">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-outline">{{ __('admin.vnd_unit') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Chính Sách Ghi Nợ & Kiểm Soát -->
                <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-outline-variant">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded bg-purple-50 text-purple-700 flex items-center justify-center">
                                <span class="material-symbols-outlined text-[18px]">gavel</span>
                            </div>
                            <div>
                                <h3 class="font-bold text-sm text-on-surface">{{ __('admin.spending_debt_policy_title') }}</h3>
                                <p class="text-[11px] text-outline">{{ __('admin.spending_debt_policy_desc') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 bg-surface-container-low border border-outline-variant rounded-lg flex items-center justify-between">
                        <div>
                            <div class="font-semibold text-xs text-on-surface">{{ __('admin.allow_internal_debt') }}</div>
                        </div>
                        <input type="checkbox" id="set-allow-debt" {{ ($settings['allow_internal_debt'] ?? true) ? 'checked' : '' }} class="rounded border-outline-variant text-primary focus:ring-primary h-5 w-5">
                    </div>

                    <div>
                        <label class="block font-semibold text-xs text-on-surface mb-1">{{ __('admin.personal_debt_ceiling') }} (₫):</label>
                        <div class="relative">
                            <input type="number" id="set-debt-ceiling" value="{{ $settings['personal_debt_ceiling'] ?? 150000 }}" class="w-full h-9 pl-3 pr-16 bg-surface border border-outline-variant rounded font-mono font-bold text-xs text-on-surface">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[11px] font-bold text-outline">{{ __('admin.vnd_unit') }}</span>
                        </div>
                    </div>

                    <div class="p-3 bg-surface-container-low border border-outline-variant rounded-lg flex items-center justify-between">
                        <div>
                            <div class="font-semibold text-xs text-on-surface">{{ __('admin.auto_lock_on_debt_limit') }}</div>
                        </div>
                        <input type="checkbox" id="set-autolock-debt" {{ ($settings['auto_lock_on_debt_limit'] ?? true) ? 'checked' : '' }} class="rounded border-outline-variant text-primary focus:ring-primary h-5 w-5">
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-admin.layout>

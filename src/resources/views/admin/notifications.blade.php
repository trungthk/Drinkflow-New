<x-admin.layout :title="__('admin.webhook_channels_title')" active="settings" :room="$room">
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
                <span class="text-primary font-bold">{{ __('admin.webhook_channel_btn') }}</span>
            </div>
            <h1 class="text-2xl font-bold text-on-surface tracking-tight">{{ __('admin.webhook_channels_title') }}</h1>
        </div>
        <div>
            <a href="{{ route('admin.settings.page', $room) }}" class="px-3.5 py-2 bg-surface-container hover:bg-surface-container-high border border-outline-variant text-on-surface rounded text-xs font-semibold flex items-center gap-1.5 shadow-xs transition-colors no-underline">
                <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                <span>{{ __('admin.general_settings_btn') }}</span>
            </a>
        </div>
    </div>

    <!-- Notice Notification Banner -->
    <div id="notice" class="hidden mb-4 rounded-xl px-4 py-3 text-xs font-medium"></div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Configured Channels List -->
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-xs">
                <div class="p-4 border-b border-outline-variant flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px] text-primary">sensors</span>
                        <h2 class="font-bold text-sm text-on-surface">{{ __('admin.active_webhooks') }}</h2>
                    </div>
                    <span class="text-xs text-outline font-mono">{{ __('admin.channels_count_unit', ['count' => $channels->count()]) }}</span>
                </div>

                <div class="divide-y divide-outline-variant/50">
                    @forelse($channels as $ch)
                        <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-surface-container-low/40 transition-colors">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded bg-primary/10 text-primary flex items-center justify-center font-bold text-sm shrink-0">
                                    <span class="material-symbols-outlined text-[20px]">
                                        {{ match($ch['type'] ?? '') { 'telegram' => 'send', 'discord' => 'sports_esports', 'slack' => 'tag', 'zalo' => 'chat', default => 'webhook' } }}
                                    </span>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-sm text-on-surface uppercase">{{ $ch['type'] ?? 'Webhook' }}</span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold border {{ ($ch['status'] ?? '') === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-gray-100 text-gray-500' }}">
                                            {{ ucfirst($ch['status'] ?? 'active') }}
                                        </span>
                                    </div>
                                    <div class="text-xs font-mono text-outline mt-0.5 truncate max-w-sm">{{ $ch['webhook_url'] ?? 'https://...' }}</div>
                                    <div class="text-[11px] text-secondary mt-1 flex items-center gap-1.5 flex-wrap">
                                        @foreach(($ch['events'] ?? []) as $ev)
                                            <span class="px-1.5 py-0.5 rounded bg-surface-container text-on-surface text-[10px] font-mono">{{ $ev }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <button type="button" onclick="testChannel({{ $ch['id'] }})" class="px-3 py-1.5 bg-primary/10 hover:bg-primary/20 text-primary rounded text-xs font-semibold flex items-center gap-1 transition-colors">
                                    <span class="material-symbols-outlined text-[14px]">bolt</span>
                                    <span>{{ __('admin.test_ping') }}</span>
                                </button>
                                <button type="button" onclick="deleteChannel({{ $ch['id'] }})" class="p-1.5 text-secondary hover:text-rose-600 rounded hover:bg-surface-container transition-colors" title="Delete channel">
                                    <span class="material-symbols-outlined text-[16px]">delete</span>
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="py-12 text-center text-outline">
                            <span class="material-symbols-outlined text-4xl text-outline-variant">notifications_off</span>
                            <p class="text-xs mt-1">{{ __('admin.no_webhooks_found') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Right: Add Webhook Channel Form -->
        <div class="space-y-4">
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 shadow-xs">
                <div class="flex items-center gap-2 pb-3 border-b border-outline-variant mb-4">
                    <span class="material-symbols-outlined text-[20px] text-primary">add_link</span>
                    <h2 class="font-bold text-sm text-on-surface">{{ __('admin.connect_new_bot') }}</h2>
                </div>

                <form id="add-channel-form" data-loading-form="true" class="space-y-3 text-xs">
                    <div>
                        <label class="block font-semibold text-on-surface mb-1">{{ __('admin.webhook_form_platform') }}</label>
                        <select id="ch-type" class="w-full h-9 px-3 bg-surface border border-outline-variant rounded text-on-surface font-semibold" required>
                            <option value="slack">Slack Incoming Webhook</option>
                            <option value="telegram">Telegram Bot Channel</option>
                            <option value="discord">Discord Webhook</option>
                            <option value="zalo">Zalo OA Webhook</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-on-surface mb-1">{{ __('admin.webhook_form_url') }}</label>
                        <input type="url" id="ch-url" placeholder="https://hooks.slack.com/services/..." class="w-full h-9 px-3 bg-surface border border-outline-variant rounded font-mono text-xs text-on-surface" required>
                    </div>

                    <div>
                        <label class="block font-semibold text-on-surface mb-1">{{ __('admin.webhook_form_token') }}</label>
                        <input type="text" id="ch-token" placeholder="Optional security token" class="w-full h-9 px-3 bg-surface border border-outline-variant rounded font-mono text-xs text-on-surface">
                    </div>

                    <div>
                        <label class="block font-semibold text-on-surface mb-1.5">{{ __('admin.webhook_form_events') }}</label>
                        <div class="space-y-1.5 p-3 bg-surface-container-low border border-outline-variant rounded-lg">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="events[]" value="campaign.created" checked class="rounded border-outline-variant text-primary focus:ring-primary">
                                <span>{{ __('admin.event_campaign_created') }}</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="events[]" value="campaign.auto_lock_warning" checked class="rounded border-outline-variant text-primary focus:ring-primary">
                                <span>{{ __('admin.event_lock_warning') }}</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="events[]" value="campaign.closed" checked class="rounded border-outline-variant text-primary focus:ring-primary">
                                <span>{{ __('admin.event_campaign_closed') }}</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="events[]" value="debt.payment_recorded" checked class="rounded border-outline-variant text-primary focus:ring-primary">
                                <span>{{ __('admin.event_payment_recorded') }}</span>
                            </label>
                        </div>
                    </div>

                    <div class="pt-3">
                        <button type="submit" class="w-full h-9 bg-primary hover:bg-primary/90 text-on-primary rounded font-bold transition-colors">
                            {{ __('admin.save_webhook') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-admin.layout>

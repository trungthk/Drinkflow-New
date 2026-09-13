<x-admin.layout :title="__('admin.campaigns_management_title')" active="campaigns" :room="$room">
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
                <span class="text-primary font-bold">{{ __('admin.campaigns') }}</span>
            </div>
            <h1 class="text-2xl font-bold text-on-surface tracking-tight">{{ __('admin.campaigns_management_title') }}</h1>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.campaigns.create', $room) }}" class="px-4 py-2 bg-primary hover:bg-primary/90 text-on-primary rounded text-xs font-bold flex items-center gap-2 shadow-sm transition-all no-underline">
                <span class="material-symbols-outlined text-[18px]">add_circle</span>
                <span>{{ __('admin.fast_create_campaign') }}</span>
            </a>
        </div>
    </div>

    <!-- Notice Notification Banner -->
    <div id="notice" class="hidden mb-4 rounded-xl px-4 py-3 text-xs font-medium"></div>

    <!-- Campaigns Search & Filter Toolbar -->
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 flex flex-wrap items-center justify-between gap-3 shadow-xs">
        <div class="flex items-center gap-3 flex-1 min-w-[280px]">
            <x-admin.search-input id="campaign-search" placeholder="{{ __('admin.search_campaigns_placeholder') }}" containerClass="relative w-full max-w-md" />
        </div>
        <div class="flex items-center gap-1.5 overflow-x-auto">
            <button type="button" data-filter="all" class="campaign-filter px-3 py-1.5 rounded text-xs font-semibold bg-primary text-on-primary transition-colors">{{ __('admin.filter_all') }}</button>
            <button type="button" data-filter="active" class="campaign-filter px-3 py-1.5 rounded text-xs font-semibold bg-surface-container hover:bg-surface-container-high text-on-surface transition-colors">{{ __('admin.filter_active') }}</button>
            <button type="button" data-filter="scheduled" class="campaign-filter px-3 py-1.5 rounded text-xs font-semibold bg-surface-container hover:bg-surface-container-high text-on-surface transition-colors">{{ __('admin.filter_scheduled') }}</button>
            <button type="button" data-filter="closed" class="campaign-filter px-3 py-1.5 rounded text-xs font-semibold bg-surface-container hover:bg-surface-container-high text-on-surface transition-colors">{{ __('admin.filter_closed') }}</button>
            <button type="button" data-filter="archived" class="campaign-filter px-3 py-1.5 rounded text-xs font-semibold bg-surface-container hover:bg-surface-container-high text-on-surface transition-colors">{{ __('admin.filter_archived') }}</button>
        </div>
    </div>

    <!-- Campaigns Table Ledger -->
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-surface-container-low text-outline font-mono uppercase text-[11px] border-b border-outline-variant">
                        <th class="py-3 px-4">{{ __('admin.th_campaign_restaurant') }}</th>
                        <th class="py-3 px-4">{{ __('admin.th_order_window') }}</th>
                        <th class="py-3 px-4 text-center">{{ __('admin.th_status') }}</th>
                        <th class="py-3 px-4 text-right">{{ __('admin.th_orders_count') }}</th>
                        <th class="py-3 px-4 text-right">{{ __('admin.th_store_total') }}</th>
                        <th class="py-3 px-4 text-center">{{ __('admin.th_actions') }}</th>
                    </tr>
                </thead>
                <tbody id="campaigns-tbody" class="divide-y divide-outline-variant/50">
                    @forelse($campaigns as $camp)
                        @php
                            $stClass = match($camp->status) {
                                'active' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'closing' => 'bg-amber-50 text-amber-700 border-amber-200 animate-pulse',
                                'scheduled' => 'bg-blue-50 text-blue-700 border-blue-200',
                                'closed' => 'bg-surface-container text-secondary border-outline-variant',
                                'archived' => 'bg-gray-100 text-gray-500 border-gray-200',
                                default => 'bg-surface text-outline border-outline-variant'
                            };
                            $stLabel = match($camp->status) {
                                'active' => __('admin.filter_active'),
                                'closing' => __('admin.status_closing'),
                                'scheduled' => __('admin.filter_scheduled'),
                                'closed' => __('admin.filter_closed'),
                                'archived' => __('admin.filter_archived'),
                                default => $camp->status
                            };
                        @endphp
                        <tr class="hover:bg-surface-container-low/50 transition-colors" data-campaign-row data-status="{{ $camp->status }}" data-search="{{ strtolower($camp->title . ' ' . $camp->restaurant) }}">
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-on-surface text-sm">{{ $camp->title }}</div>
                                <div class="text-secondary flex items-center gap-1.5 mt-0.5">
                                    <span class="material-symbols-outlined text-[14px]">storefront</span>
                                    <span>{{ $camp->restaurant }}</span>
                                    @if($camp->source_url)
                                        <a href="{{ $camp->source_url }}" target="_blank" class="text-primary hover:underline text-[11px]">🔗 {{ __('admin.store_link') }}</a>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-secondary">
                                <div>{{ $camp->start_time ? \Carbon\Carbon::parse($camp->start_time)->format('H:i d/m') : 'N/A' }}</div>
                                <div class="text-[11px] text-outline">{{ __('admin.time_until', ['time' => $camp->end_time ? \Carbon\Carbon::parse($camp->end_time)->format('H:i d/m') : 'N/A']) }}</div>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold border {{ $stClass }}">
                                    {{ $stLabel }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-bold text-on-surface">
                                {{ __('admin.orders_unit', ['count' => $camp->orders_count ?? 0]) }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-bold text-primary">
                                {{ number_format($camp->subtotal_amount ?? 0) }} ₫
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    @if(in_array($camp->status, ['active', 'closing', 'scheduled']))
                                        <a href="{{ route('admin.campaigns.show', [$room, $camp]) }}" class="px-2.5 py-1 bg-primary text-on-primary hover:bg-primary/90 rounded text-[11px] font-semibold flex items-center gap-1 no-underline">
                                            <span class="material-symbols-outlined text-[14px]">sensors</span>
                                            <span>{{ __('admin.live_control_btn') }}</span>
                                        </a>
                                    @else
                                        <a href="{{ route('admin.campaigns.show', [$room, $camp]) }}?view=detail" class="px-2.5 py-1 bg-surface-container hover:bg-surface-container-high text-on-surface rounded text-[11px] font-semibold flex items-center gap-1 no-underline">
                                            <span class="material-symbols-outlined text-[14px]">receipt_long</span>
                                            <span>{{ __('admin.reconcile_btn') }}</span>
                                        </a>
                                    @endif
                                    <button type="button" onclick="duplicateCampaign({{ $camp->id }})" class="p-1 text-secondary hover:text-primary rounded hover:bg-surface-container transition-colors" title="{{ __('admin.duplicate_campaign') }}">
                                        <span class="material-symbols-outlined text-[16px]">content_copy</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-outline">
                                <div class="flex flex-col items-center gap-2">
                                    <span class="material-symbols-outlined text-4xl text-outline-variant">campaign</span>
                                    <p class="font-medium text-sm">{{ __('admin.no_campaigns_found') }}</p>
                                    <a href="{{ route('admin.campaigns.create', $room) }}" class="mt-2 px-4 py-2 bg-primary text-on-primary rounded text-xs font-semibold">{{ __('admin.create_first_campaign') }}</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($campaigns->hasPages())
            <div class="p-4 border-t border-outline-variant">
                {{ $campaigns->links() }}
            </div>
        @endif
    </div>
</x-admin.layout>

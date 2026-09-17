<x-admin.layout :title="__('admin.campaigns_management_title')" active="campaigns" :room="$room">
    <!-- Header & Action Ribbon -->
    <div class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-outline-variant/40">
        <div>
            <div class="flex items-center gap-2 text-xs font-mono text-outline mb-1">
                <a href="{{ route('admin.dashboard.page', $room) }}" class="hover:text-primary transition-colors">{{ __('admin.breadcrumb_admin') }}</a>
                <span>/</span>
                <span>{{ __('admin.breadcrumb_rooms') }}</span>
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
    <form id="campaigns-filter-form" method="GET" action="{{ route('admin.campaigns.page', $room) }}" class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 flex flex-wrap items-center justify-between gap-3 shadow-xs">
        <div class="flex items-center gap-3 flex-1 min-w-[280px]">
            <x-admin.search-input id="campaign-search" name="search" :value="$filters['search'] ?? ''" placeholder="{{ __('admin.search_campaigns_placeholder') }}" containerClass="relative w-full max-w-md" />
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <select id="campaign-status-select" name="status" class="h-9 px-3 bg-surface border border-outline-variant rounded text-xs text-on-surface">
                <option value="all" {{ ($filters['status'] ?? 'all') === 'all' ? 'selected' : '' }}>{{ __('admin.filter_all') }}</option>
                @foreach($statusFilters as $statusFilter)
                    <option value="{{ $statusFilter['value'] }}" {{ ($filters['status'] ?? '') === $statusFilter['value'] ? 'selected' : '' }}>{{ $statusFilter['label'] }}</option>
                @endforeach
            </select>
            <button type="submit" class="h-9 inline-flex items-center gap-1.5 px-3 rounded-lg bg-primary text-on-primary text-xs font-semibold hover:bg-primary-container transition-colors">
                <span class="material-symbols-outlined text-[16px]">filter_alt</span>
                {{ __('admin.filter_apply') }}
            </button>
            <a href="{{ route('admin.campaigns.page', $room) }}" class="h-9 inline-flex items-center gap-1.5 px-3 rounded-lg border border-outline-variant bg-surface text-on-surface text-xs font-semibold hover:bg-surface-container transition-colors no-underline">
                <span class="material-symbols-outlined text-[16px]">filter_alt_off</span>
                {{ __('admin.filter_clear') }}
            </a>
        </div>
    </form>

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
                            $statusValue = $camp->status instanceof \BackedEnum ? $camp->status->value : (string) $camp->status;
                            $isExpired = ($statusValue === 'active') && $camp->deadline && $camp->deadline->isPast();
                            $stClass = match(true) {
                                $isExpired => 'bg-rose-50 text-rose-700 border-rose-200',
                                $statusValue === 'active' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                $statusValue === 'closing' => 'bg-amber-50 text-amber-700 border-amber-200 animate-pulse',
                                $statusValue === 'scheduled' => 'bg-blue-50 text-blue-700 border-blue-200',
                                $statusValue === 'closed' => 'bg-surface-container text-secondary border-outline-variant',
                                $statusValue === 'archived' => 'bg-gray-100 text-gray-500 border-gray-200',
                                default => 'bg-surface text-outline border-outline-variant'
                            };
                            $stLabel = match(true) {
                                $isExpired => __('admin.status_expired'),
                                $statusValue === 'draft' => __('admin.status_draft'),
                                $statusValue === 'active' => __('admin.filter_active'),
                                $statusValue === 'closing' => __('admin.status_closing'),
                                $statusValue === 'scheduled' => __('admin.filter_scheduled'),
                                $statusValue === 'closed' => __('admin.filter_closed'),
                                $statusValue === 'archived' => __('admin.filter_archived'),
                                $statusValue === 'cancelled' => __('admin.status_cancelled'),
                                default => __('admin.status_'.$statusValue)
                            };
                            $windowStart = $camp->started_at;
                            $windowEnd = $camp->deadline ?? $camp->closed_at;
                        @endphp
                        <tr class="hover:bg-surface-container-low/50 transition-colors">
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-on-surface text-sm">{{ $camp->name }}</div>
                                <div class="text-secondary flex items-center gap-1.5 mt-0.5">
                                    <span class="material-symbols-outlined text-[14px]">storefront</span>
                                    <span>{{ $camp->restaurant }}</span>
                                    @if($camp->source_url)
                                        <a href="{{ $camp->source_url }}" target="_blank" class="text-primary hover:underline text-[11px]">🔗 {{ __('admin.store_link') }}</a>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-secondary">
                                <div>{{ $windowStart ? __('admin.time_from', ['time' => $windowStart->format('H:i d/m/Y')]) : __('admin.order_window_not_started') }}</div>
                                <div class="text-[11px] text-outline">{{ __('admin.time_until', ['time' => $windowEnd?->format('H:i d/m/Y') ?? '—']) }}</div>
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
                                <details class="relative inline-block text-left">
                                    <summary class="list-none cursor-pointer inline-flex items-center justify-center w-8 h-8 rounded-lg text-secondary hover:text-primary hover:bg-surface-container transition-colors" aria-label="{{ __('admin.th_actions') }}">
                                        <span class="material-symbols-outlined text-[20px]">more_vert</span>
                                    </summary>
                                    <div class="absolute right-0 mt-1 w-48 z-20 rounded-xl border border-outline-variant bg-surface-container-lowest shadow-xl p-1.5 space-y-0.5">
                                        <a href="{{ route('admin.campaigns.show', [$room, $camp]) }}?view=detail" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-xs text-on-surface hover:bg-surface-container no-underline">
                                            <span class="material-symbols-outlined text-[14px]">visibility</span>
                                            <span>{{ __('admin.view_campaign_details') }}</span>
                                        </a>
                                    @if(in_array($statusValue, ['draft'], true))
                                        <a href="{{ route('admin.campaigns.edit', [$room, $camp]) }}" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-xs text-on-surface hover:bg-surface-container no-underline">
                                            <span class="material-symbols-outlined text-[14px]">edit</span>
                                            <span>{{ __('admin.edit') }}</span>
                                        </a>
                                    @elseif(in_array($statusValue, ['active', 'closing', 'scheduled'], true))
                                        <a href="{{ route('admin.campaigns.edit', [$room, $camp]) }}" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-xs text-on-surface hover:bg-surface-container no-underline">
                                            <span class="material-symbols-outlined text-[14px]">edit</span>
                                            <span>{{ __('admin.edit') }}</span>
                                        </a>
                                        @if(in_array($statusValue, ['active', 'closing'], true))
                                            <button type="button" onclick="window.openCloseCampaignModal({{ $camp->id }}); this.closest('details').open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-xs text-on-surface hover:bg-surface-container text-left">
                                                <span class="material-symbols-outlined text-[16px]">lock</span>{{ __('admin.close_campaign_early') }}
                                            </button>
                                        @endif
                                        <button type="button" onclick="window.openCancelCampaignModal({{ $camp->id }}); this.closest('details').open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-xs text-error hover:bg-error-container/30 text-left">
                                            <span class="material-symbols-outlined text-[16px]">cancel</span>{{ __('admin.cancel_campaign_btn') }}
                                        </button>
                                    @elseif($statusValue === 'closed')
                                        <button type="button" onclick="window.archiveCampaign({{ $camp->id }}); this.closest('details').open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-xs text-on-surface hover:bg-surface-container text-left">
                                            <span class="material-symbols-outlined text-[16px]">archive</span>{{ __('admin.archive_campaign') }}
                                        </button>
                                    @endif
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-outline">
                                <div class="flex flex-col items-center gap-2">
                                    <span class="material-symbols-outlined text-4xl text-outline-variant">campaign</span>
                                    @if(($filters['search'] ?? '') !== '' || ($filters['status'] ?? 'all') !== 'all')
                                        <p class="font-medium text-sm">{{ __('admin.no_campaigns_matching_filters') }}</p>
                                    @else
                                        <p class="font-medium text-sm">{{ __('admin.no_campaigns_found') }}</p>
                                        <a href="{{ route('admin.campaigns.create', $room) }}" class="mt-2 px-4 py-2 bg-primary text-on-primary rounded text-xs font-semibold">{{ __('admin.create_first_campaign') }}</a>
                                    @endif
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

    <!-- Duplicate Campaign Modal -->
    <div id="duplicate-campaign-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4">
        <div class="w-full max-w-sm rounded-xl bg-surface-container-lowest p-5 shadow-xl">
            <h2 class="text-base font-bold text-on-surface">{{ __('admin.duplicate_campaign') }}</h2>
            <p class="mt-2 text-xs text-outline">{{ __('admin.confirm_duplicate_campaign') }}</p>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" data-duplicate-cancel class="px-3 py-2 rounded text-xs font-semibold bg-surface-container">{{ __('admin.cancel') }}</button>
                <button type="button" data-duplicate-confirm class="px-3 py-2 rounded text-xs font-semibold bg-primary text-on-primary">{{ __('admin.confirm_duplicate') }}</button>
            </div>
        </div>
    </div>

    <!-- Close Campaign Confirmation Modal -->
    <div id="close-campaign-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4">
        <div class="w-full max-w-sm rounded-xl bg-surface-container-lowest border border-outline-variant p-5 shadow-xl">
            <div class="flex items-center gap-2 text-amber-600 mb-2">
                <span class="material-symbols-outlined text-[22px]">lock</span>
                <h2 class="text-base font-bold text-on-surface">{{ __('admin.confirm_close_campaign_title') }}</h2>
            </div>
            <p class="mt-2 text-xs text-outline leading-relaxed">{{ __('admin.confirm_close_campaign_desc') }}</p>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" data-close-cancel class="px-3 py-2 rounded-lg text-xs font-semibold bg-surface-container hover:bg-surface-container-high text-on-surface transition-colors">{{ __('admin.cancel') }}</button>
                <button type="button" data-close-confirm class="px-3.5 py-2 rounded-lg text-xs font-semibold bg-primary hover:bg-primary-container text-on-primary transition-colors flex items-center gap-1.5 disabled:opacity-60">
                    <span class="material-symbols-outlined text-[16px] hidden animate-spin" data-spinner>progress_activity</span>
                    <span data-label>{{ __('admin.confirm_close_btn') }}</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Cancel Campaign Confirmation Modal -->
    <div id="cancel-campaign-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4">
        <div class="w-full max-w-sm rounded-xl bg-surface-container-lowest border border-outline-variant p-5 shadow-xl">
            <div class="flex items-center gap-2 text-error mb-2">
                <span class="material-symbols-outlined text-[22px]">cancel</span>
                <h2 class="text-base font-bold text-on-surface">{{ __('admin.confirm_cancel_campaign_title') }}</h2>
            </div>
            <p class="mt-2 text-xs text-outline leading-relaxed">{{ __('admin.confirm_cancel_campaign_desc') }}</p>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" data-cancel-modal-cancel class="px-3 py-2 rounded-lg text-xs font-semibold bg-surface-container hover:bg-surface-container-high text-on-surface transition-colors">{{ __('admin.cancel') }}</button>
                <button type="button" data-cancel-modal-confirm class="px-3.5 py-2 rounded-lg text-xs font-semibold bg-error hover:bg-error/90 text-white transition-colors flex items-center gap-1.5 disabled:opacity-60">
                    <span class="material-symbols-outlined text-[16px] hidden animate-spin" data-spinner>progress_activity</span>
                    <span data-label>{{ __('admin.confirm_cancel_campaign_btn') }}</span>
                </button>
            </div>
        </div>
    </div>
</x-admin.layout>

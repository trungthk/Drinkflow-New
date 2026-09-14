<x-admin.layout :title="__('admin.orders_management')" active="orders" :room="$room">
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
                <span class="text-primary font-bold">{{ __('admin.orders') }}</span>
            </div>
            <h1 class="text-2xl font-bold text-on-surface tracking-tight">{{ __('admin.orders_management') }}</h1>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-emerald-50 text-emerald-700 text-xs font-semibold border border-emerald-200">
                <span class="w-2 h-2 rounded-full bg-emerald-600 animate-pulse"></span>
                <span>Live Sync Socket</span>
            </span>
        </div>
    </div>

    <!-- Notice Notification Banner -->
    <div id="notice" class="hidden mb-4 rounded-xl px-4 py-3 text-xs font-medium"></div>

    <!-- Orders Filter & Search Toolbar -->
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 flex flex-wrap items-center justify-between gap-3 shadow-xs">
        <div class="flex items-center gap-3 flex-1 min-w-[280px]">
            <x-admin.search-input id="order-search" placeholder="{{ __('admin.search_orders_placeholder') }}" containerClass="relative w-full max-w-md" />
        </div>
        <div class="flex items-center gap-2">
            <select id="campaign-filter-select" data-searchable="true" class="h-9 px-3 bg-surface border border-outline-variant rounded text-xs text-on-surface">
                <option value="">{{ __('admin.filter_campaign_all') }}</option>
                @foreach($campaigns as $c)
                    <option value="{{ $c->id }}">{{ $c->title }} ({{ $c->restaurant }})</option>
                @endforeach
            </select>
            <select id="status-filter-select" class="h-9 px-3 bg-surface border border-outline-variant rounded text-xs text-on-surface">
                <option value="all">{{ __('admin.filter_all') }}</option>
                <option value="pending">{{ __('admin.status_pending') }}</option>
                <option value="confirmed">{{ __('admin.status_confirmed') }}</option>
                <option value="paid">{{ __('admin.status_paid') }}</option>
                <option value="cancelled">{{ __('admin.status_cancelled') }}</option>
            </select>
        </div>
    </div>

    <!-- Orders Table Ledger -->
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-surface-container-low text-outline font-mono uppercase text-[11px] border-b border-outline-variant">
                        <th class="py-3 px-4">{{ __('admin.th_order_code_user') }}</th>
                        <th class="py-3 px-4">{{ __('admin.th_campaign_restaurant') }}</th>
                        <th class="py-3 px-4">{{ __('admin.th_items_detail') }}</th>
                        <th class="py-3 px-4 text-center">{{ __('admin.th_status') }}</th>
                        <th class="py-3 px-4 text-right">{{ __('admin.th_subtotal_actual') }}</th>
                        <th class="py-3 px-4 text-center">{{ __('admin.th_actions') }}</th>
                    </tr>
                </thead>
                <tbody id="orders-tbody" class="divide-y divide-outline-variant/50">
                    @forelse($orders as $ord)
                        @php
                            $member = $ord->roomUser?->globalUser?->name ?? $ord->roomUser?->display_name ?? 'Member #' . $ord->room_user_id;
                            $stClass = match($ord->status) {
                                'paid' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'confirmed' => 'bg-blue-50 text-blue-700 border-blue-200',
                                'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
                                'cancelled' => 'bg-rose-50 text-rose-700 border-rose-200 line-through',
                                default => 'bg-surface-container text-secondary border-outline-variant'
                            };
                            $itemsSummary = $ord->items->map(function($i) {
                                $topps = $i->toppings->pluck('name')->implode(', ');
                                return $i->quantity . 'x ' . $i->item_name . ($i->size ? ' (' . $i->size . ')' : '') . ($topps ? ' [' . $topps . ']' : '');
                            })->implode(' • ');
                        @endphp
                        <tr class="hover:bg-surface-container-low/50 transition-colors" data-order-row data-status="{{ $ord->status }}" data-campaign-id="{{ $ord->campaign_id }}" data-search="{{ strtolower($member . ' ' . $ord->id . ' ' . $itemsSummary) }}">
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-on-surface text-sm flex items-center gap-1.5">
                                    <span>#ORD-{{ $ord->id }}</span>
                                    @if($ord->is_locked)
                                        <span class="material-symbols-outlined text-[14px] text-amber-600" title="{{ __('admin.order_locked_tooltip') }}">lock</span>
                                    @endif
                                </div>
                                <div class="text-secondary text-xs flex items-center gap-1 mt-0.5">
                                    <span class="material-symbols-outlined text-[14px]">person</span>
                                    <span>{{ $member }}</span>
                                </div>
                                <div class="text-[11px] font-mono text-outline mt-0.5">
                                    {{ $ord->created_at ? $ord->created_at->format('H:i d/m/Y') : '' }}
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-on-surface">{{ $ord->campaign?->title ?? 'N/A' }}</div>
                                <div class="text-[11px] text-outline flex items-center gap-1 mt-0.5">
                                    <span class="material-symbols-outlined text-[12px]">storefront</span>
                                    <span>{{ $ord->campaign?->restaurant ?? 'N/A' }}</span>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 max-w-xs">
                                <div class="text-on-surface font-medium line-clamp-2" title="{{ $itemsSummary }}">{{ $itemsSummary ?: __('admin.ordered_items') }}</div>
                                @if($ord->note)
                                    <div class="text-[11px] text-amber-700 italic mt-0.5">📝 {{ $ord->note }}</div>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold border {{ $stClass }}">
                                    {{ ucfirst($ord->status) }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono">
                                <div class="text-xs text-outline line-through">{{ number_format($ord->subtotal_amount ?? 0) }} ₫</div>
                                <div class="font-bold text-sm text-primary">{{ number_format($ord->final_amount ?? $ord->subtotal_amount ?? 0) }} ₫</div>
                                @if($ord->sponsor_amount > 0)
                                    <div class="text-[10px] text-emerald-600">-{{ number_format($ord->sponsor_amount) }} ₫</div>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button type="button" onclick="openPriceAdjustmentModal({{ $ord->id }})" class="px-2.5 py-1 bg-primary text-on-primary hover:bg-primary/90 rounded text-[11px] font-semibold flex items-center gap-1 transition-colors" title="{{ __('admin.adjust_price_btn') }}">
                                        <span class="material-symbols-outlined text-[14px]">tune</span>
                                        <span>{{ __('admin.adjust_price_btn') }}</span>
                                    </button>
                                    @if($ord->is_locked)
                                        <button type="button" onclick="unlockOrder({{ $ord->id }})" class="p-1 text-secondary hover:text-amber-600 rounded hover:bg-surface-container transition-colors" title="{{ __('admin.unlock_order_btn') }}">
                                            <span class="material-symbols-outlined text-[16px]">lock_open</span>
                                        </button>
                                    @endif
                                    @if($ord->status !== 'cancelled')
                                        <button type="button" onclick="cancelOrder({{ $ord->id }})" class="p-1 text-secondary hover:text-rose-600 rounded hover:bg-surface-container transition-colors" title="{{ __('admin.cancel_order_btn') }}">
                                            <span class="material-symbols-outlined text-[16px]">cancel</span>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-outline">
                                <div class="flex flex-col items-center gap-2">
                                    <span class="material-symbols-outlined text-4xl text-outline-variant">local_shipping</span>
                                    <p class="font-medium text-sm">{{ __('admin.no_orders_found') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())
            <div class="p-4 border-t border-outline-variant">
                {{ $orders->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Price Adjustment -->
    <div id="price-adjust-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4 backdrop-blur-xs">
        <div id="modal-backdrop" class="absolute inset-0"></div>
        <div class="relative z-10 w-full max-w-2xl bg-surface-container-lowest border border-outline-variant rounded-xl p-6 shadow-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-3 border-b border-outline-variant mb-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded bg-primary text-on-primary flex items-center justify-center">
                        <span class="material-symbols-outlined text-[18px]">tune</span>
                    </div>
                    <div>
                        <h3 class="font-bold text-base text-on-surface" id="modal-order-title">{{ __('admin.adjust_price_title', ['id' => '']) }}</h3>
                        <p class="text-xs text-outline">{{ __('admin.adjust_reason') }}</p>
                    </div>
                </div>
                <button type="button" onclick="closePriceAdjustModal()" class="text-outline hover:text-on-surface p-1">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>

            <div id="modal-body" class="space-y-4 text-xs">
                <!-- Dynamically rendered via JS -->
            </div>
        </div>
    </div>
</x-admin.layout>

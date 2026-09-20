@php
    $isLocked = $campaign->isLocked();
    $menuItems = $campaign->items()->orderBy('sort_order')->orderBy('id')->get();
    $campaignStatusValue = $campaign->status instanceof \BackedEnum ? $campaign->status->value : (string) $campaign->status;
@endphp

<x-admin.layout :title="__('admin.brand_title') . ' · ' . $campaign->name . ' · ' . __('admin.campaign_nav_menu')" active="campaigns" :room="$room">
    <div id="campaign-app" class="space-y-6">
        <!-- TOP SUB-NAVIGATION BAR -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-outline-variant/60">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.campaigns.info', [$room, $campaign]) }}"
                    class="p-2 rounded-xl border border-outline-variant hover:bg-surface-container text-outline hover:text-on-surface transition-colors flex items-center justify-center shrink-0"
                    title="{{ __('admin.back_to_campaigns') }}">
                    <span class="material-symbols-outlined text-[20px]">arrow_back</span>
                </a>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-xl sm:text-2xl font-bold text-on-surface tracking-tight">{{ $campaign->name }}</h1>
                        <span class="text-xs text-outline bg-surface-container-low border border-outline-variant px-2 py-0.5 rounded font-mono font-semibold">#{{ $campaign->code ?? 'N/A' }}</span>
                        <x-admin.campaign-status-badge :campaign="$campaign" />
                    </div>
                    <p class="text-xs text-outline flex items-center gap-1.5 mt-0.5">
                        <span class="material-symbols-outlined text-[15px] text-primary">storefront</span>
                        <span class="font-medium text-on-surface-variant">{{ $campaign->restaurant }}</span>
                        <span class="text-outline-variant">•</span>
                        <span>{{ $room->name }}</span>
                    </p>
                </div>
            </div>

            <!-- Sub-navigation Tabs -->
            <div class="flex items-center gap-1.5 bg-surface-container-low p-1.5 rounded-2xl border border-outline-variant/60 self-start sm:self-auto overflow-x-auto max-w-full">
                <a href="{{ route('admin.campaigns.info', [$room, $campaign]) }}"
                    class="px-3.5 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 transition-all no-underline text-outline hover:text-on-surface hover:bg-surface-container/60">
                    <span class="material-symbols-outlined text-[18px]">info</span>
                    <span>{{ __('admin.campaign_nav_info') }}</span>
                </a>
                <a href="{{ route('admin.campaigns.orders', [$room, $campaign]) }}"
                    class="px-3.5 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 transition-all no-underline text-outline hover:text-on-surface hover:bg-surface-container/60">
                    <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                    <span>{{ __('admin.campaign_nav_orders') }}</span>
                </a>
                <a href="{{ route('admin.campaigns.menu', [$room, $campaign]) }}"
                    class="px-3.5 py-2 rounded-xl text-xs font-bold flex items-center gap-2 transition-all no-underline bg-surface-container-lowest text-primary shadow-xs border border-outline-variant/50">
                    <span class="material-symbols-outlined text-[18px]">restaurant_menu</span>
                    <span>{{ __('admin.campaign_nav_menu') }}</span>
                </a>
            </div>
        </div>

        <script>
            window.openModal = function (id) { const el = document.getElementById(id); if (el) el.style.display = 'flex'; };
            window.closeModal = function (id) { const el = document.getElementById(id); if (el) el.style.display = 'none'; };
            window.closeModalOnBackdrop = function (event, id, canClose) {
                if (event.target !== event.currentTarget) return;
                if (typeof canClose === 'function' && !canClose()) return;
                window.closeModal(id);
            };
        </script>

        @if ($isLocked)
            <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-800 flex items-start gap-2.5">
                <span class="material-symbols-outlined text-[20px] shrink-0">lock</span>
                <span>{{ __('admin.campaign_locked_cannot_modify') }}</span>
            </div>
        @endif

                @php
                    $categories = $menuItems->pluck('category')->filter()->unique()->sort()->values();
                    $itemsJson = $menuItems->map(
                        fn($it) => [
                            'id' => $it->id,
                            'name' => $it->name,
                            'category' => $it->category ?: '',
                            'base_price' => (float) $it->base_price,
                            'image_url' => $it->image_url,
                            'status' => $it->status?->value ?? (string) $it->status,
                            'initial_status' => $it->status?->value ?? (string) $it->status,
                        ],
                    );
                @endphp
                <section id="campaign-items-section"
                    class="bg-surface-container-lowest border border-outline-variant rounded-xl p-5 shadow-xs space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-outline-variant/40">
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-lg font-bold text-on-surface">{{ __('admin.campaign_items_tab') }}</h2>
                                <span
                                    class="px-2 py-0.5 rounded-full text-xs font-semibold bg-surface-container text-outline font-mono"
                                    id="items-count-badge">{{ $itemsJson->count() }} {{ __('admin.portions') }}</span>
                            </div>
                            <p class="text-xs text-outline mt-0.5">{{ __('admin.campaign_items_manage_description') }}</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2.5">
                            <!-- Category Filter Dropdown (Client-side JS) -->
                            <div class="flex items-center gap-1.5">
                                <label for="category-filter-select"
                                    class="text-xs font-semibold text-outline">{{ __('admin.source_category') }}:</label>
                                {{-- Searchable dropdown: matches keywords regardless of case or diacritics. --}}
                                <div class="w-56">
                                    <select id="category-filter-select" onchange="onCategoryFilterChange(this.value)"
                                        data-searchable="true"
                                        data-placeholder="{{ __('admin.select_search_placeholder') }}"
                                        data-empty-text="{{ __('admin.no_options_found') }}"
                                        class="h-9 px-3 bg-surface border border-outline-variant rounded-lg text-xs font-semibold focus:border-primary outline-hidden transition-colors cursor-pointer">
                                        <option value="all">{{ __('admin.filter_all') }}</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category }}">{{ $category }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Reset Changes Button (if any changes) -->
                            <button type="button" id="reset-changes-btn" onclick="resetItemChanges()"
                                class="h-9 px-3 rounded-lg border border-outline-variant hover:bg-surface-container text-outline hover:text-on-surface text-xs font-semibold flex items-center gap-1 transition-colors cursor-pointer"
                                style="display: none;">
                                <span class="material-symbols-outlined text-[16px]">undo</span>
                                <span>{{ __('admin.reset_changes') }}</span>
                            </button>

                            <!-- Batch Update Button -> Open Confirm Modal -->
                            <button type="button" id="items-save-btn" onclick="openItemsConfirmModal()" disabled
                                class="h-9 px-4 rounded-lg bg-primary hover:bg-primary/90 text-on-primary text-xs font-semibold flex items-center gap-2 transition-all shadow-xs disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                                <span class="material-symbols-outlined text-[16px]">save</span>
                                <span>{{ __('admin.save_items_status_btn') }}</span>
                                <span id="items-changed-badge"
                                    class="px-1.5 py-0.2 rounded-full bg-white text-primary text-[10px] font-bold"
                                    style="display: none;"></span>
                            </button>
                        </div>
                    </div>

                    <!-- Items Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3" id="items-grid">
                        @foreach ($itemsJson as $it)
                            <div class="border rounded-lg p-3 flex items-center gap-3 transition-colors border-outline-variant bg-surface"
                                id="item-card-{{ $it['id'] }}" data-item-id="{{ $it['id'] }}"
                                data-item-category="{{ $it['category'] }}">
                                <div
                                    class="w-12 h-12 rounded bg-surface-container overflow-hidden shrink-0 relative flex items-center justify-center">
                                    <span
                                        class="material-symbols-outlined text-outline-variant text-[20px]">restaurant</span>
                                    @if ($it['image_url'])
                                        <img src="{{ $it['image_url'] }}" alt="{{ $it['name'] }}" loading="lazy"
                                            onload="this.hidden = false" onerror="this.hidden = true"
                                            class="absolute inset-0 w-full h-full object-cover">
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="font-semibold text-sm truncate text-on-surface">{{ $it['name'] }}</div>
                                    <div class="text-xs text-outline">
                                        <span>{{ $it['category'] ?: __('admin.filter_all') }}</span> ·
                                        <span class="font-mono font-medium text-on-surface">{{ \App\Support\Helpers\FormatHelper::formatCurrency($it['base_price']) }}</span>
                                    </div>
                                    <div id="item-changed-indicator-{{ $it['id'] }}"
                                        class="text-[10px] font-semibold mt-0.5 flex items-center gap-1" style="display: none;">
                                        <span class="w-1.5 h-1.5 rounded-full" id="item-changed-dot-{{ $it['id'] }}"></span>
                                        <span id="item-changed-label-{{ $it['id'] }}"></span>
                                    </div>
                                </div>
                                <button type="button" role="switch" id="item-toggle-{{ $it['id'] }}"
                                    aria-checked="{{ $it['status'] === 'active' ? 'true' : 'false' }}"
                                    onclick="toggleItemStatus({{ $it['id'] }})" @disabled($isLocked)
                                    data-item-status="{{ $it['status'] }}" data-item-initial-status="{{ $it['status'] }}"
                                    class="relative inline-flex h-6 w-11 rounded-full transition-colors cursor-pointer disabled:cursor-not-allowed disabled:opacity-50 shrink-0 {{ $it['status'] === 'active' ? 'bg-primary' : 'bg-outline-variant' }}">
                                    <span id="item-toggle-dot-{{ $it['id'] }}"
                                        class="inline-block h-5 w-5 mt-0.5 rounded-full bg-white shadow transition-transform {{ $it['status'] === 'active' ? 'translate-x-5' : 'translate-x-0.5' }}"></span>
                                </button>
                            </div>
                        @endforeach

                        <div id="items-empty-state" class="col-span-full py-12 text-center space-y-2" style="display: none;">
                            <span
                                class="material-symbols-outlined text-[36px] text-outline-variant">restaurant_menu</span>
                            <p class="text-sm text-outline">{{ __('admin.no_campaign_items') }}</p>
                        </div>
                    </div>

                    <!-- MODAL: CONFIRM BATCH UPDATE ITEMS STATUS -->
                    <div id="modal-items-confirm"
                        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
                        style="display: none;" onclick="closeModalOnBackdrop(event, 'modal-items-confirm', () => !window.__itemsIsSaving)">
                        <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-2xl w-full max-w-md overflow-hidden flex flex-col" onclick="event.stopPropagation()">
                            <div
                                class="p-5 border-b border-outline-variant/60 flex items-center justify-between bg-surface-container-low">
                                <div class="flex items-center gap-2.5">
                                    <span
                                        class="w-9 h-9 rounded-full bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                        <span class="material-symbols-outlined text-[22px]">checklist</span>
                                    </span>
                                    <div>
                                        <h3 class="text-base font-bold text-on-surface">
                                            {{ __('admin.confirm_update_items_status_title') }}
                                        </h3>
                                        <p class="text-xs text-outline font-mono">#{{ $campaign->code }} ·
                                            {{ $campaign->name }}</p>
                                    </div>
                                </div>
                                <button type="button" id="items-confirm-close-btn" onclick="closeItemsConfirmModal()"
                                    class="text-outline hover:text-on-surface disabled:opacity-40 cursor-pointer">
                                    <span class="material-symbols-outlined text-[20px]">close</span>
                                </button>
                            </div>

                            <div class="p-5 space-y-4 text-xs">
                                <p class="text-on-surface leading-relaxed" id="items-confirm-desc">
                                </p>

                                <!-- Breakdown stats -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-950">
                                        <span
                                            class="text-[11px] text-emerald-800 font-medium block">{{ __('admin.will_be_activated') }}</span>
                                        <span class="text-lg font-bold font-mono mt-0.5 block text-emerald-700"
                                            id="items-confirm-activated-count">
                                        </span>
                                    </div>
                                    <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-amber-950">
                                        <span
                                            class="text-[11px] text-amber-800 font-medium block">{{ __('admin.will_be_hidden') }}</span>
                                        <span class="text-lg font-bold font-mono mt-0.5 block text-amber-700"
                                            id="items-confirm-hidden-count">
                                        </span>
                                    </div>
                                </div>

                                <!-- Modal Actions -->
                                <div class="pt-3 border-t border-outline-variant/60 flex items-center justify-end gap-2">
                                    <button type="button" id="items-confirm-cancel-btn" onclick="closeItemsConfirmModal()"
                                        class="px-4 py-2.5 rounded-lg border border-outline-variant text-xs font-semibold text-on-surface hover:bg-surface-container transition-colors cursor-pointer disabled:opacity-50">
                                        {{ __('admin.cancel') }}
                                    </button>
                                    <button type="button" id="items-confirm-save-btn" onclick="executeSaveBatchStatus()"
                                        class="px-4 py-2.5 rounded-lg bg-primary hover:bg-primary/90 text-on-primary text-xs font-semibold flex items-center gap-2 transition-colors cursor-pointer shadow-xs disabled:opacity-50">
                                        <span id="items-confirm-save-loading" class="flex items-center gap-1.5" style="display: none;">
                                            <svg class="animate-spin h-4 w-4 text-white"
                                                xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                                    stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor"
                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                </path>
                                            </svg>
                                            <span>{{ __('admin.processing') }}</span>
                                        </span>
                                        <span id="items-confirm-save-normal" class="flex items-center gap-1.5">
                                            <span class="material-symbols-outlined text-[18px]">check_circle</span>
                                            <span>{{ __('admin.confirm_update_btn') }}</span>
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <script>
                    (function () {
                        'use strict';
                        const itemsData = {{ Js::from($itemsJson) }};
                        let selectedCategory = 'all';
                        window.__itemsIsSaving = false;

                        window.__menuIsLocked = {{ Js::from($isLocked) }};

                        function changedCount() {
                            return itemsData.filter(item => item.status !== item.initial_status).length;
                        }

                        function updateItemCardVisualState(item) {
                            const card = document.getElementById('item-card-' + item.id);
                            const toggle = document.getElementById('item-toggle-' + item.id);
                            const dot = document.getElementById('item-toggle-dot-' + item.id);
                            const indicator = document.getElementById('item-changed-indicator-' + item.id);
                            const dotIndicator = document.getElementById('item-changed-dot-' + item.id);
                            const label = document.getElementById('item-changed-label-' + item.id);
                            const changed = item.status !== item.initial_status;

                            if (card) {
                                card.classList.toggle('border-primary/50', changed);
                                card.classList.toggle('bg-primary/5', changed);
                                card.classList.toggle('shadow-2xs', changed);
                                card.classList.toggle('border-outline-variant', !changed);
                                card.classList.toggle('bg-surface', !changed);
                            }
                            if (toggle) {
                                toggle.setAttribute('aria-checked', item.status === 'active' ? 'true' : 'false');
                                toggle.classList.toggle('bg-primary', item.status === 'active');
                                toggle.classList.toggle('bg-outline-variant', item.status !== 'active');
                            }
                            if (dot) {
                                dot.classList.toggle('translate-x-5', item.status === 'active');
                                dot.classList.toggle('translate-x-0.5', item.status !== 'active');
                            }
                            if (indicator) {
                                indicator.style.display = changed ? '' : 'none';
                                if (label) label.textContent = item.status === 'active' ? '{{ addslashes(__('admin.will_be_activated')) }}' : '{{ addslashes(__('admin.will_be_hidden')) }}';
                                if (dotIndicator) {
                                    dotIndicator.classList.toggle('bg-primary', item.status === 'active');
                                    dotIndicator.classList.toggle('bg-amber-600', item.status !== 'active');
                                }
                                if (label) {
                                    label.classList.toggle('text-primary', false);
                                }
                                if (indicator) {
                                    indicator.classList.toggle('text-primary', item.status === 'active');
                                    indicator.classList.toggle('text-amber-700', item.status !== 'active');
                                }
                            }
                        }

                        function refreshSaveControls() {
                            const count = changedCount();
                            const resetBtn = document.getElementById('reset-changes-btn');
                            const saveBtn = document.getElementById('items-save-btn');
                            const badge = document.getElementById('items-changed-badge');
                            if (resetBtn) resetBtn.style.display = count > 0 ? '' : 'none';
                            if (saveBtn) saveBtn.disabled = count === 0;
                            if (badge) {
                                badge.style.display = count > 0 ? '' : 'none';
                                badge.textContent = count;
                            }
                        }

                        function refreshItemsGrid() {
                            let visibleCount = 0;
                            itemsData.forEach(item => {
                                const card = document.getElementById('item-card-' + item.id);
                                if (!card) return;
                                const matchCat = selectedCategory === 'all' || item.category === selectedCategory;
                                card.style.display = matchCat ? '' : 'none';
                                if (matchCat) visibleCount++;
                            });
                            const emptyState = document.getElementById('items-empty-state');
                            if (emptyState) emptyState.style.display = visibleCount === 0 ? '' : 'none';
                        }

                        window.onCategoryFilterChange = function (value) {
                            selectedCategory = value;
                            refreshItemsGrid();
                        };

                        window.toggleItemStatus = function (id) {
                            if (window.__menuIsLocked) return;
                            const item = itemsData.find(it => it.id === id);
                            if (!item) return;
                            item.status = item.status === 'active' ? 'inactive' : 'active';
                            updateItemCardVisualState(item);
                            refreshSaveControls();
                        };

                        window.resetItemChanges = function () {
                            itemsData.forEach(item => {
                                item.status = item.initial_status;
                                updateItemCardVisualState(item);
                            });
                            refreshSaveControls();
                        };

                        window.openItemsConfirmModal = function () {
                            if (changedCount() === 0) {
                                if (window.notify) window.notify('{{ addslashes(__('admin.no_changes_to_save')) }}', 'info');
                                return;
                            }
                            const desc = document.getElementById('items-confirm-desc');
                            if (desc) {
                                desc.textContent = '{{ addslashes(__('admin.confirm_update_items_status_desc', ['count' => ':count'])) }}'.replace(':count', changedCount());
                            }
                            const activatedCount = itemsData.filter(i => i.status !== i.initial_status && i.status === 'active').length;
                            const hiddenCount = itemsData.filter(i => i.status !== i.initial_status && i.status === 'inactive').length;
                            const activatedEl = document.getElementById('items-confirm-activated-count');
                            const hiddenEl = document.getElementById('items-confirm-hidden-count');
                            if (activatedEl) activatedEl.textContent = activatedCount + ' {{ __('admin.portions') }}';
                            if (hiddenEl) hiddenEl.textContent = hiddenCount + ' {{ __('admin.portions') }}';
                            window.openModal('modal-items-confirm');
                        };

                        window.closeItemsConfirmModal = function () {
                            if (window.__itemsIsSaving) return;
                            window.closeModal('modal-items-confirm');
                        };

                        function setItemsSavingState(saving) {
                            window.__itemsIsSaving = saving;
                            const saveBtn = document.getElementById('items-confirm-save-btn');
                            const cancelBtn = document.getElementById('items-confirm-cancel-btn');
                            const closeBtn = document.getElementById('items-confirm-close-btn');
                            const loadingEl = document.getElementById('items-confirm-save-loading');
                            const normalEl = document.getElementById('items-confirm-save-normal');
                            if (saveBtn) saveBtn.disabled = saving;
                            if (cancelBtn) cancelBtn.disabled = saving;
                            if (closeBtn) closeBtn.disabled = saving;
                            if (loadingEl) loadingEl.style.display = saving ? '' : 'none';
                            if (normalEl) normalEl.style.display = saving ? 'none' : '';
                        }

                        window.executeSaveBatchStatus = async function () {
                            const changed = itemsData.filter(item => item.status !== item.initial_status);
                            if (changed.length === 0) {
                                window.closeModal('modal-items-confirm');
                                return;
                            }
                            setItemsSavingState(true);
                            try {
                                const endpoint = '{{ route('admin.campaign-items.batch-status', [$room, $campaign]) }}';
                                const res = await dfApi(endpoint, {
                                    method: 'PATCH',
                                    body: {
                                        items: changed.map(it => ({ id: it.id, status: it.status }))
                                    }
                                });
                                itemsData.forEach(item => {
                                    item.initial_status = item.status;
                                });
                                window.closeModal('modal-items-confirm');
                                refreshSaveControls();
                                if (window.notify) {
                                    window.notify(res.message || '{{ addslashes(__('admin.campaign_items_batch_updated_success', ['count' => ':count'])) }}'.replace(':count', changed.length), 'success');
                                }
                            } catch (err) {
                                if (window.notify) {
                                    window.notify(err.message || '{{ addslashes(__('admin.order_status_update_failed')) }}', 'error');
                                } else {
                                    alert(err.message || '{{ addslashes(__('admin.campaign_menu_item_status_failed')) }}');
                                }
                            } finally {
                                setItemsSavingState(false);
                            }
                        };
                    })();
                </script>
    </div>
</x-admin.layout>

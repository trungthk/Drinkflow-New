{{-- Admin order modal, driven by resources/js/admin/order-on-behalf.js. Two modes sharing one menu/cart UI:
     "order on behalf of a member" (create) and "edit order" (replace an existing order's items).
     Neither mode supports ordering for someone else: every line is billed to the order's member. --}}
<div id="order-on-behalf-modal"
     data-url="{{ route('admin.orders.on-behalf', $room) }}"
     data-edit-url="{{ route('admin.orders.items.update', [$room, '__ORDER__']) }}"
     data-show-url="{{ route('admin.orders.show', [$room, '__ORDER__']) }}"
     data-campaign-id="{{ $data['campaign_id'] }}"
     data-items="{{ json_encode($data['items'], JSON_UNESCAPED_UNICODE) }}"
     data-i18n="{{ json_encode([
         'required' => __('admin.on_behalf_required'),
         'failed' => __('admin.on_behalf_failed'),
         'submitting' => __('admin.on_behalf_submitting'),
         'remove' => __('admin.on_behalf_remove'),
         'selfPaid' => __('admin.on_behalf_self_paid'),
         'itemPlaceholder' => __('admin.on_behalf_item_placeholder'),
         'decrease' => __('admin.on_behalf_decrease'),
         'increase' => __('admin.on_behalf_increase'),
         'editTitle' => __('admin.edit_order_title', ['code' => ':code']),
         'editSubmit' => __('admin.edit_order_submit'),
         'editSubmitting' => __('admin.edit_order_submitting'),
         'editFailed' => __('admin.edit_order_failed'),
         'editRequired' => __('admin.edit_order_required'),
         'unavailable' => __('admin.edit_order_unavailable'),
         'hasUnavailable' => __('admin.edit_order_has_unavailable'),
         'loadFailed' => __('admin.load_order_failed'),
         'loading' => __('admin.edit_order_loading'),
     ], JSON_UNESCAPED_UNICODE) }}"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4 backdrop-blur-xs"
     role="dialog" aria-modal="true" aria-labelledby="order-on-behalf-title">
    <div data-on-behalf-backdrop class="absolute inset-0"></div>
    <div class="relative z-10 w-full max-w-2xl max-h-[calc(100vh-2rem)] flex flex-col bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-2xl overflow-hidden">
        <div class="flex items-start gap-3.5 p-5 border-b border-outline-variant/60">
            <div class="w-10 h-10 rounded-xl bg-primary/10 border border-primary/20 text-primary flex items-center justify-center shrink-0">
                <span data-on-behalf-icon class="material-symbols-outlined text-[22px]" data-edit-icon="edit_note">person_add</span>
            </div>
            <div class="flex-1 min-w-0">
                <h3 id="order-on-behalf-title" class="font-bold text-base text-on-surface">{{ __('admin.on_behalf_title') }}</h3>
                <p data-on-behalf-subtitle data-edit-text="{{ __('admin.edit_order_subtitle') }}" class="text-xs text-outline mt-1">{{ __('admin.on_behalf_subtitle') }}</p>
            </div>
            <button type="button" data-on-behalf-close class="text-outline hover:text-on-surface p-1 rounded-lg hover:bg-surface-container transition-colors" aria-label="{{ __('admin.close_modal_btn') }}">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>

        <form data-on-behalf-form class="flex-1 overflow-y-auto p-5 space-y-4 text-xs" novalidate>
            <div data-on-behalf-member-fixed class="hidden">
                <span class="font-semibold text-on-surface">{{ __('admin.on_behalf_member') }}</span>
                <p data-on-behalf-member-name class="mt-1 rounded-lg bg-surface-container-low px-3 py-2 font-semibold text-on-surface"></p>
            </div>
            <label data-on-behalf-member-wrap class="block">
                <span class="font-semibold text-on-surface">{{ __('admin.on_behalf_member') }}<span class="ml-0.5 text-red-600" aria-hidden="true">*</span></span>
                @if(count($data['members']) > 0)
                    <select data-on-behalf-member required data-searchable="true"
                            data-placeholder="{{ __('admin.on_behalf_search_placeholder') }}"
                            data-empty-text="{{ __('admin.on_behalf_search_empty') }}"
                            aria-label="{{ __('admin.on_behalf_member') }}"
                            class="mt-1 w-full h-9 rounded-lg border border-outline-variant bg-surface px-2.5 text-xs text-on-surface">
                        <option value="">{{ __('admin.on_behalf_member_placeholder') }}</option>
                        @foreach($data['members'] as $member)
                            <option value="{{ $member['id'] }}" data-search="{{ $member['email'] }}">{{ $member['name'] }}{{ $member['email'] ? ' — ' . $member['email'] : '' }}</option>
                        @endforeach
                    </select>
                @else
                    <p class="mt-1 rounded-lg bg-surface-container-low px-3 py-2 text-outline">{{ __('admin.on_behalf_no_members') }}</p>
                @endif
            </label>

            <fieldset class="rounded-xl border border-outline-variant/70 p-3.5 space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-[1fr_6rem] gap-3">
                    <label class="block min-w-0">
                        <span class="font-semibold text-on-surface">{{ __('admin.on_behalf_item') }}</span>
                        <select data-on-behalf-item data-searchable="true"
                                data-placeholder="{{ __('admin.on_behalf_search_placeholder') }}"
                                data-empty-text="{{ __('admin.on_behalf_search_empty') }}"
                                aria-label="{{ __('admin.on_behalf_item') }}"
                                class="mt-1 w-full h-9 rounded-lg border border-outline-variant bg-surface px-2.5 text-xs text-on-surface"></select>
                    </label>
                    <label class="block">
                        <span class="font-semibold text-on-surface">{{ __('admin.on_behalf_quantity') }}</span>
                        <input data-on-behalf-quantity type="number" min="1" max="99" value="1" class="mt-1 w-full h-9 rounded-lg border border-outline-variant bg-surface px-2.5 text-xs text-on-surface">
                    </label>
                </div>
                <label data-on-behalf-size-wrap class="hidden">
                    <span class="font-semibold text-on-surface">{{ __('admin.on_behalf_size') }}</span>
                    <select data-on-behalf-size class="mt-1 w-full h-9 rounded-lg border border-outline-variant bg-surface px-2.5 text-xs text-on-surface"></select>
                </label>
                <div data-on-behalf-toppings-wrap class="hidden">
                    <span class="font-semibold text-on-surface">{{ __('admin.on_behalf_toppings') }}</span>
                    <div data-on-behalf-toppings class="mt-1 flex flex-wrap gap-2"></div>
                </div>
                <label class="block">
                    <span class="font-semibold text-on-surface">{{ __('admin.on_behalf_item_note') }}</span>
                    <input data-on-behalf-item-note type="text" maxlength="500" class="mt-1 w-full h-9 rounded-lg border border-outline-variant bg-surface px-2.5 text-xs text-on-surface">
                </label>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <label class="inline-flex items-center gap-2 text-on-surface">
                        <input data-on-behalf-self-paid type="checkbox" class="rounded border-outline-variant">
                        <span>{{ __('admin.on_behalf_self_paid') }}</span>
                    </label>
                    <button type="button" data-on-behalf-add class="inline-flex items-center gap-1.5 rounded-lg border border-primary/40 bg-primary/10 px-3 py-1.5 font-semibold text-primary hover:bg-primary/20 transition-colors">
                        <span class="material-symbols-outlined text-[16px]">add_shopping_cart</span>
                        <span>{{ __('admin.on_behalf_add_item') }}</span>
                    </button>
                </div>
            </fieldset>

            <div>
                <h4 class="font-semibold text-on-surface mb-1.5">{{ __('admin.on_behalf_items') }}</h4>
                <ul data-on-behalf-cart class="space-y-1.5"></ul>
                <p data-on-behalf-empty class="rounded-lg bg-surface-container-low px-3 py-2 text-outline">{{ __('admin.on_behalf_empty_cart') }}</p>
            </div>

            <label class="block">
                <span class="font-semibold text-on-surface">{{ __('admin.on_behalf_order_note') }}</span>
                <textarea data-on-behalf-note rows="2" maxlength="1000" class="mt-1 w-full rounded-lg border border-outline-variant bg-surface p-2.5 text-xs text-on-surface"></textarea>
            </label>

            <p data-on-behalf-error class="hidden rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-rose-700 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-300" role="alert"></p>
        </form>

        <div class="flex flex-wrap items-center justify-between gap-3 p-5 border-t border-outline-variant/60 bg-surface-container-low/60">
            <div class="text-xs">
                <span class="text-outline">{{ __('admin.on_behalf_total') }}:</span>
                <span data-on-behalf-total class="font-bold font-mono text-primary">0đ</span>
            </div>
            <div class="flex items-center gap-2.5">
                <button type="button" data-on-behalf-close class="px-4 py-2 text-xs font-semibold rounded-xl border border-outline-variant text-on-surface hover:bg-surface-container transition-colors">
                    {{ __('admin.cancel') }}
                </button>
                <button type="button" data-on-behalf-submit @disabled(count($data['members']) === 0) class="px-4 py-2 text-xs font-semibold rounded-xl bg-primary text-white shadow-xs inline-flex items-center gap-1.5 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                    <span class="material-symbols-outlined text-[16px]">send</span>
                    <span data-on-behalf-submit-label>{{ __('admin.on_behalf_submit') }}</span>
                </button>
            </div>
        </div>
    </div>
</div>

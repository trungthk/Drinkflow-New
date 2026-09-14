import { debounce } from './ui-enhancements';

/**
 * Initialize order search, filtering, deletion, and price adjustment.
 *
 * @returns {void}
 */
export function initAdminOrders() {
    const page = document.querySelector('#admin-orders-page');
    if (!page) return;

    const i18n = JSON.parse(page.dataset.i18n || '{}');
    const search = page.querySelector('#order-search');
    const campaign = page.querySelector('#campaign-filter-select');
    const status = page.querySelector('#status-filter-select');
    const modal = page.querySelector('#price-adjust-modal');
    const body = page.querySelector('#modal-body');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const room = document.querySelector('meta[name="room-slug"]')?.content || window.__DF_ROOM_SLUG__ || '';
    const escape = (value) => String(value ?? '').replace(/[&<>'"]/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[char]));

    /**
     * Apply active filters to order rows.
     *
     * @returns {void}
     */
    const filterRows = () => {
        const keyword = search?.value.trim().toLowerCase() || '';
        page.querySelectorAll('[data-order-row]').forEach((row) => {
            const matches = row.dataset.search?.includes(keyword)
                && (!campaign?.value || row.dataset.campaignId === campaign.value)
                && (status?.value === 'all' || row.dataset.status === status?.value);
            row.style.display = matches ? '' : 'none';
        });
    };
    search?.addEventListener('admin:search', filterRows);
    campaign?.addEventListener('change', filterRows);
    status?.addEventListener('change', filterRows);

    /**
     * Close the adjustment modal.
     *
     * @returns {void}
     */
    const closeModal = () => {
        modal?.classList.add('hidden');
        modal?.classList.remove('flex');
    };
    page.querySelector('#modal-backdrop')?.addEventListener('click', closeModal);
    window.closePriceAdjustModal = closeModal;

    /**
     * Display editable item prices for an order.
     *
     * @param {number} orderId Order ID.
     * @returns {Promise<void>}
     */
    window.openPriceAdjustmentModal = async (orderId) => {
        if (!modal || !body) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        body.innerHTML = '<div class="py-8 text-center text-outline"><span class="material-symbols-outlined animate-spin text-[24px]">progress_activity</span></div>';
        try {
            const response = await fetch('/admin/' + room + '/orders/' + orderId, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error(i18n.loadFailed);
            const order = (await response.json()).data;
            const title = page.querySelector('#modal-order-title');
            if (title) title.textContent = '#ORD-' + order.id + ' - ' + (order.room_user?.display_name || '');
            const items = (order.items || []).map((item) => '<div class="grid grid-cols-[1fr_7rem] gap-3 items-center p-2.5 rounded border border-outline-variant/60"><div class="min-w-0"><div class="font-bold text-on-surface truncate">' + escape(item.quantity) + '× ' + escape(item.item_name) + (item.size ? ' (' + escape(item.size) + ')' : '') + '</div><div class="text-[11px] text-outline truncate">' + escape(item.toppings?.map((topping) => topping.name).join(', ') || '—') + '</div></div><input type="number" min="0" data-price data-id="' + escape(item.id) + '" data-quantity="' + escape(item.quantity) + '" value="' + escape(item.unit_price) + '" class="w-full h-9 px-2 bg-surface border border-outline-variant rounded font-mono font-bold text-right text-primary"></div>').join('');
            body.innerHTML = '<form id="price-adjust-form" class="space-y-4"><div class="space-y-2"><h4 class="font-semibold text-on-surface">' + escape(i18n.orderedItems) + '</h4>' + items + '</div><div class="flex justify-between rounded-lg bg-surface-container-low p-3 text-sm"><span class="font-semibold">' + escape(i18n.subtotal) + '</span><span id="adjusted-subtotal" class="font-mono font-bold text-primary"></span></div><div><label class="block font-semibold text-on-surface mb-1" for="adj-reason">' + escape(i18n.adjustmentReason) + '</label><textarea id="adj-reason" rows="2" required class="w-full p-2.5 bg-surface border border-outline-variant rounded text-xs"></textarea></div><div class="pt-3 border-t border-outline-variant flex justify-end gap-2"><button type="button" data-close class="px-4 py-2 bg-surface-container rounded font-semibold">' + escape(i18n.cancel) + '</button><button type="submit" class="px-4 py-2 bg-primary text-on-primary rounded font-semibold">' + escape(i18n.save) + '</button></div></form>';
            const refreshTotal = () => {
                const total = [...body.querySelectorAll('[data-price]')].reduce((sum, input) => sum + (Number(input.value) || 0) * (Number(input.dataset.quantity) || 0), 0);
                body.querySelector('#adjusted-subtotal').textContent = new Intl.NumberFormat(document.documentElement.lang || 'vi-VN').format(total) + ' ₫';
            };
            body.querySelectorAll('[data-price]').forEach((input) => input.addEventListener('input', debounce(refreshTotal, 150)));
            body.querySelector('[data-close]')?.addEventListener('click', closeModal);
            refreshTotal();
            body.querySelector('#price-adjust-form')?.addEventListener('submit', async (event) => {
                event.preventDefault();
                const submit = event.currentTarget.querySelector('[type="submit"]');
                submit.disabled = true;
                const payload = { items: [...body.querySelectorAll('[data-price]')].map((input) => ({ id: Number(input.dataset.id), unit_price: Number(input.value) })), reason: body.querySelector('#adj-reason').value };
                try {
                    const update = await fetch('/admin/' + room + '/orders/' + order.id, { method: 'PATCH', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }, body: JSON.stringify(payload) });
                    if (!update.ok) throw new Error(i18n.updateFailed);
                    closeModal();
                    window.location.reload();
                } catch (error) {
                    window.alert(error.message || i18n.updateFailed);
                    submit.disabled = false;
                }
            });
        } catch (error) {
            body.innerHTML = '<div class="py-8 text-center text-error">' + escape(error.message || i18n.loadFailed) + '</div>';
        }
    };

    /**
     * Cancel an order after confirmation.
     *
     * @param {number} orderId Order ID.
     * @returns {Promise<void>}
     */
    window.cancelOrder = async (orderId) => {
        if (!window.confirm(i18n.cancelOrderConfirm)) return;
        const response = await fetch('/admin/' + room + '/orders/' + orderId + '/cancel', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } });
        if (response.ok) window.location.reload();
    };

    /**
     * Delete an order after confirmation; backend emits order.deleted.
     *
     * @param {number} orderId Order ID.
     * @returns {Promise<void>}
     */
    window.deleteOrder = async (orderId) => {
        if (!window.confirm(i18n.deleteConfirm)) return;
        try {
            const response = await fetch('/admin/' + room + '/orders/' + orderId, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } });
            if (!response.ok) throw new Error(i18n.deleteFailed);
            window.location.reload();
        } catch (error) {
            window.alert(error.message || i18n.deleteFailed);
        }
    };
}

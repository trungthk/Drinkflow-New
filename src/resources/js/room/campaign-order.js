import { formatMoney } from '../shared/money';

/**
 * Campaign Order Page menu loading and dynamic order submission
 */
export function initCampaignOrder() {
    const container = document.querySelector('[data-campaign-order-container]');
    if (!container) return;

    const detailUrl = container.dataset.detailUrl;
    const orderUrl = container.dataset.orderUrl;
    const roomSlug = container.dataset.roomSlug;
    const itemsContainer = container.querySelector('#items');
    const form = container.querySelector('#order-form');
    const errorEl = container.querySelector('#error');
    const successEl = container.querySelector('#success');

    let menu = [];
    const esc = (value) => String(value ?? '').replace(/[&<>'"]/g, (c) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        "'": '&#39;',
        '"': '&quot;'
    }[c]));

    async function loadMenu() {
        if (!detailUrl || !itemsContainer) return;
        try {
            const response = await fetch(detailUrl, {
                headers: { Accept: 'application/json' }
            });
            if (!response.ok) {
                itemsContainer.innerHTML = `<p class="text-red-600">${container.dataset.msgLoadError || ''}</p>`;
                return;
            }
            const payload = await response.json();
            menu = payload.data?.items || [];
            if (!menu.length) {
                itemsContainer.innerHTML = `<p class="text-slate-500">${container.dataset.msgEmptyMenu || ''}</p>`;
                return;
            }

            itemsContainer.innerHTML = menu.map((item) =>
                `<label class="flex items-center justify-between gap-3 rounded-2xl bg-white p-4 shadow-sm border border-slate-200/80 hover:border-emerald-300 transition-all cursor-pointer">
                    <span class="min-w-0 flex-1">
                        <span class="block font-semibold text-slate-900 truncate">${esc(item.name)}</span>
                        <span class="text-xs text-slate-500 font-mono font-medium">${formatMoney(item.base_price)}</span>
                    </span>
                    <input data-item="${item.id}" class="w-20 rounded-xl border border-slate-200 px-3 py-2 text-center text-sm font-semibold text-slate-900 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none" type="number" min="0" max="99" value="0" aria-label="${esc((container.dataset.msgQuantityLabel || '').replace(':name', item.name))}">
                </label>`
            ).join('');
        } catch (_) {
            if (itemsContainer) {
                itemsContainer.innerHTML = `<p class="text-red-600">${container.dataset.msgLoadError || ''}</p>`;
            }
        }
    }

    if (form) {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const selected = [...container.querySelectorAll('[data-item]')]
                .map((input) => ({
                    item_id: Number(input.dataset.item),
                    quantity: Number(input.value)
                }))
                .filter((item) => item.quantity > 0);

            if (errorEl) {
                errorEl.classList.add('hidden');
                errorEl.textContent = '';
            }

            if (!selected.length) {
                if (errorEl) {
                    errorEl.textContent = container.dataset.msgSelectRequired || '';
                    errorEl.classList.remove('hidden');
                }
                return;
            }

            const paymentSelect = container.querySelector('#payment');
            const noteInput = container.querySelector('#note');
            const csrfToken = document.querySelector('meta[name=csrf-token]')?.content || '';

            try {
                const response = await fetch(orderUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        items: selected,
                        payment_method: paymentSelect?.value || 'transfer',
                        note: noteInput?.value || ''
                    })
                });

                const payload = await response.json();
                if (!response.ok) {
                    if (errorEl) {
                        errorEl.innerHTML = esc(payload.message || Object.values(payload.errors || {}).flat()[0] || '');
                        if (payload.code === 'active_order_exists' && payload.order_url) {
                            errorEl.innerHTML += ` <a class="font-semibold underline text-[#006948]" href="${esc(payload.order_url)}">${esc((container.dataset.msgViewOrderNumber || '').replace(':id', payload.order_id))}</a>`;
                        }
                        errorEl.classList.remove('hidden');
                    }
                    return;
                }

                form.classList.add('hidden');
                const order = payload.data;
                if (successEl) {
                            const viewUrl = `/rooms/${roomSlug}/orders/${order.id}/view`;
                    successEl.innerHTML = `
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-[28px] text-emerald-600">check_circle</span>
                            <div>
                                <h2 class="text-base sm:text-lg font-bold text-emerald-950">${container.dataset.msgOrderSuccess || ''}</h2>
                                <p class="text-xs sm:text-sm text-emerald-800 mt-0.5">${esc((container.dataset.msgOrderNumber || '').replace(':id', order.id))} · <span class="font-mono font-bold">${formatMoney(order.final_amount)}</span></p>
                            </div>
                        </div>
                        <a class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs transition-colors shadow-sm" href="${esc(viewUrl)}">
                            <span>${container.dataset.msgViewOrder || ''}</span>
                            <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                        </a>
                    `;
                    successEl.classList.remove('hidden');
                }
            } catch (err) {
                if (errorEl) {
                    errorEl.textContent = container.dataset.msgErrorGeneric || '';
                    errorEl.classList.remove('hidden');
                }
            }
        });
    }

    loadMenu();
}

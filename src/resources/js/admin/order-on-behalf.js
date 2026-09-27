import { formatMoney } from '../shared/money';
import { escapeHtml } from '../shared/escape-html';

/**
 * Admin orders page: "order on behalf of a member" modal.
 *
 * The admin picks a member, builds a cart from the campaign menu (item, size, toppings, quantity,
 * note, self-paid flag) and submits it to admin.orders.on-behalf. Prices shown here are only an
 * estimate; the server recalculates everything (price, sponsorship, debt ceiling).
 *
 * @returns {void}
 */
export function initAdminOrderOnBehalf() {
    const modal = document.querySelector('#order-on-behalf-modal');
    const openBtn = document.querySelector('[data-on-behalf-open]');
    if (!modal || !openBtn || modal.dataset.initialized === 'true') return;
    modal.dataset.initialized = 'true';

    const parse = (raw, fallback) => {
        try {
            return JSON.parse(raw || '');
        } catch (error) {
            return fallback;
        }
    };
    const menu = parse(modal.dataset.items, []);
    const i18n = parse(modal.dataset.i18n, {});
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const q = (selector) => modal.querySelector(selector);
    const memberSelect = q('[data-on-behalf-member]');
    const itemSelect = q('[data-on-behalf-item]');
    const sizeWrap = q('[data-on-behalf-size-wrap]');
    const sizeSelect = q('[data-on-behalf-size]');
    const toppingsWrap = q('[data-on-behalf-toppings-wrap]');
    const toppingsBox = q('[data-on-behalf-toppings]');
    const quantityInput = q('[data-on-behalf-quantity]');
    const itemNoteInput = q('[data-on-behalf-item-note]');
    const selfPaidInput = q('[data-on-behalf-self-paid]');
    const cartList = q('[data-on-behalf-cart]');
    const emptyText = q('[data-on-behalf-empty]');
    const totalEl = q('[data-on-behalf-total]');
    const noteInput = q('[data-on-behalf-note]');
    const errorEl = q('[data-on-behalf-error]');
    const submitBtn = q('[data-on-behalf-submit]');
    const submitLabel = q('[data-on-behalf-submit-label]');
    const submitText = submitLabel?.textContent || '';

    /** @type {Array<{item_id: number, size_id: ?number, topping_ids: number[], quantity: number, note: string, is_self_paid: boolean, label: string, detail: string, line: number}>} */
    let cart = [];

    const findItem = (id) => menu.find((item) => item.id === Number(id));

    // Menu options, grouped by category when the menu has categories.
    const groups = new Map();
    menu.forEach((item) => {
        const key = item.category || '';
        if (!groups.has(key)) groups.set(key, []);
        groups.get(key).push(item);
    });
    const optionHtml = (item) => '<option value="' + item.id + '">' + escapeHtml(item.name) + ' — ' + escapeHtml(formatMoney(item.price)) + '</option>';
    itemSelect.innerHTML = '<option value="">' + escapeHtml(i18n.itemPlaceholder || '') + '</option>'
        + [...groups.entries()].map(([category, items]) => (category
            ? '<optgroup label="' + escapeHtml(category) + '">' + items.map(optionHtml).join('') + '</optgroup>'
            : items.map(optionHtml).join(''))).join('');

    const showError = (message) => {
        errorEl.textContent = message || '';
        errorEl.classList.toggle('hidden', !message);
    };

    const renderOptions = () => {
        const item = findItem(itemSelect.value);
        const sizes = item?.sizes || [];
        const toppings = item?.toppings || [];

        sizeWrap.classList.toggle('hidden', sizes.length === 0);
        sizeWrap.classList.toggle('block', sizes.length > 0);
        sizeSelect.innerHTML = sizes.map((size) => '<option value="' + size.id + '">' + escapeHtml(size.name)
            + (size.price_delta ? ' (+' + escapeHtml(formatMoney(size.price_delta)) + ')' : '') + '</option>').join('');

        toppingsWrap.classList.toggle('hidden', toppings.length === 0);
        toppingsBox.innerHTML = toppings.map((topping) => '<label class="inline-flex items-center gap-1.5 rounded-lg border border-outline-variant/70 px-2 py-1 text-on-surface">'
            + '<input type="checkbox" value="' + topping.id + '" class="rounded border-outline-variant">'
            + '<span>' + escapeHtml(topping.name) + (topping.price ? ' (+' + escapeHtml(formatMoney(topping.price)) + ')' : '') + '</span></label>').join('');
    };

    const renderCart = () => {
        cartList.innerHTML = cart.map((line, index) => '<li class="flex items-start justify-between gap-3 rounded-lg border border-outline-variant/60 px-3 py-2">'
            + '<div class="min-w-0"><div class="font-semibold text-on-surface">' + line.quantity + '× ' + escapeHtml(line.label) + '</div>'
            + (line.detail ? '<div class="text-[11px] text-outline">' + escapeHtml(line.detail) + '</div>' : '') + '</div>'
            + '<div class="flex items-center gap-2 shrink-0"><span class="font-mono font-semibold text-on-surface">' + escapeHtml(formatMoney(line.line)) + '</span>'
            + '<button type="button" data-remove="' + index + '" class="p-1 rounded text-outline hover:text-rose-600 hover:bg-rose-50" title="' + escapeHtml(i18n.remove || '') + '" aria-label="' + escapeHtml(i18n.remove || '') + '">'
            + '<span class="material-symbols-outlined text-[18px]">delete</span></button></div></li>').join('');
        emptyText.classList.toggle('hidden', cart.length > 0);
        totalEl.textContent = formatMoney(cart.reduce((sum, line) => sum + line.line, 0));
    };

    const addLine = () => {
        const item = findItem(itemSelect.value);
        if (!item) return;
        const quantity = Math.min(99, Math.max(1, Number.parseInt(quantityInput.value, 10) || 1));
        const size = (item.sizes || []).find((entry) => entry.id === Number(sizeSelect.value)) || null;
        const toppings = [...toppingsBox.querySelectorAll('input:checked')]
            .map((input) => (item.toppings || []).find((entry) => entry.id === Number(input.value)))
            .filter(Boolean);
        const unit = item.price + (size?.price_delta || 0) + toppings.reduce((sum, topping) => sum + topping.price, 0);
        const note = itemNoteInput.value.trim();
        const selfPaid = selfPaidInput.checked;

        cart.push({
            item_id: item.id,
            size_id: size?.id ?? null,
            topping_ids: toppings.map((topping) => topping.id),
            quantity,
            note,
            is_self_paid: selfPaid,
            label: item.name + (size ? ' (' + size.name + ')' : ''),
            detail: [toppings.map((topping) => '+ ' + topping.name).join(', '), note, selfPaid ? i18n.selfPaid : ''].filter(Boolean).join(' · '),
            line: unit * quantity,
        });

        itemSelect.value = '';
        quantityInput.value = '1';
        itemNoteInput.value = '';
        selfPaidInput.checked = false;
        renderOptions();
        renderCart();
        showError('');
    };

    const reset = () => {
        cart = [];
        if (memberSelect) memberSelect.value = '';
        itemSelect.value = '';
        noteInput.value = '';
        quantityInput.value = '1';
        itemNoteInput.value = '';
        selfPaidInput.checked = false;
        renderOptions();
        renderCart();
        showError('');
    };

    const open = () => {
        reset();
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        (memberSelect || itemSelect).focus();
    };

    const close = () => {
        if (submitBtn.dataset.busy === 'true') return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        openBtn.focus();
    };

    const setBusy = (busy) => {
        submitBtn.dataset.busy = busy ? 'true' : 'false';
        submitBtn.disabled = busy;
        submitLabel.textContent = busy ? (i18n.submitting || submitText) : submitText;
    };

    const submit = async () => {
        if (!memberSelect?.value || cart.length === 0) {
            showError(i18n.required);
            return;
        }
        showError('');
        setBusy(true);
        try {
            const response = await fetch(modal.dataset.url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                credentials: 'same-origin',
                body: JSON.stringify({
                    campaign_id: Number(modal.dataset.campaignId),
                    room_user_id: Number(memberSelect.value),
                    note: noteInput.value.trim() || null,
                    items: cart.map(({ item_id, size_id, topping_ids, quantity, note, is_self_paid }) => ({
                        item_id, size_id, topping_ids, quantity, note: note || null, is_self_paid,
                    })),
                }),
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                const firstError = payload.errors ? Object.values(payload.errors).flat()[0] : null;
                throw new Error(firstError || payload.message || i18n.failed);
            }
            setBusy(false);
            close();
            window.notify?.(payload.message, 'success');
            setTimeout(() => window.location.reload(), 900);
        } catch (error) {
            setBusy(false);
            showError(error.message || i18n.failed);
        }
    };

    openBtn.addEventListener('click', open);
    modal.querySelectorAll('[data-on-behalf-close], [data-on-behalf-backdrop]').forEach((el) => el.addEventListener('click', close));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) close();
    });
    itemSelect.addEventListener('change', renderOptions);
    q('[data-on-behalf-add]').addEventListener('click', addLine);
    cartList.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove]');
        if (!button) return;
        cart.splice(Number(button.dataset.remove), 1);
        renderCart();
    });
    submitBtn.addEventListener('click', submit);
    q('[data-on-behalf-form]').addEventListener('submit', (event) => event.preventDefault());

    renderOptions();
    renderCart();
}

import { formatMoney } from '../shared/money';
import { escapeHtml } from '../shared/escape-html';

const MAX_QUANTITY = 99;

/**
 * Admin orders page: order modal with two modes sharing the same menu/cart UI.
 *
 * - "create": order on behalf of a member ([data-on-behalf-open]) → POST admin.orders.on-behalf.
 * - "edit": replace an existing order's items ([data-order-edit="{id}"]) → PUT admin.orders.items.update.
 *
 * The admin builds a cart from the campaign menu (item, size, toppings, quantity, note, self-paid flag).
 * Ordering for someone else is intentionally not offered: every line is billed to the order's member.
 * Prices shown here are only an estimate; the server recalculates everything (price, sponsorship, debt ceiling).
 *
 * @returns {void}
 */
export function initAdminOrderOnBehalf() {
    const modal = document.querySelector('#order-on-behalf-modal');
    const openBtn = document.querySelector('[data-on-behalf-open]');
    const editButtons = document.querySelectorAll('[data-order-edit]');
    if (!modal || (!openBtn && editButtons.length === 0) || modal.dataset.initialized === 'true') return;
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
    const titleEl = q('#order-on-behalf-title');
    const subtitleEl = q('[data-on-behalf-subtitle]');
    const iconEl = q('[data-on-behalf-icon]');
    const memberWrap = q('[data-on-behalf-member-wrap]');
    const memberFixed = q('[data-on-behalf-member-fixed]');
    const memberName = q('[data-on-behalf-member-name]');
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

    // Texts of the "create" mode, restored whenever the modal is opened in that mode.
    const createTexts = {
        title: titleEl?.textContent || '',
        subtitle: subtitleEl?.textContent || '',
        icon: iconEl?.textContent || '',
        submit: submitLabel?.textContent || '',
    };

    /** @type {{type: 'create'|'edit', orderId: ?number, trigger: ?HTMLElement}} */
    let mode = { type: 'create', orderId: null, trigger: openBtn };

    /**
     * @type {Array<{item_id: ?number, size_id: ?number, topping_ids: number[], quantity: number, note: string,
     *   is_self_paid: boolean, label: string, detail: string, unit: number, unavailable: boolean}>}
     */
    let cart = [];

    const findItem = (id) => menu.find((item) => item.id === Number(id));
    const clampQuantity = (value) => Math.min(MAX_QUANTITY, Math.max(1, Number.parseInt(value, 10) || 1));

    // Menu options, grouped by category when the menu has categories.
    const groups = new Map();
    menu.forEach((item) => {
        const key = item.category || '';
        if (!groups.has(key)) groups.set(key, []);
        groups.get(key).push(item);
    });
    // data-search lets the searchable combobox (ui-enhancements) also match the category.
    const optionHtml = (item) => '<option value="' + item.id + '" data-search="' + escapeHtml(item.category || '') + '">'
        + escapeHtml(item.name) + ' — ' + escapeHtml(formatMoney(item.price)) + '</option>';
    itemSelect.innerHTML = '<option value="">' + escapeHtml(i18n.itemPlaceholder || '') + '</option>'
        + [...groups.entries()].map(([category, items]) => (category
            ? '<optgroup label="' + escapeHtml(category) + '">' + items.map(optionHtml).join('') + '</optgroup>'
            : items.map(optionHtml).join(''))).join('');

    /**
     * Set a select's value and notify listeners, so the searchable combobox label follows programmatic changes.
     *
     * @param {?HTMLSelectElement} select Select to update.
     * @param {string} value New value.
     * @returns {void}
     */
    const setSelect = (select, value) => {
        if (!select) return;
        select.value = value;
        select.dispatchEvent(new Event('change', { bubbles: true }));
    };

    /**
     * Focus a field, preferring the visible combobox button that replaces a searchable select.
     *
     * @param {HTMLElement} field Field to focus.
     * @returns {void}
     */
    const focusField = (field) => {
        const combobox = field?.nextElementSibling?.classList.contains('searchable-select-container')
            ? field.nextElementSibling.querySelector('button')
            : null;
        (combobox || field)?.focus();
    };

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

    const stepButton = (index, delta, icon, label, disabled) => '<button type="button" data-step="' + delta + '" data-index="' + index + '"'
        + (disabled ? ' disabled' : '')
        + ' class="w-6 h-6 inline-flex items-center justify-center rounded-md border border-outline-variant text-on-surface hover:bg-surface-container disabled:opacity-40 disabled:cursor-not-allowed"'
        + ' title="' + escapeHtml(label || '') + '" aria-label="' + escapeHtml(label || '') + '">'
        + '<span class="material-symbols-outlined text-[14px]">' + icon + '</span></button>';

    const renderCart = () => {
        cartList.innerHTML = cart.map((line, index) => '<li class="flex items-start justify-between gap-3 rounded-lg border px-3 py-2 '
            + (line.unavailable ? 'border-rose-300 bg-rose-50/60 dark:border-rose-800 dark:bg-rose-950/30' : 'border-outline-variant/60') + '">'
            + '<div class="min-w-0"><div class="font-semibold text-on-surface">' + escapeHtml(line.label) + '</div>'
            + (line.detail ? '<div class="text-[11px] text-outline">' + escapeHtml(line.detail) + '</div>' : '')
            + (line.unavailable ? '<div class="text-[11px] font-semibold text-rose-600 dark:text-rose-300">' + escapeHtml(i18n.unavailable || '') + '</div>' : '')
            + '</div>'
            + '<div class="flex items-center gap-2 shrink-0">'
            + '<div class="inline-flex items-center gap-1">'
            + stepButton(index, -1, 'remove', i18n.decrease, line.unavailable || line.quantity <= 1)
            + '<span class="w-6 text-center font-mono font-semibold text-on-surface" aria-live="polite">' + line.quantity + '</span>'
            + stepButton(index, 1, 'add', i18n.increase, line.unavailable || line.quantity >= MAX_QUANTITY)
            + '</div>'
            + '<span class="w-20 text-right font-mono font-semibold text-on-surface">' + (line.unavailable ? '—' : escapeHtml(formatMoney(line.unit * line.quantity))) + '</span>'
            + '<button type="button" data-remove="' + index + '" class="p-1 rounded text-outline hover:text-rose-600 hover:bg-rose-50" title="' + escapeHtml(i18n.remove || '') + '" aria-label="' + escapeHtml(i18n.remove || '') + '">'
            + '<span class="material-symbols-outlined text-[18px]">delete</span></button></div></li>').join('');
        emptyText.classList.toggle('hidden', cart.length > 0);
        totalEl.textContent = formatMoney(cart.reduce((sum, line) => sum + (line.unavailable ? 0 : line.unit * line.quantity), 0));
    };

    /**
     * Build a cart line from a menu item and the chosen options.
     *
     * @param {object} item Menu item.
     * @param {?object} size Chosen size.
     * @param {object[]} toppings Chosen toppings.
     * @param {number} quantity Quantity.
     * @param {string} note Line note.
     * @param {boolean} selfPaid Whether the member pays this line without sponsorship.
     * @returns {object} Cart line.
     */
    const makeLine = (item, size, toppings, quantity, note, selfPaid) => ({
        item_id: item.id,
        size_id: size?.id ?? null,
        topping_ids: toppings.map((topping) => topping.id),
        quantity,
        note,
        is_self_paid: selfPaid,
        label: item.name + (size ? ' (' + size.name + ')' : ''),
        detail: [toppings.map((topping) => '+ ' + topping.name).join(', '), note, selfPaid ? i18n.selfPaid : ''].filter(Boolean).join(' · '),
        unit: item.price + (size?.price_delta || 0) + toppings.reduce((sum, topping) => sum + topping.price, 0),
        unavailable: false,
    });

    /**
     * Map a saved order item back onto the current menu; lines whose item, size or toppings are no longer
     * on sale are kept (flagged) so the admin sees and removes them explicitly.
     *
     * @param {object} saved Order item from admin.orders.show.
     * @returns {object} Cart line.
     */
    const lineFromOrderItem = (saved) => {
        const quantity = clampQuantity(saved.quantity);
        const note = saved.note || '';
        const selfPaid = Boolean(saved.is_self_paid);
        const item = findItem(saved.campaign_item_id);
        const size = item && saved.size_name ? (item.sizes || []).find((entry) => entry.name === saved.size_name) : null;
        const savedToppings = saved.toppings || [];
        const toppings = item ? savedToppings.map((topping) => (item.toppings || []).find((entry) => entry.id === Number(topping.campaign_item_topping_id))) : [];

        if (item && (!saved.size_name || size) && toppings.every(Boolean)) {
            return makeLine(item, size, toppings, quantity, note, selfPaid);
        }

        return {
            item_id: null,
            size_id: null,
            topping_ids: [],
            quantity,
            note,
            is_self_paid: selfPaid,
            label: (saved.item_name || '') + (saved.size_name ? ' (' + saved.size_name + ')' : ''),
            detail: [savedToppings.map((topping) => '+ ' + topping.topping_name).join(', '), note, selfPaid ? i18n.selfPaid : ''].filter(Boolean).join(' · '),
            unit: 0,
            unavailable: true,
        };
    };

    const addLine = () => {
        const item = findItem(itemSelect.value);
        if (!item) return;
        const size = (item.sizes || []).find((entry) => entry.id === Number(sizeSelect.value)) || null;
        const toppings = [...toppingsBox.querySelectorAll('input:checked')]
            .map((input) => (item.toppings || []).find((entry) => entry.id === Number(input.value)))
            .filter(Boolean);

        cart.push(makeLine(item, size, toppings, clampQuantity(quantityInput.value), itemNoteInput.value.trim(), selfPaidInput.checked));

        setSelect(itemSelect, '');
        quantityInput.value = '1';
        itemNoteInput.value = '';
        selfPaidInput.checked = false;
        renderCart();
        showError('');
    };

    const reset = () => {
        cart = [];
        setSelect(memberSelect, '');
        setSelect(itemSelect, '');
        noteInput.value = '';
        quantityInput.value = '1';
        itemNoteInput.value = '';
        selfPaidInput.checked = false;
        renderCart();
        showError('');
    };

    const isEdit = () => mode.type === 'edit';

    const submitText = () => (isEdit() ? i18n.editSubmit : createTexts.submit);

    /**
     * Switch the modal header, member field and submit button to the given mode.
     *
     * @param {'create'|'edit'} type Modal mode.
     * @param {string} [code] Order code shown in the edit title.
     * @param {string} [member] Member name shown (read-only) in edit mode.
     * @returns {void}
     */
    const applyMode = (type, code = '', member = '') => {
        const edit = type === 'edit';
        if (titleEl) titleEl.textContent = edit ? (i18n.editTitle || '').replace(':code', code) : createTexts.title;
        if (subtitleEl) subtitleEl.textContent = edit ? (subtitleEl.dataset.editText || '') : createTexts.subtitle;
        if (iconEl) iconEl.textContent = edit ? (iconEl.dataset.editIcon || createTexts.icon) : createTexts.icon;
        memberWrap?.classList.toggle('hidden', edit);
        memberFixed?.classList.toggle('hidden', !edit);
        if (memberName) memberName.textContent = member;
        submitLabel.textContent = submitText();
        // Without any member left to order for, only the edit mode can be submitted.
        submitBtn.disabled = !edit && !memberSelect;
    };

    const show = (trigger) => {
        mode.trigger = trigger;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    const openCreate = () => {
        mode = { type: 'create', orderId: null, trigger: openBtn };
        reset();
        applyMode('create');
        show(openBtn);
        focusField(memberSelect || itemSelect);
    };

    /**
     * Put the submit button in (or out of) its loading state.
     *
     * @param {boolean} busy Whether a request is running.
     * @param {string} [label] Loading label.
     * @param {boolean} [blocking] Whether the modal must stay open (saving) rather than just loading the order.
     * @returns {void}
     */
    const setBusy = (busy, label, blocking = true) => {
        submitBtn.dataset.busy = busy && blocking ? 'true' : 'false';
        submitBtn.disabled = busy || (!isEdit() && !memberSelect);
        submitLabel.textContent = busy ? (label || submitText()) : submitText();
    };

    const openEdit = async (button) => {
        const orderId = Number(button.dataset.orderEdit);
        mode = { type: 'edit', orderId, trigger: button };
        reset();
        applyMode('edit', button.dataset.orderCode || '', button.dataset.orderMember || '');
        show(button);
        setBusy(true, i18n.loading, false);
        try {
            const response = await fetch(modal.dataset.showUrl.replace('__ORDER__', String(orderId)), {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (!response.ok) throw new Error(i18n.loadFailed);
            const order = (await response.json()).data || {};
            // Ignore a late response when the admin already switched to another order.
            if (mode.orderId !== orderId) return;
            cart = (order.items || []).map(lineFromOrderItem);
            noteInput.value = order.note || '';
            renderCart();
            focusField(itemSelect);
        } catch (error) {
            showError(error.message || i18n.loadFailed);
        } finally {
            if (mode.orderId === orderId) setBusy(false);
        }
    };

    const close = () => {
        if (submitBtn.dataset.busy === 'true') return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        mode.trigger?.focus();
        mode = { type: 'create', orderId: null, trigger: openBtn };
    };

    const request = () => {
        const items = cart.map(({ item_id, size_id, topping_ids, quantity, note, is_self_paid }) => ({
            item_id, size_id, topping_ids, quantity, note: note || null, is_self_paid,
        }));
        const note = noteInput.value.trim() || null;
        if (isEdit()) {
            return {
                url: modal.dataset.editUrl.replace('__ORDER__', String(mode.orderId)),
                method: 'PUT',
                body: { items, note },
            };
        }

        return {
            url: modal.dataset.url,
            method: 'POST',
            body: {
                campaign_id: Number(modal.dataset.campaignId),
                room_user_id: Number(memberSelect.value),
                note,
                items,
            },
        };
    };

    const submit = async () => {
        if (submitBtn.disabled) return;
        if (isEdit() ? cart.length === 0 : (!memberSelect?.value || cart.length === 0)) {
            showError(isEdit() ? i18n.editRequired : i18n.required);
            return;
        }
        if (cart.some((line) => line.unavailable)) {
            showError(i18n.hasUnavailable);
            return;
        }
        showError('');
        setBusy(true, isEdit() ? i18n.editSubmitting : i18n.submitting);
        const { url, method, body } = request();
        try {
            const response = await fetch(url, {
                method,
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                credentials: 'same-origin',
                body: JSON.stringify(body),
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                const firstError = payload.errors ? Object.values(payload.errors).flat()[0] : null;
                throw new Error(firstError || payload.message || (isEdit() ? i18n.editFailed : i18n.failed));
            }
            // Keep the button in its loading state until the page reloads.
            window.notify?.(payload.message, 'success');
            setTimeout(() => window.location.reload(), 900);
        } catch (error) {
            setBusy(false);
            showError(error.message || (isEdit() ? i18n.editFailed : i18n.failed));
        }
    };

    openBtn?.addEventListener('click', openCreate);
    editButtons.forEach((button) => button.addEventListener('click', () => {
        button.closest('details')?.removeAttribute('open');
        openEdit(button);
    }));
    modal.querySelectorAll('[data-on-behalf-close], [data-on-behalf-backdrop]').forEach((el) => el.addEventListener('click', close));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) close();
    });
    itemSelect.addEventListener('change', renderOptions);
    q('[data-on-behalf-add]').addEventListener('click', addLine);
    cartList.addEventListener('click', (event) => {
        const step = event.target.closest('[data-step]');
        if (step) {
            const line = cart[Number(step.dataset.index)];
            if (line) line.quantity = clampQuantity(line.quantity + Number(step.dataset.step));
            renderCart();
            return;
        }
        const button = event.target.closest('[data-remove]');
        if (!button) return;
        cart.splice(Number(button.dataset.remove), 1);
        renderCart();
    });
    submitBtn.addEventListener('click', submit);
    q('[data-on-behalf-form]').addEventListener('submit', (event) => event.preventDefault());

    // The item options were built after the combobox was initialised: sync its label (also renders size/toppings).
    setSelect(itemSelect, '');
    renderCart();
}

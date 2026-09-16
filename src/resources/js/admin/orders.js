import { debounce } from './ui-enhancements';

/**
 * Initialize order search, filtering, deletion, and price adjustment.
 *
 * @returns {void}
 */
export function initAdminOrders() {
    const page = document.querySelector('#admin-orders-page');
    if (!page || page.dataset.ordersInitialized === 'true') return;
    page.dataset.ordersInitialized = 'true';

    const i18n = JSON.parse(page.dataset.i18n || '{}');
    const search = page.querySelector('#order-search');
    const filterForm = page.querySelector('#orders-filter-form');
    const modal = page.querySelector('#price-adjust-modal');
    const body = page.querySelector('#modal-body');
    const detailModal = page.querySelector('#order-detail-modal');
    const detailBody = page.querySelector('#order-detail-modal-body');
    const detailTitle = page.querySelector('#detail-modal-title');
    const detailBadge = page.querySelector('#detail-modal-status-badge');
    const detailQuickActions = page.querySelector('#detail-modal-quick-actions');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const room = document.querySelector('meta[name="room-slug"]')?.content || window.__DF_ROOM_SLUG__ || '';
    const escape = (value) => String(value ?? '').replace(/[&<>'"]/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[char]));

    const formatDate = (dateStr) => {
        if (!dateStr) return '—';
        try {
            const d = new Date(dateStr);
            return d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }) + ' ' + d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' });
        } catch {
            return String(dateStr);
        }
    };
    const formatVND = (num) => new Intl.NumberFormat(document.documentElement.lang || 'vi-VN').format(num || 0) + ' ₫';

    search?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            filterForm?.requestSubmit();
        }
    });
    search?.addEventListener('admin:search-cleared', () => filterForm?.requestSubmit());

    /**
     * Close the order detail modal.
     *
     * @returns {void}
     */
    const statusClassMap = {
        completed: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800',
        confirmed: 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800',
        submitted: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800',
        delivering: 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-300 dark:border-indigo-800',
        cancelled: 'bg-rose-50 text-rose-700 border-rose-200 line-through dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800',
    };

    /**
     * Close the order detail modal.
     *
     * @returns {void}
     */
    const closeDetailModal = () => {
        detailModal?.classList.add('hidden');
        detailModal?.classList.remove('flex');
    };
    page.querySelector('#order-detail-backdrop')?.addEventListener('click', closeDetailModal);
    window.closeOrderDetailModal = closeDetailModal;

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
     * Display full order details in a structured modal.
     *
     * @param {number} orderId Order ID.
     * @returns {Promise<void>}
     */
    window.openOrderDetailModal = async (orderId) => {
        if (!detailModal || !detailBody) return;
        detailModal.classList.remove('hidden');
        detailModal.classList.add('flex');
        detailBody.innerHTML = '<div class="py-12 text-center text-outline"><span class="material-symbols-outlined animate-spin text-[28px]">progress_activity</span></div>';
        if (detailQuickActions) detailQuickActions.innerHTML = '';
        if (detailBadge) {
            detailBadge.textContent = '';
            detailBadge.className = 'px-2.5 py-0.5 rounded-md text-[11px] font-semibold border hidden';
        }

        try {
            const response = await fetch('/admin/' + room + '/orders/' + orderId, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error(i18n.loadFailed);
            const order = (await response.json()).data;

            if (detailTitle) {
                detailTitle.textContent = (i18n.orderDetailModalTitle || 'Chi tiết đơn hàng :id').replace(':id', order.code || ('#ORD-' + order.id));
            }

            const statusKey = order.status?.value || order.status || '';
            const statusLabel = {
                submitted: i18n.statusSubmitted || 'Chờ xác nhận',
                confirmed: i18n.statusConfirmed || 'Đã xác nhận',
                completed: i18n.statusCompleted || 'Món đã được giao đến',
                delivering: i18n.statusDelivering || 'Đang giao',
                cancelled: i18n.statusCancelled || 'Đã huỷ',
            }[statusKey] || statusKey;

            if (detailBadge) {
                detailBadge.textContent = statusLabel;
                detailBadge.className = 'px-2.5 py-0.5 rounded-md text-[11px] font-semibold border ' + (statusClassMap[statusKey] || 'bg-surface-container text-secondary border-outline-variant');
                detailBadge.classList.remove('hidden');
            }

            const member = order.room_user?.global_user?.name || order.room_user?.display_name || ('Member #' + order.room_user_id);
            const userCode = order.room_user?.user_code || '';
            const email = order.room_user?.global_user?.email || '';
            const avatarUrl = order.room_user?.global_user?.avatar_url || '';
            const restaurant = order.campaign?.restaurant || '—';
            const campaignName = order.campaign?.name || (i18n.notAvailable || '—');

            const itemsHtml = (order.items || []).map((item) => {
                const toppingsHtml = (item.toppings && item.toppings.length > 0)
                    ? '<div class="flex flex-wrap gap-1 mt-1">' + item.toppings.map((t) => '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-surface-container text-secondary border border-outline-variant/60">+ ' + escape(t.name || t.topping_name) + ' (' + formatVND(t.price || t.unit_price) + ')</span>').join('') + '</div>'
                    : '<div class="text-[10px] text-outline mt-0.5 italic">' + escape(i18n.noToppings || 'Không kèm topping') + '</div>';

                const itemNoteHtml = item.note ? '<div class="text-[11px] text-amber-700 dark:text-amber-400 italic mt-1 flex items-center gap-1"><span class="material-symbols-outlined text-[12px]">edit_note</span> ' + escape(item.note) + '</div>' : '';

                return '<tr class="border-b border-outline-variant/40 hover:bg-surface-container-low/50 transition-colors">'
                    + '<td class="py-2.5 px-3 text-on-surface">'
                    +   '<div class="font-bold text-xs">' + escape(item.item_name) + (item.size ? ' <span class="text-primary font-normal">(' + escape(item.size) + ')</span>' : '') + '</div>'
                    +   toppingsHtml
                    +   itemNoteHtml
                    + '</td>'
                    + '<td class="py-2.5 px-3 text-center font-mono font-bold text-on-surface">' + escape(item.quantity) + '</td>'
                    + '<td class="py-2.5 px-3 text-right font-mono text-outline">' + formatVND(item.unit_price) + '</td>'
                    + '<td class="py-2.5 px-3 text-right font-mono font-bold text-primary">' + formatVND(item.line_subtotal || item.total_price || (item.unit_price * item.quantity)) + '</td>'
                    + '</tr>';
            }).join('');

            const subtotal = Number(order.subtotal || order.subtotal_amount || 0);
            const deliveryAmount = Number(order.delivery_amount || 0);
            const discountAmount = Number(order.discount_amount || 0);
            const sponsorAmount = Number(order.sponsor_amount || 0);
            const finalAmount = Number(order.final_amount || 0);

            let breakdownHtml = '<div class="space-y-1.5">';
            breakdownHtml += '<div class="flex justify-between text-secondary"><span>' + escape(i18n.subtotal || 'Tổng tiền món') + ':</span><span class="font-mono font-semibold text-on-surface">' + formatVND(subtotal) + '</span></div>';
            if (deliveryAmount > 0) {
                breakdownHtml += '<div class="flex justify-between text-secondary"><span>' + escape(i18n.deliveryFeeOrder || 'Phí giao hàng') + ':</span><span class="font-mono font-semibold text-on-surface">+' + formatVND(deliveryAmount) + '</span></div>';
            }
            if (discountAmount > 0) {
                breakdownHtml += '<div class="flex justify-between text-emerald-600"><span>' + escape(i18n.discountVoucher || 'Giảm giá / Voucher') + ':</span><span class="font-mono font-semibold">-' + formatVND(discountAmount) + '</span></div>';
            }
            if (sponsorAmount > 0) {
                breakdownHtml += '<div class="flex justify-between text-emerald-600"><span>' + escape(i18n.roomSubsidy || 'Trợ giá phòng') + ':</span><span class="font-mono font-semibold">-' + formatVND(sponsorAmount) + '</span></div>';
            }
            breakdownHtml += '<div class="pt-2 mt-2 border-t border-outline-variant flex justify-between items-baseline"><span class="font-bold text-sm text-on-surface">' + escape(i18n.finalPayable || 'Tổng thanh toán') + ':</span><span class="font-mono font-bold text-base text-primary">' + formatVND(finalAmount) + '</span></div>';
            breakdownHtml += '</div>';

            const paymentMethodBadge = order.payment_method ? '<span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase bg-surface-container text-secondary border border-outline-variant">' + escape(order.payment_method) + '</span>' : '<span class="text-outline italic">—</span>';
            const paymentStatusKey = order.payment_status?.value || order.payment_status || 'pending';
            const paymentStatusBadge = paymentStatusKey === 'paid'
                ? '<span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800 border border-emerald-300 dark:bg-emerald-950/50 dark:text-emerald-300">Đã thanh toán</span>'
                : '<span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-800 border border-amber-300 dark:bg-amber-950/50 dark:text-amber-300">Chưa thanh toán</span>';

            detailBody.innerHTML = `
                <!-- Member & Campaign Info Card -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="bg-surface-container-low rounded-xl p-3.5 border border-outline-variant/60">
                        <div class="text-[10px] font-mono uppercase text-outline tracking-wider mb-2 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">person</span>
                            <span>${escape(i18n.customerInfo || 'Thông tin người đặt')}</span>
                        </div>
                        <div class="flex items-start gap-2.5">
                            ${avatarUrl ? `<img src="${escape(avatarUrl)}" alt="${escape(member)}" class="w-9 h-9 rounded-full object-cover shrink-0 border border-outline-variant" loading="lazy" onerror="this.src='/images/default-avatar.svg'">` : `<div class="w-9 h-9 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xs shrink-0">${escape(member.charAt(0))}</div>`}
                            <div class="min-w-0 flex-1">
                                <div class="font-bold text-on-surface text-sm truncate">${escape(member)}</div>
                                <div class="text-secondary text-[11px] font-mono mt-0.5">${userCode ? escape(userCode) : ''} ${email ? '• ' + escape(email) : ''}</div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-surface-container-low rounded-xl p-3.5 border border-outline-variant/60">
                        <div class="text-[10px] font-mono uppercase text-outline tracking-wider mb-2 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">storefront</span>
                            <span>${escape(i18n.campaignStoreInfo || 'Chiến dịch & Quán')}</span>
                        </div>
                        <div class="font-semibold text-on-surface text-xs truncate">${escape(campaignName)}</div>
                        <div class="text-secondary text-[11px] flex items-center gap-1 mt-1">
                            <span class="material-symbols-outlined text-[13px] text-outline">restaurant</span>
                            <span class="truncate">${escape(restaurant)}</span>
                        </div>
                        <div class="text-[10px] font-mono text-outline mt-1">
                            ${escape(i18n.orderCreatedTime || 'Tạo lúc')}: ${formatDate(order.created_at)}
                        </div>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="border border-outline-variant rounded-xl overflow-hidden">
                    <div class="bg-surface-container px-3.5 py-2 border-b border-outline-variant font-semibold text-on-surface flex items-center justify-between">
                        <span class="flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px] text-primary">lunch_dining</span>
                            <span>${escape(i18n.orderedItems || 'Danh sách món')} (${(order.items || []).length})</span>
                        </span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-surface-container-lowest text-outline font-mono uppercase text-[10px] border-b border-outline-variant">
                                <tr>
                                    <th class="py-2 px-3">${escape(i18n.itemNameCol || 'Món & Tuỳ chọn')}</th>
                                    <th class="py-2 px-3 text-center">${escape(i18n.itemQtyCol || 'SL')}</th>
                                    <th class="py-2 px-3 text-right">${escape(i18n.itemUnitPriceCol || 'Đơn giá')}</th>
                                    <th class="py-2 px-3 text-right">${escape(i18n.itemTotalCol || 'Thành tiền')}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/40 bg-surface-container-lowest">
                                ${itemsHtml}
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Financial Summary & Payment Details -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="bg-surface-container-low rounded-xl p-3.5 border border-outline-variant/60">
                        <div class="text-[10px] font-mono uppercase text-outline tracking-wider mb-2 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">payments</span>
                            <span>${escape(i18n.orderFinancialSummary || 'Tổng kết tài chính')}</span>
                        </div>
                        ${breakdownHtml}
                    </div>

                    <div class="bg-surface-container-low rounded-xl p-3.5 border border-outline-variant/60 space-y-2.5">
                        <div class="text-[10px] font-mono uppercase text-outline tracking-wider flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">history</span>
                            <span>${escape(i18n.orderHistoryTimestamps || 'Lịch sử & Thanh toán')}</span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-secondary">Phương thức:</span>
                            <div>${paymentMethodBadge}</div>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-secondary">Thanh toán:</span>
                            <div>${paymentStatusBadge}</div>
                        </div>
                        ${order.completed_at ? `
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-secondary">${escape(i18n.orderCompletedTime || 'Hoàn thành lúc')}:</span>
                                <span class="font-mono text-outline">${formatDate(order.completed_at)}</span>
                            </div>
                        ` : ''}
                        ${order.cancelled_at ? `
                            <div class="flex items-center justify-between text-xs text-rose-600">
                                <span>${escape(i18n.orderCancelledTime || 'Đã hủy lúc')}:</span>
                                <span class="font-mono font-bold">${formatDate(order.cancelled_at)}</span>
                            </div>
                        ` : ''}
                        ${order.note ? `
                            <div class="pt-2 border-t border-outline-variant/60">
                                <div class="text-[10px] text-outline font-semibold mb-0.5">📝 ${escape(i18n.orderNoteLabel || 'Ghi chú đơn')}:</div>
                                <div class="text-xs text-amber-800 dark:text-amber-300 italic bg-amber-50 dark:bg-amber-950/30 p-2 rounded-lg border border-amber-200 dark:border-amber-800">${escape(order.note)}</div>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;

            if (detailQuickActions && statusKey !== 'cancelled') {
                detailQuickActions.innerHTML = `
                    <button type="button" onclick="closeOrderDetailModal(); openPriceAdjustmentModal(${order.id})" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-primary/40 text-primary hover:bg-primary/10 text-xs font-semibold transition-colors">
                        <span class="material-symbols-outlined text-[16px]">tune</span>
                        <span>${escape(i18n.adjustPriceBtn || 'Điều chỉnh giá')}</span>
                    </button>
                `;
            }
        } catch (error) {
            detailBody.innerHTML = '<div class="py-12 text-center text-error font-medium">' + escape(error.message || i18n.loadFailed) + '</div>';
        }
    };

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
            if (title) title.textContent = (order.code || ('#ORD-' + order.id)) + ' - ' + (order.room_user?.display_name || '');
            const items = (order.items || []).map((item) => '<div class="grid grid-cols-[1fr_7rem] gap-3 items-center p-2.5 rounded border border-outline-variant/60"><div class="min-w-0"><div class="font-bold text-on-surface truncate">' + escape(item.quantity) + '× ' + escape(item.item_name) + (item.size ? ' (' + escape(item.size) + ')' : '') + '</div><div class="text-[11px] text-outline truncate">' + escape(item.toppings?.map((topping) => topping.name).join(', ') || '—') + '</div></div><input type="number" min="0" data-price data-id="' + escape(item.id) + '" data-quantity="' + escape(item.quantity) + '" value="' + escape(item.unit_price) + '" class="w-full h-9 px-2 bg-surface border border-outline-variant rounded font-mono font-bold text-right text-primary"></div>').join('');
            body.innerHTML = '<form id="price-adjust-form" class="space-y-4"><div class="space-y-2"><h4 class="font-semibold text-on-surface">' + escape(i18n.orderedItems) + '</h4>' + items + '</div><div class="flex justify-between rounded-lg bg-surface-container-low p-3 text-sm"><span class="font-semibold">' + escape(i18n.subtotal) + '</span><span id="adjusted-subtotal" class="font-mono font-bold text-primary"></span></div><div><label class="block font-semibold text-on-surface mb-1" for="adj-reason">' + escape(i18n.adjustmentReason) + '</label><textarea id="adj-reason" rows="2" required class="w-full p-2.5 bg-surface border border-outline-variant rounded text-xs"></textarea></div><div class="pt-3 border-t border-outline-variant flex justify-end gap-2"><button type="button" data-close class="px-4 py-2 bg-surface-container rounded font-semibold text-xs">' + escape(i18n.cancel) + '</button><button type="submit" class="px-4 py-2 bg-primary text-on-primary rounded font-semibold text-xs inline-flex items-center gap-1.5"><span class="material-symbols-outlined text-[16px]">save</span><span>' + escape(i18n.save) + '</span></button></div></form>';
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
                const cancelBtn = event.currentTarget.querySelector('[data-close]');
                const reason = body.querySelector('#adj-reason')?.value || '';
                submit.disabled = true;
                if (cancelBtn) cancelBtn.disabled = true;
                const origHtml = submit.innerHTML;
                submit.innerHTML = '<span class="material-symbols-outlined animate-spin text-[16px]">progress_activity</span> <span>' + escape(i18n.savingPriceAdjustment || 'Đang lưu...') + '</span>';

                const payload = { items: [...body.querySelectorAll('[data-price]')].map((input) => ({ id: Number(input.dataset.id), unit_price: Number(input.value) })), reason: reason };
                try {
                    const update = await fetch('/admin/' + room + '/orders/' + order.id, { method: 'PATCH', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }, body: JSON.stringify(payload) });
                    const updateData = await update.json();
                    if (!update.ok) throw new Error(updateData.message || i18n.updateFailed);
                    closeModal();

                    const successTemplate = i18n.priceAdjustSuccess || 'Đã điều chỉnh giá đơn hàng #:id thành công. Lý do: :reason';
                    const successMsg = successTemplate.replace(':id', order.id).replace(':reason', reason);
                    showNotice(successMsg, false);

                    setTimeout(() => window.location.reload(), 1200);
                } catch (error) {
                    window.alert(error.message || i18n.updateFailed);
                    submit.disabled = false;
                    if (cancelBtn) cancelBtn.disabled = false;
                    submit.innerHTML = origHtml;
                }
            });
        } catch (error) {
            body.innerHTML = '<div class="py-8 text-center text-error">' + escape(error.message || i18n.loadFailed) + '</div>';
        }
    };

    const allStatusClasses = [
        'bg-emerald-50', 'text-emerald-700', 'border-emerald-200', 'dark:bg-emerald-950/40', 'dark:text-emerald-300', 'dark:border-emerald-800',
        'bg-blue-50', 'text-blue-700', 'border-blue-200', 'dark:bg-blue-950/40', 'dark:text-blue-300', 'dark:border-blue-800',
        'bg-amber-50', 'text-amber-700', 'border-amber-200', 'dark:bg-amber-950/40', 'dark:text-amber-300', 'dark:border-amber-800',
        'bg-indigo-50', 'text-indigo-700', 'border-indigo-200', 'dark:bg-indigo-950/40', 'dark:text-indigo-300', 'dark:border-indigo-800',
        'bg-rose-50', 'text-rose-700', 'border-rose-200', 'line-through', 'dark:bg-rose-950/40', 'dark:text-rose-300', 'dark:border-rose-800',
        'bg-surface-container', 'text-secondary', 'border-outline-variant',
    ];

    /**
     * Update select element styling classes according to selected status.
     *
     * @param {HTMLSelectElement} select Target select element.
     * @param {string} status Target status value.
     * @returns {void}
     */
    const updateSelectClass = (select, status) => {
        select.classList.remove(...allStatusClasses);
        const classes = (statusClassMap[status] || 'bg-surface-container text-secondary border-outline-variant').split(' ');
        select.classList.add(...classes);
    };

    /**
     * Show notification banner on the page.
     *
     * @param {string} message Message text.
     * @param {boolean} isError Whether this is an error notice.
     * @returns {void}
     */
    const showNotice = (message, isError = false) => {
        const notice = page.querySelector('#notice');
        if (!notice) return;
        notice.textContent = message;
        notice.className = isError
            ? 'mb-4 rounded-xl px-4 py-3 text-xs font-medium bg-rose-50 text-rose-800 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800'
            : 'mb-4 rounded-xl px-4 py-3 text-xs font-medium bg-emerald-50 text-emerald-800 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800';
        notice.classList.remove('hidden');
        setTimeout(() => {
            notice.classList.add('hidden');
        }, 4000);
    };

    /**
     * Handle inline order status change.
     */
    page.querySelectorAll('.order-status-select').forEach((select) => {
        select.addEventListener('change', async () => {
            const orderId = select.dataset.orderId;
            const previousStatus = select.dataset.currentStatus;
            const newStatus = select.value;

            if (newStatus === previousStatus) return;

            select.disabled = true;
            select.classList.add('opacity-60');

            try {
                const response = await fetch('/admin/' + room + '/orders/' + orderId + '/status', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({ status: newStatus }),
                });

                const data = await response.json();
                if (!response.ok) {
                    const errorMsg = data.message || data.errors?.status?.[0] || i18n.statusChangeFailed;
                    throw new Error(errorMsg);
                }

                select.dataset.currentStatus = newStatus;
                updateSelectClass(select, newStatus);

                const statusLabels = {
                    submitted: i18n.statusSubmitted || 'Chờ xác nhận',
                    confirmed: i18n.statusConfirmed || 'Đã xác nhận',
                    completed: i18n.statusCompleted || 'Món đã được giao đến',
                    delivering: i18n.statusDelivering || 'Đang giao',
                    cancelled: i18n.statusCancelled || 'Đã huỷ',
                };
                const label = statusLabels[newStatus] || newStatus;
                const successTemplate = i18n.statusChangeSuccess || 'Đã cập nhật trạng thái đơn hàng #:id thành :status.';
                const successMsg = successTemplate.replace(':id', orderId).replace(':status', label);

                showNotice(successMsg, false);
            } catch (error) {
                select.value = previousStatus;
                showNotice(error.message || i18n.statusChangeFailed, true);
            } finally {
                select.disabled = false;
                select.classList.remove('opacity-60');
            }
        });
    });

    // Cancel Order Modal handling
    const cancelModal = page.querySelector('#cancel-order-modal');
    const confirmCancelBtn = page.querySelector('#confirm-cancel-order-btn');
    const cancelModalCloseBtn = page.querySelector('#cancel-order-modal-close-btn');
    const confirmCancelIcon = page.querySelector('#confirm-cancel-icon');
    const confirmCancelBtnText = page.querySelector('#confirm-cancel-btn-text');
    let activeCancelOrderId = null;

    /**
     * Close the cancel confirmation modal.
     *
     * @returns {void}
     */
    const closeCancelModal = () => {
        cancelModal?.classList.add('hidden');
        cancelModal?.classList.remove('flex');
        activeCancelOrderId = null;
    };
    page.querySelector('#cancel-modal-backdrop')?.addEventListener('click', closeCancelModal);
    window.closeCancelOrderModal = closeCancelModal;

    /**
     * Open the cancel order modal with order details.
     *
     * @param {number} orderId Order ID.
     * @param {string} orderCode Formatted order code (#ORD-...).
     * @param {string} memberName Display name of the member.
     * @param {string} amount Formatted order total.
     * @returns {void}
     */
    window.openCancelOrderModal = (orderId, orderCode, memberName, amount) => {
        if (!cancelModal) return;
        activeCancelOrderId = orderId;

        const codeEl = cancelModal.querySelector('#cancel-modal-order-code');
        const memberEl = cancelModal.querySelector('#cancel-modal-member-name');
        const amountEl = cancelModal.querySelector('#cancel-modal-amount');

        if (codeEl) codeEl.textContent = orderCode || ('#ORD-' + orderId);
        if (memberEl) memberEl.textContent = memberName || '—';
        if (amountEl) amountEl.textContent = amount || '—';

        cancelModal.classList.remove('hidden');
        cancelModal.classList.add('flex');
    };

    /**
     * Confirm cancellation and submit request with loading feedback.
     */
    confirmCancelBtn?.addEventListener('click', async () => {
        if (!activeCancelOrderId) return;

        const orderId = activeCancelOrderId;
        confirmCancelBtn.disabled = true;
        if (cancelModalCloseBtn) cancelModalCloseBtn.disabled = true;

        if (confirmCancelIcon) {
            confirmCancelIcon.textContent = 'progress_activity';
            confirmCancelIcon.classList.add('animate-spin');
        }
        if (confirmCancelBtnText) {
            confirmCancelBtnText.textContent = i18n.cancellingStatus || 'Đang xử lý hủy...';
        }

        try {
            const response = await fetch('/admin/' + room + '/orders/' + orderId + '/cancel', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    Accept: 'application/json',
                },
            });

            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || i18n.orderCancelledFailed);
            }

            closeCancelModal();

            // Update row status and select element directly
            const row = page.querySelector(`[data-order-row="${orderId}"]`);
            if (row) {
                const select = row.querySelector('.order-status-select');
                if (select) {
                    if (!select.querySelector('option[value="cancelled"]')) {
                        const opt = document.createElement('option');
                        opt.value = 'cancelled';
                        opt.textContent = i18n.statusCancelled || 'Đã huỷ';
                        select.appendChild(opt);
                    }
                    select.value = 'cancelled';
                    select.dataset.currentStatus = 'cancelled';
                    updateSelectClass(select, 'cancelled');
                }

                // Hide cancel button in row action dropdown
                const cancelBtn = row.querySelector('button[onclick*="openCancelOrderModal"]');
                if (cancelBtn) cancelBtn.style.display = 'none';
            }

            const successTemplate = i18n.orderCancelledSuccess || 'Đã hủy đơn hàng #:id thành công.';
            showNotice(successTemplate.replace(':id', orderId), false);
        } catch (error) {
            showNotice(error.message || i18n.orderCancelledFailed, true);
        } finally {
            confirmCancelBtn.disabled = false;
            if (cancelModalCloseBtn) cancelModalCloseBtn.disabled = false;
            if (confirmCancelIcon) {
                confirmCancelIcon.textContent = 'cancel';
                confirmCancelIcon.classList.remove('animate-spin');
            }
            if (confirmCancelBtnText) {
                confirmCancelBtnText.textContent = i18n.confirmCancelBtn || 'Xác nhận hủy';
            }
        }
    });

    // Bulk Actions Implementation
    const selectAllCheckbox = page.querySelector('#orders-select-all');
    const bulkBar = page.querySelector('#orders-bulk-bar');
    const bulkCountEl = page.querySelector('#bulk-selected-count');
    const bulkStatusSelect = page.querySelector('#bulk-status-select');
    const bulkApplyBtn = page.querySelector('#bulk-apply-status-btn');
    const bulkApplyIcon = page.querySelector('#bulk-apply-icon');
    const bulkApplyText = page.querySelector('#bulk-apply-text');
    const bulkCancelBtn = page.querySelector('#bulk-cancel-btn');
    const bulkDeselectBtn = page.querySelector('#bulk-deselect-btn');

    const bulkCancelModal = page.querySelector('#bulk-cancel-modal');
    const bulkCancelModalDesc = page.querySelector('#bulk-cancel-modal-desc');
    const confirmBulkCancelBtn = page.querySelector('#confirm-bulk-cancel-btn');
    const bulkCancelModalCloseBtn = page.querySelector('#bulk-cancel-modal-close-btn');
    const confirmBulkCancelIcon = page.querySelector('#confirm-bulk-cancel-icon');
    const confirmBulkCancelBtnText = page.querySelector('#confirm-bulk-cancel-btn-text');

    /**
     * Get array of currently selected order IDs.
     *
     * @returns {number[]}
     */
    const getSelectedOrderIds = () => {
        return Array.from(page.querySelectorAll('.order-row-checkbox:checked'))
            .map((cb) => Number(cb.value))
            .filter((id) => !isNaN(id) && id > 0);
    };

    /**
     * Update bulk bar visibility and selected count display.
     *
     * @returns {void}
     */
    const updateBulkSelectionState = () => {
        const allCheckboxes = Array.from(page.querySelectorAll('.order-row-checkbox'));
        const checkedBoxes = allCheckboxes.filter((cb) => cb.checked);
        const count = checkedBoxes.length;

        if (bulkCountEl) {
            const template = i18n.selectedOrdersCount || ':count đơn đã chọn';
            bulkCountEl.textContent = template.replace(':count', count);
        }

        if (count > 0) {
            bulkBar?.classList.remove('hidden');
        } else {
            bulkBar?.classList.add('hidden');
        }

        if (selectAllCheckbox) {
            if (allCheckboxes.length > 0 && checkedBoxes.length === allCheckboxes.length) {
                selectAllCheckbox.checked = true;
                selectAllCheckbox.indeterminate = false;
            } else if (checkedBoxes.length > 0) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = true;
            } else {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            }
        }
    };

    // Listen to select all checkbox change
    selectAllCheckbox?.addEventListener('change', () => {
        const isChecked = selectAllCheckbox.checked;
        page.querySelectorAll('.order-row-checkbox').forEach((cb) => {
            cb.checked = isChecked;
        });
        updateBulkSelectionState();
    });

    // Listen to individual row checkbox changes
    page.querySelectorAll('.order-row-checkbox').forEach((cb) => {
        cb.addEventListener('change', updateBulkSelectionState);
    });

    // Deselect all button click
    bulkDeselectBtn?.addEventListener('click', () => {
        if (selectAllCheckbox) selectAllCheckbox.checked = false;
        page.querySelectorAll('.order-row-checkbox').forEach((cb) => {
            cb.checked = false;
        });
        updateBulkSelectionState();
    });

    /**
     * Apply status change to all selected orders.
     */
    bulkApplyBtn?.addEventListener('click', async () => {
        const selectedIds = getSelectedOrderIds();
        if (selectedIds.length === 0) {
            showNotice(i18n.noOrdersSelected || 'Vui lòng chọn ít nhất một đơn hàng.', true);
            return;
        }

        const targetStatus = bulkStatusSelect?.value || 'confirmed';
        bulkApplyBtn.disabled = true;
        if (bulkApplyIcon) {
            bulkApplyIcon.textContent = 'progress_activity';
            bulkApplyIcon.classList.add('animate-spin');
        }
        if (bulkApplyText) {
            bulkApplyText.textContent = i18n.statusUpdating || 'Đang cập nhật...';
        }

        try {
            const response = await fetch('/admin/' + room + '/orders/bulk-status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    order_ids: selectedIds,
                    status: targetStatus,
                }),
            });

            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || i18n.statusChangeFailed);
            }

            const successTemplate = i18n.bulkStatusUpdatedSuccess || 'Đã cập nhật trạng thái cho :count đơn hàng.';
            showNotice(data.message || successTemplate.replace(':count', selectedIds.length), false);

            setTimeout(() => window.location.reload(), 1000);
        } catch (error) {
            showNotice(error.message || i18n.statusChangeFailed, true);
        } finally {
            bulkApplyBtn.disabled = false;
            if (bulkApplyIcon) {
                bulkApplyIcon.textContent = 'done_all';
                bulkApplyIcon.classList.remove('animate-spin');
            }
            if (bulkApplyText) {
                bulkApplyText.textContent = i18n.bulkApplyStatus || 'Áp dụng trạng thái';
            }
        }
    });

    /**
     * Close the bulk cancel modal.
     *
     * @returns {void}
     */
    const closeBulkCancelModal = () => {
        bulkCancelModal?.classList.add('hidden');
        bulkCancelModal?.classList.remove('flex');
    };
    page.querySelector('#bulk-cancel-backdrop')?.addEventListener('click', closeBulkCancelModal);
    window.closeBulkCancelModal = closeBulkCancelModal;

    /**
     * Open bulk cancel modal.
     */
    bulkCancelBtn?.addEventListener('click', () => {
        const selectedIds = getSelectedOrderIds();
        if (selectedIds.length === 0) {
            showNotice(i18n.noOrdersSelected || 'Vui lòng chọn ít nhất một đơn hàng.', true);
            return;
        }

        if (bulkCancelModalDesc) {
            const template = i18n.bulkCancelConfirmDesc || 'Bạn có chắc chắn muốn hủy :count đơn hàng đã chọn không?';
            bulkCancelModalDesc.textContent = template.replace(':count', selectedIds.length);
        }

        bulkCancelModal?.classList.remove('hidden');
        bulkCancelModal?.classList.add('flex');
    });

    /**
     * Confirm bulk cancel action.
     */
    confirmBulkCancelBtn?.addEventListener('click', async () => {
        const selectedIds = getSelectedOrderIds();
        if (selectedIds.length === 0) return;

        confirmBulkCancelBtn.disabled = true;
        if (bulkCancelModalCloseBtn) bulkCancelModalCloseBtn.disabled = true;

        if (confirmBulkCancelIcon) {
            confirmBulkCancelIcon.textContent = 'progress_activity';
            confirmBulkCancelIcon.classList.add('animate-spin');
        }
        if (confirmBulkCancelBtnText) {
            confirmBulkCancelBtnText.textContent = i18n.cancellingStatus || 'Đang xử lý hủy...';
        }

        try {
            const response = await fetch('/admin/' + room + '/orders/bulk-cancel', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    order_ids: selectedIds,
                }),
            });

            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || i18n.orderCancelledFailed);
            }

            closeBulkCancelModal();

            const successTemplate = i18n.bulkCancelledSuccess || 'Đã hủy :count đơn hàng thành công.';
            showNotice(data.message || successTemplate.replace(':count', selectedIds.length), false);

            setTimeout(() => window.location.reload(), 1000);
        } catch (error) {
            showNotice(error.message || i18n.orderCancelledFailed, true);
        } finally {
            confirmBulkCancelBtn.disabled = false;
            if (bulkCancelModalCloseBtn) bulkCancelModalCloseBtn.disabled = false;
            if (confirmBulkCancelIcon) {
                confirmBulkCancelIcon.textContent = 'cancel';
                confirmBulkCancelIcon.classList.remove('animate-spin');
            }
            if (confirmBulkCancelBtnText) {
                confirmBulkCancelBtnText.textContent = i18n.confirmCancelBtn || 'Xác nhận hủy';
            }
        }
    });
}

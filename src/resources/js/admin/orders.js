/**
 * Admin Realtime Orders Controller
 */
export function initAdminOrders() {
    const searchInput = document.querySelector('#order-search');
    const campaignSelect = document.querySelector('#campaign-filter-select');
    const statusSelect = document.querySelector('#status-filter-select');
    const rows = document.querySelectorAll('[data-order-row]');
    const modal = document.querySelector('#price-adjust-modal');
    const modalBackdrop = document.querySelector('#modal-backdrop');
    const modalBody = document.querySelector('#modal-body');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const roomSlug = document.querySelector('[data-room-slug]')?.dataset.roomSlug || window.__DF_ROOM_SLUG__ || '';

    if (!searchInput && !rows.length && !modal) return;

    modalBackdrop?.addEventListener('click', closePriceAdjustModal);

    function applyOrderFilters() {
        const term = searchInput?.value.trim().toLowerCase() || '';
        const campId = campaignSelect?.value || '';
        const activeStatus = statusSelect?.value || 'all';

        rows.forEach(row => {
            const matchesSearch = row.dataset.search?.includes(term);
            const matchesCamp = !campId || row.dataset.campaignId === campId;
            const matchesStatus = activeStatus === 'all' || row.dataset.status === activeStatus;
            row.style.display = (matchesSearch && matchesCamp && matchesStatus) ? '' : 'none';
        });
    }

    searchInput?.addEventListener('input', applyOrderFilters);
    campaignSelect?.addEventListener('change', applyOrderFilters);
    statusSelect?.addEventListener('change', applyOrderFilters);

    const openAdjustModalFn = async function(orderId) {
        if (!modal || !modalBody) return;
        modalBody.innerHTML = '<div class="py-8 text-center text-outline"><span class="material-symbols-outlined animate-spin text-[24px]">progress_activity</span></div>';
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        try {
            const res = await fetch(`/admin/${roomSlug}/orders/${orderId}`, {
                headers: { 'Accept': 'application/json' }
            });
            const { data: order } = await res.json();
            const modalTitle = document.querySelector('#modal-order-title');
            if (modalTitle) modalTitle.textContent = `#ORD-${order.id} - ${order.room_user?.display_name || 'Member'}`;

            modalBody.innerHTML = `
                <div class="space-y-3">
                    <div class="bg-surface-container-low rounded-lg p-3 border border-outline-variant">
                        <div class="font-semibold text-on-surface mb-2">Ordered Items:</div>
                        <div class="space-y-2">
                            ${(order.items || []).map(it => `
                                <div class="flex items-center justify-between gap-3 bg-surface-container-lowest p-2 rounded border border-outline-variant/60">
                                    <div class="flex-1">
                                        <div class="font-bold text-on-surface">${it.quantity}x ${it.item_name} ${it.size ? `(${it.size})` : ''}</div>
                                        <div class="text-[11px] text-outline">${it.toppings?.map(t => t.name).join(', ') || '—'}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-[11px] text-outline line-through">${new Intl.NumberFormat('vi-VN').format(it.unit_price || 0)} ₫</div>
                                        <div class="font-bold text-primary">${new Intl.NumberFormat('vi-VN').format(it.final_price || it.unit_price || 0)} ₫</div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>

                    <form id="price-adjust-form" class="space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-on-surface mb-1">Shared Ship Fee:</label>
                                <input type="number" id="adj-shipping" value="${order.shipping_fee || 0}" class="w-full h-9 px-3 bg-surface border border-outline-variant rounded font-mono font-bold text-on-surface">
                            </div>
                            <div>
                                <label class="block font-semibold text-on-surface mb-1">Voucher / Discount:</label>
                                <input type="number" id="adj-discount" value="${order.discount_amount || 0}" class="w-full h-9 px-3 bg-surface border border-outline-variant rounded font-mono font-bold text-emerald-600">
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-on-surface mb-1">Final Payable Amount:</label>
                            <input type="number" id="adj-final" value="${order.final_amount || order.subtotal_amount || 0}" class="w-full h-9 px-3 bg-surface border border-outline-variant rounded font-mono font-bold text-base text-primary">
                        </div>

                        <div>
                            <label class="block font-semibold text-on-surface mb-1">Adjustment Reason / Note:</label>
                            <textarea id="adj-reason" rows="2" placeholder="..." class="w-full p-2.5 bg-surface border border-outline-variant rounded text-xs text-on-surface" required></textarea>
                        </div>

                        <div class="pt-3 border-t border-outline-variant flex items-center justify-end gap-2">
                            <button type="button" onclick="closePriceAdjustModal()" class="px-4 py-2 bg-surface-container hover:bg-surface-container-high text-on-surface rounded font-semibold">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-primary hover:bg-primary/90 text-on-primary rounded font-semibold">Save Changes</button>
                        </div>
                    </form>
                </div>
            `;

            document.querySelector('#price-adjust-form')?.addEventListener('submit', async (e) => {
                e.preventDefault();
                const submitBtn = e.target.querySelector('button[type="submit"]');
                const origBtnHtml = submitBtn ? submitBtn.innerHTML : '';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = `<span class="inline-flex items-center gap-1.5"><svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Saving...</span>`;
                }

                const finalAmount = document.querySelector('#adj-final')?.value;
                const shipping = document.querySelector('#adj-shipping')?.value;
                const discount = document.querySelector('#adj-discount')?.value;
                const reason = document.querySelector('#adj-reason')?.value;

                try {
                    const updateRes = await fetch(`/admin/${roomSlug}/orders/${order.id}`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            final_amount: Number(finalAmount),
                            shipping_fee: Number(shipping),
                            discount_amount: Number(discount),
                            note: reason
                        })
                    });
                    if (updateRes.ok) {
                        closePriceAdjustModal();
                        window.location.reload();
                    } else {
                        alert('Could not update order price.');
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = origBtnHtml;
                        }
                    }
                } catch(e) {
                    console.error(e);
                    alert('Server connection error.');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = origBtnHtml;
                    }
                }
            });
        } catch (e) {
            console.error(e);
            modalBody.innerHTML = '<div class="py-8 text-center text-error">Error loading order.</div>';
        }
    };

    window.openPriceAdjustmentModal = openAdjustModalFn;
    window.openPriceAdjustModal = openAdjustModalFn;

    window.closePriceAdjustModal = function() {
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    window.unlockOrder = async function(id) {
        if (!confirm('Unlock order?')) return;
        try {
            const res = await fetch(`/admin/${roomSlug}/orders/${id}/unlock`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
            if (res.ok) window.location.reload();
            else alert('Could not unlock order.');
        } catch (e) {
            console.error(e);
        }
    };

    window.cancelOrder = async function(id) {
        if (!confirm('Cancel order?')) return;
        try {
            const res = await fetch(`/admin/${roomSlug}/orders/${id}/cancel`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
            if (res.ok) window.location.reload();
            else alert('Could not cancel order.');
        } catch (e) {
            console.error(e);
        }
    };
}

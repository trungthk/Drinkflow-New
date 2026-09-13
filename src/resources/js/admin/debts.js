/**
 * Admin Debts & Split Billing Controller
 */
export function initAdminDebts() {
    const searchInput = document.querySelector('#debt-search');
    const statusBtns = document.querySelectorAll('.debt-status-filter');
    const rows = document.querySelectorAll('[data-debt-row]');
    const modal = document.querySelector('#debt-modal');
    const modalBackdrop = document.querySelector('#debt-backdrop');
    const modalBody = document.querySelector('#debt-modal-body');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const roomSlug = document.querySelector('[data-room-slug]')?.dataset.roomSlug || window.__DF_ROOM_SLUG__ || '';

    if (!searchInput && !rows.length && !modal) return;

    modalBackdrop?.addEventListener('click', closeDebtModal);

    function applyDebtFilters() {
        const term = searchInput?.value.trim().toLowerCase() || '';
        const activeStatus = document.querySelector('.debt-status-filter.bg-primary')?.dataset.status || 'all';

        rows.forEach(row => {
            const matchesSearch = row.dataset.search?.includes(term);
            const matchesStatus = activeStatus === 'all' || row.dataset.status === activeStatus;
            row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
        });
    }

    searchInput?.addEventListener('input', applyDebtFilters);
    statusBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            statusBtns.forEach(b => {
                b.classList.remove('bg-primary', 'text-on-primary');
                b.classList.add('bg-surface-container', 'text-on-surface');
            });
            btn.classList.add('bg-primary', 'text-on-primary');
            btn.classList.remove('bg-surface-container', 'text-on-surface');
            applyDebtFilters();
        });
    });

    window.openRecordPaymentModal = function(debtId, remaining, memberName) {
        const titleEl = document.querySelector('#debt-modal-title');
        if (titleEl) titleEl.textContent = `Confirm Payment: ${memberName}`;
        if (modalBody) {
            modalBody.innerHTML = `
                <form id="record-pay-form" class="space-y-3 text-xs">
                    <div>
                        <label class="block font-semibold text-on-surface mb-1">Payment Amount:</label>
                        <input type="number" id="pay-amount" value="${remaining}" max="${remaining}" min="1000" class="w-full h-9 px-3 bg-surface border border-outline-variant rounded font-mono font-bold text-base text-primary" required>
                    </div>
                    <div>
                        <label class="block font-semibold text-on-surface mb-1">Payment Method:</label>
                        <select id="pay-method" class="w-full h-9 px-3 bg-surface border border-outline-variant rounded text-on-surface">
                            <option value="vietqr">VietQR</option>
                            <option value="cash">Cash</option>
                            <option value="room_fund">Room Fund</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-on-surface mb-1">Reference / Note:</label>
                        <input type="text" id="pay-ref" placeholder="..." class="w-full h-9 px-3 bg-surface border border-outline-variant rounded text-on-surface">
                    </div>
                    <div class="pt-3 border-t border-outline-variant flex items-center justify-end gap-2">
                        <button type="button" onclick="closeDebtModal()" class="px-4 py-2 bg-surface-container text-on-surface rounded font-semibold">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded font-semibold">Confirm Payment</button>
                    </div>
                </form>
            `;
        }
        modal?.classList.remove('hidden');
        modal?.classList.add('flex');

        document.querySelector('#record-pay-form')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = e.target.querySelector('button[type="submit"]');
            const origBtnHtml = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = `<span class="inline-flex items-center gap-1.5"><svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Saving...</span>`;
            }

            const amount = document.querySelector('#pay-amount')?.value;
            const method = document.querySelector('#pay-method')?.value;
            const ref = document.querySelector('#pay-ref')?.value;

            try {
                const res = await fetch(`/admin/${roomSlug}/debts/${debtId}/payments`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({ amount: Number(amount), payment_method: method, reference: ref })
                });
                if (res.ok) {
                    window.location.reload();
                } else {
                    alert('Error recording payment.');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = origBtnHtml;
                    }
                }
            } catch(e) {
                console.error(e);
                alert('Server error.');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = origBtnHtml;
                }
            }
        });
    };

    window.openAdjustDebtModal = function(debtId, remaining, memberName) {
        const titleEl = document.querySelector('#debt-modal-title');
        if (titleEl) titleEl.textContent = `Adjust Debt: ${memberName}`;
        if (modalBody) {
            modalBody.innerHTML = `
                <form id="adjust-debt-form" class="space-y-3 text-xs">
                    <div>
                        <label class="block font-semibold text-on-surface mb-1">Adjustment Type:</label>
                        <select id="adj-type" class="w-full h-9 px-3 bg-surface border border-outline-variant rounded text-on-surface">
                            <option value="discount">Giảm nợ (Discount / Waiver)</option>
                            <option value="surcharge">Tăng nợ (Surcharge)</option>
                            <option value="forgive">Miễn nợ (Forgive)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-on-surface mb-1">Amount:</label>
                        <input type="number" id="adj-amount" value="${remaining}" min="1000" class="w-full h-9 px-3 bg-surface border border-outline-variant rounded font-mono font-bold text-on-surface" required>
                    </div>
                    <div>
                        <label class="block font-semibold text-on-surface mb-1">Reason:</label>
                        <textarea id="adj-reason" rows="2" placeholder="..." class="w-full p-2.5 bg-surface border border-outline-variant rounded text-on-surface" required></textarea>
                    </div>
                    <div class="pt-3 border-t border-outline-variant flex items-center justify-end gap-2">
                        <button type="button" onclick="closeDebtModal()" class="px-4 py-2 bg-surface-container text-on-surface rounded font-semibold">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-primary hover:bg-primary/90 text-on-primary rounded font-semibold">Save Adjustment</button>
                    </div>
                </form>
            `;
        }
        modal?.classList.remove('hidden');
        modal?.classList.add('flex');

        document.querySelector('#adjust-debt-form')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = e.target.querySelector('button[type="submit"]');
            const origBtnHtml = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = `<span class="inline-flex items-center gap-1.5"><svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Saving...</span>`;
            }

            const type = document.querySelector('#adj-type')?.value;
            const amount = document.querySelector('#adj-amount')?.value;
            const reason = document.querySelector('#adj-reason')?.value;

            try {
                const res = await fetch(`/admin/${roomSlug}/debts/${debtId}/adjust`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({ type, amount: Number(amount), reason })
                });
                if (res.ok) {
                    window.location.reload();
                } else {
                    alert('Error adjusting debt.');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = origBtnHtml;
                    }
                }
            } catch(e) {
                console.error(e);
                alert('Server error.');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = origBtnHtml;
                }
            }
        });
    };

    window.closeDebtModal = function() {
        modal?.classList.add('hidden');
        modal?.classList.remove('flex');
    };

    window.triggerBotReminder = function() {
        alert('Bot reminder dispatched.');
    };

    window.exportDebtCSV = function() {
        alert('Exporting CSV statement.');
    };
}

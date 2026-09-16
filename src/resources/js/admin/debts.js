import { renderSubmitLoading } from '../shared/submit-loading';

/**
 * Admin Debts & Split Billing Controller
 */
export function initAdminDebts() {
    const searchInput = document.querySelector('#debt-search');
    const filterForm = document.querySelector('#debts-filter-form');
    const modal = document.querySelector('#debt-modal');
    const modalBackdrop = document.querySelector('#debt-backdrop');
    const modalBody = document.querySelector('#debt-modal-body');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const roomSlug = document.querySelector('[data-room-slug]')?.dataset.roomSlug || window.__DF_ROOM_SLUG__ || '';

    if (!filterForm && !modal) return;

    const closeDebtModal = () => {
        modal?.classList.add('hidden');
        modal?.classList.remove('flex');
    };
    window.closeDebtModal = closeDebtModal;
    modalBackdrop?.addEventListener('click', closeDebtModal);

    searchInput?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            filterForm?.requestSubmit();
        }
    });
    searchInput?.addEventListener('admin:search-cleared', () => filterForm?.requestSubmit());

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
                renderSubmitLoading(submitBtn);
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
                renderSubmitLoading(submitBtn);
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

    // ── Approve Payment Detail Modal ──────────────────────────────────────
    const approveModal    = document.querySelector('#approve-debt-modal');
    const approveBackdrop = document.querySelector('#approve-debt-backdrop');
    const approveClose    = document.querySelector('#approve-modal-close');
    const approveCancel   = document.querySelector('#approve-modal-cancel');
    const approveConfirm  = document.querySelector('#approve-modal-confirm');
    const approveConfirmText = document.querySelector('#approve-modal-confirm-text');

    /**
     * Open the Approve Payment detail modal and fill it with debt data.
     *
     * @param {Object} data – Debt info passed from Blade inline call.
     */
    window.openApproveDebtModal = function(data) {
        if (!approveModal) return;

        // Fill member info
        const memberEl = document.querySelector('#approve-modal-member');
        const memberMetaEl = document.querySelector('#approve-modal-member-meta');
        if (memberEl) memberEl.textContent = data.member || '—';
        if (memberMetaEl) {
            const parts = [];
            if (data.memberEmail) parts.push(data.memberEmail);
            if (data.memberCode) parts.push(data.memberCode);
            memberMetaEl.textContent = parts.join(' · ') || '—';
        }

        // Fill time – prefer updatedAt (time member submitted request), fallback to createdAt
        const timeEl = document.querySelector('#approve-modal-time');
        if (timeEl) timeEl.textContent = data.updatedAt || data.createdAt || '—';

        // Fill campaign
        const campaignEl = document.querySelector('#approve-modal-campaign');
        if (campaignEl) campaignEl.textContent = data.campaign || '—';

        // Fill transfer content/note
        const contentEl = document.querySelector('#approve-modal-content');
        if (contentEl) contentEl.textContent = data.transferContent || '—';

        // Fill amount
        const amountEl = document.querySelector('#approve-modal-amount');
        if (amountEl) {
            amountEl.textContent = Number(data.amount || 0).toLocaleString('vi-VN') + ' ₫';
        }

        // Bind debt id to confirm button
        if (approveConfirm) approveConfirm.dataset.debtId = data.id;

        // Show modal
        approveModal.classList.remove('hidden');
        approveModal.classList.add('flex');
    };

    function closeApproveModal() {
        approveModal?.classList.add('hidden');
        approveModal?.classList.remove('flex');
    }

    approveBackdrop?.addEventListener('click', closeApproveModal);
    approveClose?.addEventListener('click', closeApproveModal);
    approveCancel?.addEventListener('click', closeApproveModal);

    // ESC key closes approve modal
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeApproveModal();
    });

    approveConfirm?.addEventListener('click', async () => {
        const debtId = approveConfirm.dataset.debtId;
        if (!debtId) return;

        // Loading state
        const origText = approveConfirmText ? approveConfirmText.textContent : '';
        if (approveConfirmText) approveConfirmText.textContent = '...';
        approveConfirm.disabled = true;
        approveConfirm.classList.add('opacity-75', 'cursor-not-allowed');

        try {
            const res = await fetch(`/admin/${roomSlug}/debts/${debtId}/approve`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            });
            const data = await res.json();

            if (res.ok) {
                closeApproveModal();
                // Optimistically update the row on the page
                const row = document.querySelector(`[data-debt-id="${debtId}"]`);
                if (row) {
                    // Update status badge cell
                    const statusCell = row.querySelector('td:nth-child(3) span');
                    if (statusCell) {
                        statusCell.className = 'inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold border bg-emerald-50 text-emerald-700 border-emerald-200';
                        statusCell.textContent = '✓ Đã thanh toán';
                    }
                    // Update remaining amount cell
                    const amtCell = row.querySelector('td:nth-child(5)');
                    if (amtCell) {
                        amtCell.className = 'py-3.5 px-4 text-right font-mono font-bold text-sm text-emerald-600';
                        amtCell.textContent = '0 ₫';
                    }
                    // Update action cell
                    const actCell = row.querySelector('td:nth-child(6) div');
                    if (actCell) {
                        actCell.innerHTML = `<span class="text-[11px] text-emerald-700 font-semibold flex items-center gap-0.5">
                            <span class="material-symbols-outlined text-[14px]">verified</span>
                            <span>Đã quyết toán</span>
                        </span>`;
                    }
                    row.dataset.status = 'paid';
                } else {
                    // Fallback reload
                    window.location.reload();
                }
            } else {
                alert(data.message || 'Lỗi khi duyệt thanh toán.');
                // Restore button
                if (approveConfirmText) approveConfirmText.textContent = origText;
                approveConfirm.disabled = false;
                approveConfirm.classList.remove('opacity-75', 'cursor-not-allowed');
            }
        } catch (e) {
            console.error(e);
            alert('Lỗi kết nối máy chủ.');
            if (approveConfirmText) approveConfirmText.textContent = origText;
            approveConfirm.disabled = false;
            approveConfirm.classList.remove('opacity-75', 'cursor-not-allowed');
        }
    });

    window.exportDebtCSV = function() {
        window.location.assign(`/admin/${roomSlug}/debts/export`);
    };
}

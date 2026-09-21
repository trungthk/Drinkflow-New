import { renderSubmitLoading } from '../shared/submit-loading';
import { formatMoney } from '../shared/money';

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
    const formatMoneyInput = (input) => {
        if (!input) return;
        const digits = String(input.value || '').replace(/[^0-9]/g, '');
        input.value = digits ? Number(digits).toLocaleString('vi-VN') : '';
    };
    const numericValue = (selector) => Number(String(document.querySelector(selector)?.value || '').replace(/[^0-9]/g, '')) || 0;

    if (!filterForm && !modal) return;

    const i18n = JSON.parse(modal?.dataset.i18n || '{}');
    const t = (key, replacements = {}) => Object.entries(replacements).reduce(
        (text, [name, value]) => text.replaceAll(`:${name}`, value),
        i18n[key] || '',
    );
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    })[char]);

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
        if (titleEl) titleEl.textContent = t('confirmTitle', { member: memberName });
        if (modalBody) {
            modalBody.innerHTML = `
                <form id="record-pay-form" class="space-y-3 text-xs">
                    <div>
                        <label class="block font-semibold text-on-surface mb-1">${escapeHtml(t('paymentAmount'))} <span class="text-error">*</span>:</label>
                        <input type="text" inputmode="numeric" id="pay-amount" value="${Number(remaining).toLocaleString('vi-VN')}" data-max="${remaining}" class="w-full h-9 px-3 bg-surface border border-outline-variant rounded font-mono font-bold text-base text-primary" required>
                    </div>
                    <div>
                        <label class="block font-semibold text-on-surface mb-1">${escapeHtml(t('paymentMethod'))} <span class="text-error">*</span>:</label>
                        <select id="pay-method" required class="w-full h-9 px-3 bg-surface border border-outline-variant rounded text-on-surface">
                            <option value="vietqr">${escapeHtml(t('methodVietqr'))}</option>
                            <option value="cash">${escapeHtml(t('methodCash'))}</option>
                            <option value="room_fund">${escapeHtml(t('methodRoomFund'))}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-on-surface mb-1">${escapeHtml(t('reference'))}:</label>
                        <input type="text" id="pay-ref" placeholder="..." class="w-full h-9 px-3 bg-surface border border-outline-variant rounded text-on-surface">
                    </div>
                    <div class="pt-3 border-t border-outline-variant flex items-center justify-end gap-2">
                        <button type="button" onclick="closeDebtModal()" class="px-4 py-2 bg-surface-container text-on-surface rounded font-semibold">${escapeHtml(t('cancel'))}</button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded font-semibold inline-flex items-center gap-1.5"><span class="material-symbols-outlined text-[16px]">payments</span>${escapeHtml(t('confirmPayment'))}</button>
                    </div>
                </form>
            `;
        }
        modal?.classList.remove('hidden');
        modal?.classList.add('flex');

        document.querySelector('#pay-amount')?.addEventListener('input', (event) => formatMoneyInput(event.target));

        document.querySelector('#record-pay-form')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = e.target.querySelector('button[type="submit"]');
            const origBtnHtml = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                renderSubmitLoading(submitBtn);
            }

            const amount = numericValue('#pay-amount');
            const method = document.querySelector('#pay-method')?.value;
            const ref = document.querySelector('#pay-ref')?.value;
            if (amount > Number(remaining)) {
                alert(t('amountExceeds', { max: Number(remaining).toLocaleString('vi-VN') }));
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = origBtnHtml;
                }
                return;
            }

            try {
                const res = await fetch(`/admin/${roomSlug}/debts/${debtId}/payments`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({ amount: Number(amount), payment_method: method, reference: ref })
                });
                if (res.ok) {
                    window.location.reload();
                } else {
                    const errorPayload = await res.json().catch(() => ({}));
                    alert(errorPayload.message || errorPayload.errors?.amount?.[0] || t('paymentError'));
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = origBtnHtml;
                    }
                }
            } catch(e) {
                console.error(e);
                alert(t('serverError'));
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = origBtnHtml;
                }
            }
        });
    };

    window.openAdjustDebtModal = function(debtId, remaining, memberName) {
        const titleEl = document.querySelector('#debt-modal-title');
        if (titleEl) titleEl.textContent = t('adjustTitle', { member: memberName });
        if (modalBody) {
            modalBody.innerHTML = `
                <form id="adjust-debt-form" class="space-y-3 text-xs">
                    <div>
                        <label class="block font-semibold text-on-surface mb-1">${escapeHtml(t('adjustType'))} <span class="text-error">*</span>:</label>
                        <select id="adj-type" required class="w-full h-9 px-3 bg-surface border border-outline-variant rounded text-on-surface">
                            <option value="decrease">${escapeHtml(t('adjustDecrease'))}</option>
                            <option value="increase">${escapeHtml(t('adjustIncrease'))}</option>
                            <option value="waive">${escapeHtml(t('adjustWaive'))}</option>
                            <option value="correction">${escapeHtml(t('adjustCorrection'))}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-on-surface mb-1">${escapeHtml(t('amount'))} <span class="text-error">*</span>:</label>
                        <input type="text" inputmode="numeric" id="adj-amount" value="${Number(remaining).toLocaleString('vi-VN')}" class="w-full h-9 px-3 bg-surface border border-outline-variant rounded font-mono font-bold text-on-surface" required>
                    </div>
                    <div>
                        <label class="block font-semibold text-on-surface mb-1">${escapeHtml(t('reason'))} <span class="text-error">*</span>:</label>
                        <textarea id="adj-reason" rows="2" placeholder="..." class="w-full p-2.5 bg-surface border border-outline-variant rounded text-on-surface" required></textarea>
                    </div>
                    <div class="pt-3 border-t border-outline-variant flex items-center justify-end gap-2">
                        <button type="button" onclick="closeDebtModal()" class="px-4 py-2 bg-surface-container text-on-surface rounded font-semibold">${escapeHtml(t('cancel'))}</button>
                        <button type="submit" class="px-4 py-2 bg-primary hover:bg-primary/90 text-on-primary rounded font-semibold inline-flex items-center gap-1.5"><span class="material-symbols-outlined text-[16px]">save</span>${escapeHtml(t('saveAdjustment'))}</button>
                    </div>
                </form>
            `;
        }
        modal?.classList.remove('hidden');
        modal?.classList.add('flex');

        document.querySelector('#adj-amount')?.addEventListener('input', (event) => formatMoneyInput(event.target));

        document.querySelector('#adjust-debt-form')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = e.target.querySelector('button[type="submit"]');
            const origBtnHtml = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                renderSubmitLoading(submitBtn);
            }

            const type = document.querySelector('#adj-type')?.value;
            const amount = numericValue('#adj-amount');
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
                    const errorPayload = await res.json().catch(() => ({}));
                    alert(errorPayload.message || Object.values(errorPayload.errors || {}).flat()[0] || t('adjustError'));
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = origBtnHtml;
                    }
                }
            } catch(e) {
                console.error(e);
                alert(t('serverError'));
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = origBtnHtml;
                }
            }
        });
    };

    // ── Debt Detail Modal (read-only) ─────────────────────────────────────
    const detailModal = document.querySelector('#debt-detail-modal');
    const closeDetailModal = () => {
        detailModal?.classList.add('hidden');
        detailModal?.classList.remove('flex');
    };
    const renderDetailList = (name, rows, renderRow, emptyText) => {
        const container = detailModal?.querySelector(`[data-debt-detail-list="${name}"]`);
        if (!container) return;
        container.innerHTML = rows.length
            ? rows.map(renderRow).join('')
            : `<div class="px-3 py-3 text-[11px] text-outline">${escapeHtml(emptyText)}</div>`;
    };
    const openDebtDetail = (detail) => {
        if (!detailModal) return;
        detailModal.querySelectorAll('[data-debt-detail-field]').forEach((el) => {
            el.textContent = detail[el.dataset.debtDetailField] || (el.dataset.debtDetailField === 'sponsor_type' ? '' : '—');
        });
        // Optional blocks are hidden when they carry no value.
        detailModal.querySelectorAll('[data-debt-detail-row]').forEach((el) => {
            el.classList.toggle('hidden', !detail[el.dataset.debtDetailRow]);
        });
        renderDetailList('payments', detail.payments || [], (payment) => `
            <div class="px-3 py-2.5 flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="font-semibold text-on-surface">${escapeHtml(payment.method)}</div>
                    <div class="text-[11px] text-outline">${escapeHtml(payment.paid_at)}${payment.reference ? ' · ' + escapeHtml(payment.reference) : ''}</div>
                </div>
                <div class="font-mono font-bold text-emerald-700 shrink-0">${escapeHtml(payment.amount)}</div>
            </div>`, i18n.noPayments || '');
        renderDetailList('adjustments', detail.adjustments || [], (adjustment) => `
            <div class="px-3 py-2.5 flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="font-semibold text-on-surface">${escapeHtml(adjustment.type)}</div>
                    <div class="text-[11px] text-outline">${escapeHtml(adjustment.created_at)}${adjustment.reason ? ' · ' + escapeHtml(adjustment.reason) : ''}</div>
                </div>
                <div class="text-right shrink-0">
                    <div class="font-mono font-bold text-on-surface">${escapeHtml(adjustment.amount)}</div>
                    <div class="text-[11px] font-mono text-outline">${escapeHtml(adjustment.before)} → ${escapeHtml(adjustment.after)}</div>
                </div>
            </div>`, i18n.noAdjustments || '');
        detailModal.classList.remove('hidden');
        detailModal.classList.add('flex');
    };
    document.addEventListener('click', (event) => {
        const openButton = event.target instanceof Element ? event.target.closest('[data-open-debt-detail]') : null;
        if (openButton) {
            try {
                openDebtDetail(JSON.parse(openButton.dataset.debtDetail || '{}'));
            } catch (error) {
                console.error(error);
            }
            return;
        }
        if (event.target instanceof Element && (event.target.closest('[data-debt-detail-close]') || event.target.id === 'debt-detail-backdrop')) {
            closeDetailModal();
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && detailModal && !detailModal.classList.contains('hidden')) closeDetailModal();
    });
    // Applying a date range submits the filter form (same behavior as the audit log page).
    document.addEventListener('admin:daterange-change', (event) => {
        if (event.target?.id !== 'debt-date-range') return;
        filterForm?.requestSubmit();
    });
    document.querySelector('#debt-user-filter')?.addEventListener('change', () => filterForm?.requestSubmit());

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

        const isPayAll = Boolean(data.isPayAll);
        const titleEl = document.querySelector('#approve-modal-title');
        const subtitleEl = document.querySelector('#approve-modal-subtitle');
        const badgeTextEl = document.querySelector('#approve-modal-badge-text');
        const statusBadgeEl = document.querySelector('#approve-modal-status-badge');
        const breakdownContainer = document.querySelector('#approve-modal-breakdown-container');
        const breakdownList = document.querySelector('#approve-modal-breakdown-list');
        const breakdownCount = document.querySelector('#approve-modal-breakdown-count');
        const amountLabelEl = document.querySelector('#approve-modal-amount-label');
        const singleInfoGrid = document.querySelector('#approve-modal-single-info');

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
            amountEl.textContent = formatMoney(data.amount);
        }

        // Bind debt id to confirm button
        if (approveConfirm) {
            approveConfirm.dataset.debtId = data.id;
            approveConfirm.dataset.isPayAll = isPayAll ? '1' : '0';
        }

        if (isPayAll) {
            if (titleEl) titleEl.textContent = t('approveAllTitle');
            if (subtitleEl) subtitleEl.textContent = t('approveAllSubtitle');
            if (badgeTextEl) badgeTextEl.textContent = t('approveAllBadge');
            if (statusBadgeEl) {
                statusBadgeEl.className = 'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold border bg-purple-100 text-purple-900 border-purple-300 shrink-0';
            }
            if (amountLabelEl) amountLabelEl.textContent = t('approveAllAmountLabel');
            if (singleInfoGrid) singleInfoGrid.classList.add('hidden');

            // Render debts breakdown
            if (breakdownContainer && breakdownList) {
                breakdownContainer.classList.remove('hidden');
                const debts = data.pendingDebts || [];
                if (breakdownCount) breakdownCount.textContent = t('debtsCount', { count: debts.length });
                breakdownList.innerHTML = debts.map(d => `
                    <div class="p-2.5 flex items-center justify-between text-xs hover:bg-surface-container transition-colors">
                        <div class="flex flex-col min-w-0 pr-2">
                            <div class="flex items-center gap-1.5 font-semibold text-on-surface">
                                <span class="font-mono text-[11px] text-primary font-bold">${escapeHtml(d.code || 'N/A')}</span>
                                <span class="text-outline-variant">•</span>
                                <span class="truncate">${escapeHtml(d.campaign || 'N/A')}</span>
                            </div>
                            ${d.note ? `<div class="text-[11px] text-outline truncate italic mt-0.5">${escapeHtml(d.note)}</div>` : ''}
                        </div>
                        <span class="font-mono font-bold text-amber-600 shrink-0 text-sm">${formatMoney(d.amount)}</span>
                    </div>
                `).join('') || `<div class="p-3 text-center text-outline">${escapeHtml(t('noDebts'))}</div>`;
            }

            if (approveConfirmText) {
                const count = (data.pendingDebts || []).length;
                approveConfirmText.textContent = t('approveAllConfirm', { count });
            }
        } else {
            if (titleEl) titleEl.textContent = t('approveTitle');
            if (subtitleEl) subtitleEl.textContent = t('approveSubtitle');
            if (badgeTextEl) badgeTextEl.textContent = t('approveBadge');
            if (statusBadgeEl) {
                statusBadgeEl.className = 'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold border bg-amber-100 text-amber-900 border-amber-300 shrink-0';
            }
            if (amountLabelEl) amountLabelEl.textContent = t('approveAmountLabel');
            if (singleInfoGrid) singleInfoGrid.classList.remove('hidden');
            if (breakdownContainer) breakdownContainer.classList.add('hidden');
            if (approveConfirmText) approveConfirmText.textContent = t('approveConfirm');
        }

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
        const isPayAll = approveConfirm.dataset.isPayAll === '1';
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
                if (isPayAll) {
                    // For full debt settlement, refresh to update all counters and table rows
                    window.location.reload();
                } else {
                    // Optimistically update the single row on the page
                    const row = document.querySelector(`[data-debt-id="${debtId}"]`);
                    if (row) {
                        // Update status badge cell
                        const statusCell = row.querySelector('td:nth-child(3) span');
                        if (statusCell) {
                            statusCell.className = 'inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold border bg-emerald-50 text-emerald-700 border-emerald-200';
                            statusCell.textContent = `✓ ${t('statusPaid')}`;
                        }
                        // Update remaining amount cell
                        const amtCell = row.querySelector('td:nth-child(5)');
                        if (amtCell) {
                            amtCell.className = 'py-3.5 px-4 text-right font-mono font-bold text-sm text-emerald-600';
                            amtCell.textContent = formatMoney(0);
                        }
                        // Update action cell
                        const actCell = row.querySelector('td:nth-child(6) div');
                        if (actCell) {
                            actCell.innerHTML = `<span class="text-[11px] text-emerald-700 font-semibold flex items-center gap-0.5">
                                <span class="material-symbols-outlined text-[14px]">verified</span>
                                <span>${escapeHtml(t('settled'))}</span>
                            </span>`;
                        }
                        row.dataset.status = 'paid';
                    } else {
                        // Fallback reload
                        window.location.reload();
                    }
                }
            } else {
                alert(data.message || t('paymentError'));
                // Restore button
                if (approveConfirmText) approveConfirmText.textContent = origText;
                approveConfirm.disabled = false;
                approveConfirm.classList.remove('opacity-75', 'cursor-not-allowed');
            }
        } catch (e) {
            console.error(e);
            alert(t('serverError'));
            if (approveConfirmText) approveConfirmText.textContent = origText;
            approveConfirm.disabled = false;
            approveConfirm.classList.remove('opacity-75', 'cursor-not-allowed');
        }
    });

    window.exportDebtCSV = function() {
        window.location.assign(`/admin/${roomSlug}/debts/export`);
    };
}

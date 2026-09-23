import { formatMoney } from '../shared/money';

/**
 * Admin Reports & Financial Analytics Controller
 */
export function initAdminReports() {
    const dateRangePicker = document.querySelector('#report-date-range');
    const roomSlug = document.querySelector('[data-room-slug]')?.dataset.roomSlug || window.__DF_ROOM_SLUG__ || '';
    const money = formatMoney;

    let currentActiveTab = 'campaigns';
    const loadedTabCache = new Set();

    function emptyStateHtml(icon = 'inbox', title = '', description = '') {
        return `
            <div class="flex flex-col items-center justify-center py-10 px-4 text-center rounded-xl border border-dashed border-outline-variant/80 bg-surface-container-low/30">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-surface-container-high/60 text-outline mb-2.5">
                    <span class="material-symbols-outlined text-[26px]">${icon}</span>
                </div>
                ${title ? `<h4 class="text-xs font-bold text-on-surface mb-0.5">${title}</h4>` : ''}
                <p class="text-xs text-outline font-medium max-w-sm leading-relaxed">${description}</p>
            </div>
        `;
    }

    const i18n = JSON.parse(document.querySelector('#report-tabs')?.dataset.i18n || '{}');
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    })[char]);
    const ALIGN_CLASS = { left: 'text-left', center: 'text-center', right: 'text-right' };

    /**
     * Render the member identity cell: display name with the account email underneath.
     */
    function memberCellHtml(name, email) {
        return `
            <div class="font-semibold text-on-surface truncate">${escapeHtml(name || i18n.member)}</div>
            ${email ? `<div class="text-[11px] text-outline truncate">${escapeHtml(email)}</div>` : ''}
        `;
    }

    /**
     * Render a report table whose header and body share one column definition,
     * so widths, alignment and typography always match.
     *
     * @param {Array<{label: string, align?: string, width?: string}>} columns Column definitions.
     * @param {Array<Array<string>>} rows Pre-rendered cell HTML per row, in column order.
     */
    function reportTableHtml(columns, rows) {
        const cellClass = (column) => `py-3 px-4 align-middle ${ALIGN_CLASS[column.align || 'left']}`;
        return `
            <div class="overflow-x-auto border border-outline-variant/60 rounded-xl">
                <table class="report-table table-colgroup w-full table-fixed text-left text-xs border-collapse">
                    <colgroup>${columns.map((column) => `<col${column.width ? ` style="width:${column.width}"` : ''}>`).join('')}</colgroup>
                    <thead>
                        <tr class="bg-surface-container-low text-outline uppercase text-[11px] tracking-wide font-semibold border-b border-outline-variant/60">
                            ${columns.map((column) => `<th class="${cellClass(column)}">${escapeHtml(column.label)}</th>`).join('')}
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/40">
                        ${rows.map((cells) => `
                            <tr class="hover:bg-surface-container-low/50 transition-colors">
                                ${cells.map((cell, index) => `<td class="${cellClass(columns[index])}">${cell}</td>`).join('')}
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }

    if (!dateRangePicker && !document.querySelector('.rtab')) return;

    window.switchReportTab = function(tabId) {
        currentActiveTab = tabId;

        document.querySelectorAll('.report-panel').forEach(p => p.classList.add('hidden'));
        document.querySelectorAll('.rtab').forEach(b => {
            b.classList.remove('border-primary', 'text-primary', 'font-bold');
            b.classList.add('border-transparent', 'text-outline');
        });

        document.querySelector(`#panel-${tabId}`)?.classList.remove('hidden');
        const activeBtn = document.querySelector(`#rtab-${tabId}`);
        activeBtn?.classList.add('border-primary', 'text-primary', 'font-bold');
        activeBtn?.classList.remove('border-transparent', 'text-outline');

        // Lazy load tab data on active
        if (tabId !== 'campaigns') {
            window.loadTabReportData(tabId);
        }
    };

    window.loadTabReportData = async function(tabId = currentActiveTab, force = false) {
        const dateFrom = dateRangePicker?.querySelector('.date-from-hidden')?.value || '';
        const dateTo = dateRangePicker?.querySelector('.date-to-hidden')?.value || '';
        const cacheKey = `${tabId}_${dateFrom}_${dateTo}`;

        if (!force && loadedTabCache.has(cacheKey)) {
            return;
        }

        const drinksList = document.querySelector('#top-drinks-list');
        const storesList = document.querySelector('#top-stores-list');
        const debtsList = document.querySelector('#debts-users-list');
        const sponsorsList = document.querySelector('#sponsors-leaderboard-list');
        const usersList = document.querySelector('#users-analytics-list');

        const skeletonHtml = `
            <div class="space-y-2 animate-pulse py-2">
                <div class="h-10 bg-slate-200 dark:bg-slate-800 rounded"></div>
                <div class="h-10 bg-slate-200 dark:bg-slate-800 rounded"></div>
                <div class="h-10 bg-slate-200 dark:bg-slate-800 rounded"></div>
            </div>
        `;

        // Only show skeleton for the currently requested tab
        if (tabId === 'products') {
            if (drinksList) drinksList.innerHTML = skeletonHtml;
            if (storesList) storesList.innerHTML = skeletonHtml;
        } else if (tabId === 'debts') {
            if (debtsList) debtsList.innerHTML = skeletonHtml;
        } else if (tabId === 'sponsors') {
            if (sponsorsList) sponsorsList.innerHTML = skeletonHtml;
        } else if (tabId === 'users') {
            if (usersList) usersList.innerHTML = skeletonHtml;
        }

        try {
            const queryParams = new URLSearchParams();
            if (tabId) queryParams.set('tab', tabId);
            if (dateFrom) queryParams.set('date_from', dateFrom);
            if (dateTo) queryParams.set('date_to', dateTo);
            const queryString = queryParams.toString() ? `?${queryParams.toString()}` : '';

            const res = await fetch(`/admin/${roomSlug}/reports${queryString}`, {
                headers: { 'Accept': 'application/json' }
            });
            const { data } = await res.json();
            if (!data) return;

            // Update stats cards
            const kpiCmp = document.querySelector('#kpi-campaigns');
            const kpiOrd = document.querySelector('#kpi-orders');
            const kpiSpd = document.querySelector('#kpi-spending');
            const kpiSpon = document.querySelector('#kpi-sponsor');
            const sponSub = document.querySelector('#sponsor-subtotal-text');
            const debtRemaining = document.querySelector('#debt-summary-remaining');

            if (kpiCmp && data.campaign_count !== undefined) kpiCmp.textContent = data.campaign_count;
            if (kpiOrd && data.order_count !== undefined) kpiOrd.textContent = data.order_count;
            if (kpiSpd && data.spending !== undefined) kpiSpd.textContent = money(data.spending);
            if (kpiSpon && data.sponsor_amount !== undefined) kpiSpon.textContent = money(data.sponsor_amount);
            if (sponSub && data.sponsor_amount !== undefined) sponSub.textContent = money(data.sponsor_amount);
            if (debtRemaining && data.debt !== undefined) debtRemaining.textContent = money(data.debt);

            // Render Products Tab
            if (tabId === 'products' || tabId === 'all') {
                if (drinksList) {
                    if (data.popular_drinks && data.popular_drinks.length > 0) {
                        drinksList.innerHTML = data.popular_drinks.map((d, idx) => `
                            <div class="flex items-center justify-between p-2.5 bg-surface-container-low rounded border border-outline-variant/60">
                                <div class="flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-full bg-primary/10 text-primary font-bold flex items-center justify-center text-[10px]">${idx + 1}</span>
                                    <span class="font-bold text-on-surface">${escapeHtml(d.item_name)}</span>
                                </div>
                                <span class="font-mono font-bold text-primary">${escapeHtml(d.quantity)}</span>
                            </div>
                        `).join('');
                    } else {
                        drinksList.innerHTML = emptyStateHtml('local_cafe', escapeHtml(i18n.noDrinksTitle), escapeHtml(i18n.noDrinksDesc));
                    }
                }

                if (storesList) {
                    if (data.popular_stores && data.popular_stores.length > 0) {
                        storesList.innerHTML = data.popular_stores.map((s, idx) => `
                            <div class="flex items-center justify-between p-2.5 bg-surface-container-low rounded border border-outline-variant/60">
                                <div class="flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-full bg-secondary/10 text-secondary font-bold flex items-center justify-center text-[10px]">${idx + 1}</span>
                                    <span class="font-bold text-on-surface">${escapeHtml(s.restaurant)}</span>
                                </div>
                                <span class="font-mono font-bold text-on-surface">${escapeHtml(s.orders)} (${money(s.spending)})</span>
                            </div>
                        `).join('');
                    } else {
                        storesList.innerHTML = emptyStateHtml('storefront', escapeHtml(i18n.noStoresTitle), escapeHtml(i18n.noStoresDesc));
                    }
                }
            }

            // Render Debts Tab
            if (tabId === 'debts' || tabId === 'all') {
                if (debtsList) {
                    if (data.debts_by_user && data.debts_by_user.length > 0) {
                        const columns = [
                            { label: i18n.member, width: '26%' },
                            { label: i18n.debtCount, align: 'center', width: '11%' },
                            { label: i18n.totalOriginal, align: 'right', width: '16%' },
                            { label: i18n.totalPaid, align: 'right', width: '16%' },
                            { label: i18n.totalRemaining, align: 'right', width: '16%' },
                            { label: i18n.status, align: 'center', width: '15%' },
                        ];
                        const rows = data.debts_by_user.map((u) => {
                            const remaining = Number(u.outstanding_debt || 0);
                            const isCleared = remaining <= 0;
                            return [
                                memberCellHtml(u.user_name, u.user_email),
                                `<span class="font-mono font-semibold text-on-surface">${escapeHtml(u.debt_count)}</span>`,
                                `<span class="font-mono font-semibold text-outline">${money(u.total_original)}</span>`,
                                `<span class="font-mono font-semibold text-emerald-600">${money(u.total_paid)}</span>`,
                                `<span class="font-mono font-bold ${isCleared ? 'text-outline' : 'text-error'}">${money(remaining)}</span>`,
                                isCleared
                                    ? `<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20"><span class="material-symbols-outlined text-[12px]">check_circle</span>${escapeHtml(i18n.statusCleared)}</span>`
                                    : `<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20"><span class="material-symbols-outlined text-[12px]">schedule</span>${escapeHtml(i18n.statusOwing)}</span>`,
                            ];
                        });
                        debtsList.innerHTML = reportTableHtml(columns, rows);
                    } else {
                        debtsList.innerHTML = emptyStateHtml('check_circle', escapeHtml(i18n.noDebtsTitle), escapeHtml(i18n.noDebtsDesc));
                    }
                }
            }

            // Render Sponsors Tab
            if (tabId === 'sponsors' || tabId === 'all') {
                if (sponsorsList) {
                    if (data.sponsors_leaderboard && data.sponsors_leaderboard.length > 0) {
                        const columns = [
                            { label: i18n.rank, align: 'center', width: '10%' },
                            { label: i18n.sponsor, width: '42%' },
                            { label: i18n.sponsoredOrders, align: 'center', width: '20%' },
                            { label: i18n.totalSponsored, align: 'right', width: '28%' },
                        ];
                        const rankClass = (idx) => idx === 0 ? 'bg-amber-400 text-amber-950 shadow-xs'
                            : idx === 1 ? 'bg-slate-300 text-slate-800'
                            : idx === 2 ? 'bg-amber-700/20 text-amber-800'
                            : 'bg-surface-container text-outline';
                        const rows = data.sponsors_leaderboard.map((s, idx) => [
                            `<span class="w-6 h-6 rounded-full inline-flex items-center justify-center font-bold text-xs ${rankClass(idx)}">${idx + 1}</span>`,
                            memberCellHtml(s.user_name, s.user_email),
                            `<span class="font-mono font-semibold text-on-surface">${escapeHtml(s.sponsored_campaigns)}</span>`,
                            `<span class="font-mono font-bold text-emerald-600">${money(s.total_sponsored)}</span>`,
                        ]);
                        sponsorsList.innerHTML = reportTableHtml(columns, rows);
                    } else {
                        sponsorsList.innerHTML = emptyStateHtml('volunteer_activism', escapeHtml(i18n.noSponsorsTitle), escapeHtml(i18n.noSponsorsDesc));
                    }
                }
            }

            // Render Users Tab
            if (tabId === 'users' || tabId === 'all') {
                if (usersList) {
                    if (data.top_users && data.top_users.length > 0) {
                        const columns = [
                            { label: '#', align: 'center', width: '10%' },
                            { label: i18n.member, width: '42%' },
                            { label: i18n.ordersPlaced, align: 'center', width: '20%' },
                            { label: i18n.totalSpent, align: 'right', width: '28%' },
                        ];
                        const rows = data.top_users.map((u, idx) => [
                            `<span class="font-mono font-bold text-outline">${idx + 1}</span>`,
                            memberCellHtml(u.user_name, u.user_email),
                            `<span class="font-mono font-semibold text-on-surface">${escapeHtml(u.order_count)}</span>`,
                            `<span class="font-mono font-bold text-primary">${money(u.total_spent)}</span>`,
                        ]);
                        usersList.innerHTML = reportTableHtml(columns, rows);
                    } else {
                        usersList.innerHTML = emptyStateHtml('group', escapeHtml(i18n.noUsersTitle), escapeHtml(i18n.noUsersDesc));
                    }
                }
            }

            loadedTabCache.add(cacheKey);
        } catch(e) {
            console.error('Error loading tab report:', e);
        }
    };

    window.loadReportData = async function() {
        // Clear cache on full reload or date range change
        loadedTabCache.clear();
        await window.loadTabReportData(currentActiveTab, true);
    };

    window.exportReportCSV = function() {
        const queryParams = new URLSearchParams();
        const dateFrom = dateRangePicker?.querySelector('.date-from-hidden')?.value || '';
        const dateTo = dateRangePicker?.querySelector('.date-to-hidden')?.value || '';
        if (dateFrom) queryParams.set('date_from', dateFrom);
        if (dateTo) queryParams.set('date_to', dateTo);
        const queryString = queryParams.toString() ? `?${queryParams.toString()}` : '';

        window.location.assign(`/admin/${roomSlug}/reports/export${queryString}`);
    };

    document.addEventListener('admin:daterange-change', () => {
        window.loadReportData();
    });
}


/**
 * Admin Dashboard Live Monitor & Realtime Controller
 */
export function initAdminDashboard() {
    const dashboardEl = document.querySelector('[data-admin-dashboard]');
    if (!dashboardEl) return;

    const dashboardUrl = dashboardEl.dataset.dashboardUrl || '';
    const socketTokenUrl = dashboardEl.dataset.tokenUrl || '';
    const realtimeUrl = dashboardEl.dataset.realtimeUrl || 'http://localhost:3001';
    const closeUrlTemplate = dashboardEl.dataset.closeUrlTemplate || '';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const money = v => new Intl.NumberFormat('vi-VN').format(Number(v || 0)) + ' ₫';
    const esc = v => String(v ?? '').replace(/[&<>'"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[c]));
    let timerInterval = null;
    let activeCampaignId = null;

    function renderCountdown(deadline) {
        if (timerInterval) clearInterval(timerInterval);
        const timerEl = document.querySelector('#hero-timer');
        const closingTextEl = document.querySelector('#metric-campaign-closing-text');

        if (!deadline) {
            if (timerEl) timerEl.textContent = '--:--:--';
            if (closingTextEl) closingTextEl.textContent = 'No deadline';
            return;
        }

        const tick = () => {
            const diff = Math.max(0, Math.floor((new Date(deadline) - Date.now()) / 1000));
            const hours = String(Math.floor(diff / 3600)).padStart(2, '0');
            const mins = String(Math.floor((diff % 3600) / 60)).padStart(2, '0');
            const secs = String(diff % 60).padStart(2, '0');
            const timeStr = `${hours}:${mins}:${secs}`;
            if (timerEl) timerEl.textContent = timeStr;
            if (closingTextEl) closingTextEl.textContent = `${mins}m ${secs}s`;
            if (diff <= 0 && timerInterval) clearInterval(timerInterval);
        };

        tick();
        timerInterval = setInterval(tick, 1000);
    }

    function renderTrendChart(weeklyTrend, totalCampaigns, totalSpending) {
        const wrapper = document.querySelector('#svg-chart-wrapper');
        const labelsContainer = document.querySelector('#chart-day-labels');
        if (!wrapper) return;

        if (!weeklyTrend || !weeklyTrend.length) {
            wrapper.innerHTML = `<div class="h-full flex items-center justify-center text-outline text-xs">No data</div>`;
            return;
        }

        const badge = document.querySelector('#chart-summary-badge');
        if (badge) {
            badge.textContent = `${totalCampaigns || 0} campaigns · ${money(totalSpending)}`;
        }

        const maxC = 6;
        const maxS = Math.max(...weeklyTrend.map(d => d.spending_amount || d.spending || 0), 4000000);

        const width = 700;
        const height = 210;
        const topPad = 20;
        const bottomPad = 180;
        const leftPad = 45;
        const rightPad = 655;
        const usableWidth = rightPad - leftPad;
        const stepX = usableWidth / Math.max(weeklyTrend.length - 1, 1);

        const points = [];
        let barsHtml = '';
        let peakInfo = null;

        weeklyTrend.forEach((item, idx) => {
            const cx = leftPad + (idx * stepX);
            const barW = 32;
            const count = item.campaigns_count || item.count || 0;
            const spend = item.spending_amount || item.spending || 0;
            const barH = (count / maxC) * (bottomPad - topPad);
            const barY = bottomPad - barH;

            barsHtml += `
                <g class="group cursor-pointer">
                    <rect x="${cx - (barW / 2)}" y="${barY}" width="${barW}" height="${barH}" rx="3" fill="#006948" class="hover:opacity-80 transition-opacity"></rect>
                    <text x="${cx}" y="${barY - 6}" text-anchor="middle" font-size="10" font-family="JetBrains Mono" font-weight="bold" fill="#006948">${count}</text>
                </g>
            `;

            const spendingY = bottomPad - ((spend / maxS) * (bottomPad - topPad));
            points.push({ x: cx, y: spendingY, amount: spend, day: item.day_name || item.day });

            if (item.is_peak) {
                peakInfo = { x: cx, y: spendingY, amount: spend, day: item.day_name || item.day };
            }
        });

        const linePath = points.map((p, i) => `${i === 0 ? 'M' : 'L'} ${p.x} ${p.y}`).join(' ');
        const areaPath = points.length ? `${linePath} L ${points[points.length - 1].x} ${bottomPad} L ${points[0].x} ${bottomPad} Z` : '';

        let peakMarker = '';
        if (peakInfo) {
            peakMarker = `
                <g class="peak-marker">
                    <circle cx="${peakInfo.x}" cy="${peakInfo.y}" r="6" fill="#006948" stroke="#ffffff" stroke-width="2"></circle>
                    <rect x="${peakInfo.x - 65}" y="${Math.max(5, peakInfo.y - 32)}" width="130" height="22" rx="4" fill="#002114" opacity="0.9"></rect>
                    <text x="${peakInfo.x}" y="${Math.max(20, peakInfo.y - 18)}" text-anchor="middle" font-size="10" font-weight="bold" font-family="JetBrains Mono" fill="#68dba9">Peak: ${esc(peakInfo.day)}</text>
                </g>
            `;
        }

        wrapper.innerHTML = `
            <svg viewBox="0 0 ${width} ${height}" class="w-full h-full overflow-visible">
                <defs>
                    <linearGradient id="spendingGrad" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#006948" stop-opacity="0.25"/>
                        <stop offset="100%" stop-color="#006948" stop-opacity="0.0"/>
                    </linearGradient>
                </defs>
                <line x1="${leftPad}" y1="${bottomPad}" x2="${rightPad}" y2="${bottomPad}" stroke="currentColor" class="text-outline-variant/60" stroke-width="1"></line>
                <line x1="${leftPad}" y1="${topPad}" x2="${rightPad}" y2="${topPad}" stroke="currentColor" class="text-outline-variant/30" stroke-width="1" stroke-dasharray="3 3"></line>
                <line x1="${leftPad}" y1="${(topPad + bottomPad)/2}" x2="${rightPad}" y2="${(topPad + bottomPad)/2}" stroke="currentColor" class="text-outline-variant/30" stroke-width="1" stroke-dasharray="3 3"></line>

                ${barsHtml}

                <path d="${areaPath}" fill="url(#spendingGrad)"></path>
                <path d="${linePath}" fill="none" stroke="#006948" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"></path>

                ${points.map(p => `
                    <circle cx="${p.x}" cy="${p.y}" r="4" fill="#ffffff" stroke="#006948" stroke-width="2" class="hover:r-6 transition-all cursor-pointer">
                        <title>${p.day}: ${money(p.amount)}</title>
                    </circle>
                `).join('')}

                ${peakMarker}
            </svg>
        `;

        if (labelsContainer) {
            labelsContainer.innerHTML = weeklyTrend.map(d => `
                <div class="text-center">
                    <span class="font-bold text-on-surface">${esc(d.day_name || d.day)}</span>
                    <span class="block text-[10px] font-mono text-outline">${esc(d.date || '')}</span>
                </div>
            `).join('');
        }
    }

    function renderDashboard(data) {
        if (!data) return;

        // KPI
        const activeRooms = document.querySelector('#metric-active-rooms');
        if (activeRooms) activeRooms.textContent = data.active_rooms || 1;

        const roomsHint = document.querySelector('#metric-rooms-hint');
        if (roomsHint) roomsHint.textContent = `${data.total_members_across_rooms || data.active_room_users || 0} members`;

        const liveCmp = document.querySelector('#metric-live-campaigns');
        if (liveCmp) liveCmp.textContent = data.active_campaigns || 0;

        const ordersToday = document.querySelector('#metric-orders-today');
        if (ordersToday) ordersToday.textContent = data.orders_today || 0;

        const ordersGrowth = document.querySelector('#metric-orders-growth-val');
        if (ordersGrowth) ordersGrowth.textContent = `${data.orders_growth_percent >= 0 ? '+' : ''}${data.orders_growth_percent || 0}%`;

        const totalValue = document.querySelector('#metric-total-value');
        if (totalValue) totalValue.textContent = money(data.today_total_value);

        const sponsorsVal = document.querySelector('#metric-sponsors-val');
        if (sponsorsVal) sponsorsVal.textContent = money(data.today_sponsor_value);

        const unpaidDebt = document.querySelector('#metric-unpaid-debt');
        if (unpaidDebt) unpaidDebt.textContent = money(data.outstanding_debts);

        const debtUsers = document.querySelector('#metric-debt-users');
        if (debtUsers) debtUsers.textContent = `${data.pending_debt_users_count || 0} users`;

        // Nav Badge
        const navBadge = document.querySelector('#nav-live-badge');
        if (navBadge) {
            if (data.active_campaigns > 0) {
                navBadge.textContent = `${data.active_campaigns} Live`;
                navBadge.classList.remove('hidden');
            } else {
                navBadge.classList.add('hidden');
            }
        }

        // Weekly Trend Chart
        renderTrendChart(data.weekly_trend, data.weekly_total_campaigns, data.weekly_total_spending);

        // Campaign Control Panels
        const campaignPanels = document.querySelector('#campaign-panels-container');
        const hero = data.active_campaign;
        const sec = data.secondary_campaign;

        if (hero) {
            if (campaignPanels) campaignPanels.classList.remove('hidden');
            const heroCard = document.querySelector('#hero-campaign-card');
            const secCard = document.querySelector('#secondary-campaign-card');

            if (heroCard) {
                heroCard.classList.remove('hidden');
                if (sec) {
                    heroCard.classList.remove('lg:col-span-3');
                    heroCard.classList.add('lg:col-span-2');
                } else {
                    heroCard.classList.remove('lg:col-span-2');
                    heroCard.classList.add('lg:col-span-3');
                }
            }

            activeCampaignId = hero.id;
            const heroTitle = document.querySelector('#hero-campaign-title');
            const heroCode = document.querySelector('#hero-campaign-code');
            const heroTime = document.querySelector('#hero-campaign-time');
            const heroNet = document.querySelector('#hero-net-payable');
            const heroGross = document.querySelector('#hero-gross-subtotal');
            const heroSponsor = document.querySelector('#hero-sponsor-total');
            const heroPartText = document.querySelector('#hero-participation-text');
            const heroBar = document.querySelector('#hero-progress-bar');
            const heroSponsorNames = document.querySelector('#hero-sponsor-names');

            if (heroTitle) heroTitle.textContent = hero.name || 'Live Campaign';
            if (heroCode) heroCode.textContent = hero.code || `#CMP-${hero.id}`;
            if (heroTime) heroTime.textContent = hero.started_at ? `Mở lúc: ${new Date(hero.started_at).toLocaleTimeString('vi-VN', {hour: '2-digit', minute: '2-digit'})}` : 'Hôm nay';

            const gross = Number(hero.total_amount || 0);
            const sponsor = Number(hero.sponsor_total || 0);
            const net = Math.max(0, gross - sponsor);

            if (heroNet) heroNet.textContent = money(net);
            if (heroGross) heroGross.textContent = `Gross: ${money(gross)}`;
            if (heroSponsor) heroSponsor.textContent = `-${money(sponsor)}`;

            const participants = data.active_campaign_participants || hero.orders_count || 0;
            const totalMembers = data.active_room_users || 1;
            const percent = data.participation_percent || Math.min(100, Math.round((participants / totalMembers) * 100));

            if (heroPartText) heroPartText.textContent = `${participants} / ${totalMembers} (${percent}%)`;
            if (heroBar) heroBar.style.width = `${percent}%`;

            renderCountdown(hero.deadline);

            const modalCampaignName = document.querySelector('#modal-campaign-name');
            const modalMembersCount = document.querySelector('#modal-members-count');
            const modalSubtotalVal = document.querySelector('#modal-subtotal-val');
            const modalTitle = document.querySelector('#modal-title');

            if (modalCampaignName) modalCampaignName.textContent = hero.name;
            if (modalMembersCount) modalMembersCount.textContent = `${participants} orders`;
            if (modalSubtotalVal) modalSubtotalVal.textContent = money(gross);
            if (modalTitle) modalTitle.textContent = `#${hero.code || hero.id}`;

            if (heroSponsorNames) {
                heroSponsorNames.innerHTML = `
                    <span>Sponsor: <strong>${money(sponsor)}</strong></span>
                `;
            }

            if (sec && secCard) {
                secCard.classList.remove('hidden', 'opacity-50');
                const secCode = document.querySelector('#sec-campaign-code');
                const secTitle = document.querySelector('#sec-campaign-title');
                const secVendor = document.querySelector('#sec-campaign-vendor');
                const secDeadline = document.querySelector('#sec-campaign-deadline');
                const secOrders = document.querySelector('#sec-campaign-orders');
                const secSubtotal = document.querySelector('#sec-campaign-subtotal');

                if (secCode) secCode.textContent = sec.code || `#CMP-${sec.id}`;
                if (secTitle) secTitle.textContent = sec.name || 'Secondary';
                if (secVendor) secVendor.textContent = `Quán: ${sec.restaurant || 'Phúc Long'}`;
                if (secDeadline) secDeadline.textContent = sec.deadline ? new Date(sec.deadline).toLocaleTimeString('vi-VN', {hour: '2-digit', minute: '2-digit'}) : 'Hôm nay';
                if (secOrders) secOrders.textContent = `${sec.orders_count || 0}`;
                if (secSubtotal) secSubtotal.textContent = money(sec.total_amount);
            } else if (secCard) {
                secCard.classList.add('hidden');
            }
        } else {
            if (campaignPanels) campaignPanels.classList.add('hidden');
        }

        // Payment account
        const acc = (data.payment_accounts || [])[0];
        if (acc) {
            const pBank = document.querySelector('#payment-bank-name');
            const pMasked = document.querySelector('#payment-account-masked');
            if (pBank) pBank.textContent = `${acc.bank_name || acc.bank_code} (${acc.account_name || 'Quỹ phòng'})`;
            if (pMasked) pMasked.textContent = acc.account_number_masked || '•••• •••• ••••';
        }

        // Orders Table & Section Visibility
        const ordersSection = document.querySelector('#recent-orders-section');
        const ordersStreamContainer = document.querySelector('#orders-stream-container');
        const sideStreamContainer = document.querySelector('#side-stream-container');
        const tbody = document.querySelector('#orders-tbody');
        const orders = data.recent_orders || [];

        if (orders && orders.length > 0) {
            if (ordersSection) ordersSection.classList.remove('hidden');
            if (ordersStreamContainer) {
                ordersStreamContainer.classList.add('xl:grid-cols-[1.6fr_.9fr]');
            }
            if (sideStreamContainer) {
                sideStreamContainer.classList.remove('grid', 'grid-cols-1', 'md:grid-cols-2', 'gap-4', 'space-y-0');
                sideStreamContainer.classList.add('space-y-4');
            }
            if (tbody) {
                tbody.innerHTML = orders.map(o => `
                    <tr class="hover:bg-surface-container-low/50 transition-colors">
                        <td class="py-2.5 px-3 font-mono text-outline">#${o.id}</td>
                        <td class="py-2.5 px-3 font-semibold text-on-surface">${esc(o.room_user?.global_user?.name || o.room_user?.display_name || 'Member')}</td>
                        <td class="py-2.5 px-3 text-outline">${esc(o.campaign?.name || 'Campaign')}</td>
                        <td class="py-2.5 px-3 text-right font-mono font-bold text-on-surface">${money(o.final_amount)}</td>
                        <td class="py-2.5 px-3 text-center">
                            <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-mono font-semibold ${o.status === 'confirmed' ? 'bg-emerald-50 text-emerald-700' : (o.status === 'cancelled' ? 'bg-rose-50 text-rose-700' : 'bg-blue-50 text-blue-700')}">
                                ${esc(o.status)}
                            </span>
                        </td>
                    </tr>
                `).join('');
            }
        } else {
            if (ordersSection) ordersSection.classList.add('hidden');
            if (ordersStreamContainer) {
                ordersStreamContainer.classList.remove('xl:grid-cols-[1.6fr_.9fr]');
            }
            if (sideStreamContainer) {
                sideStreamContainer.classList.remove('space-y-4');
                sideStreamContainer.classList.add('grid', 'grid-cols-1', 'md:grid-cols-2', 'gap-4', 'space-y-0');
            }
            if (tbody) {
                tbody.innerHTML = '';
            }
        }
    }

    async function loadDashboard() {
        if (!dashboardUrl) return;
        try {
            const res = await fetch(dashboardUrl, { headers: { Accept: 'application/json' } });
            if (!res.ok) throw new Error('Failed to load dashboard data');
            const json = await res.json();
            renderDashboard(json.data || {});
        } catch (e) {
            console.error('Dashboard load error', e);
        }
    }

    loadDashboard();

    // Close Modal Controls
    const modal = document.querySelector('#close-campaign-modal');
    const btnOpen = document.querySelector('#btn-open-close-modal');
    const btnCancel = document.querySelector('#btn-cancel-modal');
    const btnCloseIcon = document.querySelector('#btn-close-modal-icon');
    const backdrop = document.querySelector('#modal-backdrop');
    const btnConfirm = document.querySelector('#btn-confirm-close');

    function openModal() {
        modal?.classList.remove('hidden');
        modal?.classList.add('flex');
    }
    function closeModal() {
        modal?.classList.remove('flex');
        modal?.classList.add('hidden');
    }

    btnOpen?.addEventListener('click', openModal);
    btnCancel?.addEventListener('click', closeModal);
    btnCloseIcon?.addEventListener('click', closeModal);
    backdrop?.addEventListener('click', closeModal);

    btnConfirm?.addEventListener('click', async () => {
        if (!activeCampaignId) return;
        btnConfirm.disabled = true;
        btnConfirm.textContent = 'Closing...';

        const url = closeUrlTemplate.replace(':id', activeCampaignId);
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ allow_debt: true })
            });

            if (res.ok) {
                closeModal();
                loadDashboard();
            } else {
                const err = await res.json().catch(() => ({}));
                alert(err.message || 'Error closing campaign');
            }
        } catch (err) {
            alert('Error closing campaign');
        } finally {
            btnConfirm.disabled = false;
            btnConfirm.innerHTML = `<span class="material-symbols-outlined text-[16px]">lock</span><span>Confirm</span>`;
        }
    });

    // Socket.IO Realtime
    if (socketTokenUrl) {
        fetch(socketTokenUrl, { headers: { Accept: 'application/json' } })
            .then(r => r.json())
            .then(({ data }) => {
                if (!window.io) return;
                const socket = window.io(realtimeUrl, {
                    auth: { token: data.token },
                    transports: ['websocket', 'polling']
                });

                socket.on('connect', () => {
                    const stateEl = document.querySelector('#socket-state');
                    if (stateEl) {
                        stateEl.innerHTML = '<span class="w-2 h-2 rounded-full bg-emerald-500"></span><span>Socket Live</span>';
                        stateEl.classList.remove('text-outline');
                        stateEl.classList.add('text-emerald-700', 'bg-emerald-50', 'border-emerald-200');
                    }
                });

                [
                    'order.created', 'order.updated', 'order.deleted',
                    'campaign.created', 'campaign.updated', 'campaign.deleted', 'campaign.closed',
                    'campaign.menu.updated', 'campaign.menu.deleted', 'campaign.participant.declined', 'campaign.participant.rejoined'
                ].forEach(ev => {
                    socket.on(ev, () => {
                        const stream = document.querySelector('#activity-stream');
                        if (stream) {
                            stream.insertAdjacentHTML('afterbegin', `
                                <div class="p-2.5 rounded bg-emerald-50 border border-emerald-200 text-xs flex items-center justify-between">
                                    <span class="text-emerald-800 font-semibold">${esc(ev)}</span>
                                    <span class="text-[10px] font-mono text-emerald-600">${new Date().toLocaleTimeString('vi-VN')}</span>
                                </div>
                            `);
                        }
                        loadDashboard();
                    });
                });
            })
            .catch(() => {
                const stateEl = document.querySelector('#socket-state');
                if (stateEl) stateEl.innerHTML = '<span class="w-2 h-2 rounded-full bg-slate-300"></span><span>Socket Offline</span>';
            });
    }
}

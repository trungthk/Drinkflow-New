/**
 * Admin Reports & Financial Analytics Controller
 */
export function initAdminReports() {
    const dateRangePicker = document.querySelector('#report-date-range');
    const roomSlug = document.querySelector('[data-room-slug]')?.dataset.roomSlug || window.__DF_ROOM_SLUG__ || '';
    const money = v => new Intl.NumberFormat('vi-VN').format(Number(v || 0)) + ' ₫';

    if (!dateRangePicker && !document.querySelector('.rtab')) return;

    window.switchReportTab = function(tabId) {
        document.querySelectorAll('.report-panel').forEach(p => p.classList.add('hidden'));
        document.querySelectorAll('.rtab').forEach(b => {
            b.classList.remove('border-primary', 'text-primary', 'font-bold');
            b.classList.add('border-transparent', 'text-outline');
        });

        document.querySelector(`#panel-${tabId}`)?.classList.remove('hidden');
        const activeBtn = document.querySelector(`#rtab-${tabId}`);
        activeBtn?.classList.add('border-primary', 'text-primary', 'font-bold');
        activeBtn?.classList.remove('border-transparent', 'text-outline');
    };

    window.loadReportData = async function() {
        const dateFrom = dateRangePicker?.querySelector('.date-from-hidden')?.value || '';
        const dateTo = dateRangePicker?.querySelector('.date-to-hidden')?.value || '';

        const drinksList = document.querySelector('#top-drinks-list');
        const storesList = document.querySelector('#top-stores-list');

        if (drinksList) {
            drinksList.innerHTML = `
                <div class="space-y-2 animate-pulse">
                    <div class="h-10 bg-slate-200 dark:bg-slate-800 rounded"></div>
                    <div class="h-10 bg-slate-200 dark:bg-slate-800 rounded"></div>
                    <div class="h-10 bg-slate-200 dark:bg-slate-800 rounded"></div>
                </div>
            `;
        }
        if (storesList) {
            storesList.innerHTML = `
                <div class="space-y-2 animate-pulse">
                    <div class="h-10 bg-slate-200 dark:bg-slate-800 rounded"></div>
                    <div class="h-10 bg-slate-200 dark:bg-slate-800 rounded"></div>
                    <div class="h-10 bg-slate-200 dark:bg-slate-800 rounded"></div>
                </div>
            `;
        }

        try {
            const queryParams = new URLSearchParams();
            if (dateFrom) queryParams.set('date_from', dateFrom);
            if (dateTo) queryParams.set('date_to', dateTo);
            const queryString = queryParams.toString() ? `?${queryParams.toString()}` : '';

            const res = await fetch(`/admin/${roomSlug}/reports${queryString}`, {
                headers: { 'Accept': 'application/json' }
            });
            const { data } = await res.json();
            if (!data) return;

            const kpiCmp = document.querySelector('#kpi-campaigns');
            const kpiOrd = document.querySelector('#kpi-orders');
            const kpiSpd = document.querySelector('#kpi-spending');
            const kpiSpon = document.querySelector('#kpi-sponsor');
            const sponSub = document.querySelector('#sponsor-subtotal-text');

            if (kpiCmp) kpiCmp.textContent = data.campaign_count || 0;
            if (kpiOrd) kpiOrd.textContent = data.order_count || 0;
            if (kpiSpd) kpiSpd.textContent = money(data.spending);
            if (kpiSpon) kpiSpon.textContent = money(data.sponsor_amount);
            if (sponSub) sponSub.textContent = money(data.sponsor_amount);

            // Popular drinks
            if (drinksList) {
                if (data.popular_drinks && data.popular_drinks.length > 0) {
                    drinksList.innerHTML = data.popular_drinks.map((d, idx) => `
                        <div class="flex items-center justify-between p-2.5 bg-surface-container-low rounded border border-outline-variant/60">
                            <div class="flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full bg-primary/10 text-primary font-bold flex items-center justify-center text-[10px]">${idx + 1}</span>
                                <span class="font-bold text-on-surface">${d.item_name}</span>
                            </div>
                            <span class="font-mono font-bold text-primary">${d.quantity}</span>
                        </div>
                    `).join('');
                } else {
                    drinksList.innerHTML = '<div class="py-6 text-center text-outline">Chưa có dữ liệu</div>';
                }
            }

            // Popular stores
            if (storesList) {
                if (data.popular_stores && data.popular_stores.length > 0) {
                    storesList.innerHTML = data.popular_stores.map((s, idx) => `
                        <div class="flex items-center justify-between p-2.5 bg-surface-container-low rounded border border-outline-variant/60">
                            <div class="flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full bg-secondary/10 text-secondary font-bold flex items-center justify-center text-[10px]">${idx + 1}</span>
                                <span class="font-bold text-on-surface">${s.restaurant}</span>
                            </div>
                            <span class="font-mono font-bold text-on-surface">${s.orders} (${money(s.spending)})</span>
                        </div>
                    `).join('');
                } else {
                    storesList.innerHTML = '<div class="py-6 text-center text-outline">Chưa có dữ liệu</div>';
                }
            }
        } catch(e) {
            console.error('Error loading report:', e);
        }
    };

    window.exportReportCSV = function() {
        alert('Exporting report statement...');
    };

    document.addEventListener('admin:daterange-change', () => {
        window.loadReportData();
    });

    window.loadReportData();
}


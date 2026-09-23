import { formatMoney } from '../shared/money';

const COLOR_ITEMS = '#006948';
const COLOR_VALUE = '#2563eb';

const esc = v => String(v ?? '').replace(/[&<>'"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[c]));

/** Round a value up to a "nice" axis maximum (1, 2, 5 x 10^n). */
function niceMax(value) {
    if (value <= 0) return 1;
    const pow = Math.pow(10, Math.floor(Math.log10(value)));
    const n = value / pow;
    return (n <= 1 ? 1 : n <= 2 ? 2 : n <= 5 ? 5 : 10) * pow;
}

function parseJsonAttr(el, attr, fallback) {
    try {
        const raw = el.dataset[attr];
        return raw ? JSON.parse(raw) : fallback;
    } catch (e) {
        return fallback;
    }
}

/** Render the "Top nhà tài trợ" ranking as a vertical bar chart with a hover tooltip. */
function renderTopSponsorsChart(container, sponsors, labels) {
    if (!container) return;

    if (!sponsors || !sponsors.length) {
        container.innerHTML = `
            <div class="h-full min-h-[200px] flex flex-col items-center justify-center text-center gap-2 text-slate-400">
                <span class="material-symbols-outlined text-[26px] text-slate-300" aria-hidden="true">volunteer_activism</span>
                <p class="text-xs">${esc(labels.noSponsorData)}</p>
            </div>
        `;
        return;
    }

    const width = 400;
    const height = 220;
    const topPad = 26;
    const bottomPad = 170;
    const leftPad = 8;
    const rightPad = 392;
    const barGap = 18;
    const barW = Math.min(52, (rightPad - leftPad - barGap * (sponsors.length - 1)) / sponsors.length);
    const maxAmount = niceMax(Math.max(...sponsors.map(s => Number(s.amount || 0)), 1));

    const totalBarsWidth = sponsors.length * barW + (sponsors.length - 1) * barGap;
    const startX = leftPad + Math.max(0, (rightPad - leftPad - totalBarsWidth) / 2);

    const points = sponsors.map((s, idx) => ({
        x: startX + idx * (barW + barGap),
        amount: Number(s.amount || 0),
    }));

    const barsHtml = points.map((p, idx) => {
        const barH = Math.max(2, (p.amount / maxAmount) * (bottomPad - topPad));
        const barY = bottomPad - barH;
        const name = sponsors[idx].name || '';
        const shortName = name.length > 10 ? name.slice(0, 9) + '…' : name;
        return `
            <g data-sponsor-idx="${idx}" class="cursor-pointer">
                <rect x="${p.x}" y="${barY}" width="${barW}" height="${barH}" rx="4" fill="${COLOR_ITEMS}" opacity="0.85" class="transition-opacity hover:opacity-100"></rect>
                <text x="${p.x + barW / 2}" y="${barY - 6}" text-anchor="middle" font-size="10" font-family="Inter, sans-serif" font-weight="bold" fill="${COLOR_ITEMS}">${esc(formatMoney(p.amount))}</text>
                <text x="${p.x + barW / 2}" y="${bottomPad + 16}" text-anchor="middle" font-size="10" font-family="Inter, sans-serif" fill="#64748b">${esc(shortName)}</text>
                <rect x="${p.x - barGap / 2}" y="0" width="${barW + barGap}" height="${height}" fill="transparent" class="pointer-events-auto"></rect>
            </g>
        `;
    }).join('');

    container.innerHTML = `
        <div class="relative w-full h-full min-h-[200px]">
            <svg viewBox="0 0 ${width} ${height}" class="w-full h-full overflow-visible">
                <line x1="${leftPad}" y1="${bottomPad}" x2="${rightPad}" y2="${bottomPad}" stroke="currentColor" class="text-slate-200"></line>
                ${barsHtml}
            </svg>
            <div data-sponsor-tooltip class="pointer-events-none absolute z-20 hidden min-w-[170px] rounded-lg border border-slate-200 bg-white px-3 py-2 text-[11px] shadow-lg"></div>
        </div>
    `;

    const svg = container.querySelector('svg');
    const tooltip = container.querySelector('[data-sponsor-tooltip]');
    if (!svg || !tooltip) return;

    const show = idx => {
        const s = sponsors[idx];
        tooltip.innerHTML = `
            <div class="font-semibold text-slate-900 mb-1">${esc(s.name)}</div>
            <div class="flex items-center justify-between gap-4">
                <span class="text-slate-500">${esc(labels.value)}</span>
                <strong class="font-mono" style="color:${COLOR_ITEMS}">${esc(formatMoney(s.amount))}</strong>
            </div>
            <div class="mt-0.5 text-slate-400">${esc((labels.sponsoredOrders || ':count').replace(':count', String(s.sponsored_orders || 0)))}</div>
        `;
        tooltip.classList.remove('hidden');

        const target = svg.querySelector(`[data-sponsor-idx="${idx}"] rect`);
        const box = container.getBoundingClientRect();
        const targetBox = target.getBoundingClientRect();
        const tipW = tooltip.offsetWidth;
        let left = targetBox.left - box.left + targetBox.width / 2 - tipW / 2;
        left = Math.max(0, Math.min(left, box.width - tipW));
        tooltip.style.left = `${left}px`;
        tooltip.style.top = `${Math.max(0, targetBox.top - box.top - tooltip.offsetHeight - 8)}px`;
    };

    svg.querySelectorAll('[data-sponsor-idx]').forEach(group => {
        const idx = Number(group.dataset.sponsorIdx);
        group.addEventListener('mouseenter', () => show(idx));
    });
    container.addEventListener('mouseleave', () => tooltip.classList.add('hidden'));
}

/** Render the 7-day combo chart: bars for item count, a line for order value. */
function renderWeeklyTrendChart(container, trend, labels) {
    if (!container) return;

    const rows = (trend || []).map(item => ({
        day: item.day_name || '',
        date: item.date || '',
        items: Number(item.items_count || 0),
        value: Number(item.value_amount || 0),
    }));

    if (!rows.length || rows.every(r => r.items === 0 && r.value === 0)) {
        container.innerHTML = `
            <div class="h-full min-h-[200px] flex flex-col items-center justify-center text-center gap-2 text-slate-400">
                <span class="material-symbols-outlined text-[26px] text-slate-300" aria-hidden="true">bar_chart</span>
                <p class="text-xs">${esc(labels.noTrendData)}</p>
            </div>
        `;
        return;
    }

    const maxItems = Math.max(4, Math.ceil(Math.max(...rows.map(r => r.items)) / 2) * 2);
    const maxValue = niceMax(Math.max(...rows.map(r => r.value), 1));

    const width = 700;
    const height = 220;
    const topPad = 20;
    const bottomPad = 165;
    const leftPad = 34;
    const rightPad = 666;
    const stepX = (rightPad - leftPad) / Math.max(rows.length - 1, 1);
    const colW = rows.length > 1 ? stepX : rightPad - leftPad;
    const barW = 26;

    const points = rows.map((r, idx) => ({
        x: leftPad + idx * stepX,
        y: bottomPad - (r.value / maxValue) * (bottomPad - topPad),
    }));

    const barsHtml = rows.map((r, idx) => {
        const barH = (r.items / maxItems) * (bottomPad - topPad);
        const barY = bottomPad - barH;
        return `<rect x="${points[idx].x - barW / 2}" y="${barY}" width="${barW}" height="${barH}" rx="3" fill="${COLOR_ITEMS}" opacity="0.85"></rect>`;
    }).join('');

    const linePath = points.map((p, i) => `${i === 0 ? 'M' : 'L'} ${p.x} ${p.y}`).join(' ');

    const gridHtml = [topPad, bottomPad].map((y, i) => `
        <line x1="${leftPad}" y1="${y}" x2="${rightPad}" y2="${y}" stroke="currentColor" class="${i === 1 ? 'text-slate-200' : 'text-slate-100'}" stroke-width="1"></line>
    `).join('');

    const hitHtml = rows.map((r, idx) => `
        <g data-day-idx="${idx}" class="cursor-pointer">
            <rect x="${points[idx].x - colW / 2}" y="0" width="${colW}" height="${height}" fill="${COLOR_VALUE}" class="opacity-0 hover:opacity-[0.06] transition-opacity"></rect>
            <circle cx="${points[idx].x}" cy="${points[idx].y}" r="4" fill="#ffffff" stroke="${COLOR_VALUE}" stroke-width="2" class="pointer-events-none"></circle>
        </g>
    `).join('');

    container.innerHTML = `
        <div class="relative w-full h-full min-h-[200px]">
            <svg viewBox="0 0 ${width} ${height}" class="w-full h-full overflow-visible">
                ${gridHtml}
                ${barsHtml}
                <path d="${linePath}" fill="none" stroke="${COLOR_VALUE}" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="pointer-events-none"></path>
                ${hitHtml}
            </svg>
            <div data-trend-tooltip class="pointer-events-none absolute z-20 hidden min-w-[180px] rounded-lg border border-slate-200 bg-white px-3 py-2 text-[11px] shadow-lg"></div>
            <div class="mt-1 flex justify-between px-1">
                ${rows.map(r => `<span class="text-[10px] font-semibold text-slate-500" style="flex:1;text-align:center">${esc(r.day)}</span>`).join('')}
            </div>
        </div>
    `;

    const svg = container.querySelector('svg');
    const tooltip = container.querySelector('[data-trend-tooltip]');
    if (!svg || !tooltip) return;

    const show = idx => {
        const r = rows[idx];
        tooltip.innerHTML = `
            <div class="mb-1 flex items-center justify-between gap-3 border-b border-slate-100 pb-1">
                <span class="font-semibold text-slate-900">${esc(r.day)}</span>
                <span class="font-mono text-slate-400">${esc(r.date)}</span>
            </div>
            <div class="flex items-center justify-between gap-4">
                <span class="flex items-center gap-1.5 text-slate-500"><span class="h-2 w-2 rounded-sm" style="background:${COLOR_ITEMS}"></span>${esc(labels.items)}</span>
                <strong class="font-mono text-slate-900">${r.items}</strong>
            </div>
            <div class="mt-1 flex items-center justify-between gap-4">
                <span class="flex items-center gap-1.5 text-slate-500"><span class="h-2 w-2 rounded-full" style="background:${COLOR_VALUE}"></span>${esc(labels.value)}</span>
                <strong class="font-mono" style="color:${COLOR_VALUE}">${esc(formatMoney(r.value))}</strong>
            </div>
        `;
        tooltip.classList.remove('hidden');

        const ctm = svg.getScreenCTM();
        if (!ctm) return;
        const pt = svg.createSVGPoint();
        pt.x = points[idx].x;
        pt.y = points[idx].y;
        const screen = pt.matrixTransform(ctm);
        const box = container.getBoundingClientRect();
        const tipW = tooltip.offsetWidth;
        const tipH = tooltip.offsetHeight;
        let left = screen.x - box.left - tipW / 2;
        left = Math.max(0, Math.min(left, box.width - tipW));
        let top = screen.y - box.top - tipH - 14;
        if (top < 0) top = screen.y - box.top + 14;
        tooltip.style.left = `${left}px`;
        tooltip.style.top = `${top}px`;
    };

    svg.querySelectorAll('[data-day-idx]').forEach(group => {
        const idx = Number(group.dataset.dayIdx);
        group.addEventListener('mouseenter', () => show(idx));
    });
    container.addEventListener('mouseleave', () => tooltip.classList.add('hidden'));
}

/** Initialize the room dashboard's sponsor ranking and 7-day trend charts, if present on the page. */
export function initRoomDashboardCharts() {
    const root = document.querySelector('[data-room-dashboard-charts]');
    if (!root) return;

    const topSponsors = parseJsonAttr(root, 'topSponsors', []);
    const weeklyTrend = parseJsonAttr(root, 'weeklyTrend', []);
    const labels = parseJsonAttr(root, 'chartLabels', {});

    renderTopSponsorsChart(document.querySelector('#top-sponsors-chart'), topSponsors, labels);
    renderWeeklyTrendChart(document.querySelector('#weekly-trend-chart'), weeklyTrend, labels);
}

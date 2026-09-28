import { formatMoney } from '../shared/money';
import { setLoadingOverlay } from '../shared/loading-overlay';

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

// Categorical slots in fixed order (validated: adjacent CVD ΔE >= 9.1, normal-vision ΔE >= 19.6 on white).
// The leaderboard holds at most 5 sponsors; slot i always belongs to rank i.
const SPONSOR_COLORS = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4'];

/** Render the "Top nhà tài trợ" ranking as a donut chart with a legend and a hover tooltip. */
function renderTopSponsorsChart(container, sponsors, labels) {
    if (!container) return;

    const rows = (sponsors || [])
        .map((s, idx) => ({
            name: s.name || '',
            amount: Number(s.amount || 0),
            campaigns: Number(s.sponsored_campaigns || 0),
            color: SPONSOR_COLORS[idx % SPONSOR_COLORS.length],
        }))
        .filter(r => r.amount > 0);
    const total = rows.reduce((sum, r) => sum + r.amount, 0);

    if (!rows.length || total <= 0) {
        container.innerHTML = `
            <div class="h-full min-h-[200px] flex flex-col items-center justify-center text-center gap-2 text-slate-400">
                <span class="material-symbols-outlined text-[26px] text-slate-300" aria-hidden="true">volunteer_activism</span>
                <p class="text-xs">${esc(labels.noSponsorData)}</p>
            </div>
        `;
        return;
    }

    const size = 180;
    const center = size / 2;
    const radius = 70;
    const thickness = 26;
    const circumference = 2 * Math.PI * radius;
    // 2px surface gap between adjacent slices (none when a single sponsor fills the ring).
    const gap = rows.length > 1 ? 2 : 0;
    const percent = amount => Math.round((amount / total) * 1000) / 10;

    let offset = 0;
    const slicesHtml = rows.map((r, idx) => {
        const length = (r.amount / total) * circumference;
        const visible = Math.max(0.5, length - gap);
        // Start at 12 o'clock and go clockwise.
        const html = `
            <circle data-sponsor-idx="${idx}" cx="${center}" cy="${center}" r="${radius}" fill="none"
                stroke="${r.color}" stroke-width="${thickness}"
                stroke-dasharray="${visible} ${circumference - visible}"
                stroke-dashoffset="${-offset}"
                transform="rotate(-90 ${center} ${center})"
                class="cursor-pointer transition-[stroke-width] duration-150 hover:[stroke-width:30px]"></circle>
        `;
        offset += length;
        return html;
    }).join('');

    const legendHtml = rows.map((r, idx) => `
        <li data-sponsor-idx="${idx}" class="flex items-center gap-2 rounded-md px-1.5 py-1 cursor-default hover:bg-slate-50">
            <span class="h-2.5 w-2.5 shrink-0 rounded-sm" style="background:${r.color}" aria-hidden="true"></span>
            <span class="min-w-0 flex-1 truncate text-[11px] text-slate-700" title="${esc(r.name)}">${esc(r.name)}</span>
            <span class="shrink-0 font-mono text-[11px] font-semibold text-slate-900">${esc(formatMoney(r.amount))}</span>
            <span class="w-11 shrink-0 text-right font-mono text-[10px] text-slate-400">${percent(r.amount)}%</span>
        </li>
    `).join('');

    container.innerHTML = `
        <div class="relative flex h-full min-h-[200px] flex-col items-center justify-center gap-4 sm:flex-row">
            <div class="relative h-[170px] w-[170px] shrink-0">
                <svg viewBox="0 0 ${size} ${size}" class="h-full w-full" role="img" aria-label="${esc(labels.sponsorTotal)}: ${esc(formatMoney(total))}">
                    ${slicesHtml}
                </svg>
                <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center text-center">
                    <span class="text-[10px] text-slate-500">${esc(labels.sponsorTotal)}</span>
                    <strong class="font-mono text-sm text-slate-900">${esc(formatMoney(total))}</strong>
                </div>
            </div>
            <ul class="w-full min-w-0 flex-1 space-y-0.5">${legendHtml}</ul>
            <div data-sponsor-tooltip class="pointer-events-none absolute z-20 hidden min-w-[170px] rounded-lg border border-slate-200 bg-white px-3 py-2 text-[11px] shadow-lg"></div>
        </div>
    `;

    const wrapper = container.firstElementChild;
    const tooltip = container.querySelector('[data-sponsor-tooltip]');
    if (!wrapper || !tooltip) return;

    const show = (idx, event) => {
        const r = rows[idx];
        tooltip.innerHTML = `
            <div class="mb-1 flex items-center gap-1.5 font-semibold text-slate-900">
                <span class="h-2 w-2 shrink-0 rounded-sm" style="background:${r.color}"></span>${esc(r.name)}
            </div>
            <div class="flex items-center justify-between gap-4">
                <span class="text-slate-500">${esc(labels.value)}</span>
                <strong class="font-mono text-slate-900">${esc(formatMoney(r.amount))} <span class="font-normal text-slate-400">(${percent(r.amount)}%)</span></strong>
            </div>
            <div class="mt-0.5 text-slate-400">${esc((labels.sponsoredCampaigns || ':count').replace(':count', String(r.campaigns)))}</div>
        `;
        tooltip.classList.remove('hidden');

        const box = wrapper.getBoundingClientRect();
        const tipW = tooltip.offsetWidth;
        const tipH = tooltip.offsetHeight;
        let left = event.clientX - box.left - tipW / 2;
        left = Math.max(0, Math.min(left, box.width - tipW));
        let top = event.clientY - box.top - tipH - 12;
        if (top < 0) top = event.clientY - box.top + 16;
        tooltip.style.left = `${left}px`;
        tooltip.style.top = `${top}px`;
    };

    wrapper.querySelectorAll('[data-sponsor-idx]').forEach(el => {
        const idx = Number(el.dataset.sponsorIdx);
        el.addEventListener('mousemove', event => show(idx, event));
        el.addEventListener('mouseleave', () => tooltip.classList.add('hidden'));
    });
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
        <div class="relative flex w-full h-full min-h-[200px] flex-col">
            <!-- flex-1 (not h-full) so the day labels below stay inside the card instead of overflowing it. -->
            <svg viewBox="0 0 ${width} ${height}" class="w-full min-h-0 flex-1 overflow-visible">
                ${gridHtml}
                ${barsHtml}
                <path d="${linePath}" fill="none" stroke="${COLOR_VALUE}" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="pointer-events-none"></path>
                ${hitHtml}
            </svg>
            <div data-trend-tooltip class="pointer-events-none absolute z-20 hidden min-w-[180px] rounded-lg border border-slate-200 bg-white px-3 py-2 text-[11px] shadow-lg"></div>
            <div class="mt-1 flex justify-between px-1 pb-4">
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

    try {
        renderTopSponsorsChart(document.querySelector('#top-sponsors-chart'), topSponsors, labels);
        renderWeeklyTrendChart(document.querySelector('#weekly-trend-chart'), weeklyTrend, labels);
    } finally {
        // The overlays are shown by the server until the charts have been drawn.
        root.querySelectorAll('[data-chart-loading]').forEach((overlay) => setLoadingOverlay(overlay, false));
    }
}

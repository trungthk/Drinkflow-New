@extends('admin.layout')

@section('title', 'Tổng quan Room')
@section('active', 'dashboard')

@section('content')
<div class="superadmin-heading">
    <div>
        <p class="superadmin-eyebrow">Enterprise Hub · Room Scope v2.4 · Realtime Active</p>
        <h1>Tổng quan {{ $room->name }}</h1>
        <p>Quản lý tập trung các đợt order nước uống, đồng tiền chia bill tự động và tiến độ chiến dịch phòng ban.</p>
    </div>
    <div class="superadmin-actions">
        <a class="sa-button secondary" href="{{ route('admin.manage.page', [$room, 'tab' => 'reports']) }}"><span class="material-symbols-outlined">download</span>Xem báo cáo</a>
        <a class="sa-button" href="{{ route('admin.manage.page', [$room, 'tab' => 'campaigns']) }}"><span class="material-symbols-outlined">bolt</span>Tạo đơn khẩn cấp</a>
    </div>
</div>

<section id="active-campaign" class="sa-card mb-4 overflow-hidden bg-[#0b1c30] text-white">
    <div class="flex flex-col gap-5 p-6 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <div class="mb-3 flex flex-wrap items-center gap-2"><span class="status-pill status-active">● CHIẾN DỊCH ĐANG MỞ</span><span class="rounded bg-[#334155] px-2 py-1 text-[10px] font-bold" id="campaign-code">Room scope</span></div>
            <h2 id="campaign-name" class="text-2xl font-bold tracking-tight">Đang tải chiến dịch...</h2>
            <p id="campaign-description" class="mt-1 max-w-2xl text-sm text-slate-300">Đồng bộ dữ liệu campaign và trạng thái gom đơn.</p>
            <div class="mt-5 flex flex-wrap gap-6 text-xs text-slate-300"><span>Thành viên đặt <strong id="campaign-members" class="text-white">—</strong></span><span>Tạm tính giỏ hàng <strong id="campaign-total" class="text-white">—</strong></span><span>Sponsor Fund <strong id="campaign-sponsor" class="text-emerald-300">—</strong></span></div>
        </div>
        <div class="min-w-[220px] rounded-xl bg-[#213145] p-4 text-center"><p class="text-[10px] uppercase tracking-wider text-slate-300">Trạng thái realtime</p><strong id="campaign-status" class="mt-1 block text-xl text-emerald-300">Đang tải</strong><a class="mt-3 inline-flex w-full items-center justify-center rounded-lg bg-[#00875a] px-3 py-2 text-xs font-bold text-white no-underline" href="{{ route('admin.manage.page', [$room, 'tab' => 'orders']) }}"><span class="material-symbols-outlined mr-1 text-[16px]">receipt_long</span>Xem Live Orders</a></div>
    </div>
</section>

<div id="cards" class="sa-grid kpis"></div>

<div class="sa-split mt-5">
    <section class="sa-card sa-section">
        <div class="sa-section-header"><div><h2>Mục tiêu gom đơn phòng ban</h2><p>Tiến độ campaign đang hoạt động trong Room.</p></div><a class="text-[11px] font-bold text-[#00875a] no-underline" href="{{ route('admin.manage.page', [$room, 'tab' => 'campaigns']) }}">Xem campaigns →</a></div>
        <div class="rounded-xl bg-[#eff4ff] p-4"><div class="flex items-center justify-between text-[11px]"><span>Đã gom <strong id="goal-orders">—</strong></span><span>Mục tiêu <strong id="goal-target">—</strong></span></div><div class="mt-3 h-2 overflow-hidden rounded-full bg-[#dce9ff]"><div id="goal-progress" class="h-full rounded-full bg-[#00875a]" style="width:0%"></div></div><div class="mt-3 flex justify-between text-[10px] text-slate-500"><span>Socket realtime tự động cập nhật</span><span id="last-refresh">Đang tải...</span></div></div>
        <div class="sa-section-header mt-6"><div><h2>Campaign gần đây</h2><p>Danh sách hoạt động mới nhất của Room.</p></div></div>
        <div id="campaigns" class="sa-table-wrap"></div>
    </section>
    <div class="space-y-4">
        <section class="sa-card sa-section"><div class="sa-section-header"><div><h2>Đối soát &amp; VietQR</h2><p>Trạng thái tài khoản nhận tiền theo Room.</p></div><span class="status-pill status-healthy">● Verified</span></div><div class="rounded-xl bg-[#eff4ff] p-4"><p class="text-[10px] uppercase tracking-wider text-slate-500">Tài khoản thụ hưởng phòng</p><strong id="payment-account" class="mt-1 block text-sm">Đang kiểm tra...</strong><p id="payment-status" class="mt-2 text-[11px] text-slate-500">Tự động đối soát VietQR theo bill</p></div><a class="sa-button mt-4 w-full justify-center" href="{{ route('admin.manage.page', [$room, 'tab' => 'payments']) }}"><span class="material-symbols-outlined">qr_code_2</span>Quản lý VietQR</a></section>
        <section class="sa-card sa-section"><div class="sa-section-header"><div><h2>Hoạt động tức thì</h2><p>Luồng dữ liệu từ Socket.IO.</p></div><span class="status-pill status-active">Room Stream</span></div><div id="activity" class="sa-health-list"><div class="sa-health-row"><div><strong>Đang kết nối realtime</strong><small>Chờ event order và campaign.</small></div><span class="status-dot"></span></div></div></section>
    </div>
</div>

<section class="sa-card sa-section mt-5">
    <div class="sa-section-header"><div><h2>Đơn hàng gần đây</h2><p>Danh sách order được cập nhật tự động khi có event realtime.</p></div><a class="sa-button secondary" href="{{ route('admin.manage.page', [$room, 'tab' => 'orders']) }}">Mở quản lý đơn</a></div>
    <div id="orders" class="sa-table-wrap"></div>
</section>
@endsection

@push('scripts')
<script src="{{ rtrim(config('services.realtime.public_url', 'http://localhost:3001'), '/') }}/socket.io/socket.io.js"></script>
<script>
const roomId = @json($room->id);
const dashboardUrl = @json(route('admin.dashboard', $room));
const socketTokenUrl = @json(route('admin.socket-token', $room));
const realtimeUrl = @json(rtrim(config('services.realtime.public_url', 'http://localhost:3001'), '/'));
const money = n => new Intl.NumberFormat('vi-VN').format(Number(n || 0)) + ' ₫';
const escapeHtml = value => String(value ?? '').replace(/[&<>'\"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[char]));
const tableSkeleton = (columns = 5) => `<div class="animate-pulse space-y-3"><div class="grid gap-3 rounded-xl bg-[#eff4ff] p-3" style="grid-template-columns:repeat(${columns}, minmax(0, 1fr))">${Array.from({length: columns}, () => '<span class="h-3 rounded bg-[#dce9ff]"></span>').join('')}</div>${Array.from({length: 4}, () => `<div class="grid gap-3 border-b border-slate-100 px-3 py-4" style="grid-template-columns:repeat(${columns}, minmax(0, 1fr))">${Array.from({length: columns}, () => '<span class="h-3 rounded bg-slate-100"></span>').join('')}</div>`).join('')}</div>`;
const setRefresh = () => document.querySelector('#last-refresh').textContent = 'Cập nhật ' + new Date().toLocaleTimeString('vi-VN');
const statusPill = status => `<span class="status-pill ${['active','completed','paid','enabled'].includes(status) ? 'status-active' : ['blocked','cancelled','failed'].includes(status) ? 'status-blocked' : 'status-pending'}">${escapeHtml(status)}</span>`;
function render(data) {
    const cards = [['Campaign active', data.active_campaigns, 'status-active'], ['Orders hôm nay', data.orders_today, ''], ['Công nợ còn lại', money(data.outstanding_debts), 'status-blocked'], ['Room users active', data.active_room_users, ''], ['Đã sponsor', money(data.total_sponsored), 'status-active'], ['Tổng chi', money(data.total_spending), ''], ['Payment pending', data.payment_pending, 'status-pending']];
    document.querySelector('#cards').innerHTML = cards.map(([label, value, tone]) => `<article class="sa-card sa-kpi"><span class="label">${escapeHtml(label)}</span><strong class="value ${tone === 'status-blocked' ? 'text-red-700' : tone === 'status-active' ? 'text-[#00875a]' : ''}">${escapeHtml(value)}</strong><span class="hint ${tone === 'status-active' ? 'good' : ''}">Room scope isolation</span></article>`).join('');
    const active = data.last_campaign || (data.active_campaigns_list || data.campaigns || [])[0];
    if (active) { document.querySelector('#campaign-name').textContent = active.name || 'Campaign đang mở'; document.querySelector('#campaign-code').textContent = active.code || `#CAM-${active.id}`; document.querySelector('#campaign-description').textContent = active.description || active.restaurant || 'Campaign gom đơn đang hoạt động.'; document.querySelector('#campaign-status').textContent = active.status || 'active'; document.querySelector('#campaign-members').textContent = active.orders_count || data.orders_today || 0; document.querySelector('#campaign-total').textContent = money(active.total_amount); document.querySelector('#campaign-sponsor').textContent = money(active.sponsor_amount); }
    const goal = Number(active?.orders_target || active?.target_orders || 0); const current = Number(active?.orders_count || data.orders_today || 0); document.querySelector('#goal-orders').textContent = current; document.querySelector('#goal-target').textContent = goal || '—'; document.querySelector('#goal-progress').style.width = goal ? `${Math.min(100, current / goal * 100)}%` : '0%';
    document.querySelector('#orders').innerHTML = `<table class="sa-table"><thead><tr><th>#</th><th>Thành viên</th><th>Campaign</th><th>Thực thu</th><th>Trạng thái</th></tr></thead><tbody>${(data.recent_orders || []).map(order => `<tr><td>#${escapeHtml(order.id)}</td><td><strong>${escapeHtml(order.room_user?.global_user?.name || order.room_user?.display_name)}</strong></td><td>${escapeHtml(order.campaign?.name)}</td><td><strong>${money(order.final_amount)}</strong></td><td>${statusPill(order.status)}</td></tr>`).join('') || '<tr><td class="sa-empty" colspan="5">Chưa có đơn hàng.</td></tr>'}</tbody></table>`;
    const campaignRows = data.campaigns || data.active_campaigns_list || (data.last_campaign ? [data.last_campaign] : []);
    document.querySelector('#campaigns').innerHTML = `<table class="sa-table"><thead><tr><th>Campaign</th><th>Nhà hàng</th><th>Số đơn</th><th>Status</th></tr></thead><tbody>${campaignRows.slice(0, 4).map(c => `<tr><td><strong>${escapeHtml(c.name)}</strong></td><td>${escapeHtml(c.restaurant)}</td><td>${escapeHtml(c.orders_count || 0)}</td><td>${statusPill(c.status)}</td></tr>`).join('') || '<tr><td class="sa-empty" colspan="4">Chưa có campaign.</td></tr>'}</tbody></table>`;
    const account = (data.payment_accounts || [])[0]; if (account) { document.querySelector('#payment-account').textContent = `${account.bank_name || account.bank_code} · ${account.account_number_masked || ''}`; document.querySelector('#payment-status').textContent = account.status || 'configured'; }
    setRefresh();
}
async function refresh() { const response = await fetch(dashboardUrl, {headers: {'Accept': 'application/json'}}); if (!response.ok) throw new Error('dashboard'); render((await response.json()).data || {}); }
document.querySelector('#campaigns').innerHTML = tableSkeleton(4);
document.querySelector('#orders').innerHTML = tableSkeleton(5);
refresh().catch(() => { document.querySelector('#cards').innerHTML = '<p class="sa-notice is-visible error">Không tải được dashboard.</p>'; });
fetch(socketTokenUrl, {headers: {'Accept': 'application/json'}}).then(response => response.json()).then(({data}) => { if (!window.io) throw new Error('socket-client'); const socket = window.io(realtimeUrl, {auth: {token: data.token}, transports: ['websocket', 'polling']}); socket.on('connect', () => { document.querySelector('#socket-state').innerHTML = '<span class="status-dot"></span>Socket Connected'; }); socket.on('disconnect', () => { document.querySelector('#socket-state').innerHTML = '<span class="status-dot"></span>Socket Disconnected'; }); socket.on('connect_error', () => { document.querySelector('#socket-state').innerHTML = '<span class="status-dot"></span>Socket Unavailable'; }); ['order.created', 'order.updated', 'order.deleted', 'campaign.created', 'campaign.closed'].forEach(event => socket.on(event, payload => { document.querySelector('#activity').insertAdjacentHTML('afterbegin', `<div class="sa-health-row"><div><strong>${escapeHtml(event)}</strong><small>Event vừa nhận từ Room realtime.</small></div><small>${new Date().toLocaleTimeString('vi-VN')}</small></div>`); refresh(); })); }).catch(() => document.querySelector('#socket-state').innerHTML = '<span class="status-dot"></span>Socket Unavailable');
</script>
@endpush

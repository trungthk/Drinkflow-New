@extends('admin.layout')

@section('title', 'Quản lý Room')
@section('active', request('tab', 'campaigns'))

@section('content')
<div class="superadmin-heading">
    <div>
        <p class="superadmin-eyebrow">Quản trị Room / Workspace Operations</p>
        <h1>Quản lý {{ $room->name }}</h1>
        <p>Điều hành campaign, đơn gom realtime, công nợ, thành viên, tài khoản VietQR và nhật ký audit trong cùng một workspace.</p>
    </div>
    <div class="superadmin-actions"><a class="sa-button secondary" href="{{ route('admin.dashboard.page', $room) }}"><span class="material-symbols-outlined">space_dashboard</span>Tổng quan Room</a></div>
</div>
<div id="notice" class="sa-notice"></div>
<div class="sa-card mb-4 flex flex-wrap items-center gap-2 p-3">
    <span class="mr-2 text-[10px] font-bold uppercase tracking-[.12em] text-slate-500">Phân hệ</span>
    <nav class="flex flex-wrap gap-2">
        <button type="button" data-tab="campaigns" class="tab rounded-full bg-orange-600 px-4 py-2 text-sm font-semibold text-white">Chiến dịch &amp; Menu</button>
        <button type="button" data-tab="orders" class="tab rounded-full bg-white px-4 py-2 text-sm ring-1 ring-slate-200">Đơn gom Realtime</button>
        <button type="button" data-tab="debts" class="tab rounded-full bg-white px-4 py-2 text-sm ring-1 ring-slate-200">Công nợ</button>
        <button type="button" data-tab="users" class="tab rounded-full bg-white px-4 py-2 text-sm ring-1 ring-slate-200">Thành viên Room</button>
        <button type="button" data-tab="payments" class="tab rounded-full bg-white px-4 py-2 text-sm ring-1 ring-slate-200">Tài khoản VietQR</button>
        <button type="button" data-tab="settings" class="tab rounded-full bg-white px-4 py-2 text-sm ring-1 ring-slate-200">Cấu hình Room</button>
        <button type="button" data-tab="notifications" class="tab rounded-full bg-white px-4 py-2 text-sm ring-1 ring-slate-200">Kênh thông báo</button>
        <button type="button" data-tab="reports" class="tab rounded-full bg-white px-4 py-2 text-sm ring-1 ring-slate-200">Báo cáo</button>
        <button type="button" data-tab="audit" class="tab rounded-full bg-white px-4 py-2 text-sm ring-1 ring-slate-200">Audit Log</button>
    </nav>
</div>
<section id="panel" class="sa-card sa-section"></section>
@endsection

@push('scripts')
<script>
const urls = @json([
    'campaigns' => route('admin.campaigns.index', $room),
    'campaignStore' => route('admin.campaigns.store', $room),
    'orders' => route('admin.orders.index', $room),
    'debts' => route('admin.debts.index', $room),
    'users' => route('admin.room-users.index', $room),
    'payments' => route('admin.payment-accounts.index', $room),
    'paymentStore' => route('admin.payment-accounts.store', $room),
    'settings' => route('admin.settings.show', $room),
    'reports' => route('admin.reports.index', $room),
    'notifications' => route('admin.notification-channels.index', $room),
    'notificationStore' => route('admin.notification-channels.store', $room),
    'audit' => route('admin.audit.index', $room),
]);
const panel = document.querySelector('#panel');
const notice = document.querySelector('#notice');
const esc = value => String(value ?? '').replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));
const money = value => new Intl.NumberFormat('vi-VN').format(Number(value || 0)) + ' ₫';
const showNotice = (message, error = false) => { notice.textContent = message; notice.className = `mb-4 rounded-xl px-4 py-3 text-sm ${error ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700'}`; };
async function api(url, options = {}) {
    const response = await fetch(url, {headers: {'Accept':'application/json', 'Content-Type':'application/json', ...(options.headers || {})}, ...options});
    const body = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(body.message || Object.values(body.errors || {}).flat().join(' ') || `HTTP ${response.status}`);
    return body;
}
async function mutate(url, method = 'POST', body = null) {
    const result = await api(url, {method, ...(body ? {body: JSON.stringify(body)} : {})});
    showNotice('Đã cập nhật thành công.');
    return result;
}
function tabHeader(title, description = '') { return `<div class="mb-5"><h2 class="text-xl font-bold">${title}</h2><p class="mt-1 text-sm text-slate-500">${description}</p></div>`; }
function button(label, action, tone = 'slate') { const colors = {slate:'bg-slate-600', emerald:'bg-emerald-600', blue:'bg-blue-600', orange:'bg-orange-600', amber:'bg-amber-600', red:'bg-red-600'}; return `<button type="button" data-action="${action}" class="rounded-lg ${colors[tone] || colors.slate} px-3 py-1.5 text-xs font-semibold text-white">${label}</button>`; }
function skeletonTable(columns = 5) { return `<div class="animate-pulse space-y-3"><div class="grid gap-3 rounded-xl bg-slate-100 p-4" style="grid-template-columns:repeat(${Math.min(columns, 5)}, minmax(0, 1fr))">${Array.from({length: columns}, () => '<span class="h-3 rounded bg-slate-200"></span>').join('')}</div>${Array.from({length: 5}, () => `<div class="grid gap-3 border-b border-slate-100 px-3 py-4" style="grid-template-columns:repeat(${Math.min(columns, 5)}, minmax(0, 1fr))">${Array.from({length: columns}, () => '<span class="h-3 rounded bg-slate-100"></span>').join('')}</div>`).join('')}</div>`; }
async function submitWithState(event, task) { event.preventDefault(); const submit = event.submitter; const original = submit?.innerHTML; if (submit) { submit.disabled = true; submit.classList.add('cursor-wait', 'opacity-70'); submit.innerHTML = '<span class="material-symbols-outlined mr-1 inline-block animate-spin align-middle text-[15px]">progress_activity</span>Đang xử lý...'; } try { await task(); } catch (error) { showNotice(error.message, true); } finally { if (submit?.isConnected) { submit.disabled = false; submit.classList.remove('cursor-wait', 'opacity-70'); submit.innerHTML = original; } } }

async function campaigns() {
    const result = await api(urls.campaigns); const rows = result.data.data || [];
    panel.innerHTML = tabHeader('Campaigns', 'Tạo và điều khiển vòng đời campaign trong Room.') + `<form id="campaign-form" class="mb-6 grid gap-3 rounded-xl bg-slate-50 p-4 md:grid-cols-5"><input name="name" required placeholder="Tên campaign" class="rounded-lg border p-2 text-sm"><input name="restaurant" required placeholder="Nhà hàng" class="rounded-lg border p-2 text-sm"><input name="deadline" type="datetime-local" class="rounded-lg border p-2 text-sm"><input name="sponsor_name" placeholder="Sponsor" class="rounded-lg border p-2 text-sm"><button type="submit" class="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white">Tạo campaign</button></form><div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead><tr class="border-b text-slate-500"><th class="p-3">Name</th><th class="p-3">Restaurant</th><th class="p-3">Status</th><th class="p-3">Orders</th><th class="p-3">Actions</th></tr></thead><tbody>${rows.map(c => `<tr class="border-b"><td class="p-3 font-semibold">${esc(c.name)}</td><td class="p-3">${esc(c.restaurant)}</td><td class="p-3">${esc(c.status)}</td><td class="p-3">${esc(c.orders_count)}</td><td class="flex flex-wrap gap-2 p-3">${c.status === 'draft' || c.status === 'scheduled' ? button('Activate', `activate:${c.id}`, 'emerald') : ''}${['active','closing'].includes(c.status) ? button('Close', `close:${c.id}`, 'blue') : ''}${!['closed','archived'].includes(c.status) ? button('Archive', `archive:${c.id}`, 'slate') : ''}${button('Duplicate', `duplicate:${c.id}`, 'orange')}</td></tr>`).join('') || '<tr><td class="p-4 text-slate-500" colspan="5">Chưa có campaign.</td></tr>'}</tbody></table></div>`;
    document.querySelector('#campaign-form').onsubmit = event => submitWithState(event, async () => { const data = Object.fromEntries(new FormData(event.target)); await mutate(urls.campaignStore, 'POST', data); event.target.reset(); await campaigns(); });
}
async function orders() {
    const result = await api(urls.orders); const rows = result.data.data || [];
    panel.innerHTML = tabHeader('Orders', 'Theo dõi đơn theo Room và thực hiện cancel, delete, unlock.') + `<div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead><tr class="border-b text-slate-500"><th class="p-3">#</th><th class="p-3">User</th><th class="p-3">Campaign</th><th class="p-3">Amount</th><th class="p-3">Status</th><th class="p-3">Actions</th></tr></thead><tbody>${rows.map(o => `<tr class="border-b"><td class="p-3">${esc(o.id)}</td><td class="p-3">${esc(o.room_user?.global_user?.name || o.room_user?.display_name)}</td><td class="p-3">${esc(o.campaign?.name)}</td><td class="p-3">${money(o.final_amount)}</td><td class="p-3">${esc(o.status)}</td><td class="flex flex-wrap gap-2 p-3">${['completed','cancelled'].includes(o.status) ? '' : button('Cancel', `order-cancel:${o.id}`, 'amber')}${o.status !== 'completed' ? button('Delete', `order-delete:${o.id}`, 'red') : ''}${['submitted','confirmed','ordering'].includes(o.status) ? button('Unlock', `order-unlock:${o.id}`, 'slate') : ''}</td></tr>`).join('') || '<tr><td class="p-4 text-slate-500" colspan="6">Chưa có order.</td></tr>'}</tbody></table></div>`;
}
async function debts() {
    const result = await api(urls.debts); const rows = result.data.data || [];
    panel.innerHTML = tabHeader('Debts', 'Xem số tiền phải thu và cập nhật trạng thái thanh toán.') + `<div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead><tr class="border-b text-slate-500"><th class="p-3">User</th><th class="p-3">Campaign</th><th class="p-3">Original</th><th class="p-3">Remaining</th><th class="p-3">Status</th><th class="p-3">Actions</th></tr></thead><tbody>${rows.map(d => `<tr class="border-b"><td class="p-3">${esc(d.room_user?.global_user?.name || d.room_user?.display_name)}</td><td class="p-3">${esc(d.campaign?.name)}</td><td class="p-3">${money(d.original_amount)}</td><td class="p-3 font-semibold">${money(d.remaining_amount)}</td><td class="p-3">${esc(d.status)}</td><td class="p-3">${d.status !== 'paid' ? button('Mark paid', `debt-paid:${d.id}`, 'emerald') : button('Mark unpaid', `debt-unpaid:${d.id}`, 'slate')}</td></tr>`).join('') || '<tr><td class="p-4 text-slate-500" colspan="6">Chưa có debt.</td></tr>'}</tbody></table></div>`;
}
async function users() {
    const result = await api(urls.users); const rows = result.data.data || [];
    panel.innerHTML = tabHeader('Room Users', 'Block/unblock membership trong Room; Global User không bị thay đổi.') + `<div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead><tr class="border-b text-slate-500"><th class="p-3">User</th><th class="p-3">Code</th><th class="p-3">Orders</th><th class="p-3">Debts</th><th class="p-3">Status</th><th class="p-3">Actions</th></tr></thead><tbody>${rows.map(u => `<tr class="border-b"><td class="p-3">${esc(u.global_user?.name || u.display_name)}<br><span class="text-xs text-slate-500">${esc(u.global_user?.email)}</span></td><td class="p-3">${esc(u.user_code)}</td><td class="p-3">${esc(u.orders_count)}</td><td class="p-3">${esc(u.debts_count)}</td><td class="p-3">${esc(u.status)}</td><td class="p-3">${button(u.status === 'blocked' ? 'Unblock' : 'Block', `user-${u.status === 'blocked' ? 'active' : 'blocked'}:${u.id}`, u.status === 'blocked' ? 'emerald' : 'red')}</td></tr>`).join('') || '<tr><td class="p-4 text-slate-500" colspan="6">Chưa có room user.</td></tr>'}</tbody></table></div>`;
}
async function payments() {
    const result = await api(urls.payments); const rows = result.data.data || [];
    panel.innerHTML = tabHeader('Payment Accounts', 'Tài khoản chỉ hiển thị dạng mask và được giới hạn theo Room.') + `<form id="payment-form" class="mb-6 grid gap-3 rounded-xl bg-slate-50 p-4 md:grid-cols-5"><input name="bank_code" required placeholder="Bank code" class="rounded-lg border p-2 text-sm"><input name="bank_name" required placeholder="Bank name" class="rounded-lg border p-2 text-sm"><input name="account_number" required placeholder="Account number" class="rounded-lg border p-2 text-sm"><input name="account_name" required placeholder="Account name" class="rounded-lg border p-2 text-sm"><button type="submit" class="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white">Add account</button></form><div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead><tr class="border-b text-slate-500"><th class="p-3">Bank</th><th class="p-3">Account</th><th class="p-3">Name</th><th class="p-3">Status</th></tr></thead><tbody>${rows.map(a => `<tr class="border-b"><td class="p-3">${esc(a.bank_code)} · ${esc(a.bank_name)}</td><td class="p-3">${esc(a.account_number_masked || a.account_number)}</td><td class="p-3">${esc(a.account_name)}</td><td class="p-3">${esc(a.status)}${a.is_default ? ' · default' : ''}</td></tr>`).join('') || '<tr><td class="p-4 text-slate-500" colspan="4">Chưa có payment account.</td></tr>'}</tbody></table></div>`;
    document.querySelector('#payment-form').onsubmit = event => submitWithState(event, async () => { await mutate(urls.paymentStore, 'POST', Object.fromEntries(new FormData(event.target))); event.target.reset(); await payments(); });
}
async function settings() {
    const result = await api(urls.settings); const data = result.data || {};
    panel.innerHTML = tabHeader('Room Settings', 'Cấu hình tên, mô tả, ngôn ngữ và timezone cho Room.') + `<form id="settings-form" class="grid max-w-2xl gap-4"><label class="text-sm font-semibold">Name<input name="name" value="${esc(data.name)}" required class="mt-1 w-full rounded-lg border p-2 font-normal"></label><label class="text-sm font-semibold">Description<textarea name="description" class="mt-1 w-full rounded-lg border p-2 font-normal">${esc(data.description)}</textarea></label><label class="text-sm font-semibold">Language<select name="language" class="mt-1 w-full rounded-lg border p-2 font-normal"><option ${data.language === 'vi' ? 'selected' : ''}>vi</option><option ${data.language === 'en' ? 'selected' : ''}>en</option><option ${data.language === 'ja' ? 'selected' : ''}>ja</option></select></label><label class="text-sm font-semibold">Timezone<input name="timezone" value="${esc(data.timezone || 'Asia/Ho_Chi_Minh')}" class="mt-1 w-full rounded-lg border p-2 font-normal"></label><button type="submit" class="w-fit rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white">Save settings</button></form>`;
    document.querySelector('#settings-form').onsubmit = event => submitWithState(event, async () => { await mutate(urls.settings, 'PATCH', Object.fromEntries(new FormData(event.target))); });
}
async function notifications() {
    const result = await api(urls.notifications); const rows = result.data || [];
    panel.innerHTML = tabHeader('Notification channels', 'Credential chỉ lưu encrypted và giao diện chỉ hiển thị trạng thái configured.') + `<form id="notification-form" class="mb-6 grid gap-3 rounded-xl bg-slate-50 p-4 md:grid-cols-4"><select name="type" class="rounded-lg border p-2 text-sm"><option>slack</option><option>telegram</option><option>chatwork</option><option>webhook</option></select><input name="name" required placeholder="Tên channel" class="rounded-lg border p-2 text-sm"><input name="config_json" required placeholder='{"webhook":"https://..."}' class="rounded-lg border p-2 text-sm"><button type="submit" class="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white">Configure</button></form><div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead><tr class="border-b text-slate-500"><th class="p-3">Type</th><th class="p-3">Name</th><th class="p-3">Status</th><th class="p-3">Credential</th><th class="p-3">Actions</th></tr></thead><tbody>${rows.map(c => `<tr class="border-b"><td class="p-3">${esc(c.type)}</td><td class="p-3">${esc(c.name)}</td><td class="p-3">${esc(c.status)}</td><td class="p-3">${c.configured ? 'Configured · ••••••••••' : 'Not configured'}</td><td class="flex gap-2 p-3">${c.configured ? button('Test', `notify-test:${c.id}`, 'blue') : ''}${c.status !== 'disabled' ? button('Disable', `notify-disable:${c.id}`, 'slate') : ''}</td></tr>`).join('') || '<tr><td class="p-4 text-slate-500" colspan="5">Chưa có channel.</td></tr>'}</tbody></table></div>`;
    document.querySelector('#notification-form').onsubmit = event => submitWithState(event, async () => { const form = Object.fromEntries(new FormData(event.target)); await mutate(urls.notificationStore, 'POST', {type: form.type, name: form.name, status: 'enabled', config: JSON.parse(form.config_json)}); event.target.reset(); await notifications(); });
}
async function reports() {
    const result = await api(urls.reports + '?period=month'); const data = result.data || {};
    panel.innerHTML = tabHeader('Reports', 'Thống kê theo tháng hiện tại; API hỗ trợ today, week, month, quarter, year và custom dates.') + `<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">${[['Campaigns',data.campaign_count],['Orders',data.order_count],['Spending',money(data.spending)],['Sponsor',money(data.sponsor_amount)],['Debt',money(data.debt)],['Participants',data.user_participation]].map(([label,value]) => `<article class="rounded-xl bg-slate-50 p-4"><p class="text-sm text-slate-500">${label}</p><p class="mt-1 text-xl font-bold">${esc(value)}</p></article>`).join('')}</div><div class="mt-6 grid gap-6 md:grid-cols-2"><div><h3 class="font-semibold">Popular drinks</h3><ul class="mt-2 space-y-2 text-sm">${(data.popular_drinks || []).map(item => `<li class="flex justify-between border-b py-2"><span>${esc(item.item_name)}</span><b>${esc(item.quantity)}</b></li>`).join('') || '<li class="text-slate-500">Chưa có dữ liệu.</li>'}</ul></div><div><h3 class="font-semibold">Popular stores</h3><ul class="mt-2 space-y-2 text-sm">${(data.popular_stores || []).map(item => `<li class="flex justify-between border-b py-2"><span>${esc(item.restaurant)}</span><b>${esc(item.orders)} orders</b></li>`).join('') || '<li class="text-slate-500">Chưa có dữ liệu.</li>'}</ul></div></div>`;
}
async function audit() {
    const result = await api(urls.audit); const rows = result.data.data || [];
    panel.innerHTML = tabHeader('Room audit', 'Chỉ hiển thị audit log thuộc Room đang chọn.') + `<div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead><tr class="border-b text-slate-500"><th class="p-3">Time</th><th class="p-3">Event</th><th class="p-3">Target</th><th class="p-3">Admin</th></tr></thead><tbody>${rows.map(log => `<tr class="border-b"><td class="p-3">${esc(log.created_at)}</td><td class="p-3 font-semibold">${esc(log.event)}</td><td class="p-3">${esc(log.target_type)} #${esc(log.target_id)}</td><td class="p-3">${esc(log.admin_id)}</td></tr>`).join('') || '<tr><td class="p-4 text-slate-500" colspan="4">Chưa có audit.</td></tr>'}</tbody></table></div>`;
}
async function load(tab) { document.querySelectorAll('.tab').forEach(button => button.className = `tab rounded-full px-4 py-2 text-sm ${button.dataset.tab === tab ? 'bg-orange-600 font-semibold text-white' : 'bg-white ring-1 ring-slate-200'}`); panel.innerHTML = skeletonTable({campaigns:5, orders:6, debts:6, users:6, payments:4, settings:4, notifications:5, reports:4, audit:4}[tab] || 5); try { await ({campaigns, orders, debts, users, payments, settings, notifications, reports, audit}[tab])(); } catch (error) { showNotice(error.message, true); panel.innerHTML = '<p class="text-red-600">Không tải được dữ liệu.</p>'; } }
document.querySelectorAll('.tab').forEach(button => button.addEventListener('click', () => load(button.dataset.tab)));
panel.addEventListener('click', async event => { const action = event.target.dataset.action; if (!action) return; const [type, id] = action.split(':'); try { if (type === 'activate') await mutate(`${urls.campaigns}/${id}/activate`); if (type === 'close') await mutate(`${urls.campaigns}/${id}/close`); if (type === 'archive') await mutate(`${urls.campaigns}/${id}/archive`); if (type === 'duplicate') await mutate(`${urls.campaigns}/${id}/duplicate`); if (type === 'order-cancel') await mutate(`${urls.orders}/${id}/cancel`); if (type === 'order-delete') await mutate(`${urls.orders}/${id}`, 'DELETE'); if (type === 'order-unlock') await mutate(`${urls.orders}/${id}/unlock`); if (type === 'debt-paid') await mutate(`${urls.debts}/${id}/status`, 'PATCH', {status:'paid'}); if (type === 'debt-unpaid') await mutate(`${urls.debts}/${id}/status`, 'PATCH', {status:'unpaid'}); if (type.startsWith('user-')) await mutate(`${urls.users}/${id}/status`, 'PATCH', {status:type.substring(5)}); if (type === 'notify-test') await mutate(`${urls.notifications}/${id}/test`); if (type === 'notify-disable') await mutate(`${urls.notifications}/${id}`, 'DELETE'); await load(document.querySelector('.tab.bg-orange-600')?.dataset.tab || 'campaigns'); } catch (error) { showNotice(error.message, true); } });
const initialTab = new URLSearchParams(window.location.search).get('tab') || window.location.hash.replace('#', '') || 'campaigns';
load(['campaigns', 'orders', 'debts', 'users', 'payments', 'settings', 'notifications', 'reports', 'audit'].includes(initialTab) ? initialTab : 'campaigns');
</script>
@endpush

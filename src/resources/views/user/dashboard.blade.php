<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $room->name }} · DrinkFlow</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="user-shell">
<main class="mx-auto max-w-5xl px-4 py-8" data-room-id="{{ $room->id }}">
    <header class="sticky top-0 z-20 -mx-4 mb-8 flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 bg-white/90 px-4 py-4 backdrop-blur-xl">
        <div>
            <p class="text-sm text-slate-500">DrinkFlow · {{ $roomUser->user_code }}</p>
            <h1 class="text-3xl font-semibold">{{ $room->name }}</h1>
            <p class="mt-1 text-slate-600">Xin chào, {{ $roomUser->display_name }}.</p>
        </div>
        <nav class="flex gap-2 text-sm">
            <a class="rounded-lg bg-white px-3 py-2 shadow-sm hover:bg-slate-100" href="{{ route('user.orders.index', $room) }}">Đơn hàng</a>
            <a class="rounded-lg bg-white px-3 py-2 shadow-sm hover:bg-slate-100" href="{{ route('user.history.page') }}">Lịch sử</a>
            <a class="rounded-lg bg-white px-3 py-2 shadow-sm hover:bg-slate-100" href="{{ route('user.notifications.index') }}">Thông báo</a>
            <a class="rounded-lg bg-white px-3 py-2 shadow-sm hover:bg-slate-100" href="{{ route('user.profile.page') }}">Profile</a>
        </nav>
    </header>
    <section class="mb-6 rounded-2xl bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between"><div><p class="text-sm font-semibold text-slate-600">Ví &amp; Quyết toán nợ</p><p class="mt-1 text-xs text-slate-500">Công nợ cá nhân trong Room</p></div><span id="debt-status" class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">Đang tải</span></div>
        <div class="mt-4 flex items-end justify-between gap-4 rounded-xl bg-slate-50 p-4"><div><p class="text-xs text-slate-500">Cần thanh toán</p><p id="debt-total" class="mt-1 text-3xl font-bold tabular-nums text-amber-600">—</p></div><a class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700" href="{{ route('user.debts.index', $room) }}">Xem chi tiết</a></div>
    </section>
    <section class="rounded-2xl bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h2 class="text-xl font-medium">Campaign đang mở</h2><p class="text-sm text-slate-500">Chọn campaign để xem menu và đặt món.</p></div>
            <input id="search" class="rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Tìm món không dấu…" aria-label="Tìm món">
        </div>
        <div id="campaigns" class="mt-5 grid gap-4 sm:grid-cols-2"></div>
        <p id="empty" class="hidden py-10 text-center text-slate-500">Chưa có campaign phù hợp.</p>
    </section>
    <section class="mt-6 rounded-2xl bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between"><h2 class="text-xl font-medium">Thông báo mới</h2><span id="unread" class="rounded-full bg-indigo-50 px-2 py-1 text-xs text-indigo-700"></span></div>
        <ul id="notifications" class="mt-3 divide-y divide-slate-100"></ul>
    </section>
    <nav class="fixed inset-x-0 bottom-0 z-30 mx-auto flex h-16 max-w-5xl items-center justify-around border-t border-slate-200 bg-white/95 px-4 text-xs font-semibold text-slate-500 backdrop-blur-xl sm:hidden">
        <a class="flex flex-col items-center gap-1 text-slate-900" href="{{ route('user.dashboard', $room) }}"><span class="text-lg">⌂</span>Rooms</a>
        <a class="flex flex-col items-center gap-1" href="{{ route('user.orders.index', $room) }}"><span class="text-lg">▣</span>Order Flow</a>
        <a class="flex flex-col items-center gap-1" href="{{ route('user.debts.index', $room) }}"><span class="text-lg">₫</span>My Debts</a>
        <a class="flex flex-col items-center gap-1" href="{{ route('user.notifications.index') }}"><span class="text-lg">◌</span>Activity</a>
    </nav>
</main>
<script>
const campaignsUrl = @json(route('user.campaigns.index', $room));
const notificationsUrl = @json(route('user.notifications.index'));
const debtsUrl = @json(route('user.debts.index', $room));
const campaigns = document.querySelector('#campaigns');
const empty = document.querySelector('#empty');
const search = document.querySelector('#search');
async function loadCampaigns() {
    const q = search.value.trim();
    const response = await fetch(campaignsUrl + (q ? '?q=' + encodeURIComponent(q) : ''), {headers: {'Accept': 'application/json'}});
    if (!response.ok) return;
    const payload = await response.json();
    const rows = (payload.data?.data || []).flatMap(c => (c.items || []).length ? [c] : []);
    empty.classList.toggle('hidden', rows.length > 0);
    campaigns.innerHTML = rows.map(c => `<a class="rounded-xl border border-slate-200 p-4 hover:border-indigo-400" href="/rooms/{{ $room->id }}/campaigns/${c.id}/order"><div class="font-medium">${escapeHtml(c.name)}</div><div class="mt-1 text-sm text-slate-500">${escapeHtml(c.restaurant)} · ${c.items.length} món</div><div class="mt-3 text-xs text-indigo-600">Mở menu →</div></a>`).join('');
}
async function loadNotifications() {
    const response = await fetch(notificationsUrl + '?unread=1', {headers: {'Accept': 'application/json'}});
    if (!response.ok) return;
    const payload = await response.json();
    const rows = payload.data?.data || [];
    document.querySelector('#unread').textContent = rows.length ? rows.length + ' chưa đọc' : '';
    document.querySelector('#notifications').innerHTML = rows.slice(0, 5).map(n => `<li class="py-3"><div class="font-medium">${escapeHtml(n.title)}</div><div class="text-sm text-slate-500">${escapeHtml(n.body || '')}</div></li>`).join('') || '<li class="py-3 text-sm text-slate-500">Không có thông báo mới.</li>';
}
async function loadDebt() {
    const response = await fetch(debtsUrl, {headers: {'Accept': 'application/json'}});
    if (!response.ok) return;
    const rows = (await response.json()).data?.data || [];
    const total = rows.reduce((sum, debt) => sum + Number(debt.remaining_amount || 0), 0);
    document.querySelector('#debt-total').textContent = total.toLocaleString('vi-VN') + ' ₫';
    document.querySelector('#debt-status').textContent = total ? 'Cần thanh toán' : 'Đã quyết toán';
    document.querySelector('#debt-status').className = total ? 'rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700' : 'rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700';
}
function escapeHtml(value) { return String(value).replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c])); }
search.addEventListener('input', () => { clearTimeout(window.searchTimer); window.searchTimer = setTimeout(loadCampaigns, 250); });
loadCampaigns(); loadNotifications(); loadDebt(); setInterval(loadNotifications, 30000); setInterval(loadDebt, 30000);
</script>
</body>
</html>

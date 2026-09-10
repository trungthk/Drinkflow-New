<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đơn #{{ $order->id }} · DrinkFlow</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
<main class="mx-auto max-w-2xl px-4 py-8">
    <a class="text-sm text-indigo-600" href="{{ route('user.dashboard', $room) }}">← {{ $room->name }}</a>
    <section class="mt-5 rounded-2xl bg-white p-6 shadow-sm">
        <div class="flex items-start justify-between gap-4">
            <div><p class="text-sm text-slate-500">{{ $order->campaign->name }}</p><h1 class="text-2xl font-semibold">Đơn #{{ $order->id }}</h1></div>
            <span id="status-badge" class="rounded-full bg-indigo-50 px-3 py-1 text-sm font-medium text-indigo-700">{{ $order->status->value }}</span>
        </div>
        <p id="status-updated" class="mt-2 text-xs text-slate-500">Đang cập nhật trạng thái tự động.</p>
        <ol id="status-timeline" class="mt-6 grid grid-cols-2 gap-3 text-xs text-slate-500 sm:grid-cols-4">
            @foreach(['submitted' => 'Đã gửi', 'confirmed' => 'Đã nhận', 'ordering' => 'Đang làm', 'ordered' => 'Đã xong', 'delivering' => 'Đang giao', 'completed' => 'Hoàn tất'] as $value => $label)
                <li data-status="{{ $value }}" class="rounded-xl border border-slate-200 px-3 py-2"><span class="block font-semibold">{{ $label }}</span><span class="mt-1 block">{{ $value }}</span></li>
            @endforeach
        </ol>
        <ul class="mt-6 divide-y divide-slate-100">
            @foreach($order->items as $item)
                <li class="flex justify-between py-3"><span>{{ $item->quantity }} × {{ $item->item_name }} @if($item->size_name)({{ $item->size_name }})@endif</span><span>{{ number_format($item->line_subtotal, 0, ',', '.') }} ₫</span></li>
            @endforeach
        </ul>
        <div class="mt-5 flex justify-between border-t border-slate-200 pt-4 text-lg font-semibold"><span>Tổng cộng</span><span>{{ number_format($order->final_amount, 0, ',', '.') }} ₫</span></div>
        @if($order->payment_method === 'transfer')
            <a class="mt-5 block rounded-xl bg-indigo-600 px-4 py-3 text-center font-medium text-white" href="{{ route('user.orders.payment', [$room, $order]) }}">Xem thông tin VietQR</a>
        @endif
    </section>
</main>
<script>
const statusUrl = @json(route('user.orders.show', [$room, $order]));
const statusLabels = {submitted: 'Đã gửi', confirmed: 'Đã nhận', ordering: 'Đang làm', ordered: 'Đã xong', delivering: 'Đang giao', completed: 'Hoàn tất', cancelled: 'Đã hủy'};
const badge = document.querySelector('#status-badge');
const timeline = [...document.querySelectorAll('[data-status]')];
const statusUpdated = document.querySelector('#status-updated');
function renderStatus(status) {
    badge.textContent = statusLabels[status] || status;
    badge.className = status === 'cancelled' ? 'rounded-full bg-red-50 px-3 py-1 text-sm font-medium text-red-700' : status === 'completed' ? 'rounded-full bg-emerald-50 px-3 py-1 text-sm font-medium text-emerald-700' : 'rounded-full bg-indigo-50 px-3 py-1 text-sm font-medium text-indigo-700';
    const order = ['submitted', 'confirmed', 'ordering', 'ordered', 'delivering', 'completed'];
    const current = order.indexOf(status);
    timeline.forEach((node) => {
        const active = order.indexOf(node.dataset.status) <= current && current >= 0;
        node.className = active ? 'rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-2 text-indigo-700' : 'rounded-xl border border-slate-200 px-3 py-2 text-slate-500';
    });
    statusUpdated.textContent = `Cập nhật lúc ${new Date().toLocaleTimeString('vi-VN')}`;
}
async function refreshStatus() {
    try { const response = await fetch(statusUrl, {headers: {Accept: 'application/json'}}); if (!response.ok) return; renderStatus((await response.json()).data.status); } catch (_) {}
}
renderStatus(@json($order->status->value));
setInterval(refreshStatus, 15000);
</script>
</body>
</html>

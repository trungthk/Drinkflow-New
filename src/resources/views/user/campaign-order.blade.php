@extends('user.layout')

@section('title', $campaign->name . ' · DrinkFlow')
@section('content')
    <main class="mx-auto max-w-4xl px-4 py-8">
        <a class="text-sm text-indigo-600" href="{{ route('user.dashboard', $room) }}">← {{ $room->name }}</a>
        <header class="mt-5">
            <p class="text-sm text-slate-500">{{ $campaign->restaurant }}</p>
            <h1 class="text-3xl font-semibold">{{ $campaign->name }}</h1>
            <p class="mt-1 text-slate-600">Chọn món và gửi đơn. Giá sẽ được kiểm tra lại trên máy chủ.</p>
        </header>
        <form id="order-form" class="mt-6 space-y-5">
            <div id="items" class="grid gap-4 sm:grid-cols-2">
                <p class="text-slate-500">Đang tải menu…</p>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-sm">
                <label class="block text-sm font-medium" for="payment">Thanh toán</label>
                <select id="payment" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2">
                    <option value="transfer">Chuyển khoản / VietQR</option>
                    <option value="cash">Tiền mặt</option>
                </select>
                <label class="mt-4 block text-sm font-medium" for="note">Ghi chú</label>
                <textarea id="note" rows="2" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2"
                    maxlength="1000"></textarea>
                <button
                    class="mt-4 w-full rounded-lg bg-indigo-600 px-4 py-3 font-medium text-white hover:bg-indigo-700"
                    type="submit">Gửi đơn hàng</button>
                <p id="error" class="mt-3 hidden text-sm text-red-600"></p>
            </div>
        </form>
        <section id="success" class="mt-6 hidden rounded-2xl bg-emerald-50 p-5 text-emerald-900"></section>
    </main>
@endsection

@push('scripts')
    <script>
        const detailUrl = @json(route('user.campaigns.show', [$room, $campaign]));
        const orderUrl = @json(route('user.orders.store', [$room, $campaign]));
        const items = document.querySelector('#items');
        const form = document.querySelector('#order-form');
        let menu = [];
        const esc = value => String(value).replace(/[&<>'"]/g, c => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#39;',
            '"': '&quot;'
        } [c]));
        async function load() {
            const response = await fetch(detailUrl, {
                headers: {
                    'Accept': 'application/json'
                }
            });
            if (!response.ok) {
                items.innerHTML = '<p class="text-red-600">Không thể tải menu.</p>';
                return;
            }
            menu = (await response.json()).data.items || [];
            items.innerHTML = menu.map(item =>
                `<label class="flex items-center justify-between gap-3 rounded-2xl bg-white p-4 shadow-sm"><span><span class="block font-medium">${esc(item.name)}</span><span class="text-sm text-slate-500">${Number(item.base_price).toLocaleString('vi-VN')} ₫</span></span><input data-item="${item.id}" class="w-20 rounded-lg border border-slate-200 px-3 py-2 text-center" type="number" min="0" max="99" value="0" aria-label="Số lượng ${esc(item.name)}"></label>`
                ).join('') || '<p class="text-slate-500">Menu đang trống.</p>';
        }
        form.addEventListener('submit', async event => {
            event.preventDefault();
            const selected = [...document.querySelectorAll('[data-item]')].map(input => ({
                item_id: Number(input.dataset.item),
                quantity: Number(input.value)
            })).filter(item => item.quantity > 0);
            const error = document.querySelector('#error');
            error.classList.add('hidden');
            if (!selected.length) {
                error.textContent = 'Hãy chọn ít nhất một món.';
                error.classList.remove('hidden');
                return;
            }
            const response = await fetch(orderUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                },
                body: JSON.stringify({
                    items: selected,
                    payment_method: document.querySelector('#payment').value,
                    note: document.querySelector('#note').value
                })
            });
            const payload = await response.json();
            if (!response.ok) {
                error.innerHTML = esc(payload.message || Object.values(payload.errors || {}).flat()[0] ||
                    'Không thể tạo đơn.');
                if (payload.code === 'active_order_exists' && payload.order_url) {
                    error.innerHTML +=
                        ` <a class="font-semibold underline" href="${esc(payload.order_url)}">Xem đơn #${esc(payload.order_id)}</a>`;
                }
                error.classList.remove('hidden');
                return;
            }
            form.classList.add('hidden');
            const order = payload.data;
            const success = document.querySelector('#success');
            success.innerHTML =
                `<h2 class="text-xl font-semibold">Đặt món thành công</h2><p class="mt-2">Đơn #${order.id} · ${Number(order.final_amount).toLocaleString('vi-VN')} ₫</p><a class="mt-3 inline-block font-medium underline" href="/rooms/{{ $room->id }}/orders/${order.id}/view">Xem chi tiết đơn</a>`;
            success.classList.remove('hidden');
        });
        load();
    </script>
@endpush

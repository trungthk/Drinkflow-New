@extends('superadmin.layout', ['title' => 'Global Debt Overview', 'active' => 'debts'])
@section('content')
    <div class="superadmin-heading">
        <div>
            <p class="superadmin-eyebrow">Platform Data</p>
            <h1>Global Debt Overview</h1>
            <p>Theo dõi công nợ theo Room, campaign và người dùng; dữ liệu có thể xuất thành CSV.</p>
        </div><a class="sa-button" href="{{ route('superadmin.debts.export') }}"><span
                class="material-symbols-outlined">download</span>Export CSV</a>
    </div>
    <div class="sa-grid kpis">
        <article class="sa-card sa-kpi"><span class="label">Total Debt</span><strong id="total-debt"
                class="value">—</strong><span class="hint">Outstanding toàn hệ thống</span></article>
        <article class="sa-card sa-kpi"><span class="label">Rooms With Debt</span><strong id="room-debt-count"
                class="value">—</strong><span class="hint">Phân bổ theo Room</span></article>
        <article class="sa-card sa-kpi"><span class="label">Campaigns With Debt</span><strong id="campaign-debt-count"
                class="value">—</strong><span class="hint">Phân bổ theo campaign</span></article>
    </div>
    <section class="sa-card sa-section">
        <div class="sa-section-header">
            <div>
                <h2>Debt ledger</h2>
                <p id="debt-count">Đang tải dữ liệu...</p>
            </div>
        </div>
        <div class="sa-table-wrap">
            <table class="sa-table">
                <thead>
                    <tr>
                        <th>Room</th>
                        <th>Campaign</th>
                        <th>User</th>
                        <th>Original</th>
                        <th>Paid</th>
                        <th>Remaining</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="debts-table"></tbody>
            </table>
        </div>
    </section>
@endsection
@push('scripts')
    <script>
        dfApi('{{ route('superadmin.debts.index') }}').then(({
            data
        }) => {
            document.querySelector('#total-debt').textContent = money(data.total_debt);
            document.querySelector('#room-debt-count').textContent = data.by_room.length;
            document.querySelector('#campaign-debt-count').textContent = data.by_campaign.length;
            document.querySelector('#debt-count').textContent = `${data.items.total} debt record`;
            document.querySelector('#debts-table').innerHTML = data.items.data.length ? data.items.data.map(d =>
                `<tr><td>${escapeHtml(d.room?.name)}</td><td>${escapeHtml(d.campaign?.name)}</td><td>${escapeHtml(d.room_user?.global_user?.email)}</td><td>${money(d.original_amount)}</td><td>${money(d.paid_amount)}</td><td><strong>${money(d.remaining_amount)}</strong></td><td>${statusPill(d.status)}</td></tr>`
                ).join('') : '<tr><td colspan="7" class="sa-empty">Không có công nợ.</td></tr>';
        }).catch(e => {
            document.querySelector('#debt-count').textContent = e.message;
        });
    </script>
@endpush

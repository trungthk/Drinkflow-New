@extends('superadmin.layout', ['title' => 'Global Campaigns', 'active' => 'campaigns'])
@section('content')
    <div class="superadmin-heading">
        <div>
            <p class="superadmin-eyebrow">Platform Data</p>
            <h1>Global Campaigns</h1>
            <p>Xem campaign, Room, admin tạo, restaurant, orders và trạng thái trên toàn hệ thống.</p>
        </div>
    </div>
    <div id="notice" class="sa-notice"></div>
    <section class="sa-card sa-section">
        <div class="sa-section-header">
            <div>
                <h2>Campaign registry</h2>
                <p id="campaign-count">Đang tải dữ liệu...</p>
            </div><select id="campaign-status" class="sa-input" onchange="loadCampaigns()">
                <option value="">Tất cả trạng thái</option>
                <option>active</option>
                <option>scheduled</option>
                <option>closed</option>
                <option>cancelled</option>
            </select>
        </div>
        <div class="sa-table-wrap">
            <table class="sa-table">
                <thead>
                    <tr>
                        <th>Campaign</th>
                        <th>Room</th>
                        <th>Orders</th>
                        <th>Debts</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="campaigns-table"></tbody>
            </table>
        </div>
    </section>
@endsection
@push('scripts')
    <script>
        const campaignNotice = (message, type = 'success') => {
            const n = document.querySelector('#notice');
            n.textContent = message;
            n.className = `sa-notice ${type} is-visible`;
        };
        async function loadCampaigns() {
            const status = document.querySelector('#campaign-status').value;
            const {
                data
            } = await dfApi('{{ route('superadmin.campaigns.index') }}' + (status ? '?status=' + status : ''));
            document.querySelector('#campaign-count').textContent = `${data.total} campaign`;
            document.querySelector('#campaigns-table').innerHTML = data.data.length ? data.data.map(c =>
                `<tr><td><strong>${escapeHtml(c.name)}</strong><br><small>${escapeHtml(c.restaurant)}</small></td><td>${escapeHtml(c.room?.name)}</td><td>${c.orders_count}</td><td>${c.debts_count}</td><td>${statusPill(c.status)}</td><td><div class="superadmin-actions">${c.status==='closed'||c.status==='cancelled'?'<span class="sa-empty">—</span>':`<button class="sa-button secondary" onclick="forceCampaign(${c.id},'force-close')">Force close</button><button class="sa-button danger" onclick="forceCampaign(${c.id},'force-cancel')">Cancel</button>`}</div></td></tr>`
                ).join('') : '<tr><td colspan="6" class="sa-empty">Chưa có campaign.</td></tr>';
        }
        async function forceCampaign(id, action) {
            if (!confirm('Xác nhận thao tác trên campaign này?')) return;
            try {
                await dfApi(`/superadmin/campaigns/${id}/${action}`, {
                    method: 'POST'
                });
                campaignNotice('Đã thực hiện thao tác.');
                loadCampaigns();
            } catch (e) {
                campaignNotice(e.message, 'error');
            }
        }
        loadCampaigns();
    </script>
@endpush

@extends('superadmin.layout', ['title' => 'Socket.IO & Queue', 'active' => 'socket'])
@section('content')
    <div class="superadmin-heading">
        <div>
            <p class="superadmin-eyebrow">Infra &amp; Security</p>
            <h1>Socket.IO &amp; Queue</h1>
            <p>Telemetry realtime gateway và các failed jobs cần xử lý.</p>
        </div><a class="sa-button secondary" href="{{ route('superadmin.queue.page') }}">Open failed jobs</a>
    </div>
    <section class="sa-grid kpis">
        <article class="sa-card sa-kpi"><span class="label">Gateway</span><strong id="gateway-status"
                class="value">—</strong><span class="hint">Socket.IO health</span></article>
        <article class="sa-card sa-kpi"><span class="label">Users online</span><strong id="connected-users"
                class="value">—</strong><span class="hint">Live connections</span></article>
        <article class="sa-card sa-kpi"><span class="label">Admins online</span><strong id="connected-admins"
                class="value">—</strong><span class="hint">Admin + superadmin</span></article>
        <article class="sa-card sa-kpi"><span class="label">Auth failures</span><strong id="auth-failures"
                class="value">—</strong><span class="hint">Gateway rejected tokens</span></article>
    </section>
    <section class="sa-card sa-section">
        <div class="sa-section-header">
            <div>
                <h2>Recent disconnects</h2>
                <p>Gateway chỉ trả telemetry, không trả raw socket payload.</p>
            </div>
        </div>
        <div id="disconnects" class="sa-health-list"></div>
    </section>
@endsection
@push('scripts')
    <script>
        async function loadSocket() {
            const {
                data
            } = await dfApi('{{ route('superadmin.socket.index') }}');
            document.querySelector('#gateway-status').textContent = data.status;
            document.querySelector('#connected-users').textContent = data.connected_users ?? '—';
            document.querySelector('#connected-admins').textContent = (data.connected_admins ?? 0) + (data
                .connected_superadmins ?? 0);
            document.querySelector('#auth-failures').textContent = data.authentication_failures ?? '—';
            document.querySelector('#disconnects').innerHTML = (data.recent_disconnects || []).map(d =>
                `<div class="sa-health-row"><div><strong>${escapeHtml(d.actor_type)}</strong><small>${escapeHtml(d.reason)} · ${escapeHtml(d.at)}</small></div></div>`
                ).join('') || '<div class="sa-empty">Chưa có disconnect gần đây.</div>';
        }
        loadSocket();
        setInterval(loadSocket, 30000);
    </script>
@endpush

@extends('superadmin.layout', ['title' => 'Security Center', 'active' => 'security'])
@section('content')
    <div class="superadmin-heading">
        <div>
            <p class="superadmin-eyebrow">Infra &amp; Security</p>
            <h1>Security Center</h1>
            <p>Giám sát failed login, OAuth failure, device authentication và các request bất thường.</p>
        </div>
    </div>
    <section class="sa-card sa-section">
        <div class="sa-section-header">
            <div>
                <h2>Security events</h2>
                <p id="security-count">Đang tải dữ liệu...</p>
            </div><select id="severity" class="sa-input" onchange="loadSecurity()">
                <option value="">Mọi mức độ</option>
                <option>high</option>
                <option>medium</option>
                <option>low</option>
            </select>
        </div>
        <div class="sa-table-wrap">
            <table class="sa-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Type</th>
                        <th>Severity</th>
                        <th>IP</th>
                        <th>Metadata</th>
                    </tr>
                </thead>
                <tbody id="security-table"></tbody>
            </table>
        </div>
    </section>
@endsection
@push('scripts')
    <script>
        async function loadSecurity() {
            const severity = document.querySelector('#severity').value;
            const {
                data
            } = await dfApi('{{ route('superadmin.security-events.index') }}' + (severity ? '?severity=' + severity :
                ''));
            document.querySelector('#security-count').textContent = `${data.total} events`;
            document.querySelector('#security-table').innerHTML = data.data.length ? data.data.map(e =>
                `<tr><td>${escapeHtml(e.created_at)}</td><td><strong>${escapeHtml(e.type)}</strong></td><td>${statusPill(e.severity)}</td><td>${escapeHtml(e.ip_address||'—')}</td><td><small>${escapeHtml(JSON.stringify(e.metadata||{}))}</small></td></tr>`
                ).join('') : '<tr><td colspan="5" class="sa-empty">Chưa có security event.</td></tr>';
        }
        loadSecurity();
    </script>
@endpush

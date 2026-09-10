@extends('superadmin.layout', ['title' => 'Global Notifications', 'active' => 'notifications'])
@section('content')
    <div class="superadmin-heading">
        <div>
            <p class="superadmin-eyebrow">System Messaging</p>
            <h1>Global Notifications</h1>
            <p>Kênh cảnh báo cấp hệ thống cho lỗi, security alert và failed queue.</p>
        </div><button class="sa-button" onclick="createChannel()"><span class="material-symbols-outlined">add_alert</span>Thêm
            channel</button>
    </div>
    <div id="notice" class="sa-notice"></div>
    <section class="sa-card sa-section">
        <div class="sa-section-header">
            <div>
                <h2>System channels</h2>
                <p>Credential được lưu encrypted và không trả về client.</p>
            </div>
        </div>
        <div class="sa-table-wrap">
            <table class="sa-table">
                <thead>
                    <tr>
                        <th>Channel</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Credential</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="channels-table"></tbody>
            </table>
        </div>
    </section>
@endsection
@push('scripts')
    <script>
        const channelNotice = (message, type = 'success') => {
            const n = document.querySelector('#notice');
            n.textContent = message;
            n.className = `sa-notice ${type} is-visible`;
        };
        async function loadChannels() {
            const {
                data
            } = await dfApi('{{ route('superadmin.notifications.index') }}');
            document.querySelector('#channels-table').innerHTML = data.length ? data.map(c =>
                `<tr><td><strong>${escapeHtml(c.name)}</strong></td><td>${escapeHtml(c.type)}</td><td>${statusPill(c.status)}</td><td>••••••••</td><td><button class="sa-button danger" onclick="deleteChannel(${c.id})">Delete</button></td></tr>`
                ).join('') : '<tr><td colspan="5" class="sa-empty">Chưa cấu hình global channel.</td></tr>';
        };
        async function createChannel() {
            const name = prompt('Tên channel');
            if (!name) return;
            const type = prompt('Type: slack, telegram, chatwork hoặc webhook', 'slack');
            if (!type) return;
            const config = prompt('Credential / webhook');
            if (!config) return;
            try {
                await dfApi('{{ route('superadmin.notifications.store') }}', {
                    method: 'POST',
                    body: {
                        name,
                        type,
                        config,
                        status: 'active'
                    }
                });
                channelNotice('Đã tạo notification channel.');
                loadChannels();
            } catch (e) {
                channelNotice(e.message, 'error');
            }
        }
        async function deleteChannel(id) {
            if (!confirm('Xóa channel này?')) return;
            try {
                await dfApi(`/superadmin/notifications/${id}`, {
                    method: 'DELETE'
                });
                loadChannels();
            } catch (e) {
                channelNotice(e.message, 'error');
            }
        }
        loadChannels();
    </script>
@endpush

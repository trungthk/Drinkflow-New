@extends('superadmin.layout', ['title' => 'Global Users & Identities', 'active' => 'users'])
@section('content')
    <div class="superadmin-heading">
        <div>
            <p class="superadmin-eyebrow">Identity Layer</p>
            <h1>Global Users &amp; Identities</h1>
            <p>Tra cứu global identity, OAuth metadata, membership và trạng thái truy cập toàn hệ thống.</p>
        </div>
    </div>
    <div id="notice" class="sa-notice"></div>
    <section class="sa-card sa-section">
        <div class="sa-section-header">
            <div>
                <h2>Global users</h2>
                <p id="user-count">Đang tải dữ liệu...</p>
            </div><input id="user-search" class="sa-input" placeholder="Tìm tên, normalized name hoặc email"
                >
        </div>
        <div class="sa-table-wrap">
            <table class="sa-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>OAuth</th>
                        <th>Memberships</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>@forelse($users as $user)<tr><td><strong>{{ $user->name }}</strong><br><small>{{ $user->email }}</small></td><td>{{ $user->oauthIdentities->pluck('provider')->join(', ') ?: '—' }}</td><td>{{ $user->room_users_count }}</td><td>{{ $user->status }}</td><td><div class="superadmin-actions"><a class="sa-button secondary" href="{{ route('superadmin.global-users.detail.page', $user) }}">Detail</a><button class="sa-button danger" onclick="setUserStatus({{ $user->id }}, '{{ $user->status === 'blocked' ? 'active' : 'blocked' }}')">{{ $user->status === 'blocked' ? 'Unblock' : 'Block' }}</button><button class="sa-button danger" onclick="deleteUser({{ $user->id }})">Delete</button></div></td></tr>@empty<tr><td colspan="5" class="sa-empty">ChÆ°a cÃ³ user phÃ¹ há»£p.</td></tr>@endforelse</tbody>
            </table>
            <div class="mt-4">{{ $users->links() }}</div>
        </div>
    </section>
@endsection
@push('scripts')
    <script>
        const userNotice = (message, type = 'success') => {
            const n = document.querySelector('#notice');
            n.textContent = message;
            n.className = `sa-notice ${type} is-visible`;
        };
        async function loadUsers() {
            return;
            const q = document.querySelector('#user-search').value;
            const {
                data
            } = await dfApi('{{ route('superadmin.global-users.index') }}' + (q ? '?q=' + encodeURIComponent(q) : ''));
            document.querySelector('#user-count').textContent = `${data.total} global user`;
            document.querySelector('#users-table').innerHTML = data.data.length ? data.data.map(u =>
                `<tr><td><strong>${escapeHtml(u.name)}</strong><br><small>${escapeHtml(u.email)}</small></td><td>${(u.oauth_identities||[]).map(i=>escapeHtml(i.provider)).join(', ')||'—'}</td><td>${u.room_users_count}</td><td>${statusPill(u.status)}</td><td><div class="superadmin-actions"><a class="sa-button secondary" href="/superadmin/global-users/${u.id}/page">Detail</a>${u.status==='blocked'?`<button class="sa-button" onclick="setUserStatus(${u.id},'active')">Unblock</button>`:`<button class="sa-button danger" onclick="setUserStatus(${u.id},'blocked')">Block</button>`}<button class="sa-button danger" onclick="deleteUser(${u.id})">Delete</button></div></td></tr>`
                ).join('') : '<tr><td colspan="5" class="sa-empty">Chưa có user phù hợp.</td></tr>';
        }
        async function deleteUser(id) {
            if (!confirm('Bạn có chắc chắn muốn xóa Global User này? Thao tác không thể hoàn tác nếu user không còn nợ.')) return;
            try {
                await dfApi(`/superadmin/global-users/${id}`, {
                    method: 'DELETE'
                });
                userNotice('Đã xóa Global User thành công.');
                loadUsers();
            } catch (e) {
                userNotice(e.message, 'error');
            }
        }
        async function setUserStatus(id, status) {
            try {
                await dfApi(`/superadmin/global-users/${id}/status`, {
                    method: 'PATCH',
                    body: {
                        status
                    }
                });
                userNotice('Đã cập nhật global user.');
                loadUsers();
            } catch (e) {
                userNotice(e.message, 'error');
            }
        }
        loadUsers();
    </script>
@endpush

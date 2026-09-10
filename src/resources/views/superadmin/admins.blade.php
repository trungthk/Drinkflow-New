@extends('superadmin.layout', ['title' => 'Admin & Assignments', 'active' => 'admins'])
@section('content')
    <div class="superadmin-heading">
        <div>
            <p class="superadmin-eyebrow">Core System</p>
            <h1>Admin &amp; Assignments</h1>
            <p>Kiểm soát quyền quản trị, trạng thái tài khoản và phạm vi Room được phân công.</p>
        </div><button class="sa-button" onclick="createAdmin()"><span class="material-symbols-outlined">person_add</span>Tạo
            Admin</button>
    </div>
    <div id="notice" class="sa-notice"></div>
    <section class="sa-card sa-section">
        <div class="sa-section-header">
            <div>
                <h2>Administrators</h2>
                <p id="admin-count">Đang tải dữ liệu...</p>
            </div><input id="admin-search" class="sa-input" placeholder="Tìm tên hoặc email" oninput="loadAdmins()">
        </div>
        <div class="sa-table-wrap">
            <table class="sa-table">
                <thead>
                    <tr>
                        <th>Admin</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Assigned Rooms</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="admins-table"></tbody>
            </table>
        </div>
    </section>
@endsection
@push('scripts')
    <script>
        const adminNotice = (message, type = 'success') => {
            const n = document.querySelector('#notice');
            n.textContent = message;
            n.className = `sa-notice ${type} is-visible`;
        };
        async function loadAdmins() {
            const q = document.querySelector('#admin-search').value;
            const {
                data
            } = await dfApi('{{ route('superadmin.admins.index') }}' + (q ? '?q=' + encodeURIComponent(q) : ''));
            document.querySelector('#admin-count').textContent = `${data.total} tài khoản`;
            document.querySelector('#admins-table').innerHTML = data.data.length ? data.data.map(a =>
                `<tr><td><strong>${escapeHtml(a.name)}</strong><br><small>${escapeHtml(a.email)}</small></td><td>${escapeHtml(a.role)}</td><td>${statusPill(a.status)}</td><td>${a.rooms_count} Room</td><td><div class="superadmin-actions"><a class="sa-button secondary" href="/superadmin/admins/${a.id}/page">Detail</a>${a.role==='superadmin'?'<span class="status-pill status-active">Protected</span>':a.status==='active'?`<button class="sa-button danger" onclick="setAdminStatus(${a.id},'blocked')">Block</button>`:`<button class="sa-button" onclick="setAdminStatus(${a.id},'active')">Unblock</button>`}</div></td></tr>`
                ).join('') : '<tr><td colspan="5" class="sa-empty">Chưa có Admin phù hợp.</td></tr>';
        }
        async function setAdminStatus(id, status) {
            try {
                await dfApi(`/superadmin/admins/${id}/status`, {
                    method: 'PATCH',
                    body: {
                        status
                    }
                });
                adminNotice('Đã cập nhật trạng thái Admin.');
                loadAdmins();
            } catch (e) {
                adminNotice(e.message, 'error');
            }
        }
        async function createAdmin() {
            const name = prompt('Tên Admin');
            if (!name) return;
            const email = prompt('Email');
            if (!email) return;
            const password = prompt('Mật khẩu tạm thời (tối thiểu 8 ký tự)');
            if (!password) return;
            try {
                await dfApi('{{ route('superadmin.admins.store') }}', {
                    method: 'POST',
                    body: {
                        name,
                        email,
                        password,
                        role: 'admin'
                    }
                });
                adminNotice('Đã tạo Admin.');
                loadAdmins();
            } catch (e) {
                adminNotice(e.message, 'error');
            }
        }
        loadAdmins();
    </script>
@endpush

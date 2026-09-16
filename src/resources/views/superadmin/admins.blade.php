@extends('superadmin.layout', ['title' => __('superadmin.admins.title'), 'active' => 'admins'])
@section('content')
    <div class="superadmin-heading">
        <div>
            <p class="superadmin-eyebrow">{{ __('superadmin.common.core_system') }}</p>
            <h1>{{ __('superadmin.admins.title') }}</h1>
            <p>{{ __('superadmin.admins.description') }}</p>
        </div><button class="sa-button" onclick="createAdmin()"><span class="material-symbols-outlined">person_add</span>{{ __('superadmin.admins.create') }}</button>
    </div>
    <div id="notice" class="sa-notice"></div>
    <section class="sa-card sa-section">
        <div class="sa-section-header">
            <div>
                <h2>{{ __('superadmin.admins.administrators') }}</h2>
                <p id="admin-count">{{ __('superadmin.common.loading') }}</p>
            </div><input id="admin-search" class="sa-input" placeholder="{{ __('superadmin.admins.search') }}" oninput="loadAdmins()">
        </div>
        <div class="sa-table-wrap">
            <table class="sa-table">
                <thead>
                    <tr>
                        <th>{{ __('superadmin.common.admin') }}</th>
                        <th>{{ __('superadmin.common.role') }}</th>
                        <th>{{ __('superadmin.common.status') }}</th>
                        <th>{{ __('superadmin.admins.assigned_rooms') }}</th>
                        <th>{{ __('superadmin.common.actions') }}</th>
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
            document.querySelector('#admin-count').textContent = @js(__('superadmin.common.accounts_count', ['count' => '__COUNT__'])).replace('__COUNT__', data.total);
            document.querySelector('#admins-table').innerHTML = data.data.length ? data.data.map(a =>
                `<tr><td><strong>${escapeHtml(a.name)}</strong><br><small>${escapeHtml(a.email)}</small></td><td>${escapeHtml(a.role)}</td><td>${statusPill(a.status)}</td><td>${a.rooms_count} ${@js(__('superadmin.common.rooms'))}</td><td><div class="superadmin-actions"><a class="sa-button secondary" href="/superadmin/admins/${a.id}/page">${@js(__('superadmin.common.details'))}</a>${a.role==='superadmin'?`<span class="status-pill status-active">${@js(__('superadmin.common.protected'))}</span>`:a.status==='active'?`<button class="sa-button danger" onclick="setAdminStatus(${a.id},'blocked')">${@js(__('superadmin.common.block'))}</button>`:`<button class="sa-button" onclick="setAdminStatus(${a.id},'active')">${@js(__('superadmin.common.unblock'))}</button>`}</div></td></tr>`
                ).join('') : `<tr><td colspan="5" class="sa-empty">${@js(__('superadmin.admins.no_results'))}</td></tr>`;
        }
        async function setAdminStatus(id, status) {
            try {
                await dfApi(`/superadmin/admins/${id}/status`, {
                    method: 'PATCH',
                    body: {
                        status
                    }
                });
                adminNotice(@js(__('superadmin.admins.updated')));
                loadAdmins();
            } catch (e) {
                adminNotice(e.message, 'error');
            }
        }
        async function createAdmin() {
            const name = prompt(@js(__('superadmin.admins.prompt_name')));
            if (!name) return;
            const email = prompt(@js(__('superadmin.common.email')));
            if (!email) return;
            const password = prompt(@js(__('superadmin.admins.prompt_password')));
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
                adminNotice(@js(__('superadmin.admins.created')));
                loadAdmins();
            } catch (e) {
                adminNotice(e.message, 'error');
            }
        }
        loadAdmins();
    </script>
@endpush

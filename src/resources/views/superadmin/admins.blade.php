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
                <p>{{ __('superadmin.common.accounts_count', ['count' => $admins->total()]) }}</p>
            </div><form method="GET" class="superadmin-actions"><input name="q" value="{{ $filters['search'] ?? '' }}" class="sa-input" placeholder="{{ __('superadmin.admins.search') }}"><select name="role" class="sa-input"><option value="">All roles</option><option value="admin" @selected(($filters['role'] ?? '') === 'admin')>Admin</option><option value="superadmin" @selected(($filters['role'] ?? '') === 'superadmin')>Superadmin</option></select><select name="status" class="sa-input"><option value="">All status</option><option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option><option value="blocked" @selected(($filters['status'] ?? '') === 'blocked')>Blocked</option></select><button class="sa-button secondary" type="submit">Filter</button></form>
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
                <tbody>@forelse($admins as $admin)<tr><td><strong>{{ $admin->name }}</strong><br><small>{{ $admin->email }}</small></td><td>{{ $admin->role }}</td><td>{{ $admin->status }}</td><td>{{ $admin->rooms_count }} {{ __('superadmin.common.rooms') }}</td><td><div class="superadmin-actions"><a class="sa-button secondary" href="{{ route('superadmin.admins.detail.page', $admin) }}">{{ __('superadmin.common.details') }}</a>@if($admin->role === 'superadmin')<span class="status-pill status-active">{{ __('superadmin.common.protected') }}</span>@elseif($admin->status === 'active')<button class="sa-button danger" onclick="setAdminStatus({{ $admin->id }}, 'blocked')">{{ __('superadmin.common.block') }}</button>@else<button class="sa-button" onclick="setAdminStatus({{ $admin->id }}, 'active')">{{ __('superadmin.common.unblock') }}</button>@endif</div></td></tr>@empty<tr><td colspan="5" class="sa-empty">{{ __('superadmin.admins.no_results') }}</td></tr>@endforelse</tbody>
            </table>
            <div class="mt-4">{{ $admins->links() }}</div>
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
        async function setAdminStatus(id, status) {
            try {
                await dfApi(`/superadmin/admins/${id}/status`, {
                    method: 'PATCH',
                    body: {
                        status
                    }
                });
                adminNotice(@js(__('superadmin.admins.updated')));
                window.location.reload();
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
                window.location.reload();
            } catch (e) {
                adminNotice(e.message, 'error');
            }
        }
    </script>
@endpush

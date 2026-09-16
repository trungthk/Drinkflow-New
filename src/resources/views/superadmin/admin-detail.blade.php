@extends('superadmin.layout', ['active' => 'admins'])

@section('title', __('superadmin.admins.profile'))

@section('content')
    <div class="superadmin-heading">
        <div><a class="sa-back-link" href="{{ route('superadmin.admins.page') }}">← {{ __('superadmin.admins.title') }}</a>
            <h1 id="admin-name">{{ __('superadmin.admins.profile') }}</h1>
            <p id="admin-meta">{{ __('superadmin.admins.loading_profile') }}</p>
        </div>
        <div class="superadmin-actions"><button class="sa-button secondary" id="admin-reset" type="button">{{ __('superadmin.admins.reset_password') }}</button><button class="sa-button danger" id="admin-status" type="button">{{ __('superadmin.admins.update_status') }}</button>
        </div>
    </div>
    <div id="notice" class="sa-notice" role="status"></div>
    <div class="sa-split">
        <section class="sa-card sa-section">
            <div class="sa-section-header">
                <div>
                    <h2>{{ __('superadmin.admins.account_role') }}</h2>
                    <p>{{ __('superadmin.admins.account_role_description') }}</p>
                </div>
            </div>
            <div id="admin-summary" class="sa-detail-list"></div>
        </section>
        <section class="sa-card sa-section">
            <div class="sa-section-header">
                <div>
                    <h2>{{ __('superadmin.admins.room_assignment') }}</h2>
                    <p>{{ __('superadmin.admins.room_assignment_description') }}</p>
                </div>
                <button class="sa-button secondary" id="edit-rooms" type="button"><span class="material-symbols-outlined">tune</span>{{ __('superadmin.admins.manage_access') }}</button>
            </div>
            <div id="admin-rooms" class="sa-health-list"></div>
        </section>
    </div>
    <section id="room-assignment-panel" class="sa-card sa-section" hidden>
        <div class="sa-section-header"><div><h2>{{ __('superadmin.admins.room_access') }}</h2><p>{{ __('superadmin.admins.room_access_description') }}</p></div><button class="icon-button" id="close-rooms" type="button" aria-label="{{ __('superadmin.common.close') }}"><span class="material-symbols-outlined">close</span></button></div>
        <div id="room-options" class="sa-health-list"></div>
        <button class="sa-button" id="save-rooms" type="button"><span class="material-symbols-outlined">save</span>{{ __('superadmin.admins.save_assignment') }}</button>
    </section>
@endsection

@push('scripts')
    <script>
        const adminId = @json(request()->route('admin'));
        const roomsUrl = @json(route('superadmin.rooms.index'));
        const adminNotice = (message, type = 'success') => {
            const n = document.querySelector('#notice');
            n.textContent = message;
            n.className = `sa-notice ${type} is-visible`;
        };
        async function loadAdmin() {
            const {
                data: admin
            } = await dfApi(`/superadmin/admins/${adminId}`);
            document.querySelector('#admin-name').textContent = admin.name;
            document.querySelector('#admin-meta').textContent = `${admin.email} · ${admin.role} · ${admin.status}`;
            document.querySelector('#admin-summary').innerHTML =
                `<div class="sa-detail-row"><span>${@js(__('superadmin.common.name'))}</span><strong>${escapeHtml(admin.name)}</strong></div><div class="sa-detail-row"><span>${@js(__('superadmin.common.email'))}</span><strong>${escapeHtml(admin.email)}</strong></div><div class="sa-detail-row"><span>${@js(__('superadmin.common.role'))}</span><strong>${escapeHtml(admin.role)}</strong></div><div class="sa-detail-row"><span>${@js(__('superadmin.common.status'))}</span><strong>${statusPill(admin.status)}</strong></div>`;
            document.querySelector('#admin-rooms').innerHTML = (admin.rooms || []).map(room =>
                `<div class="sa-health-row"><div><strong>${escapeHtml(room.name)}</strong><small>${escapeHtml(room.slug)}</small></div><span class="status-pill status-active">${@js(__('superadmin.common.assigned'))}</span></div>`
                ).join('') || `<div class="sa-empty">${@js(__('superadmin.admins.no_assigned_rooms'))}</div>`;
            const statusButton = document.querySelector('#admin-status');
            statusButton.textContent = admin.status === 'active' ? @js(__('superadmin.admins.block_account')) : @js(__('superadmin.admins.unblock_account'));
            statusButton.onclick = () => updateAdminStatus(admin.status === 'active' ? 'blocked' : 'active');
            document.querySelector('#admin-reset').onclick = resetPassword;
        }
        async function openRoomAssignment() {
            const [{ data: admin }, { data: rooms }] = await Promise.all([dfApi(`/superadmin/admins/${adminId}`), dfApi(roomsUrl)]);
            const assigned = new Set((admin.rooms || []).map(room => Number(room.id)));
            document.querySelector('#room-options').innerHTML = (rooms.data || []).map(room => `<label class="sa-health-row"><span><strong>${escapeHtml(room.name)}</strong><small>${escapeHtml(room.slug)} · ${escapeHtml(superadminStatusLabels[room.status] || room.status)}</small></span><input type="checkbox" value="${room.id}" ${assigned.has(Number(room.id)) ? 'checked' : ''}></label>`).join('') || `<div class="sa-empty">${@js(__('superadmin.admins.no_rooms'))}</div>`;
            document.querySelector('#room-assignment-panel').hidden = false;
        }
        async function saveRoomAssignment() {
            const roomIds = [...document.querySelectorAll('#room-options input:checked')].map(input => Number(input.value));
            try { await dfApi(`/superadmin/admins/${adminId}/rooms`, { method: 'PUT', body: { room_ids: roomIds } }); adminNotice(@js(__('superadmin.admins.assignment_updated'))); document.querySelector('#room-assignment-panel').hidden = true; await loadAdmin(); } catch (error) { adminNotice(error.message, 'error'); }
        }
        async function updateAdminStatus(status) {
            try {
                await dfApi(`/superadmin/admins/${adminId}/status`, {
                    method: 'PATCH',
                    body: {
                        status
                    }
                });
                adminNotice(@js(__('superadmin.admins.updated')));
                loadAdmin();
            } catch (error) {
                adminNotice(error.message, 'error');
            }
        }
        async function resetPassword() {
            const password = prompt(@js(__('superadmin.admins.prompt_new_password')));
            if (!password) return;
            try {
                await dfApi(`/superadmin/admins/${adminId}/reset-password`, {
                    method: 'POST',
                    body: {
                        password
                    }
                });
                adminNotice(@js(__('superadmin.admins.password_reset')));
            } catch (error) {
                adminNotice(error.message, 'error');
            }
        }
        document.querySelector('#edit-rooms').onclick = () => openRoomAssignment().catch(error => adminNotice(error.message, 'error'));
        document.querySelector('#close-rooms').onclick = () => { document.querySelector('#room-assignment-panel').hidden = true; };
        document.querySelector('#save-rooms').onclick = saveRoomAssignment;
        loadAdmin().catch(error => adminNotice(error.message, 'error'));
    </script>
@endpush

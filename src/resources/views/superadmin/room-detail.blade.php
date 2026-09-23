@extends('superadmin.layout', ['active' => 'rooms'])

@section('title', 'Room profile')

@section('content')
    <div class="superadmin-heading">
        <div><a class="sa-back-link" href="{{ route('superadmin.rooms.page') }}">← Room Management</a>
            <h1 id="room-name">Room profile</h1>
            <p id="room-meta">Đang tải thông tin Room...</p>
        </div>
        <div class="superadmin-actions">
            <button class="sa-button secondary" data-modal-open="edit-room-modal" type="button"><span class="material-symbols-outlined text-[16px]">edit</span>{{ __('superadmin.rooms.edit_room') }}</button>
            <button class="sa-button" id="room-status-action" type="button"><span class="material-symbols-outlined text-[16px]">toggle_on</span>Update status</button>
            <button class="sa-button danger" onclick="deleteRoom()" type="button"><span class="material-symbols-outlined text-[16px]">delete</span>Delete room</button>
        </div>
    </div>
    <div id="notice" class="sa-notice" role="status"></div>
    <section class="sa-grid kpis kpis-4">
        <article class="sa-card sa-kpi"><span class="label">Members</span><strong id="room-members"
                class="value">—</strong><span class="hint">Tổng thành viên</span></article>
        <article class="sa-card sa-kpi"><span class="label">Active members</span><strong id="room-active-members"
                class="value">—</strong><span class="hint">Đang hoạt động</span></article>
        <article class="sa-card sa-kpi"><span class="label">Campaigns</span><strong id="room-campaigns"
                class="value">—</strong><span class="hint">Campaign trong Room</span></article>
        <article class="sa-card sa-kpi"><span class="label">Admins</span><strong id="room-admins"
                class="value">—</strong><span class="hint">Được phân quyền</span></article>
    </section>
    <section class="sa-card sa-section">
        <div class="sa-section-header">
            <div>
                <h2>Assigned admins</h2>
                <p>Admin có quyền vận hành Room này.</p>
            </div>
        </div>
        <div class="sa-table-wrap">
            <table class="sa-table">
                <thead>
                    <tr>
                        <th>Admin</th>
                        <th class="whitespace-nowrap">Role</th>
                        <th class="whitespace-nowrap">Status</th>
                    </tr>
                </thead>
                <tbody id="room-admin-list">
                    <tr>
                        <td colspan="3">
                            <x-superadmin.empty-state loading :title="__('superadmin.common.loading_title')" :description="__('superadmin.common.loading_description')" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    {{-- Empty states cloned by the page script, so every string stays translated through __(). --}}
    <template id="tpl-no-admins"><x-superadmin.empty-state icon="person_off" :title="__('superadmin.rooms.no_admins_title')" :description="__('superadmin.rooms.no_admins_description')" /></template>
    <template id="tpl-load-failed"><x-superadmin.empty-state icon="error" :title="__('superadmin.common.load_failed_title')" description="" /></template>

    <x-superadmin.modal id="edit-room-modal" icon="edit" :title="__('superadmin.rooms.edit_room')" :description="__('superadmin.rooms.edit_description')">
        <form id="edit-room-form" class="space-y-4">
            @csrf
            <div>
                <label for="edit-room-name" class="block text-xs font-semibold text-on-surface mb-1">{{ __('superadmin.rooms.field_name') }} <span class="text-error">*</span></label>
                <input type="text" id="edit-room-name" name="name" required class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary">
            </div>
            <div>
                <label for="edit-room-slug" class="block text-xs font-semibold text-on-surface mb-1">{{ __('superadmin.rooms.field_slug') }} <span class="text-error">*</span></label>
                <input type="text" id="edit-room-slug" name="slug" required pattern="[a-z0-9-]+" class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary">
            </div>
            <div>
                <label for="edit-room-status" class="block text-xs font-semibold text-on-surface mb-1">{{ __('superadmin.rooms.field_status') }}</label>
                <select id="edit-room-status" name="status" class="w-full px-3 py-2 bg-surface border border-outline-variant rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary">
                    @foreach (['active', 'inactive', 'archived'] as $value)
                        <option value="{{ $value }}">{{ __('superadmin.common.'.$value) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-on-surface mb-1">{{ __('superadmin.rooms.field_admins') }}</label>
                <div id="edit-room-admin-options" class="max-h-40 overflow-y-auto space-y-1 border border-outline-variant rounded-lg p-2">
                    <p class="sa-empty">{{ __('global.common.loading') }}</p>
                </div>
            </div>
            <div class="pt-2 flex items-center justify-end gap-2.5 border-t border-outline-variant">
                <button type="button" class="px-4 py-2 rounded-lg bg-surface-container hover:bg-surface-container-high text-on-surface text-xs font-semibold transition-colors cursor-pointer" data-modal-close>{{ __('superadmin.common.cancel') }}</button>
                <button type="submit" class="px-4 py-2 rounded-lg bg-primary hover:bg-primary-container text-on-primary text-xs font-semibold transition-colors flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <span class="material-symbols-outlined text-[16px]">save</span>{{ __('superadmin.common.save') }}
                </button>
            </div>
        </form>
    </x-superadmin.modal>
@endsection

@push('scripts')
    <script>
        const roomId = @json(request()->route('room'));
        const roomNotice = (message, type = 'success') => {
            const n = document.querySelector('#notice');
            n.textContent = message;
            n.className = `sa-notice ${type} is-visible`;
        };
        // Clone one of the <template> empty states, optionally overriding its description.
        const emptyStateHtml = (templateId, description = null) => {
            const wrapper = document.createElement('div');
            wrapper.innerHTML = document.getElementById(templateId).innerHTML;
            if (description !== null) wrapper.querySelector('[data-empty-description]').textContent = description;
            return wrapper.innerHTML;
        };
        const emptyRow = (html) => `<tr><td colspan="3">${html}</td></tr>`;
        async function loadRoom() {
            const {
                data: room
            } = await dfApi(`/superadmin/rooms/${roomId}`);
            document.querySelector('#room-name').textContent = room.name;
            document.querySelector('#room-meta').innerHTML =
                `<span class="font-mono">${escapeHtml(room.slug)}</span> · ${statusPill(room.status)}`;
            document.querySelector('#room-members').textContent = room.room_users_count ?? 0;
            document.querySelector('#room-active-members').textContent = (room.room_users || []).filter(user => user
                .status === 'active').length;
            document.querySelector('#room-campaigns').textContent = room.campaigns_count ?? (room.campaigns || [])
                .length;
            document.querySelector('#room-admins').textContent = room.admins_count ?? (room.admins || []).length;
            document.querySelector('#room-admin-list').innerHTML = (room.admins || []).map(admin => `
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <span class="superadmin-avatar shrink-0">${escapeHtml(admin.name.slice(0, 1).toUpperCase())}</span>
                            <span class="min-w-0">
                                <strong class="block truncate">${escapeHtml(admin.name)}</strong>
                                <small class="block truncate text-outline">${escapeHtml(admin.email)}</small>
                            </span>
                        </div>
                    </td>
                    <td class="whitespace-nowrap">
                        <span class="inline-flex items-center gap-1 font-semibold">
                            <span class="material-symbols-outlined text-[16px]">${admin.role === 'superadmin' ? 'shield_person' : 'person'}</span>${escapeHtml(admin.role)}
                        </span>
                    </td>
                    <td class="whitespace-nowrap">${statusPill(admin.status)}</td>
                </tr>
            `).join('') || emptyRow(emptyStateHtml('tpl-no-admins'));
            const statusBtn = document.querySelector('#room-status-action');
            const willEnable = room.status !== 'active';
            statusBtn.innerHTML = `<span class="material-symbols-outlined text-[16px]">${willEnable ? 'toggle_on' : 'toggle_off'}</span>${willEnable ? 'Enable room' : 'Disable room'}`;
            statusBtn.disabled = false;
            statusBtn.classList.remove('opacity-75', 'cursor-wait');
            delete statusBtn.dataset.originalHtml;
            statusBtn.onclick = () => updateRoomStatus(willEnable ? 'active' : 'inactive');
            populateEditForm(room);
        }
        function populateEditForm(room) {
            const form = document.querySelector('#edit-room-form');
            if (!form) return;
            form.name.value = room.name;
            form.slug.value = room.slug;
            form.status.value = room.status;
            loadEditRoomAdminOptions((room.admins || []).map(admin => admin.id));
        }
        async function loadEditRoomAdminOptions(selectedIds) {
            const container = document.querySelector('#edit-room-admin-options');
            if (!container) return;
            try {
                const admins = [];
                let nextPageUrl = '{{ route('superadmin.admins.index', ['role' => 'admin']) }}';
                while (nextPageUrl) {
                    const { data: page } = await dfApi(nextPageUrl);
                    admins.push(...page.data);
                    nextPageUrl = page.next_page_url;
                }
                const selected = new Set(selectedIds.map(Number));
                container.innerHTML = admins.map(admin => `
                    <label class="flex items-center gap-2 px-1 py-1 text-xs text-on-surface cursor-pointer">
                        <input type="checkbox" name="admin_ids[]" value="${admin.id}" ${selected.has(Number(admin.id)) ? 'checked' : ''}>
                        <span>${escapeHtml(admin.name)} <span class="text-outline">(${escapeHtml(admin.email)})</span></span>
                    </label>
                `).join('') || `<p class="sa-empty">${@js(__('superadmin.rooms.no_admin_options'))}</p>`;
            } catch (error) {
                container.innerHTML = `<p class="sa-empty">${escapeHtml(error.message)}</p>`;
            }
        }
        function resetSubmitButton(button) {
            if (!button) return;
            button.disabled = false;
            button.classList.remove('opacity-75', 'cursor-wait');
            if (button.dataset.originalHtml) button.innerHTML = button.dataset.originalHtml;
        }
        // Mirrors resources/js/shared/submit-loading.js's renderSubmitLoading(), which is only
        // reachable from module scripts; this page's inline script needs its own copy.
        function renderButtonLoading(button) {
            const label = document.body.dataset.submitLoadingText || @js(__('global.common.loading'));
            button.innerHTML = `<span class="inline-flex items-center justify-center gap-1.5"><span class="material-symbols-outlined animate-spin text-[16px]" aria-hidden="true">progress_activity</span><span>${escapeHtml(label)}</span></span>`;
        }
        document.querySelector('#edit-room-form')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const submitButton = form.querySelector('button[type="submit"]');
            const errorBox = document.querySelector('#edit-room-modal-error');
            errorBox?.classList.add('hidden');
            const adminIds = [...form.querySelectorAll('input[name="admin_ids[]"]:checked')].map(input => Number(input.value));
            try {
                await dfApi(`/superadmin/rooms/${roomId}`, {
                    method: 'PATCH',
                    body: { name: form.name.value, slug: form.slug.value, status: form.status.value, admin_ids: adminIds },
                });
                roomNotice(@js(__('superadmin.rooms.updated')));
                window.closeSuperadminModal('edit-room-modal');
                await loadRoom();
            } catch (error) {
                if (errorBox) {
                    errorBox.textContent = error.message;
                    errorBox.classList.remove('hidden');
                } else {
                    roomNotice(error.message, 'error');
                }
            } finally {
                resetSubmitButton(submitButton);
            }
        });
        async function updateRoomStatus(status) {
            const statusBtn = document.querySelector('#room-status-action');
            if (statusBtn) {
                statusBtn.dataset.originalHtml = statusBtn.dataset.originalHtml || statusBtn.innerHTML;
                statusBtn.disabled = true;
                statusBtn.classList.add('opacity-75', 'cursor-wait');
                renderButtonLoading(statusBtn);
            }
            try {
                await dfApi(`/superadmin/rooms/${roomId}/status`, {
                    method: 'PATCH',
                    body: {
                        status
                    }
                });
                roomNotice('Đã cập nhật trạng thái Room.');
                await loadRoom();
            } catch (error) {
                roomNotice(error.message, 'error');
                resetSubmitButton(statusBtn);
            }
        }
        function deleteRoom() {
            openSuperadminConfirm({
                message: @js(__('superadmin.rooms.confirm_delete')),
                onConfirm: async () => {
                    await dfApi(`/superadmin/rooms/${roomId}`, { method: 'DELETE' });
                    roomNotice(@js(__('superadmin.rooms.deleted')));
                    window.setTimeout(() => window.location.href = '{{ route('superadmin.rooms.page') }}', 500);
                },
            });
        }
        loadRoom().catch(error => {
            roomNotice(error.message, 'error');
            document.querySelector('#room-admin-list').innerHTML = emptyRow(emptyStateHtml('tpl-load-failed', error.message));
        });
    </script>
@endpush

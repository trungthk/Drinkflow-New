@extends('superadmin.layout', ['active' => 'admins'])

@section('title', __('superadmin.admins.profile'))

@section('content')
    <div class="superadmin-heading">
        <div><a class="sa-back-link" href="{{ route('superadmin.admins.page') }}">← {{ __('superadmin.admins.title') }}</a>
            <h1 id="admin-name">{{ __('superadmin.admins.profile') }}</h1>
            <p id="admin-meta">{{ __('superadmin.admins.loading_profile') }}</p>
        </div>
        <div class="superadmin-actions">
            <button class="sa-button secondary" data-modal-open="reset-password-modal" type="button"><span class="material-symbols-outlined text-[16px]">lock_reset</span>{{ __('superadmin.admins.reset_password') }}</button>
            <button class="sa-button warning" id="admin-status" type="button"><span class="material-symbols-outlined text-[16px]">lock</span>{{ __('superadmin.admins.update_status') }}</button>
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
            <div id="admin-summary" class="sa-detail-list">
                <x-superadmin.empty-state loading :title="__('superadmin.common.loading_title')" :description="__('superadmin.common.loading_description')" />
            </div>
        </section>
        <section class="sa-card sa-section">
            <div class="sa-section-header">
                <div>
                    <h2>{{ __('superadmin.admins.room_assignment') }}</h2>
                    <p>{{ __('superadmin.admins.room_assignment_description') }}</p>
                </div>
                <button class="sa-button secondary" id="edit-rooms" type="button"><span class="material-symbols-outlined text-[16px]">tune</span>{{ __('superadmin.admins.manage_access') }}</button>
            </div>
            <div id="admin-rooms" class="sa-health-list">
                <x-superadmin.empty-state loading :title="__('superadmin.common.loading_title')" :description="__('superadmin.common.loading_description')" />
            </div>
        </section>
    </div>

    <x-superadmin.modal id="room-access-modal" icon="tune" :title="__('superadmin.admins.room_access')" :description="__('superadmin.admins.room_access_description')">
        <form id="room-access-form" class="space-y-4">
            @csrf
            <div id="room-options" class="max-h-80 overflow-y-auto space-y-1 border border-outline-variant rounded-lg p-2">
                <x-superadmin.empty-state loading :bordered="false" :title="__('superadmin.common.loading_title')" :description="__('superadmin.admins.loading_rooms')" />
            </div>
            <div class="pt-2 flex items-center justify-end gap-2.5 border-t border-outline-variant">
                <button type="button" class="px-4 py-2 rounded-lg bg-surface-container hover:bg-surface-container-high text-on-surface text-xs font-semibold transition-colors cursor-pointer" data-modal-close>{{ __('superadmin.common.cancel') }}</button>
                <button type="submit" class="px-4 py-2 rounded-lg bg-primary hover:bg-primary-container text-on-primary text-xs font-semibold transition-colors flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <span class="material-symbols-outlined text-[16px]">save</span>{{ __('superadmin.admins.save_assignment') }}
                </button>
            </div>
        </form>
    </x-superadmin.modal>

    <x-superadmin.modal id="reset-password-modal" icon="lock_reset" :title="__('superadmin.admins.reset_password')" max-width="max-w-md">
        <form id="reset-password-form" class="space-y-4">
            @csrf
            <p id="reset-password-description" class="text-xs text-on-surface-variant"></p>
            <div>
                <label for="reset-password" class="block text-xs font-semibold text-on-surface mb-1">{{ __('superadmin.admins.field_password') }} <span class="text-error">*</span></label>
                <x-superadmin.password-input id="reset-password" name="password" required minlength="8" />
            </div>
            <div>
                <label for="reset-password-confirmation" class="block text-xs font-semibold text-on-surface mb-1">{{ __('superadmin.admins.field_password_confirmation') }} <span class="text-error">*</span></label>
                <x-superadmin.password-input id="reset-password-confirmation" name="password_confirmation" required minlength="8" />
                <p class="mt-1 text-[11px] text-outline">{{ __('superadmin.admins.password_hint') }}</p>
            </div>
            <div class="pt-2 flex items-center justify-end gap-2.5 border-t border-outline-variant">
                <button type="button" class="px-4 py-2 rounded-lg bg-surface-container hover:bg-surface-container-high text-on-surface text-xs font-semibold transition-colors cursor-pointer" data-modal-close>{{ __('superadmin.common.cancel') }}</button>
                <button type="submit" class="px-4 py-2 rounded-lg bg-primary hover:bg-primary-container text-on-primary text-xs font-semibold transition-colors flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <span class="material-symbols-outlined text-[16px]">lock_reset</span>{{ __('superadmin.admins.save_password') }}
                </button>
            </div>
        </form>
    </x-superadmin.modal>

    {{-- Empty states cloned by the page script, so every string stays translated through __(). --}}
    <template id="tpl-rooms-loading"><x-superadmin.empty-state loading :bordered="false" :title="__('superadmin.common.loading_title')" :description="__('superadmin.admins.loading_rooms')" /></template>
    <template id="tpl-no-assigned-rooms"><x-superadmin.empty-state icon="meeting_room" :title="__('superadmin.admins.no_assigned_rooms_title')" :description="__('superadmin.admins.no_assigned_rooms_description')" /></template>
    <template id="tpl-no-rooms"><x-superadmin.empty-state icon="domain_disabled" :bordered="false" :title="__('superadmin.admins.no_rooms_title')" :description="__('superadmin.admins.no_rooms_description')" /></template>
    <template id="tpl-load-failed"><x-superadmin.empty-state icon="error" :title="__('superadmin.common.load_failed_title')" description="" /></template>
@endsection

@push('scripts')
    <script>
        const adminId = @json(request()->route('admin'));
        const roomsUrl = @json(route('superadmin.rooms.index'));
        const roleLabels = @json(['admin' => __('superadmin.admins.role_admin'), 'superadmin' => __('superadmin.admins.role_superadmin')]);
        let currentAdmin = null;
        const adminNotice = (message, type = 'success') => {
            const n = document.querySelector('#notice');
            n.textContent = message;
            n.className = `sa-notice ${type} is-visible`;
        };
        // The shared submit-loading handler (resources/js/superadmin/loading.js) swaps the submit button
        // for a spinner; these modals stay on the page, so restore it once the request settles.
        const resetSubmitButton = (form) => {
            const button = form.querySelector('button[type="submit"]');
            if (!button) return;
            button.disabled = false;
            button.classList.remove('opacity-75', 'cursor-wait');
            if (button.dataset.originalHtml) button.innerHTML = button.dataset.originalHtml;
        };
        const showModalError = (modalId, message) => {
            const errorBox = document.querySelector(`#${modalId}-error`);
            errorBox.textContent = message;
            errorBox.classList.remove('hidden');
        };
        const hideModalError = (modalId) => document.querySelector(`#${modalId}-error`)?.classList.add('hidden');
        // Fill a container with one of the <template> empty states, optionally overriding its description.
        const renderEmpty = (container, templateId, description = null) => {
            container.innerHTML = document.getElementById(templateId).innerHTML;
            if (description !== null) container.querySelector('[data-empty-description]').textContent = description;
        };
        const dateTimeFormat = new Intl.DateTimeFormat(document.documentElement.lang || undefined, { dateStyle: 'medium', timeStyle: 'short' });
        const formatDateTime = (value, fallback) => value ? dateTimeFormat.format(new Date(value)) : fallback;
        const detailRow = (label, valueHtml) => `<div class="sa-detail-row"><span>${escapeHtml(label)}</span><strong>${valueHtml}</strong></div>`;

        async function loadAdmin() {
            const {
                data: admin
            } = await dfApi(`/superadmin/admins/${adminId}`);
            currentAdmin = admin;
            const roleLabel = roleLabels[admin.role] || admin.role;
            document.querySelector('#admin-name').textContent = admin.name;
            document.querySelector('#admin-meta').innerHTML = `${escapeHtml(admin.email)} · ${escapeHtml(roleLabel)} · ${statusPill(admin.status)}`;
            document.querySelector('#admin-summary').innerHTML = [
                detailRow(@js(__('superadmin.common.name')), escapeHtml(admin.name)),
                detailRow(@js(__('superadmin.common.email')), escapeHtml(admin.email)),
                detailRow(@js(__('superadmin.admins.field_phone')), escapeHtml(admin.phone || @js(__('superadmin.admins.not_provided')))),
                detailRow(@js(__('superadmin.common.role')), escapeHtml(roleLabel)),
                detailRow(@js(__('superadmin.common.status')), statusPill(admin.status)),
                detailRow(@js(__('superadmin.admins.created_at')), escapeHtml(formatDateTime(admin.created_at, '—'))),
                detailRow(@js(__('superadmin.admins.last_login')), escapeHtml(formatDateTime(admin.last_login_at, @js(__('superadmin.admins.never_logged_in'))))),
            ].join('');
            const roomsContainer = document.querySelector('#admin-rooms');
            if ((admin.rooms || []).length === 0) {
                renderEmpty(roomsContainer, 'tpl-no-assigned-rooms');
            } else {
                roomsContainer.innerHTML = admin.rooms.map(room =>
                    `<div class="sa-health-row"><div><strong>${escapeHtml(room.name)}</strong><small>${escapeHtml(room.slug)}</small></div><span class="status-pill status-active">${@js(__('superadmin.common.assigned'))}</span></div>`
                ).join('');
            }
            const statusButton = document.querySelector('#admin-status');
            const isActive = admin.status === 'active';
            statusButton.className = `sa-button ${isActive ? 'warning' : ''}`;
            statusButton.innerHTML = `<span class="material-symbols-outlined text-[16px]">${isActive ? 'lock' : 'lock_open'}</span>${escapeHtml(isActive ? @js(__('superadmin.admins.block_account')) : @js(__('superadmin.admins.unblock_account')))}`;
            statusButton.disabled = false;
            statusButton.onclick = () => isActive ? confirmBlock() : unblockAdmin(statusButton);
            document.querySelector('#reset-password-description').textContent = @js(__('superadmin.admins.reset_password_description')).replace(':name', admin.name);
        }

        async function openRoomAssignment() {
            const container = document.querySelector('#room-options');
            hideModalError('room-access-modal');
            renderEmpty(container, 'tpl-rooms-loading');
            window.openSuperadminModal('room-access-modal');
            try {
                const [{ data: admin }, { data: firstPage }] = await Promise.all([dfApi(`/superadmin/admins/${adminId}`), dfApi(roomsUrl)]);
                const assigned = new Set((admin.rooms || []).map(room => Number(room.id)));
                const rooms = [...firstPage.data];
                let nextPageUrl = firstPage.next_page_url;
                while (nextPageUrl) {
                    const { data: page } = await dfApi(nextPageUrl);
                    rooms.push(...page.data);
                    nextPageUrl = page.next_page_url;
                }
                if (rooms.length === 0) {
                    renderEmpty(container, 'tpl-no-rooms');
                    return;
                }
                const statusLabels = JSON.parse(document.body.dataset.statusLabels || '{}');
                container.innerHTML = rooms.map(room => `
                    <label class="flex items-center justify-between gap-3 px-2 py-2 rounded-lg text-xs text-on-surface cursor-pointer hover:bg-surface-container">
                        <span class="min-w-0"><strong class="block truncate">${escapeHtml(room.name)}</strong><small class="block truncate text-outline">${escapeHtml(room.slug)} · ${escapeHtml(statusLabels[room.status] || room.status)}</small></span>
                        <input type="checkbox" name="room_ids[]" value="${room.id}" ${assigned.has(Number(room.id)) ? 'checked' : ''}>
                    </label>
                `).join('');
            } catch (error) {
                container.innerHTML = '';
                showModalError('room-access-modal', error.message);
            }
        }

        document.querySelector('#room-access-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            hideModalError('room-access-modal');
            const roomIds = [...form.querySelectorAll('input[name="room_ids[]"]:checked')].map(input => Number(input.value));
            try {
                await dfApi(`/superadmin/admins/${adminId}/rooms`, { method: 'PUT', body: { room_ids: roomIds } });
                window.closeSuperadminModal('room-access-modal');
                adminNotice(@js(__('superadmin.admins.assignment_updated')));
                await loadAdmin();
            } catch (error) {
                showModalError('room-access-modal', error.message);
            } finally {
                resetSubmitButton(form);
            }
        });

        // Let the browser block a mismatched confirmation before submit (and before the loading spinner).
        const bindPasswordConfirmation = (form) => {
            const check = () => form.password_confirmation.setCustomValidity(
                form.password.value === form.password_confirmation.value ? '' : @js(__('superadmin.admins.password_mismatch')));
            form.password.addEventListener('input', check);
            form.password_confirmation.addEventListener('input', check);
        };
        bindPasswordConfirmation(document.querySelector('#reset-password-form'));

        document.querySelector('#reset-password-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            hideModalError('reset-password-modal');
            try {
                await dfApi(`/superadmin/admins/${adminId}/reset-password`, { method: 'POST', body: { password: form.password.value } });
                form.reset();
                window.closeSuperadminModal('reset-password-modal');
                adminNotice(@js(__('superadmin.admins.password_reset')));
            } catch (error) {
                showModalError('reset-password-modal', error.message);
            } finally {
                resetSubmitButton(form);
            }
        });

        function confirmBlock() {
            openSuperadminConfirm({
                message: @js(__('superadmin.admins.confirm_block')).replace(':name', currentAdmin?.name || ''),
                confirmLabel: @js(__('superadmin.admins.block_account')),
                onConfirm: () => updateAdminStatus('blocked', true),
            });
        }

        async function unblockAdmin(button) {
            const originalHtml = button.innerHTML;
            button.disabled = true;
            window.renderSubmitLoading(button);
            await updateAdminStatus('active');
            // On success loadAdmin() already re-rendered the button; only restore it after a failure.
            if (button.disabled) {
                button.disabled = false;
                button.innerHTML = originalHtml;
            }
        }

        async function updateAdminStatus(status, rethrow = false) {
            try {
                await dfApi(`/superadmin/admins/${adminId}/status`, { method: 'PATCH', body: { status } });
                adminNotice(@js(__('superadmin.admins.updated')));
                await loadAdmin();
            } catch (error) {
                if (rethrow) throw error;
                adminNotice(error.message, 'error');
            }
        }

        document.querySelector('#edit-rooms').onclick = openRoomAssignment;
        loadAdmin().catch(error => {
            adminNotice(error.message, 'error');
            document.querySelectorAll('#admin-summary, #admin-rooms').forEach(container => renderEmpty(container, 'tpl-load-failed', error.message));
        });
    </script>
@endpush

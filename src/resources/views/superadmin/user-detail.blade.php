@extends('superadmin.layout', ['active' => 'users', 'title' => __('superadmin.users.profile')])

@section('content')
    <div class="superadmin-heading">
        <div><a class="sa-back-link" href="{{ route('superadmin.global-users.page') }}">← {{ __('superadmin.layout.global_users') }}</a>
            <h1 id="user-name">{{ __('superadmin.users.profile') }}</h1>
            <p id="user-meta">{{ __('superadmin.users.loading_profile') }}</p>
        </div>
        <div class="superadmin-actions">
            <button class="sa-button" id="user-status-action" type="button"><span class="material-symbols-outlined text-[16px]">toggle_on</span>{{ __('superadmin.users.update_status') }}</button>
            <button class="sa-button danger" id="user-delete-action" type="button"><span class="material-symbols-outlined text-[16px]">delete</span>{{ __('superadmin.users.delete_user') }}</button>
        </div>
    </div>
    <div id="notice" class="sa-notice" role="status"></div>
    <div class="sa-split">
        <section class="sa-card sa-section">
            <div class="sa-section-header">
                <div>
                    <h2>{{ __('superadmin.users.identity_metadata') }}</h2>
                    <p>{{ __('superadmin.users.identity_description') }}</p>
                </div>
            </div>
            <div id="identity-list" class="sa-detail-list">
                <x-superadmin.empty-state loading :title="__('superadmin.common.loading_title')" :description="__('superadmin.common.loading_description')" />
            </div>
        </section>
        <section class="sa-card sa-section">
            <div class="sa-section-header">
                <div>
                    <h2>{{ __('superadmin.users.room_memberships') }}</h2>
                    <p>{{ __('superadmin.users.membership_description') }}</p>
                </div>
            </div>
            <div class="sa-table-wrap">
                <table class="sa-table">
                    <thead>
                        <tr>
                            <th>{{ __('superadmin.common.room') }}</th>
                            <th>{{ __('superadmin.common.status') }}</th>
                            <th class="whitespace-nowrap">{{ __('superadmin.users.devices') }}</th>
                            <th class="text-right">{{ __('superadmin.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody id="membership-list">
                        <tr><td colspan="4"><x-superadmin.empty-state loading :title="__('superadmin.common.loading_title')" :description="__('superadmin.common.loading_description')" /></td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    {{-- Empty states cloned by the page script, so every string stays translated through __(). --}}
    <template id="tpl-no-memberships"><x-superadmin.empty-state icon="meeting_room" :title="__('superadmin.users.no_memberships_title')" :description="__('superadmin.users.no_memberships_description')" /></template>
    <template id="tpl-load-failed"><x-superadmin.empty-state icon="error" :title="__('superadmin.common.load_failed_title')" description="" /></template>
@endsection

@push('scripts')
    <script>
        const globalUserId = @json(request()->route('globalUser'));
        const userNotice = (message, type = 'success') => {
            const n = document.querySelector('#notice');
            n.textContent = message;
            n.className = `sa-notice ${type} is-visible`;
        };
        const formatDateTime = (value) => value ? new Date(value).toLocaleString(document.documentElement.lang || undefined) : @js(__('superadmin.users.never_logged_in'));
        // Clone one of the <template> empty states, optionally overriding its description.
        const emptyStateHtml = (templateId, description = null) => {
            const wrapper = document.createElement('div');
            wrapper.innerHTML = document.getElementById(templateId).innerHTML;
            if (description !== null) wrapper.querySelector('[data-empty-description]').textContent = description;
            return wrapper.innerHTML;
        };
        const emptyRow = (html) => `<tr><td colspan="4">${html}</td></tr>`;

        let currentUser = null;

        async function loadUser() {
            const {
                data: user
            } = await dfApi(`/superadmin/global-users/${globalUserId}`);
            currentUser = user;
            document.querySelector('#user-name').textContent = user.name;
            document.querySelector('#user-meta').innerHTML =
                `<span class="font-mono">${escapeHtml(user.email)}</span> · ${statusPill(user.status)}`;
            document.querySelector('#identity-list').innerHTML = `
                <div class="sa-detail-row"><span>${@js(__('superadmin.common.name'))}</span><strong>${escapeHtml(user.name)}</strong></div>
                <div class="sa-detail-row"><span>${@js(__('superadmin.common.email'))}</span><strong>${escapeHtml(user.email)}</strong></div>
                <div class="sa-detail-row"><span>${@js(__('superadmin.common.status'))}</span><strong>${statusPill(user.status)}</strong></div>
                <div class="sa-detail-row"><span>${@js(__('superadmin.users.created_at'))}</span><strong>${formatDateTime(user.created_at)}</strong></div>
                <div class="sa-detail-row"><span>${@js(__('superadmin.users.last_login'))}</span><strong>${user.last_login_at ? formatDateTime(user.last_login_at) : @js(__('superadmin.users.never_logged_in'))}</strong></div>
                <div class="sa-detail-row"><span>${@js(__('superadmin.users.rooms_joined'))}</span><strong>${user.room_users_count ?? 0}</strong></div>
                <div class="sa-detail-row"><span>${@js(__('superadmin.users.total_orders'))}</span><strong>${user.orders_count ?? 0}</strong></div>
                ${(user.oauth_identities || []).map(identity => `<div class="sa-detail-row"><span>${escapeHtml(identity.provider)}</span><strong>${escapeHtml(identity.provider_email || @js(__('superadmin.users.linked_identity')))}<small>${escapeHtml(identity.last_login_at || @js(__('superadmin.users.no_login_metadata')))}</small></strong></div>`).join('')}
            `;
            document.querySelector('#membership-list').innerHTML = (user.room_users || []).map(membership => `
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <span class="superadmin-avatar shrink-0">${escapeHtml((membership.room?.name || 'R').slice(0, 1).toUpperCase())}</span>
                            <span class="min-w-0">
                                <strong class="block truncate">${escapeHtml(membership.room?.name || 'Room')}</strong>
                                <small class="block truncate text-outline">${escapeHtml(membership.room?.slug || '')}</small>
                            </span>
                        </div>
                    </td>
                    <td class="whitespace-nowrap">${statusPill(membership.status)}</td>
                    <td class="whitespace-nowrap">
                        <span class="inline-flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px]">devices</span>${(membership.devices || []).length}
                        </span>
                    </td>
                    <td>
                        <div class="flex items-center justify-end gap-2 whitespace-nowrap">
                            <button class="sa-button danger" type="button" data-action="remove-membership" data-room-user-id="${membership.id}"><span class="material-symbols-outlined text-[16px]">person_remove</span>${@js(__('superadmin.users.remove'))}</button>
                        </div>
                    </td>
                </tr>
            `).join('') || emptyRow(emptyStateHtml('tpl-no-memberships'));
            // A soft-deleted account is read-only: no status change, delete or membership removal.
            const isDeleted = user.status === 'deleted';
            document.querySelector('#user-delete-action').hidden = isDeleted;
            document.querySelectorAll('[data-action="remove-membership"]').forEach(btn => { btn.hidden = isDeleted; });
            const statusBtn = document.querySelector('#user-status-action');
            statusBtn.hidden = isDeleted;
            const isBlocked = user.status === 'blocked';
            statusBtn.innerHTML = `<span class="material-symbols-outlined text-[16px]">${isBlocked ? 'lock_open' : 'lock'}</span>${isBlocked ? @js(__('superadmin.common.unblock')) : @js(__('superadmin.common.block'))}`;
            statusBtn.className = `sa-button ${isBlocked ? '' : 'warning'}`;
            statusBtn.disabled = false;
            statusBtn.classList.remove('opacity-75', 'cursor-wait');
            delete statusBtn.dataset.originalHtml;
        }

        document.querySelector('#user-status-action')?.addEventListener('click', () => {
            if (!currentUser) return;
            const isBlocked = currentUser.status === 'blocked';
            const nextStatus = isBlocked ? 'active' : 'blocked';
            openSuperadminConfirm({
                message: (isBlocked ? @js(__('superadmin.users.confirm_unblock')) : @js(__('superadmin.users.confirm_block'))).replace(':name', currentUser.name),
                description: isBlocked ? @js(__('superadmin.users.unblock_description')) : @js(__('superadmin.users.block_description')),
                confirmIcon: isBlocked ? 'lock_open' : 'lock',
                confirmLabel: isBlocked ? @js(__('superadmin.common.unblock')) : @js(__('superadmin.common.block')),
                onConfirm: async () => {
                    await dfApi(`/superadmin/global-users/${globalUserId}/status`, { method: 'PATCH', body: { status: nextStatus } });
                    userNotice(@js(__('superadmin.users.status_updated')));
                    await loadUser();
                },
            });
        });

        document.addEventListener('click', (event) => {
            const removeBtn = event.target.closest('[data-action="remove-membership"]');
            if (!removeBtn) return;
            openSuperadminConfirm({
                message: @js(__('superadmin.users.confirm_remove_membership')),
                confirmLabel: @js(__('superadmin.users.remove')),
                onConfirm: async () => {
                    await dfApi(`/superadmin/global-users/${globalUserId}/memberships/${removeBtn.dataset.roomUserId}`, { method: 'DELETE' });
                    userNotice(@js(__('superadmin.users.membership_removed')));
                    await loadUser();
                },
            });
        });

        document.querySelector('#user-delete-action')?.addEventListener('click', () => {
            openSuperadminConfirm({
                message: @js(__('superadmin.users.confirm_delete')),
                confirmLabel: @js(__('superadmin.common.delete')),
                onConfirm: async () => {
                    await dfApi(`/superadmin/global-users/${globalUserId}`, { method: 'DELETE' });
                    userNotice(@js(__('superadmin.users.deleted')));
                    window.setTimeout(() => window.location.href = '{{ route('superadmin.global-users.page') }}', 500);
                },
            });
        });

        loadUser().catch(error => {
            userNotice(error.message, 'error');
            const failed = emptyStateHtml('tpl-load-failed', error.message);
            document.querySelector('#identity-list').innerHTML = failed;
            document.querySelector('#membership-list').innerHTML = emptyRow(failed);
        });
    </script>
@endpush

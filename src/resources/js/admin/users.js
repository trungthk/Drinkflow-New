/**
 * Admin Room Users & Devices Controller
 */
export function initAdminUsers() {
    const searchInput = document.querySelector('#user-search');
    const filterForm = document.querySelector('#users-filter-form');
    const statusSelect = document.querySelector('#user-status-filter');
    const rows = document.querySelectorAll('[data-user-row]');
    const noResults = document.querySelector('#users-no-filter-results');
    const modal = document.querySelector('#device-modal');
    const modalBackdrop = document.querySelector('#device-backdrop');
    const modalBody = document.querySelector('#device-modal-body');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const roomSlug = document.querySelector('[data-room-slug]')?.dataset.roomSlug || window.__DF_ROOM_SLUG__ || '';
    const actionModal = document.querySelector('#user-action-modal');
    const actionTitle = document.querySelector('#user-action-title');
    const actionMessage = document.querySelector('#user-action-message');
    const actionConfirm = document.querySelector('#user-action-confirm');
    const actionCancel = document.querySelector('#user-action-cancel');
    let pendingAction = null;

    if (!searchInput && !rows.length && !modal) return;

    const closeDeviceModal = () => {
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };
    window.closeDeviceModal = closeDeviceModal;
    modalBackdrop?.addEventListener('click', closeDeviceModal);

    function applyUserFilters() {
        const term = searchInput?.value.trim().toLowerCase() || '';
        const activeStatus = statusSelect?.value || 'all';
        let visibleRows = 0;

        rows.forEach(row => {
            const matchesSearch = row.dataset.search?.includes(term);
            const matchesStatus = activeStatus === 'all' || row.dataset.status === activeStatus;
            row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
            if (matchesSearch && matchesStatus) visibleRows += 1;
        });
        noResults?.classList.toggle('hidden', rows.length === 0 || visibleRows > 0);
    }

    searchInput?.addEventListener('admin:search', applyUserFilters);
    statusSelect?.addEventListener('change', applyUserFilters);
    searchInput?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            filterForm?.requestSubmit();
        }
    });

    const closeActionModal = () => {
        actionModal?.classList.add('hidden');
        actionModal?.classList.remove('flex');
        pendingAction = null;
    };

    actionCancel?.addEventListener('click', closeActionModal);
    actionModal?.addEventListener('click', (event) => {
        if (event.target === actionModal) closeActionModal();
    });

    const openActionModal = (title, message, action) => {
        if (!actionModal || !actionConfirm) return;
        actionTitle.textContent = title;
        actionMessage.textContent = message;
        actionConfirm.textContent = actionModal.dataset.confirmLabel || 'Confirm';
        pendingAction = action;
        actionModal.classList.remove('hidden');
        actionModal.classList.add('flex');
    };

    actionConfirm?.addEventListener('click', async () => {
        if (!pendingAction) return;
        const action = pendingAction;
        actionConfirm.disabled = true;
        actionConfirm.classList.add('opacity-60', 'cursor-not-allowed');
        try {
            await action();
        } finally {
            actionConfirm.disabled = false;
            actionConfirm.classList.remove('opacity-60', 'cursor-not-allowed');
        }
    });

    const openTrustModalFn = async function(roomUserId, memberName) {
        if (!modal || !modalBody) return;
        const titleEl = document.querySelector('#device-modal-title');
        if (titleEl) titleEl.textContent = `${memberName}`;
        modalBody.innerHTML = '<div class="py-8 text-center text-outline text-xs"><span class="material-symbols-outlined animate-spin text-[24px]">progress_activity</span></div>';
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        try {
            const res = await fetch(`/admin/${roomSlug}/room-users/${roomUserId}`, {
                headers: { 'Accept': 'application/json' }
            });
            const { data: user } = await res.json();

            if (!user.devices || user.devices.length === 0) {
                modalBody.innerHTML = `
                    <div class="py-8 text-center text-outline">
                        <span class="material-symbols-outlined text-3xl mb-1 text-outline-variant">smartphone</span>
                        <p class="text-xs">Không có thiết bị đăng ký</p>
                    </div>
                `;
                return;
            }

            modalBody.innerHTML = user.devices.map(dev => `
                <div class="flex items-center justify-between p-3 bg-surface-container-low border border-outline-variant rounded-lg text-xs">
                    <div class="flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-[20px] text-primary">laptop_mac</span>
                        <div>
                            <div class="font-mono font-bold text-on-surface">${dev.device_uuid || dev.device_name || 'Device'}</div>
                            <div class="text-[11px] text-outline">${dev.last_seen_at || '—'}</div>
                        </div>
                    </div>
                    <div>
                        ${dev.status === 'revoked' ? `
                            <span class="px-2 py-0.5 rounded bg-rose-50 text-rose-700 text-[10px] font-semibold border border-rose-200">Đã hủy</span>
                        ` : `
                            <button type="button" data-revoke-device data-room-user-id="${roomUserId}" data-device-id="${dev.id}" class="px-2.5 py-1 bg-rose-50 text-rose-700 hover:bg-rose-100 rounded text-[11px] font-semibold border border-rose-200 transition-colors">
                                Hủy quyền
                            </button>
                        `}
                    </div>
                </div>
            `).join('');
        } catch(e) {
            console.error(e);
            modalBody.innerHTML = '<div class="py-8 text-center text-error text-xs">Error loading devices.</div>';
        }
    };

    window.openDeviceTrustModal = openTrustModalFn;
    window.openDeviceModal = openTrustModalFn;

    document.addEventListener('click', (event) => {
        const devicesButton = event.target.closest('[data-open-devices]');
        if (devicesButton) {
            openTrustModalFn(devicesButton.dataset.roomUserId, devicesButton.dataset.memberName || 'Member');
            return;
        }

        const statusButton = event.target.closest('[data-toggle-user-status]');
        if (statusButton) {
            window.toggleUserStatus(Number(statusButton.dataset.roomUserId), statusButton.dataset.newStatus);
            return;
        }

        const removeButton = event.target.closest('[data-remove-room-user]');
        if (removeButton) {
            window.removeRoomUser(Number(removeButton.dataset.roomUserId));
            return;
        }

        const revokeButton = event.target.closest('[data-revoke-device]');
        if (revokeButton) {
            window.revokeDevice(Number(revokeButton.dataset.roomUserId), Number(revokeButton.dataset.deviceId));
            return;
        }

        if (event.target.closest('[data-close-device-modal]')) closeDeviceModal();
    });

    window.revokeDevice = async function(roomUserId, deviceId) {
        if (!confirm('Revoke this device?')) return;
        try {
            const res = await fetch(`/admin/${roomSlug}/room-users/${roomUserId}/devices/${deviceId}/revoke`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
            if (res.ok) {
                closeDeviceModal();
                window.location.reload();
            } else {
                alert('Could not revoke device.');
            }
        } catch(e) {
            console.error(e);
        }
    };

    window.toggleUserStatus = async function(roomUserId, newStatus) {
        const isBlocking = newStatus === 'blocked';
        openActionModal(
            actionModal?.dataset.statusTitle || 'Toggle User Status',
            actionModal?.dataset[isBlocking ? 'blockMessage' : 'unblockMessage'] || 'Confirm status change?',
            async () => {
                const res = await fetch(`/admin/${roomSlug}/room-users/${roomUserId}/status`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({ status: newStatus })
                });
                if (res.ok) window.location.reload();
            }
        );
    };

    window.removeRoomUser = async function(roomUserId) {
        openActionModal(
            actionModal?.dataset.removeTitle || 'Remove User',
            actionModal?.dataset.removeMessage || 'Remove this user from the room?',
            async () => {
                const res = await fetch(`/admin/${roomSlug}/room-users/${roomUserId}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
                });
                if (res.ok) window.location.reload();
            }
        );
    };

}

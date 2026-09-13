/**
 * Admin Room Users & Devices Controller
 */
export function initAdminUsers() {
    const searchInput = document.querySelector('#user-search');
    const statusBtns = document.querySelectorAll('.user-status-filter');
    const rows = document.querySelectorAll('[data-user-row]');
    const modal = document.querySelector('#device-modal');
    const modalBackdrop = document.querySelector('#device-backdrop');
    const modalBody = document.querySelector('#device-modal-body');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const roomSlug = document.querySelector('[data-room-slug]')?.dataset.roomSlug || window.__DF_ROOM_SLUG__ || '';

    if (!searchInput && !rows.length && !modal) return;

    modalBackdrop?.addEventListener('click', closeDeviceModal);

    function applyUserFilters() {
        const term = searchInput?.value.trim().toLowerCase() || '';
        const activeStatus = document.querySelector('.user-status-filter.bg-primary')?.dataset.status || 'all';

        rows.forEach(row => {
            const matchesSearch = row.dataset.search?.includes(term);
            const matchesStatus = activeStatus === 'all' || row.dataset.status === activeStatus;
            row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
        });
    }

    searchInput?.addEventListener('input', applyUserFilters);
    statusBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            statusBtns.forEach(b => {
                b.classList.remove('bg-primary', 'text-on-primary');
                b.classList.add('bg-surface-container', 'text-on-surface');
            });
            btn.classList.add('bg-primary', 'text-on-primary');
            btn.classList.remove('bg-surface-container', 'text-on-surface');
            applyUserFilters();
        });
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
                            <button type="button" onclick="revokeDevice(${roomUserId}, ${dev.id})" class="px-2.5 py-1 bg-rose-50 text-rose-700 hover:bg-rose-100 rounded text-[11px] font-semibold border border-rose-200 transition-colors">
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
        if (!confirm(`Change user status to ${newStatus.toUpperCase()}?`)) return;
        try {
            const res = await fetch(`/admin/${roomSlug}/room-users/${roomUserId}/status`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ status: newStatus })
            });
            if (res.ok) window.location.reload();
            else alert('Could not update user status.');
        } catch(e) {
            console.error(e);
        }
    };

    window.closeDeviceModal = function() {
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };
}

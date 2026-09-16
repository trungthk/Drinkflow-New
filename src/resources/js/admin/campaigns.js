/**
 * Admin Campaigns List & Management
 */
export function initAdminCampaigns() {
    const searchInput = document.querySelector('#campaign-search');
    const filterForm = document.querySelector('#campaigns-filter-form');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const roomSlug = document.querySelector('[data-room-slug]')?.dataset.roomSlug || window.__DF_ROOM_SLUG__ || '';
    const duplicateModal = document.querySelector('#duplicate-campaign-modal');
    const closeModal = document.querySelector('#close-campaign-modal');
    const cancelModal = document.querySelector('#cancel-campaign-modal');

    let campaignToDuplicate = null;
    let campaignToClose = null;
    let campaignToCancel = null;

    if (!filterForm && !duplicateModal && !closeModal && !cancelModal) return;

    searchInput?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            filterForm?.requestSubmit();
        }
    });
    searchInput?.addEventListener('admin:search-cleared', () => filterForm?.requestSubmit());

    // Duplicate Campaign Modal
    const closeDuplicateModal = () => {
        duplicateModal?.classList.add('hidden');
        duplicateModal?.classList.remove('flex');
        campaignToDuplicate = null;
    };

    window.duplicateCampaign = function(id) {
        campaignToDuplicate = id;
        duplicateModal?.classList.remove('hidden');
        duplicateModal?.classList.add('flex');
    };

    document.querySelector('[data-duplicate-cancel]')?.addEventListener('click', closeDuplicateModal);
    document.querySelector('[data-duplicate-confirm]')?.addEventListener('click', async (e) => {
        if (!campaignToDuplicate) return;
        const btn = e.currentTarget;
        btn.disabled = true;
        const slug = roomSlug || window.__DF_ROOM_SLUG__;
        try {
            const res = await fetch(`/admin/${slug}/campaigns/${campaignToDuplicate}/duplicate`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
            if (res.ok) {
                window.location.reload();
            } else {
                const payload = await res.json().catch(() => ({}));
                window.notify?.(payload.message || 'Duplicate failed', 'error');
                closeDuplicateModal();
            }
        } catch (e) {
            console.error(e);
            closeDuplicateModal();
        } finally {
            btn.disabled = false;
        }
    });

    // Close Campaign Modal
    const hideCloseModal = () => {
        closeModal?.classList.add('hidden');
        closeModal?.classList.remove('flex');
        campaignToClose = null;
        const confirmBtn = closeModal?.querySelector('[data-close-confirm]');
        if (confirmBtn) {
            confirmBtn.disabled = false;
            confirmBtn.querySelector('[data-spinner]')?.classList.add('hidden');
        }
    };

    window.openCloseCampaignModal = function(id) {
        campaignToClose = id;
        closeModal?.classList.remove('hidden');
        closeModal?.classList.add('flex');
    };

    closeModal?.querySelector('[data-close-cancel]')?.addEventListener('click', hideCloseModal);
    closeModal?.querySelector('[data-close-confirm]')?.addEventListener('click', async (e) => {
        if (!campaignToClose) return;
        const btn = e.currentTarget;
        const spinner = btn.querySelector('[data-spinner]');
        btn.disabled = true;
        spinner?.classList.remove('hidden');

        const slug = roomSlug || window.__DF_ROOM_SLUG__;
        try {
            const res = await fetch(`/admin/${slug}/campaigns/${campaignToClose}/close`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
            if (!res.ok) {
                const payload = await res.json().catch(() => ({}));
                throw new Error(payload.message || 'Failed to close campaign.');
            }
            window.location.reload();
        } catch (error) {
            window.notify?.(error.message, 'error');
            btn.disabled = false;
            spinner?.classList.add('hidden');
        }
    });

    // Cancel Campaign Modal
    const hideCancelModal = () => {
        cancelModal?.classList.add('hidden');
        cancelModal?.classList.remove('flex');
        campaignToCancel = null;
        const confirmBtn = cancelModal?.querySelector('[data-cancel-modal-confirm]');
        if (confirmBtn) {
            confirmBtn.disabled = false;
            confirmBtn.querySelector('[data-spinner]')?.classList.add('hidden');
        }
    };

    window.openCancelCampaignModal = function(id) {
        campaignToCancel = id;
        cancelModal?.classList.remove('hidden');
        cancelModal?.classList.add('flex');
    };

    cancelModal?.querySelector('[data-cancel-modal-cancel]')?.addEventListener('click', hideCancelModal);
    cancelModal?.querySelector('[data-cancel-modal-confirm]')?.addEventListener('click', async (e) => {
        if (!campaignToCancel) return;
        const btn = e.currentTarget;
        const spinner = btn.querySelector('[data-spinner]');
        btn.disabled = true;
        spinner?.classList.remove('hidden');

        const slug = roomSlug || window.__DF_ROOM_SLUG__;
        try {
            const res = await fetch(`/admin/${slug}/campaigns/${campaignToCancel}/cancel`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
            if (!res.ok) {
                const payload = await res.json().catch(() => ({}));
                throw new Error(payload.message || 'Failed to cancel campaign.');
            }
            window.location.reload();
        } catch (error) {
            window.notify?.(error.message, 'error');
            btn.disabled = false;
            spinner?.classList.add('hidden');
        }
    });

    // Archive Campaign
    window.archiveCampaign = async function(id) {
        if (!window.confirm('Archive this campaign?')) return;
        const slug = roomSlug || window.__DF_ROOM_SLUG__;
        try {
            const res = await fetch(`/admin/${slug}/campaigns/${id}/archive`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
            if (!res.ok) {
                const payload = await res.json().catch(() => ({}));
                throw new Error(payload.message || 'Failed to archive campaign.');
            }
            window.location.reload();
        } catch (error) {
            window.notify?.(error.message, 'error');
        }
    };
}

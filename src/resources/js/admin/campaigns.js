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
    const archiveModal = document.querySelector('#archive-campaign-modal');

    let campaignToDuplicate = null;
    let campaignToClose = null;
    let campaignToCancel = null;
    let campaignToArchive = null;

    const setSpinnerVisible = (button, visible) => {
        const spinner = button?.querySelector('[data-spinner]');
        const actionIcon = button?.querySelector('[data-action-icon]');
        if (spinner) spinner.style.display = visible ? 'inline-block' : 'none';
        if (actionIcon) actionIcon.style.display = visible ? 'none' : 'inline-block';
    };

    if (!filterForm && !duplicateModal && !closeModal && !cancelModal && !archiveModal) return;

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
            setSpinnerVisible(confirmBtn, false);
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
        btn.disabled = true;
        setSpinnerVisible(btn, true);

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
            setSpinnerVisible(btn, false);
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
            setSpinnerVisible(confirmBtn, false);
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
        btn.disabled = true;
        setSpinnerVisible(btn, true);

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
            setSpinnerVisible(btn, false);
        }
    });

    // Archive Campaign Modal
    const hideArchiveModal = () => {
        archiveModal?.classList.add('hidden');
        archiveModal?.classList.remove('flex');
        campaignToArchive = null;
        const confirmBtn = archiveModal?.querySelector('[data-archive-confirm]');
        if (confirmBtn) {
            confirmBtn.disabled = false;
            setSpinnerVisible(confirmBtn, false);
        }
    };

    window.openArchiveCampaignModal = function(id) {
        campaignToArchive = id;
        archiveModal?.classList.remove('hidden');
        archiveModal?.classList.add('flex');
    };

    archiveModal?.querySelector('[data-archive-cancel]')?.addEventListener('click', hideArchiveModal);
    archiveModal?.querySelector('[data-archive-confirm]')?.addEventListener('click', async (event) => {
        if (!campaignToArchive) return;
        const btn = event.currentTarget;
        btn.disabled = true;
        setSpinnerVisible(btn, true);
        const slug = roomSlug || window.__DF_ROOM_SLUG__;
        try {
            const res = await fetch(`/admin/${slug}/campaigns/${campaignToArchive}/archive`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
            if (!res.ok) {
                const payload = await res.json().catch(() => ({}));
                throw new Error(payload.message || archiveModal?.dataset.errorMessage);
            }
            window.location.reload();
        } catch (error) {
            window.notify?.(error.message, 'error');
            btn.disabled = false;
            setSpinnerVisible(btn, false);
        }
    });
}

/**
 * Admin Campaigns List & Management
 */
export function initAdminCampaigns() {
    const searchInput = document.querySelector('#campaign-search');
    const filterForm = document.querySelector('#campaigns-filter-form');
    const statusSelect = document.querySelector('#campaign-status-select');
    const statusFilter = document.querySelector('#campaign-status-filter');
    const rows = document.querySelectorAll('[data-campaign-row]');
    const noResults = document.querySelector('#campaigns-no-filter-results');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const roomSlug = document.querySelector('[data-room-slug]')?.dataset.roomSlug || window.__DF_ROOM_SLUG__ || '';
    const duplicateModal = document.querySelector('#duplicate-campaign-modal');
    let campaignToDuplicate = null;

    if (!searchInput && !rows.length) return;

    function applyFilters() {
        const term = searchInput?.value.trim().toLowerCase() || '';
        const activeFilter = statusSelect?.value || statusFilter?.value || 'all';
        let visibleRows = 0;

        rows.forEach(row => {
            const matchesSearch = row.dataset.search?.includes(term);
            const matchesFilter = activeFilter === 'all' || row.dataset.status === activeFilter;
            row.style.display = (matchesSearch && matchesFilter) ? '' : 'none';
            if (matchesSearch && matchesFilter) visibleRows += 1;
        });
        noResults?.classList.toggle('hidden', rows.length === 0 || visibleRows > 0);
    }

    searchInput?.addEventListener('admin:search', applyFilters);
    statusSelect?.addEventListener('change', () => {
        if (statusFilter) statusFilter.value = statusSelect.value;
        applyFilters();
    });
    searchInput?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            filterForm?.requestSubmit();
        }
    });

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

    async function transitionCampaign(id, action, message) {
        if (!window.confirm(message)) return;
        const slug = roomSlug || window.__DF_ROOM_SLUG__;
        try {
            const res = await fetch(`/admin/${slug}/campaigns/${id}/${action}`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
            if (!res.ok) {
                const payload = await res.json().catch(() => ({}));
                throw new Error(payload.message || 'Campaign action failed.');
            }
            window.location.reload();
        } catch (error) {
            window.notify?.(error.message, 'error');
        }
    }

    window.cancelCampaign = id => transitionCampaign(id, 'cancel', 'Cancel/delete this campaign?');
    window.closeCampaign = id => transitionCampaign(id, 'close', 'Close orders for this campaign?');
    window.archiveCampaign = id => transitionCampaign(id, 'archive', 'Archive this campaign?');
    window.deleteCampaign = async function (id) {
        if (!window.confirm('Delete this campaign permanently?')) return;
        const slug = roomSlug || window.__DF_ROOM_SLUG__;
        try {
            const res = await fetch(`/admin/${slug}/campaigns/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
            if (!res.ok) {
                const payload = await res.json().catch(() => ({}));
                throw new Error(payload.message || 'Campaign deletion failed.');
            }
            window.location.reload();
        } catch (error) {
            window.notify?.(error.message, 'error');
        }
    };

    document.querySelector('[data-duplicate-cancel]')?.addEventListener('click', closeDuplicateModal);
    document.querySelector('[data-duplicate-confirm]')?.addEventListener('click', async () => {
        if (!campaignToDuplicate) return;
        const slug = roomSlug || window.__DF_ROOM_SLUG__;
        try {
            const res = await fetch(`/admin/${slug}/campaigns/${campaignToDuplicate}/duplicate`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
            if (res.ok) {
                window.location.reload();
            } else {
                closeDuplicateModal();
            }
        } catch (e) {
            console.error(e);
            closeDuplicateModal();
        }
    });
}

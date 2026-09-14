/**
 * Admin Campaigns List & Management
 */
export function initAdminCampaigns() {
    const searchInput = document.querySelector('#campaign-search');
    const filterBtns = document.querySelectorAll('.campaign-filter');
    const rows = document.querySelectorAll('[data-campaign-row]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const roomSlug = document.querySelector('[data-room-slug]')?.dataset.roomSlug || window.__DF_ROOM_SLUG__ || '';
    const duplicateModal = document.querySelector('#duplicate-campaign-modal');
    let campaignToDuplicate = null;

    if (!searchInput && !rows.length) return;

    function applyFilters() {
        const term = searchInput?.value.trim().toLowerCase() || '';
        const activeFilter = document.querySelector('.campaign-filter.bg-primary')?.dataset.filter || 'all';

        rows.forEach(row => {
            const matchesSearch = row.dataset.search?.includes(term);
            const matchesFilter = activeFilter === 'all' || row.dataset.status === activeFilter;
            row.style.display = (matchesSearch && matchesFilter) ? '' : 'none';
        });
    }

    searchInput?.addEventListener('admin:search', applyFilters);
    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => {
                b.classList.remove('bg-primary', 'text-on-primary');
                b.classList.add('bg-surface-container', 'text-on-surface');
            });
            btn.classList.add('bg-primary', 'text-on-primary');
            btn.classList.remove('bg-surface-container', 'text-on-surface');
            applyFilters();
        });
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

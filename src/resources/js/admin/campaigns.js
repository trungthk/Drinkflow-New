/**
 * Admin Campaigns List & Management
 */
export function initAdminCampaigns() {
    const searchInput = document.querySelector('#campaign-search');
    const filterBtns = document.querySelectorAll('.campaign-filter');
    const rows = document.querySelectorAll('[data-campaign-row]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const roomSlug = document.querySelector('[data-room-slug]')?.dataset.roomSlug || window.__DF_ROOM_SLUG__ || '';

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

    searchInput?.addEventListener('input', applyFilters);
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

    window.duplicateCampaign = async function(id) {
        if (!confirm('Duplicate this campaign?')) return;
        const slug = roomSlug || window.__DF_ROOM_SLUG__;
        try {
            const res = await fetch(`/admin/${slug}/campaigns/${id}/duplicate`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
            if (res.ok) {
                window.location.reload();
            } else {
                alert('An error occurred while duplicating campaign.');
            }
        } catch (e) {
            console.error(e);
            alert('Server error.');
        }
    };
}

/**
 * Public Versions Page Interactive Module (Live Search / Filter)
 */
export function initVersionsPage() {
    const searchInput = document.getElementById('version-search-input');
    const items = document.querySelectorAll('.version-item');

    if (!searchInput || items.length === 0) return;

    searchInput.addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase().trim();
        items.forEach(item => {
            const text = item.textContent.toLowerCase();
            if (text.includes(query)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });
}

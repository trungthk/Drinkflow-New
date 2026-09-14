/**
 * Admin Audit Trail Modal Controller
 */
export function initAdminAudit() {
    const modal = document.querySelector('#payload-modal');
    const modalBackdrop = document.querySelector('#payload-backdrop');
    if (!modal) return;

    modalBackdrop?.addEventListener('click', closePayloadModal);

    window.viewAuditPayload = function(log, eventLabel = '') {
        const titleEl = document.querySelector('#payload-title');
        const contentEl = document.querySelector('#payload-content');
        if (titleEl) titleEl.textContent = `Audit #${log.id} - ${eventLabel || log.event}`;
        if (contentEl) contentEl.textContent = JSON.stringify(log, null, 2);
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    window.closePayloadModal = function() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            window.closePayloadModal();
        }
    });

    document.addEventListener('admin:daterange-change', (event) => {
        if (event.target?.id !== 'audit-date-range') return;
        document.getElementById('audit-filter-form')?.requestSubmit();
    });
}

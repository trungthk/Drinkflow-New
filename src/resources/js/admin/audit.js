/**
 * Admin Audit Trail Modal Controller
 */
export function initAdminAudit() {
    const modal = document.querySelector('#payload-modal');
    const modalBackdrop = document.querySelector('#payload-backdrop');
    if (!modal) return;

    const resetLink = document.querySelector('[data-audit-filter-reset]');
    resetLink?.addEventListener('click', (event) => {
        event.preventDefault();
        if (resetLink.dataset.loading === 'true') return;

        resetLink.dataset.loading = 'true';
        resetLink.setAttribute('aria-busy', 'true');
        resetLink.classList.add('pointer-events-none', 'opacity-70');
        resetLink.querySelector('[data-reset-icon]')?.classList.add('animate-spin');
        window.location.assign(resetLink.href);
    });

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

    modalBackdrop?.addEventListener('click', window.closePayloadModal);
    document.querySelector('[data-close-audit-payload]')?.addEventListener('click', window.closePayloadModal);

    document.querySelectorAll('[data-audit-payload]').forEach((button) => {
        button.addEventListener('click', () => {
            const payload = JSON.parse(button.dataset.auditPayload || '{}');
            window.viewAuditPayload(payload, button.dataset.auditEventLabel || '');
        });
    });

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

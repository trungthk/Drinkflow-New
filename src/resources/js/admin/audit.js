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

    function populateDataBlock(preId, emptyId, data) {
        const preEl = document.querySelector(`#${preId}`);
        const emptyEl = document.querySelector(`#${emptyId}`);
        if (!preEl || !emptyEl) return;
        if (!data || (typeof data === 'object' && Object.keys(data).length === 0)) {
            preEl.classList.add('hidden');
            emptyEl.classList.remove('hidden');
        } else {
            emptyEl.classList.add('hidden');
            preEl.classList.remove('hidden');
            preEl.textContent = typeof data === 'string' ? data : JSON.stringify(data, null, 2);
        }
    }

    window.viewAuditPayload = function(log, eventLabel = '', targetLabel = '', formattedTime = '') {
        const titleEl = document.querySelector('#payload-title');
        const timeEl = document.querySelector('#modal-audit-time');
        const eventBadge = document.querySelector('#modal-audit-event');
        const eventRaw = document.querySelector('#modal-audit-event-raw');
        const targetLabelEl = document.querySelector('#modal-audit-target-label');
        const targetIdEl = document.querySelector('#modal-audit-target-id');
        const actorEl = document.querySelector('#modal-audit-actor');
        const ipEl = document.querySelector('#modal-audit-ip');
        const deviceEl = document.querySelector('#modal-audit-device');
        const userAgentEl = document.querySelector('#modal-audit-user-agent');
        const contentEl = document.querySelector('#payload-content');

        if (titleEl) {
            titleEl.textContent = `Audit #${log.id ?? 'N/A'}`;
        }
        if (timeEl) {
            timeEl.textContent = formattedTime || (log.created_at ? new Date(log.created_at).toLocaleString('vi-VN') : 'N/A');
        }
        if (eventBadge) {
            eventBadge.textContent = eventLabel || log.event || '-';
        }
        if (eventRaw) {
            eventRaw.textContent = (eventLabel && eventLabel !== log.event && log.event) ? `(${log.event})` : '';
        }
        if (targetLabelEl) {
            targetLabelEl.textContent = targetLabel || log.target_type || '-';
        }
        if (targetIdEl) {
            targetIdEl.textContent = log.target_id ? `#${log.target_id}` : '';
        }
        if (actorEl) {
            actorEl.textContent = `${log.actor_type || 'Unknown'} #${log.actor_id ?? 'N/A'}`;
        }
        if (ipEl) {
            ipEl.textContent = log.ip_address || '127.0.0.1';
        }
        if (deviceEl) {
            deviceEl.textContent = log.device_uuid || '-';
            deviceEl.setAttribute('title', log.device_uuid || '');
        }
        if (userAgentEl) {
            userAgentEl.textContent = log.user_agent || '-';
        }

        populateDataBlock('modal-audit-before', 'modal-audit-before-empty', log.before_data);
        populateDataBlock('modal-audit-after', 'modal-audit-after-empty', log.after_data);
        populateDataBlock('modal-audit-metadata', 'modal-audit-metadata-empty', log.metadata);

        if (contentEl) {
            contentEl.textContent = JSON.stringify(log, null, 2);
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    window.closePayloadModal = function() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    modalBackdrop?.addEventListener('click', window.closePayloadModal);
    document.querySelector('[data-close-audit-payload]')?.addEventListener('click', window.closePayloadModal);

    // Copy Raw JSON Button
    const copyBtn = document.querySelector('#payload-copy-btn');
    const copyText = document.querySelector('#payload-copy-text');
    let copyTimer = null;
    copyBtn?.addEventListener('click', async (e) => {
        e.preventDefault();
        e.stopPropagation();
        const content = document.querySelector('#payload-content')?.textContent;
        if (!content) return;
        try {
            await navigator.clipboard.writeText(content);
            const originalText = copyText?.textContent || 'Copy JSON';
            const copiedLabel = copyBtn.dataset.copiedLabel || 'Đã sao chép';
            if (copyText) copyText.textContent = copiedLabel;
            copyBtn.classList.add('text-primary', 'border-primary/40');
            clearTimeout(copyTimer);
            copyTimer = setTimeout(() => {
                if (copyText) copyText.textContent = originalText;
                copyBtn.classList.remove('text-primary', 'border-primary/40');
            }, 2000);
        } catch (err) {
            console.error('Failed to copy JSON:', err);
        }
    });

    document.querySelectorAll('[data-audit-payload]').forEach((button) => {
        button.addEventListener('click', () => {
            let payload = {};
            try {
                payload = JSON.parse(button.dataset.auditPayload || '{}');
            } catch (e) {
                console.error('Failed to parse audit payload:', e);
            }
            window.viewAuditPayload(
                payload,
                button.dataset.auditEventLabel || '',
                button.dataset.auditTargetLabel || '',
                button.dataset.auditTime || ''
            );
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


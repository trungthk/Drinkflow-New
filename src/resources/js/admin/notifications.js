/** Initialize room notification-channel create, test, and disable interactions. */
export function initAdminNotifications() {
    const form = document.querySelector('#add-channel-form');
    const typeSelect = document.querySelector('#ch-type');
    const notice = document.querySelector('#notice');
    const deleteModal = document.querySelector('#channel-delete-modal');
    const deleteConfirm = document.querySelector('#channel-delete-confirm');
    const deleteCancel = document.querySelector('#channel-delete-cancel');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const roomSlug = document.querySelector('[data-room-slug]')?.dataset.roomSlug || window.__DF_ROOM_SLUG__ || '';
    const translations = notice?.dataset || {};
    let pendingDeleteId = null;

    if (!form && !document.querySelector('[data-notification-channel]')) return;

    const showNotify = (message, type = 'success') => {
        if (!notice) return;
        notice.textContent = message;
        notice.className = `mb-4 rounded-xl px-4 py-3 text-xs font-medium ${type === 'error' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'}`;
        notice.classList.remove('hidden');
        window.setTimeout(() => notice.classList.add('hidden'), 5000);
    };
    const setLoading = (button, active) => {
        if (!button) return;
        if (active) {
            button.dataset.originalHtml = button.innerHTML;
            button.disabled = true;
            button.classList.add('opacity-70', 'cursor-not-allowed');
            button.innerHTML = `<span class="material-symbols-outlined animate-spin text-[16px]">progress_activity</span><span>${translations.processing || 'Processing...'}</span>`;
            return;
        }
        button.disabled = false;
        button.classList.remove('opacity-70', 'cursor-not-allowed');
        if (button.dataset.originalHtml) button.innerHTML = button.dataset.originalHtml;
    };
    const responseData = async (response) => response.json().catch(() => ({}));
    const reloadAfterNotice = (message) => {
        showNotify(message);
        window.setTimeout(() => window.location.reload(), 700);
    };
    const switchPlatform = (type) => {
        document.querySelectorAll('.platform-config-fields').forEach((element) => element.classList.add('hidden'));
        document.querySelector(`#platform-${type}`)?.classList.remove('hidden');
    };

    typeSelect?.addEventListener('change', (event) => switchPlatform(event.target.value));
    if (typeSelect) switchPlatform(typeSelect.value);

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const type = typeSelect?.value || 'webhook';
        const config = type === 'telegram'
            ? { bot_token: document.querySelector('#ch-tg-token')?.value.trim() || '', chat_id: document.querySelector('#ch-tg-chat-id')?.value.trim() || '' }
            : type === 'slack'
                ? { webhook_url: document.querySelector('#ch-slack-url')?.value.trim() || '' }
                : type === 'chatwork'
                    ? { api_token: document.querySelector('#ch-cw-token')?.value.trim() || '', room_id: document.querySelector('#ch-cw-room-id')?.value.trim() || '' }
                    : { webhook_url: document.querySelector('#ch-wh-url')?.value.trim() || '', secret_token: document.querySelector('#ch-wh-secret')?.value.trim() || '' };
        const button = form.querySelector('button[type="submit"]');
        setLoading(button, true);
        try {
            const response = await fetch(`/admin/${roomSlug}/notification-channels`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' }, body: JSON.stringify({ type, name: document.querySelector('#ch-name')?.value.trim(), status: 'enabled', config }) });
            const data = await responseData(response);
            if (!response.ok) throw new Error(data.message || translations.notificationSaveFailed || 'Could not save webhook.');
            reloadAfterNotice(translations.notificationSaved || 'Saved successfully.');
        } catch (error) {
            showNotify(error.message || translations.notificationServerError || 'Server error.', 'error');
            setLoading(button, false);
        }
    });

    window.testChannel = async (id) => {
        const button = document.querySelector(`[data-channel-test="${id}"]`);
        setLoading(button, true);
        try {
            const response = await fetch(`/admin/${roomSlug}/notification-channels/${id}/test`, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' } });
            const data = await responseData(response);
            if (!response.ok) throw new Error(data.message || translations.notificationTestFailed || 'Could not send test ping.');
            showNotify(translations.notificationTestSent || 'Test ping sent successfully.');
        } catch (error) {
            showNotify(error.message || translations.notificationServerError || 'Server error.', 'error');
        } finally {
            setLoading(button, false);
        }
    };

    const closeDeleteModal = () => {
        pendingDeleteId = null;
        deleteModal?.classList.add('hidden');
        deleteModal?.classList.remove('flex');
    };
    window.deleteChannel = (id) => {
        pendingDeleteId = id;
        deleteModal?.classList.remove('hidden');
        deleteModal?.classList.add('flex');
        deleteConfirm?.focus();
    };
    deleteCancel?.addEventListener('click', closeDeleteModal);
    deleteModal?.addEventListener('click', (event) => { if (event.target === deleteModal) closeDeleteModal(); });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && pendingDeleteId !== null) closeDeleteModal(); });
    deleteConfirm?.addEventListener('click', async () => {
        if (pendingDeleteId === null) return;
        const id = pendingDeleteId;
        const trigger = document.querySelector(`[data-channel-delete="${id}"]`);
        setLoading(deleteConfirm, true);
        try {
            const response = await fetch(`/admin/${roomSlug}/notification-channels/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' } });
            const data = await responseData(response);
            if (!response.ok) throw new Error(data.message || translations.notificationDeleteFailed || 'Could not delete channel.');
            closeDeleteModal();
            setLoading(trigger, true);
            reloadAfterNotice(translations.notificationSaved || 'Saved successfully.');
        } catch (error) {
            showNotify(error.message || translations.notificationDeleteFailed || 'Could not delete channel.', 'error');
            setLoading(deleteConfirm, false);
        }
    });
}

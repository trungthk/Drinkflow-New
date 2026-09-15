/** Submit the room-member decline decision without leaving the campaign menu. */
export function initCampaignDecline() {
    const form = document.querySelector('[data-decline-campaign]');
    if (!form) return;

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const button = form.querySelector('button[type="submit"]');
        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
        if (button) button.disabled = true;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': token },
                credentials: 'same-origin'
            });
            if (!response.ok) throw new Error('Unable to record campaign decline.');
            button?.classList.add('hidden');
            form.querySelector('[data-decline-message]')?.classList.remove('hidden');
        } catch (_) {
            if (button) button.disabled = false;
        }
    });
}

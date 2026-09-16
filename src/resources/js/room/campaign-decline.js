/** Submit the room-member participation decision without leaving the campaign menu. */
export function initCampaignDecline() {
    const form = document.querySelector('[data-participation-form]');
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
            if (!response.ok) throw new Error('Unable to update campaign participation.');
            window.location.reload();
        } catch (_) {
            if (button) button.disabled = false;
        }
    });
}

/**
 * Admin Room Settings Controller
 */
export function initAdminSettings() {
    const form = document.querySelector('#room-settings-form');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const roomSlug = document.querySelector('[data-room-slug]')?.dataset.roomSlug || window.__DF_ROOM_SLUG__ || '';

    if (!form) return;

    window.insertTag = function(tag) {
        const input = document.querySelector('#set-template');
        if (input) {
            input.value += ' ' + tag;
            input.focus();
        }
    };

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const payload = {
            campaign_title_template: document.querySelector('#set-template').value,
            default_start_time: document.querySelector('#set-start-time').value,
            default_end_time: document.querySelector('#set-end-time').value,
            auto_close_warning_minutes: Number(document.querySelector('#set-warning-minutes').value),
            max_campaign_budget: Number(document.querySelector('#set-max-budget').value),
            allow_internal_debt: document.querySelector('#set-allow-debt').checked,
            personal_debt_ceiling: Number(document.querySelector('#set-debt-ceiling').value),
            auto_lock_on_debt_limit: document.querySelector('#set-autolock-debt').checked
        };

        try {
            const res = await fetch(`/admin/${roomSlug}/settings`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            });
            if (res.ok) {
                alert('Settings saved successfully.');
                window.location.reload();
            } else {
                alert('Error saving room settings.');
            }
        } catch (e) {
            console.error(e);
            alert('Server error.');
        }
    });
}

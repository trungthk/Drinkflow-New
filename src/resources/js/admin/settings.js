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

    function formatNumberWithDots(input) {
        if (!input) return;
        const raw = String(input.value || '').replace(/\D/g, '');
        if (!raw) {
            input.value = '';
            return;
        }
        input.value = raw.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    const budgetInput = document.querySelector('#set-max-budget');
    const debtInput = document.querySelector('#set-debt-ceiling');

    [budgetInput, debtInput].forEach(input => {
        if (input) {
            formatNumberWithDots(input);
            input.addEventListener('input', () => formatNumberWithDots(input));
            input.addEventListener('change', () => formatNumberWithDots(input));
        }
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const cleanNumber = (val) => {
            const raw = String(val || '').replace(/\D/g, '');
            return raw ? parseInt(raw, 10) : 0;
        };

        const payload = {
            campaign_title_template: document.querySelector('#set-template')?.value || '',
            default_start_time: document.querySelector('#set-start-time')?.value || '10:00',
            default_end_time: document.querySelector('#set-end-time')?.value || '10:45',
            auto_close_warning_minutes: Number(document.querySelector('#set-warning-minutes')?.value || 15),
            max_campaign_budget: cleanNumber(budgetInput?.value),
            allow_internal_debt: document.querySelector('#set-allow-debt')?.checked ?? true,
            personal_debt_ceiling: cleanNumber(debtInput?.value),
            auto_lock_on_debt_limit: document.querySelector('#set-autolock-debt')?.checked ?? true
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


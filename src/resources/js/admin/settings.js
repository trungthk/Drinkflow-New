/**
 * Admin Room Settings Controller
 */
export function initAdminSettings() {
    const form = document.querySelector('#room-settings-form');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const roomSlug = document.querySelector('[data-room-slug]')?.dataset.roomSlug || window.__DF_ROOM_SLUG__ || '';
    const submitButton = document.querySelector('button[type="submit"][form="room-settings-form"]');

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
            max_campaign_budget: cleanNumber(budgetInput?.value),
            personal_debt_ceiling: cleanNumber(debtInput?.value),
            auto_lock_on_debt_limit: document.querySelector('#set-autolock-debt')?.checked ?? true
        };

        const originalButtonContent = submitButton?.dataset.originalContent || submitButton?.innerHTML || '';
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.classList.add('opacity-80', 'cursor-not-allowed');
            submitButton.innerHTML = `
                <span class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
                <span>${document.body.dataset.processingText || 'Processing...'}</span>
            `;
        }

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
        } finally {
            if (submitButton && !document.hidden) {
                submitButton.disabled = false;
                submitButton.classList.remove('opacity-80', 'cursor-not-allowed');
                submitButton.innerHTML = originalButtonContent;
            }
        }
    });
}

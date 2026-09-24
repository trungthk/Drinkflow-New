/**
 * Superadmin search clear (x) buttons.
 *
 * Shows the button only while the search field has a value; clicking it empties the
 * field and submits the surrounding filter form so the list reloads without the keyword.
 */
export function initSuperadminSearchClear() {
    const toggle = (wrap) => {
        const input = wrap.querySelector('input');
        const button = wrap.querySelector('[data-search-clear]');
        if (input && button) button.hidden = input.value === '';
    };

    document.querySelectorAll('[data-search-clear-wrap]').forEach(toggle);

    document.addEventListener('input', (event) => {
        const wrap = event.target.closest?.('[data-search-clear-wrap]');
        if (wrap) toggle(wrap);
    });

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-search-clear]');
        if (!button) return;

        const wrap = button.closest('[data-search-clear-wrap]');
        const input = wrap?.querySelector('input');
        if (!input) return;

        input.value = '';
        toggle(wrap);

        const form = input.form;
        if (!form) return;
        if (typeof form.requestSubmit === 'function') form.requestSubmit();
        else form.submit();
    });
}

/**
 * Render the shared, translated spinner and loading label inside a submit control.
 * The label never wraps; icon-only buttons ([data-icon-only]) temporarily widen to fit it,
 * see restoreSubmitLoading().
 */
export function renderSubmitLoading(button) {
    const label = document.body.dataset.submitLoadingText || document.documentElement.dataset.submitLoadingText || 'Loading...';
    if (button.tagName === 'INPUT') {
        button.value = label;
        return;
    }

    if (button.hasAttribute('data-icon-only')) {
        button.style.width = 'auto';
        button.style.paddingInline = '0.75rem';
    }

    const content = document.createElement('span');
    content.className = 'inline-flex items-center justify-center gap-1.5 whitespace-nowrap';
    const spinner = document.createElement('span');
    spinner.className = 'material-symbols-outlined animate-spin text-[16px]';
    spinner.setAttribute('aria-hidden', 'true');
    spinner.textContent = 'progress_activity';
    const text = document.createElement('span');
    text.textContent = label;
    content.append(spinner, text);
    button.replaceChildren(content);
}

/** Undo the inline sizing renderSubmitLoading() applies to icon-only buttons. */
export function restoreSubmitLoading(button) {
    button.style.removeProperty('width');
    button.style.removeProperty('padding-inline');
}

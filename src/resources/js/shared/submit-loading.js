/** Render the shared, translated spinner and loading label inside a submit control. */
export function renderSubmitLoading(button) {
    const label = document.body.dataset.submitLoadingText || document.documentElement.dataset.submitLoadingText || 'Loading...';
    if (button.tagName === 'INPUT') {
        button.value = label;
        return;
    }

    const content = document.createElement('span');
    content.className = 'inline-flex items-center justify-center gap-1.5';
    const spinner = document.createElement('span');
    spinner.className = 'material-symbols-outlined animate-spin text-[16px]';
    spinner.setAttribute('aria-hidden', 'true');
    spinner.textContent = 'progress_activity';
    const text = document.createElement('span');
    text.textContent = label;
    content.append(spinner, text);
    button.replaceChildren(content);
}

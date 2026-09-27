/**
 * Show or hide a <x-loading-overlay /> element.
 *
 * @param {?Element} overlay Overlay element ([data-loading-overlay]).
 * @param {boolean} visible Whether the content is loading.
 * @returns {void}
 */
export function setLoadingOverlay(overlay, visible) {
    if (!overlay) return;
    overlay.classList.toggle('hidden', !visible);
    overlay.classList.toggle('flex', visible);
    overlay.setAttribute('aria-busy', visible ? 'true' : 'false');
}

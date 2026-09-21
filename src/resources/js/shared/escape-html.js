/**
 * Escape a value for safe interpolation into an HTML string (element content or quoted attribute).
 *
 * @param {unknown} value Raw value; null and undefined become an empty string.
 * @returns {string} HTML-escaped text.
 */
export function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    })[char]);
}

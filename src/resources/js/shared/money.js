/**
 * Shared money formatting for admin scripts.
 *
 * Mirrors App\Support\Helpers\FormatHelper::formatCurrency() for VND so server-rendered and
 * script-rendered amounts look identical: dot-grouped thousands followed directly by "đ".
 */
export const MONEY_SUFFIX = 'đ';

/**
 * Format an amount as VND, e.g. 142000 -> "142.000đ".
 *
 * @param {number|string|null|undefined} value Raw amount; empty or invalid values become 0.
 * @returns {string} Formatted amount (rounded to whole đồng, like PHP number_format) with the currency suffix.
 */
export function formatMoney(value) {
    const amount = Number(value);

    return new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 })
        .format(Number.isFinite(amount) ? amount : 0) + MONEY_SUFFIX;
}

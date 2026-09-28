/**
 * Tag list inputs (resources/views/components/admin/tags-input.blade.php).
 *
 * Enter, comma, space or paste turns the typed text into tags; Backspace on an empty field or the × button removes
 * one. Entries are checked on the client (domain or IPv4/IPv6 format, duplicates, maximum count) before being added;
 * the server validates them again. Messages come from the element's data-i18n JSON.
 */

const DOMAIN_PATTERN = /^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i;
const IPV4_PATTERN = /^(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}$/;

/** Whether a string is a valid IPv6 address (the URL parser validates bracketed hosts). */
function isIpv6(value) {
    if (!value.includes(':')) return false;
    try {
        return new URL(`http://[${value}]/`).hostname.length > 2;
    } catch (error) {
        return false;
    }
}

const normalize = (value, type) => {
    const trimmed = String(value || '').trim();
    return type === 'domain' ? trimmed.toLowerCase().replace(/^@+/, '') : trimmed;
};

const isValid = (value, type) => (type === 'ip' ? IPV4_PATTERN.test(value) || isIpv6(value) : DOMAIN_PATTERN.test(value));

const fill = (template, value) => String(template || '').replace(':value', value);

function parseI18n(root) {
    try {
        return JSON.parse(root.dataset.i18n || '{}');
    } catch (error) {
        return {};
    }
}

/**
 * Current values of a tags input.
 *
 * @param {HTMLElement} root Element carrying [data-tags-input].
 * @returns {string[]} Tag values in display order.
 */
export function readTagsInput(root) {
    return [...root.querySelectorAll('[data-tag]')].map((tag) => tag.dataset.tag);
}

function setupTagsInput(root) {
    if (root.dataset.tagsReady) return;
    root.dataset.tagsReady = '1';

    const type = root.dataset.validate === 'ip' ? 'ip' : 'domain';
    const max = Number(root.dataset.max) || 100;
    const chipClass = root.dataset.chipClass || '';
    const i18n = parseI18n(root);
    const box = root.querySelector('[data-tags-box]');
    const field = root.querySelector('[data-tags-field]');
    const error = root.querySelector('[data-tags-error]');
    const counter = root.querySelector('[data-tags-count]');
    if (!box || !field) return;

    const showError = (message) => {
        if (!error) return;
        error.textContent = message || '';
        error.classList.toggle('hidden', !message);
        box.classList.toggle('border-error', Boolean(message));
    };

    const refreshCount = () => {
        if (counter) counter.textContent = `${readTagsInput(root).length}/${max}`;
    };

    const createChip = (value) => {
        const chip = document.createElement('span');
        chip.className = `inline-flex max-w-full items-center gap-1 rounded-md border px-2 py-0.5 font-mono text-[11px] font-semibold ${chipClass}`;
        chip.dataset.tag = value;
        const label = document.createElement('span');
        label.className = 'truncate';
        label.textContent = value;
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = '-mr-0.5 inline-flex h-4 w-4 items-center justify-center rounded opacity-70 hover:opacity-100 cursor-pointer';
        remove.dataset.tagRemove = '';
        remove.setAttribute('aria-label', fill(i18n.remove, value));
        remove.innerHTML = '<span class="material-symbols-outlined text-[14px]">close</span>';
        chip.append(label, remove);
        return chip;
    };

    /** Add every entry of a raw string; returns the entries that could not be added (kept in the field). */
    const addFrom = (raw) => {
        const rejected = [];
        let message = '';
        raw.split(/[\s,;]+/).map((part) => normalize(part, type)).filter(Boolean).forEach((value) => {
            const existing = readTagsInput(root);
            if (!isValid(value, type)) {
                rejected.push(value);
                message = fill(i18n.invalid, value);
            } else if (existing.includes(value)) {
                message = fill(i18n.duplicate, value);
            } else if (existing.length >= max) {
                rejected.push(value);
                message = i18n.tooMany || '';
            } else {
                box.insertBefore(createChip(value), field);
            }
        });
        showError(message);
        refreshCount();
        return rejected;
    };

    const commitField = () => {
        if (!field.value.trim()) return;
        field.value = addFrom(field.value).join(' ');
    };

    field.addEventListener('keydown', (event) => {
        if (['Enter', ',', ';', ' ', 'Tab'].includes(event.key)) {
            if (event.key === 'Tab' && !field.value.trim()) return;
            event.preventDefault();
            commitField();
        } else if (event.key === 'Backspace' && field.value === '') {
            const tags = root.querySelectorAll('[data-tag]');
            tags[tags.length - 1]?.remove();
            showError('');
            refreshCount();
        }
    });
    field.addEventListener('paste', (event) => {
        const text = event.clipboardData?.getData('text') || '';
        if (!/[\s,;]/.test(text)) return;
        event.preventDefault();
        field.value = addFrom(`${field.value} ${text}`).join(' ');
    });
    field.addEventListener('blur', commitField);
    field.addEventListener('input', () => { if (!field.value) showError(''); });

    box.addEventListener('click', (event) => {
        const remove = event.target.closest('[data-tag-remove]');
        if (remove) {
            remove.closest('[data-tag]')?.remove();
            showError('');
            refreshCount();
            field.focus();
            return;
        }
        if (event.target === box) field.focus();
    });
}

/** Enhance every tags input on the page. */
export function initTagsInputs() {
    document.querySelectorAll('[data-tags-input]').forEach(setupTagsInput);
}

/**
 * Commit text still typed in the fields (so "Save" does not drop it) and collect every list.
 *
 * @param {ParentNode} scope Container to search.
 * @returns {Record<string, string[]>} Values keyed by each input's name.
 */
export function collectTagsInputs(scope = document) {
    const values = {};
    scope.querySelectorAll('[data-tags-input]').forEach((root) => {
        root.querySelector('[data-tags-field]')?.dispatchEvent(new Event('blur'));
        values[root.dataset.tagsInput] = readTagsInput(root);
    });
    return values;
}

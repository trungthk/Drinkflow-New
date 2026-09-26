import { formatMoney } from '../shared/money';

/**
 * Shared "End orders & finalize campaign" modal (x-admin.close-campaign-modal).
 *
 * Any element with [data-close-campaign-open][data-campaign-id] opens it. Every open re-fetches the
 * campaign's close summary so the admin always confirms against the latest numbers; the confirm
 * button stays disabled until that data has loaded.
 *
 * After a successful close it dispatches a cancelable `admin:campaign-closed` event on window
 * (detail: { campaignId, campaign }). The page reloads unless a listener calls preventDefault().
 */
export function initCloseCampaignModal() {
    const modal = document.querySelector('[data-close-campaign-modal]');
    if (!modal) return;

    let i18n = {};
    try {
        i18n = JSON.parse(modal.dataset.i18n || '{}');
    } catch {
        i18n = {};
    }

    const summaryUrlTemplate = modal.dataset.summaryUrlTemplate || '';
    const closeUrlTemplate = modal.dataset.closeUrlTemplate || '';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const heading = modal.querySelector('[data-close-summary-heading]');
    const fields = modal.querySelectorAll('[data-close-summary]');
    const loading = modal.querySelector('[data-close-summary-loading]');
    const errorBox = modal.querySelector('[data-close-summary-error]');
    const allowDebt = modal.querySelector('#close-campaign-allow-debt');
    const confirmBtn = modal.querySelector('[data-close-campaign-confirm]');
    const dismissBtns = modal.querySelectorAll('[data-close-campaign-dismiss]');
    const confirmBtnHtml = confirmBtn?.innerHTML || '';

    let campaignId = null;
    let summaryController = null;
    let isClosing = false;

    const urlFor = (template, id) => template.replace('__CAMPAIGN__', encodeURIComponent(String(id)));

    function notify(message, type) {
        if (window.notify) window.notify(message, type);
        else alert(message);
    }

    function setLoading(on) {
        loading?.classList.toggle('hidden', !on);
        loading?.classList.toggle('flex', on);
    }

    function setError(message) {
        if (!errorBox) return;
        errorBox.textContent = message || '';
        errorBox.classList.toggle('hidden', !message);
    }

    const MONEY_FIELDS = ['gross_subtotal', 'discount_total', 'extra_fee_total', 'sponsor_total', 'self_paid_total', 'final_total'];
    const ITEM_FIELDS = ['own_items', 'proxy_items', 'self_paid_items', 'total_items'];

    function formatField(name, data) {
        if (name === 'ordered_users') return `${data.ordered_users_count ?? 0} / ${data.total_users_count ?? 0}`;
        const value = Number(data[name] ?? 0);
        if (MONEY_FIELDS.includes(name)) return formatMoney(value);
        if (ITEM_FIELDS.includes(name)) return `${value} ${i18n.portions || ''}`.trim();
        return String(value);
    }

    function renderSummary(data) {
        const code = data.code ? `#${data.code}` : `#${data.id}`;
        if (heading) heading.textContent = data.name ? `${code} · ${data.name}` : code;
        fields.forEach(el => {
            const text = formatField(el.dataset.closeSummary, data);
            const sign = el.dataset.sign && Number(data[el.dataset.closeSummary] ?? 0) > 0 ? el.dataset.sign : '';
            el.textContent = `${sign}${text}`;
        });
    }

    function resetSummary() {
        if (heading) heading.textContent = '—';
        fields.forEach(el => { el.textContent = '—'; });
    }

    function hide() {
        if (isClosing) return;
        summaryController?.abort();
        summaryController = null;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    async function loadSummary() {
        summaryController?.abort();
        const controller = new AbortController();
        summaryController = controller;

        if (confirmBtn) confirmBtn.disabled = true;
        setError('');
        setLoading(true);

        try {
            const res = await fetch(urlFor(summaryUrlTemplate, campaignId), {
                headers: { Accept: 'application/json' },
                cache: 'no-store',
                signal: controller.signal,
            });
            const json = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(json.message || i18n.failed);

            const data = json.data || {};
            renderSummary(data);
            if (data.is_closable) {
                if (confirmBtn) confirmBtn.disabled = false;
            } else {
                setError(i18n.notClosable);
            }
        } catch (err) {
            if (err.name === 'AbortError') return;
            setError(err.message || i18n.failed);
        } finally {
            if (summaryController === controller) {
                summaryController = null;
                setLoading(false);
            }
        }
    }

    function open(id) {
        if (!id || isClosing) return;
        campaignId = id;
        resetSummary();
        if (allowDebt) allowDebt.checked = true;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        loadSummary();
    }

    async function confirmClose() {
        if (!campaignId || isClosing) return;
        isClosing = true;
        confirmBtn.disabled = true;
        dismissBtns.forEach(btn => { btn.disabled = true; });
        confirmBtn.innerHTML = `<svg class="animate-spin h-4 w-4 text-white" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><span></span>`;
        confirmBtn.querySelector('span').textContent = i18n.closing || '';

        try {
            const res = await fetch(urlFor(closeUrlTemplate, campaignId), {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ allow_debt: allowDebt?.checked ?? true }),
            });
            const json = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(json.message || i18n.closeFailed);

            isClosing = false;
            notify(i18n.success, 'success');
            hide();

            const event = new CustomEvent('admin:campaign-closed', {
                cancelable: true,
                detail: { campaignId, campaign: json.data || null },
            });
            if (window.dispatchEvent(event)) window.location.reload();
        } catch (err) {
            isClosing = false;
            notify(err.message || i18n.closeFailed, 'error');
            confirmBtn.disabled = false;
        } finally {
            dismissBtns.forEach(btn => { btn.disabled = false; });
            confirmBtn.innerHTML = confirmBtnHtml;
        }
    }

    document.addEventListener('click', event => {
        const trigger = event.target.closest('[data-close-campaign-open]');
        if (!trigger) return;
        event.preventDefault();
        open(trigger.dataset.campaignId);
    });
    dismissBtns.forEach(btn => btn.addEventListener('click', hide));
    modal.addEventListener('click', event => {
        if (event.target === modal) hide();
    });
    confirmBtn?.addEventListener('click', confirmClose);
}

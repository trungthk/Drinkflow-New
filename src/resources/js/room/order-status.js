/**
 * Order Status polling & timeline renderer for DrinkFlow User Room
 */
export function initOrderStatus() {
    const container = document.querySelector('[data-order-status-container]');
    if (!container) return;

    const statusUrl = container.dataset.statusUrl;
    const initialStatus = container.dataset.initialStatus;
    const badge = container.querySelector('#status-badge');
    const timeline = [...container.querySelectorAll('[data-status]')];
    const statusUpdated = container.querySelector('#status-updated');

    const statusLabels = {
        submitted: container.dataset.labelSubmitted || 'Đã gửi',
        confirmed: container.dataset.labelConfirmed || 'Đã nhận',
        ordering: container.dataset.labelOrdering || 'Đang làm',
        ordered: container.dataset.labelOrdered || 'Đã xong',
        delivering: container.dataset.labelDelivering || 'Đang giao',
        completed: container.dataset.labelCompleted || 'Hoàn tất',
        cancelled: container.dataset.labelCancelled || 'Đã hủy'
    };

    function renderStatus(status) {
        if (badge) {
            badge.textContent = statusLabels[status] || status;
            badge.className = status === 'cancelled'
                ? 'rounded-full bg-red-50 px-3 py-1 text-sm font-medium text-red-700'
                : status === 'completed'
                ? 'rounded-full bg-emerald-50 px-3 py-1 text-sm font-medium text-emerald-700'
                : 'rounded-full bg-indigo-50 px-3 py-1 text-sm font-medium text-indigo-700';
        }

        const orderSteps = ['submitted', 'confirmed', 'ordering', 'ordered', 'delivering', 'completed'];
        const current = orderSteps.indexOf(status);
        timeline.forEach((node) => {
            const active = orderSteps.indexOf(node.dataset.status) <= current && current >= 0;
            node.className = active
                ? 'rounded-xl border border-emerald-300 bg-emerald-50 px-3 py-2 text-[#006948] transition-all'
                : 'rounded-xl border border-slate-200 px-3 py-2 text-slate-500 transition-all';
        });

        if (statusUpdated) {
            const locale = document.documentElement.lang || 'vi';
            statusUpdated.textContent = `${container.dataset.labelUpdatedPrefix || 'Cập nhật lúc'} ${new Date().toLocaleTimeString(locale)}`;
        }
    }

    async function refreshStatus() {
        if (!statusUrl) return;
        try {
            const response = await fetch(statusUrl, {
                headers: { Accept: 'application/json' }
            });
            if (!response.ok) return;
            const data = await response.json();
            if (data?.data?.status) {
                renderStatus(data.data.status);
            }
        } catch (_) {}
    }

    if (initialStatus) {
        renderStatus(initialStatus);
    }
    setInterval(refreshStatus, 15000);
}

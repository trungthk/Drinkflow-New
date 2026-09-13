/**
 * Admin Campaign Detail & Settlement Utilities
 */
export function initAdminCampaignDetail() {
    const btnCopy = document.querySelector('#btn-copy-items');
    const btnExport = document.querySelector('#btn-export-statement');
    if (!btnCopy && !btnExport) return;

    btnCopy?.addEventListener('click', () => {
        let payload = [];
        let rest = '';
        if (btnCopy.dataset.summary) {
            try { payload = JSON.parse(btnCopy.dataset.summary); } catch (e) {}
        } else {
            payload = window.__DF_CAMPAIGN_SUMMARY__ || [];
        }
        rest = btnCopy.dataset.restaurant || window.__DF_CAMPAIGN_RESTAURANT__ || '';

        const lines = [`=== ${rest} ===`];
        payload.forEach(item => {
            lines.push(`- ${item.name || item.item_name || 'Item'} ${item.size ? '(' + item.size + ')' : ''}: ${item.quantity || 1}`);
        });
        navigator.clipboard.writeText(lines.join('\n')).then(() => {
            alert('Đã sao chép danh sách đơn món!');
        });
    });

    btnExport?.addEventListener('click', () => {
        let orders = [];
        if (btnExport.dataset.orders) {
            try { orders = JSON.parse(btnExport.dataset.orders); } catch (e) {}
        } else {
            orders = window.__DF_CAMPAIGN_ORDERS__ || [];
        }
        const campaignId = btnExport.dataset.campaignId || window.__DF_CAMPAIGN_ID__ || 'export';

        const rows = [
            ["No.", "Member", "User Code", "Items", "Gross Bill", "Subsidy", "Payable", "Status"]
        ];
        orders.forEach((o, idx) => {
            rows.push([
                idx + 1,
                `"${(o.member || '').replace(/"/g, '""')}"`,
                `"${o.code || ''}"`,
                o.items_count || 0,
                o.subtotal || 0,
                o.sponsor_amount || 0,
                o.final_amount || 0,
                `"${o.status || ''}"`
            ]);
        });
        const csvContent = "data:text/csv;charset=utf-8," + rows.map(e => e.join(",")).join("\n");
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", `quyet_toan_campaign_${campaignId}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
}

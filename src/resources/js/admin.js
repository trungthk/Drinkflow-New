import './bootstrap';

import { initAdminGoToTop } from './admin/go-to-top';
import { initUiEnhancements, renderTableSkeleton, debounce } from './admin/ui-enhancements';
import { initAdminAuth } from './admin/auth';
import { initAdminAudit } from './admin/audit';
import { initAdminCampaigns } from './admin/campaigns';
import { campaignCreateComponent } from './admin/campaign-create';
import { initAdminCampaignDetail } from './admin/campaign-detail';
import { liveCampaignComponent } from './admin/campaign-live';
import { initAdminDashboard } from './admin/dashboard';
import { initAdminDebts } from './admin/debts';
import { initAdminNotifications } from './admin/notifications';
import { initAdminOrders } from './admin/orders';
import { initAdminPayments } from './admin/payments';
import { initAdminReports } from './admin/reports';
import { initAdminSettings } from './admin/settings';
import { initAdminUsers } from './admin/users';

export {
    initAdminGoToTop,
    initUiEnhancements,
    renderTableSkeleton,
    debounce,
    initAdminAuth,
    initAdminAudit,
    initAdminCampaigns,
    campaignCreateComponent,
    initAdminCampaignDetail,
    liveCampaignComponent,
    initAdminDashboard,
    initAdminDebts,
    initAdminNotifications,
    initAdminOrders,
    initAdminPayments,
    initAdminReports,
    initAdminSettings,
    initAdminUsers,
};

// Expose globals for Alpine and inline calls
if (typeof window !== 'undefined') {
    window.campaignCreateComponent = campaignCreateComponent;
    window.liveCampaignComponent = liveCampaignComponent;
    window.renderTableSkeleton = renderTableSkeleton;
    window.debounce = debounce;
    window.__DF_ROOM_SLUG__ = window.__DF_ROOM_SLUG__ || document.querySelector('meta[name="room-slug"]')?.content || document.body?.dataset?.roomSlug || '';
}

document.addEventListener('DOMContentLoaded', () => {
    window.__DF_ROOM_SLUG__ = window.__DF_ROOM_SLUG__ || document.querySelector('meta[name="room-slug"]')?.content || document.body?.dataset?.roomSlug || '';

    initUiEnhancements();
    initAdminGoToTop();
    initAdminAuth();
    initAdminAudit();
    initAdminCampaigns();
    initAdminCampaignDetail();
    initAdminDashboard();
    initAdminDebts();
    initAdminNotifications();
    initAdminOrders();
    initAdminPayments();
    initAdminReports();
    initAdminSettings();
    initAdminUsers();
});


import './bootstrap';
import { initToastNotifications } from './shared/toast';
import { initLazyImages } from './shared/lazy-image';

initLazyImages();

import { initAdminAlertModal } from './admin/alert-modal';
import { initAdminGoToTop } from './admin/go-to-top';
import { initUiEnhancements, renderTableSkeleton, debounce, toggleAdminSidebar } from './admin/ui-enhancements';
import { initAdminAuth } from './admin/auth';
import { initAdminAudit } from './admin/audit';
import { initAdminCampaigns } from './admin/campaigns';
import { campaignCreateComponent } from './admin/campaign-create';
import { initAdminCampaignDetail } from './admin/campaign-detail';
import { initAdminDashboard } from './admin/dashboard';
import { initAdminDebts } from './admin/debts';
import { initAdminNotifications } from './admin/notifications';
import { initAdminOrders } from './admin/orders';
import { initAdminReports } from './admin/reports';
import { initAdminSettings } from './admin/settings';
import { initAdminUsers } from './admin/users';
import { initAdminLoading, initAdminReloadButtons } from './admin/loading';

export {
    initAdminGoToTop,
    initUiEnhancements,
    renderTableSkeleton,
    debounce,
    toggleAdminSidebar,
    initAdminAuth,
    initAdminAudit,
    initAdminCampaigns,
    campaignCreateComponent,
    initAdminCampaignDetail,
    initAdminDashboard,
    initAdminDebts,
    initAdminNotifications,
    initAdminOrders,
    initAdminReports,
    initAdminSettings,
    initAdminUsers,
    initAdminLoading,
    initAdminReloadButtons,
};

// Expose globals for Alpine and inline calls
if (typeof window !== 'undefined') {
    window.campaignCreateComponent = campaignCreateComponent;
    window.renderTableSkeleton = renderTableSkeleton;
    window.debounce = debounce;
    window.toggleAdminSidebar = toggleAdminSidebar;
    window.__DF_ROOM_SLUG__ = window.__DF_ROOM_SLUG__ || document.querySelector('meta[name="room-slug"]')?.content || document.body?.dataset?.roomSlug || '';
}

document.addEventListener('DOMContentLoaded', () => {
    initToastNotifications();
    // Runs after initToastNotifications() so this modal-based override wins over the toast-based
    // `window.alert` it sets: every alert(...) in the admin bundle should open a modal, not a toast.
    initAdminAlertModal();
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
    initAdminReports();
    initAdminSettings();
    initAdminUsers();
    initAdminLoading();
    initAdminReloadButtons();
});

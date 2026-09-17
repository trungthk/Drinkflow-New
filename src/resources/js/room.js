import './bootstrap';
import { initToastNotifications } from './shared/toast';
import { initLazyImages } from './shared/lazy-image';

initLazyImages();

import { initOrderStatus } from './room/order-status';
import { initCampaignOrder } from './room/campaign-order';
import { initGlobalGoToTop } from './global/go-to-top';
import { initGlobalLoading } from './global/loading';
import { initRoomRealtime } from './room/realtime';
import { initCampaignDecline } from './room/campaign-decline';
import { initRoomHeader } from './room/header';

document.addEventListener('DOMContentLoaded', () => {
    initToastNotifications();
    initOrderStatus();
    initCampaignOrder();
    initGlobalGoToTop();
    initGlobalLoading();
    initRoomRealtime();
    initCampaignDecline();
    initRoomHeader();
});

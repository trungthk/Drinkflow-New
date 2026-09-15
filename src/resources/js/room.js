import './bootstrap';

import { initOrderStatus } from './room/order-status';
import { initCampaignOrder } from './room/campaign-order';
import { initGlobalGoToTop } from './global/go-to-top';
import { initGlobalLoading } from './global/loading';
import { initRoomRealtime } from './room/realtime';
import { initCampaignDecline } from './room/campaign-decline';

document.addEventListener('DOMContentLoaded', () => {
    initOrderStatus();
    initCampaignOrder();
    initGlobalGoToTop();
    initGlobalLoading();
    initRoomRealtime();
    initCampaignDecline();
});

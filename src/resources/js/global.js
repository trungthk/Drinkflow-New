import './bootstrap';

import { initGlobalHeader } from './global/header';
import { initLogoutModal } from './global/logout-modal';
import { initGlobalLoading } from './global/loading';
import { initGlobalGoToTop } from './global/go-to-top';
import { initGlobalDashboard } from './global/dashboard';
import { initGlobalFooterNav } from './global/footer';
import { initGlobalFeedback } from './global/feedback';

document.addEventListener('DOMContentLoaded', () => {
    initGlobalHeader();
    initLogoutModal();
    initGlobalLoading();
    initGlobalGoToTop();
    initGlobalDashboard();
    initGlobalFooterNav();
    initGlobalFeedback();
});

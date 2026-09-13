import './bootstrap';

import { initGlobalHeader } from './global/header';
import { initLogoutModal } from './global/logout-modal';
import { initGlobalLoading } from './global/loading';
import { initGlobalGoToTop } from './global/go-to-top';
import { initGlobalDashboard } from './global/dashboard';

document.addEventListener('DOMContentLoaded', () => {
    initGlobalHeader();
    initLogoutModal();
    initGlobalLoading();
    initGlobalGoToTop();
    initGlobalDashboard();
});

import './bootstrap';
import { initToastNotifications } from './shared/toast';
import { initLazyImages } from './shared/lazy-image';

initToastNotifications();
initLazyImages();

import { initPublicHeader } from './public/header';
import { initAuthModal } from './public/auth-modal';
import { initVideoModal } from './public/video-modal';
import { initPublicLoading } from './public/loading';
import { initGoToTop } from './public/go-to-top';
import { initContactPage } from './public/contact';
import { initTermsPage } from './public/terms';

import { initGlobalHeader } from './global/header';
import { initLogoutModal } from './global/logout-modal';
import { initGlobalLoading } from './global/loading';
import { initGlobalGoToTop } from './global/go-to-top';

import { initSuperadminLoading, initSuperadminReloadButtons } from './superadmin/loading';
import { exposeSuperadminGlobals } from './superadmin/shared';
import { initSuperadminModals, openSuperadminConfirm } from './superadmin/modal';
import { initDateRangePickers } from './admin/ui-enhancements';
import { renderSubmitLoading } from './shared/submit-loading';

// Runs before DOMContentLoaded: the inline @push('scripts') blocks in superadmin/*.blade.php
// call window.dfApi/escapeHtml/statusPill/money/openSuperadminConfirm synchronously as soon as
// the page script runs (see resources/views/superadmin/layout.blade.php's shim for why).
exposeSuperadminGlobals();
window.openSuperadminConfirm = openSuperadminConfirm;
window.renderSubmitLoading = renderSubmitLoading;

document.addEventListener('DOMContentLoaded', () => {
    // Public Components & Pages
    initPublicHeader();
    initAuthModal();
    initVideoModal();
    initPublicLoading();
    initGoToTop();
    initContactPage();
    initTermsPage();

    // Global User Components & Pages
    initGlobalHeader();
    initLogoutModal();
    initGlobalLoading();
    initGlobalGoToTop();

    // Superadmin Components & Pages
    initSuperadminLoading();
    initSuperadminReloadButtons();
    initSuperadminModals();
    initDateRangePickers();
});

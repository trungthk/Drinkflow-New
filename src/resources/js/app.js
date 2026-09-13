import './bootstrap';

import { initPublicHeader } from './public/header';
import { initAuthModal } from './public/auth-modal';
import { initVideoModal } from './public/video-modal';
import { initPublicLoading } from './public/loading';
import { initGoToTop } from './public/go-to-top';
import { initContactPage } from './public/contact';
import { initTermsPage } from './public/terms';
import { initVersionsPage } from './public/versions';

import { initGlobalHeader } from './global/header';
import { initLogoutModal } from './global/logout-modal';
import { initGlobalLoading } from './global/loading';
import { initGlobalGoToTop } from './global/go-to-top';

document.addEventListener('DOMContentLoaded', () => {
    // Public Components & Pages
    initPublicHeader();
    initAuthModal();
    initVideoModal();
    initPublicLoading();
    initGoToTop();
    initContactPage();
    initTermsPage();
    initVersionsPage();

    // Global User Components & Pages
    initGlobalHeader();
    initLogoutModal();
    initGlobalLoading();
    initGlobalGoToTop();
});

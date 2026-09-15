import './bootstrap';
import { initToastNotifications } from './shared/toast';

import { initPublicHeader } from './public/header';
import { initFooterNav } from './public/footer';
import { initAuthModal } from './public/auth-modal';
import { initVideoModal } from './public/video-modal';
import { initPublicLoading } from './public/loading';
import { initGoToTop } from './public/go-to-top';
import { initContactPage } from './public/contact';
import { initTermsPage } from './public/terms';

document.addEventListener('DOMContentLoaded', () => {
    initToastNotifications();
    initPublicHeader();
    initFooterNav();
    initAuthModal();
    initVideoModal();
    initPublicLoading();
    initGoToTop();
    initContactPage();
    initTermsPage();
});

import './bootstrap';

import { initPublicHeader } from './public/header';
import { initAuthModal } from './public/auth-modal';
import { initVideoModal } from './public/video-modal';
import { initPublicLoading } from './public/loading';
import { initGoToTop } from './public/go-to-top';
import { initContactPage } from './public/contact';
import { initTermsPage } from './public/terms';
import { initVersionsPage } from './public/versions';

document.addEventListener('DOMContentLoaded', () => {
    initPublicHeader();
    initAuthModal();
    initVideoModal();
    initPublicLoading();
    initGoToTop();
    initContactPage();
    initTermsPage();
    initVersionsPage();
});

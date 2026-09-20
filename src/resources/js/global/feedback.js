/**
 * Global User Feedback Interactive Module (Captcha Refresh & Dynamic Load More)
 */

function escapeHtml(str) {
    if (!str) return '';
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

export function initGlobalFeedback() {
    // 1. Captcha Refresh Handler
    const refreshBtn = document.getElementById('refresh-captcha-btn');
    const captchaWrapper = document.getElementById('captcha-img-wrapper');

    async function refreshCaptcha(e) {
        if (e) e.preventDefault();
        const wrapper = document.getElementById('captcha-img-wrapper');
        const btn = document.getElementById('refresh-captcha-btn');
        if (!wrapper) return;

        const syncIcon = btn ? btn.querySelector('span.material-symbols-outlined') : null;
        if (syncIcon) syncIcon.classList.add('animate-spin');

        const loadingText = wrapper.dataset.loadingText || '';
        const captchaApiUrl = wrapper.dataset.captchaApi || '/captcha/api/contact';
        const captchaFallbackUrl = wrapper.dataset.captchaFallback || '/captcha/contact';

        wrapper.innerHTML = `
            <div class="flex items-center gap-1.5 text-xs text-[#006948] font-medium animate-pulse">
                <svg class="animate-spin h-3.5 w-3.5 text-[#006948]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>${escapeHtml(loadingText)}</span>
            </div>
        `;

        try {
            const res = await fetch(captchaApiUrl + '?' + Date.now(), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (res.ok) {
                const data = await res.json();
                wrapper.innerHTML = '';
                if (data.img) {
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = data.img;
                    const newImg = tempDiv.querySelector('img');
                    if (newImg) {
                        newImg.className = 'w-full h-full object-contain';
                        newImg.alt = 'captcha';
                        wrapper.appendChild(newImg);
                    } else {
                        const directImg = document.createElement('img');
                        directImg.src = captchaFallbackUrl + '?' + Date.now();
                        directImg.alt = 'captcha';
                        directImg.className = 'w-full h-full object-contain';
                        wrapper.appendChild(directImg);
                    }
                } else {
                    const directImg = document.createElement('img');
                    directImg.src = captchaFallbackUrl + '?' + Date.now();
                    directImg.alt = 'captcha';
                    directImg.className = 'w-full h-full object-contain';
                    wrapper.appendChild(directImg);
                }
            } else {
                wrapper.innerHTML = '';
                const directImg = document.createElement('img');
                directImg.src = captchaFallbackUrl + '?' + Date.now();
                directImg.alt = 'captcha';
                directImg.className = 'w-full h-full object-contain';
                wrapper.appendChild(directImg);
            }
        } catch (err) {
            console.warn('Captcha API fetch failed, falling back to direct image URL', err);
            wrapper.innerHTML = '';
            const directImg = document.createElement('img');
            directImg.src = captchaFallbackUrl + '?' + Date.now();
            directImg.alt = 'captcha';
            directImg.className = 'w-full h-full object-contain';
            wrapper.appendChild(directImg);
        } finally {
            if (syncIcon) {
                setTimeout(() => {
                    syncIcon.classList.remove('animate-spin');
                }, 300);
            }
        }
    }

    if (refreshBtn) {
        refreshBtn.addEventListener('click', refreshCaptcha);
    }
    if (captchaWrapper) {
        captchaWrapper.addEventListener('click', refreshCaptcha);
    }

    // 2. Load More Feedback Handler
    const loadMoreBtn = document.getElementById('load-more-feedbacks-btn');
    const container = document.getElementById('feedbacks-container');
    const loadMoreWrapper = document.getElementById('load-more-wrapper');
    const countBadge = document.getElementById('showing-feedbacks-count');

    if (loadMoreBtn && container) {
        loadMoreBtn.addEventListener('click', async function() {
            const nextPage = parseInt(loadMoreBtn.dataset.nextPage, 10) || 2;
            const baseUrl = loadMoreBtn.dataset.url || window.location.pathname;
            const loadingText = loadMoreBtn.dataset.loadingText || '';
            const allLoadedText = loadMoreBtn.dataset.allLoadedText || '';
            const showingTemplate = loadMoreBtn.dataset.showingText || '';
            const btnTextSpan = document.getElementById('load-more-text');

            // Set loading state
            loadMoreBtn.disabled = true;
            if (btnTextSpan) btnTextSpan.textContent = loadingText;
            const icon = loadMoreBtn.querySelector('span.material-symbols-outlined');
            if (icon) {
                icon.textContent = 'progress_activity';
                icon.classList.add('animate-spin');
            }

            try {
                const url = new URL(baseUrl, window.location.origin);
                url.searchParams.set('page', nextPage);

                const response = await fetch(url.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const result = await response.json();

                if (result.success && Array.isArray(result.data)) {
                    result.data.forEach(item => {
                        const article = document.createElement('article');
                        article.className = 'feedback-item bg-white border border-slate-200 rounded-2xl p-4 shadow-2xs hover:border-slate-300 transition-colors animate-fadeIn';

                        // Build stars
                        let starsHtml = '';
                        for (let i = 1; i <= 5; i++) {
                            const isFilled = i <= item.rating;
                            starsHtml += `<span class="material-symbols-outlined text-[15px]" style="font-variation-settings: 'FILL' ${isFilled ? 1 : 0}; ${!isFilled ? 'color: #cbd5e1;' : ''}">star</span>`;
                        }

                        article.innerHTML = `
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-full bg-emerald-50 text-emerald-700 font-bold text-xs flex items-center justify-center border border-emerald-200 shrink-0">
                                        ${escapeHtml(item.user_initial || 'U')}
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-semibold text-slate-800">${escapeHtml(item.user_display_name)}</h4>
                                        <span class="text-[11px] text-slate-400">${escapeHtml(item.created_at_formatted)} · ${escapeHtml(item.subsystem_label || '')}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-0.5 text-[#006948]">
                                    ${starsHtml}
                                </div>
                            </div>
                            <p class="text-xs text-slate-600 leading-relaxed">
                                "${escapeHtml(item.content)}"
                            </p>
                        `;
                        container.appendChild(article);
                    });

                    // Update count
                    const currentCount = container.querySelectorAll('article.feedback-item').length;
                    const totalCount = result.total || currentCount;
                    if (countBadge) {
                        const templateWithCount = showingTemplate.replace('__COUNT__', currentCount);
                        countBadge.textContent = `${templateWithCount} / ${totalCount}`;
                    }

                    if (result.has_more) {
                        loadMoreBtn.dataset.nextPage = result.next_page;
                        loadMoreBtn.disabled = false;
                        if (btnTextSpan) btnTextSpan.textContent = loadMoreBtn.getAttribute('title') || '';
                        if (icon) {
                            icon.textContent = 'expand_more';
                            icon.classList.remove('animate-spin');
                        }
                    } else {
                        // All loaded
                        if (loadMoreWrapper) {
                            loadMoreWrapper.innerHTML = `
                                <span class="text-xs text-slate-400 font-medium">${escapeHtml(allLoadedText)}</span>
                            `;
                        }
                    }
                }
            } catch (error) {
                console.error('Error loading more feedbacks:', error);
                loadMoreBtn.disabled = false;
                if (btnTextSpan) btnTextSpan.textContent = loadMoreBtn.dataset.retryText || '';
                if (icon) {
                    icon.textContent = 'refresh';
                    icon.classList.remove('animate-spin');
                }
            }
        });
    }
}

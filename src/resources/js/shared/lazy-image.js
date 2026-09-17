/**
 * Shared Lazy Loading Image Module
 * 
 * Provides IntersectionObserver-based lazy loading for:
 * 1. Alpine directive: x-lazy-src="item.image_url"
 * 2. Vanilla HTML elements: <img data-lazy-src="..." loading="lazy">
 * 
 * Images are only fetched when entering or nearing the viewport (within 100px),
 * preventing unnecessary network requests and bandwidth usage for offscreen items.
 */

const TRANSPARENT_PLACEHOLDER = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1 1'%3E%3C/svg%3E";

export function initLazyImages() {
    if (typeof window === 'undefined') return;

    // 1. Shared IntersectionObserver for data-lazy-src / data-src elements
    let sharedObserver = null;

    function getSharedObserver() {
        if (!sharedObserver && typeof IntersectionObserver !== 'undefined') {
            sharedObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const target = entry.target;
                        const src = target.getAttribute('data-lazy-src') || target.getAttribute('data-src');
                        if (src) {
                            target.src = src;
                            target.removeAttribute('data-lazy-src');
                            target.removeAttribute('data-src');
                        }
                        observer.unobserve(target);
                    }
                });
            }, {
                rootMargin: '100px 0px',
                threshold: 0.01,
            });
        }
        return sharedObserver;
    }

    // Expose global observer helper
    window.dfObserveLazyImage = function(el) {
        if (!el) return;
        const observer = getSharedObserver();
        if (observer && (el.getAttribute('data-lazy-src') || el.getAttribute('data-src'))) {
            observer.observe(el);
        }
    };

    // Scan existing DOM for data-lazy-src
    const observer = getSharedObserver();
    if (observer) {
        document.querySelectorAll('img[data-lazy-src], img[data-src]').forEach(img => {
            observer.observe(img);
        });
    }

    // 2. Register Alpine directive: x-lazy-src
    const registerDirective = (alpine) => {
        if (!alpine || alpine._dfLazySrcDirectiveRegistered) return;
        alpine._dfLazySrcDirectiveRegistered = true;

        alpine.directive('lazy-src', (el, { expression }, { evaluateLater, effect, cleanup }) => {
            const evaluateUrl = evaluateLater(expression);
            let latestUrl = '';
            let isLoaded = false;
            let elObserver = null;

            // Set placeholder initial src if not present
            if (!el.getAttribute('src')) {
                el.setAttribute('src', TRANSPARENT_PLACEHOLDER);
            }

            const loadCurrentUrl = () => {
                if (!latestUrl || isLoaded) return;
                isLoaded = true;
                el.src = latestUrl;
                if (elObserver) {
                    elObserver.unobserve(el);
                    elObserver.disconnect();
                    elObserver = null;
                }
            };

            if (typeof IntersectionObserver !== 'undefined') {
                elObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            loadCurrentUrl();
                        }
                    });
                }, {
                    rootMargin: '100px 0px',
                    threshold: 0.01,
                });

                elObserver.observe(el);
            } else {
                // Fallback for browsers without IntersectionObserver
                isLoaded = true;
            }

            effect(() => {
                evaluateUrl(url => {
                    const newUrl = String(url || '').trim();
                    if (newUrl !== latestUrl) {
                        latestUrl = newUrl;
                        if (!latestUrl) {
                            el.src = TRANSPARENT_PLACEHOLDER;
                            return;
                        }

                        // If already intersecting or intersection observer unavailable, apply immediately
                        if (isLoaded) {
                            el.src = latestUrl;
                        }
                    }
                });
            });

            cleanup(() => {
                if (elObserver) {
                    elObserver.disconnect();
                    elObserver = null;
                }
            });
        });
    };

    if (window.Alpine) {
        registerDirective(window.Alpine);
    } else {
        document.addEventListener('alpine:init', () => {
            registerDirective(window.Alpine);
        });
    }
}

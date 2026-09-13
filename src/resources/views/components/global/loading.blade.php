<!-- Global User Loading Page & Submit Overlay Component -->
<div id="global-page-loading"
     class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-white/75 backdrop-blur-xs transition-opacity duration-200 opacity-100"
     role="status"
     aria-live="polite"
     aria-label="Đang tải">
  <div class="flex flex-col items-center gap-2.5 select-none transform scale-100 transition-all duration-200" id="global-loading-card">
    <!-- Gentle Minimal Brand Spinner -->
    <div class="relative w-10 h-10 flex items-center justify-center">
      <div class="w-10 h-10 rounded-full border-2 border-emerald-100 border-t-[#006948] animate-spin"></div>
      <span class="material-symbols-outlined text-[18px] text-[#006948] absolute animate-pulse">local_cafe</span>
    </div>

    <!-- Minimal, Soft Single Line Text -->
    <p class="text-xs font-medium text-slate-500 tracking-wide" id="global-loading-title">
      Đang tải...
    </p>
  </div>
</div>

<script>
  (function() {
    const loadingOverlay = document.getElementById('global-page-loading');
    const loadingCard = document.getElementById('global-loading-card');
    const loadingTitle = document.getElementById('global-loading-title');
    let autoHideTimer = null;

    window.showGlobalLoading = function(title) {
      if (!loadingOverlay || !loadingCard) return;

      if (title && loadingTitle) loadingTitle.textContent = title;

      loadingOverlay.classList.remove('hidden', 'pointer-events-none');
      loadingOverlay.classList.add('flex');

      requestAnimationFrame(() => {
        loadingOverlay.classList.remove('opacity-0');
        loadingOverlay.classList.add('opacity-100');
        loadingCard.classList.remove('scale-95');
        loadingCard.classList.add('scale-100');
      });

      // Safety fallback: auto-hide after 10s if network hangs
      clearTimeout(autoHideTimer);
      autoHideTimer = setTimeout(() => {
        window.hideGlobalLoading();
      }, 10000);
    };

    window.hideGlobalLoading = function() {
      if (!loadingOverlay || !loadingCard) return;

      loadingOverlay.classList.remove('opacity-100');
      loadingOverlay.classList.add('opacity-0');
      loadingCard.classList.remove('scale-100');
      card = loadingCard;
      card.classList.add('scale-95');

      setTimeout(() => {
        loadingOverlay.classList.add('hidden', 'pointer-events-none');
        loadingOverlay.classList.remove('flex');
      }, 200);

      clearTimeout(autoHideTimer);
    };

    // Smooth subtle reveal on page readiness
    function onInitialPageLoad() {
      setTimeout(() => {
        window.hideGlobalLoading();
      }, 180);
    }

    if (document.readyState === 'complete') {
      onInitialPageLoad();
    } else {
      window.addEventListener('load', onInitialPageLoad);
    }

    // Auto-attach to all forms on submit
    document.addEventListener('submit', function(e) {
      const form = e.target;
      if (!form || form.hasAttribute('data-no-loading')) return;

      if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
        return;
      }

      const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
      let customTitle = 'Đang lưu...';
      if (submitBtn) {
        if (submitBtn.hasAttribute('data-loading-title')) {
          customTitle = submitBtn.getAttribute('data-loading-title');
        } else if (submitBtn.textContent.trim()) {
          const raw = submitBtn.textContent.trim().replace(/\s+/g, ' ');
          if (raw.length <= 20) {
            customTitle = `Đang ${raw.toLowerCase()}...`;
          }
        }
      }

      window.showGlobalLoading(customTitle);

      if (submitBtn && !submitBtn.disabled) {
        submitBtn.dataset.originalHtml = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-75', 'cursor-wait');
      }
    });

    // Reset loading on back button (browser bfcache)
    window.addEventListener('pageshow', function(e) {
      if (e.persisted) {
        window.hideGlobalLoading();
        document.querySelectorAll('button[type="submit"][disabled]').forEach(btn => {
          btn.disabled = false;
          btn.classList.remove('opacity-75', 'cursor-wait');
          if (btn.dataset.originalHtml) {
            btn.innerHTML = btn.dataset.originalHtml;
          }
        });
      }
    });
  })();
</script>

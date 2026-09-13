/**
 * DrinkFlow Admin UI Enhancements
 * - Debounce Search with Clear ('x') Button
 * - Searchable Select (Combobox with Filter)
 * - Date Range Picker with Presets
 * - Table Skeleton Loading State
 * - Form Submit Loading & Double-Click Prevention
 */

export function debounce(func, wait = 300) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func.apply(this, args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * 1. Debounce Search & Clear Button
 */
export function initSearchDebounceAndClear() {
    const searchInputs = document.querySelectorAll('[data-search-input], .admin-search-input');

    searchInputs.forEach(input => {
        if (input.dataset.searchInitialized) return;
        input.dataset.searchInitialized = 'true';

        const wrapper = input.closest('.search-input-wrapper') || input.parentElement;
        let clearBtn = wrapper.querySelector('.search-clear-btn');

        if (!clearBtn && wrapper) {
            clearBtn = document.createElement('button');
            clearBtn.type = 'button';
            clearBtn.className = 'search-clear-btn absolute right-2.5 top-1/2 -translate-y-1/2 text-outline hover:text-on-surface p-0.5 rounded-full hover:bg-surface-container transition-all cursor-pointer hidden';
            clearBtn.setAttribute('aria-label', 'Clear search');
            clearBtn.innerHTML = '<span class="material-symbols-outlined text-[16px] block">close</span>';
            if (wrapper.classList.contains('relative') || getComputedStyle(wrapper).position === 'relative') {
                wrapper.appendChild(clearBtn);
            }
        }

        const toggleClearBtn = () => {
            if (clearBtn) {
                if (input.value.trim().length > 0) {
                    clearBtn.classList.remove('hidden');
                } else {
                    clearBtn.classList.add('hidden');
                }
            }
        };

        const triggerFilter = debounce(() => {
            const event = new CustomEvent('admin:search', {
                bubbles: true,
                detail: { query: input.value.trim().toLowerCase() }
            });
            input.dispatchEvent(event);
        }, 250);

        input.addEventListener('input', () => {
            toggleClearBtn();
            triggerFilter();
        });

        clearBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            input.value = '';
            toggleClearBtn();
            input.focus();
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new CustomEvent('admin:search', {
                bubbles: true,
                detail: { query: '' }
            }));
        });

        toggleClearBtn();
    });
}

/**
 * 2. Searchable Select (Combobox)
 */
export function initSearchableSelects() {
    const selects = document.querySelectorAll('select[data-searchable]');

    selects.forEach(select => {
        if (select.dataset.searchableInitialized) return;
        select.dataset.searchableInitialized = 'true';

        // Hide original select but keep in DOM for form serialization
        select.classList.add('hidden');

        const wrapper = document.createElement('div');
        wrapper.className = 'searchable-select-container relative w-full text-xs font-sans';

        const triggerBtn = document.createElement('button');
        triggerBtn.type = 'button';
        triggerBtn.className = 'w-full h-9 px-3 bg-surface border border-outline-variant rounded flex items-center justify-between text-left text-on-surface hover:border-primary focus:border-primary focus:ring-1 focus:ring-primary transition-all cursor-pointer';

        const selectedOption = select.options[select.selectedIndex] || select.options[0];
        const triggerText = document.createElement('span');
        triggerText.className = 'truncate font-medium';
        triggerText.textContent = selectedOption ? selectedOption.text : 'Select...';

        const triggerIcon = document.createElement('span');
        triggerIcon.className = 'material-symbols-outlined text-[18px] text-outline transition-transform duration-200';
        triggerIcon.textContent = 'arrow_drop_down';

        triggerBtn.appendChild(triggerText);
        triggerBtn.appendChild(triggerIcon);
        wrapper.appendChild(triggerBtn);

        // Dropdown Panel
        const dropdown = document.createElement('div');
        dropdown.className = 'searchable-dropdown absolute left-0 right-0 top-full mt-1 bg-surface-container-lowest border border-outline-variant rounded-lg shadow-xl z-50 p-2 space-y-1.5 hidden max-h-60 flex flex-col backdrop-blur-xs';

        // Search Input inside dropdown
        const searchBox = document.createElement('div');
        searchBox.className = 'relative flex items-center mb-1';
        searchBox.innerHTML = `
            <span class="material-symbols-outlined absolute left-2.5 text-[16px] text-outline">search</span>
            <input type="text" placeholder="${select.dataset.placeholder || 'Tìm kiếm...'}" class="w-full h-8 pl-8 pr-3 bg-surface border border-outline-variant rounded text-xs text-on-surface outline-none focus:border-primary">
        `;
        dropdown.appendChild(searchBox);

        // Options List Container
        const listContainer = document.createElement('div');
        listContainer.className = 'overflow-y-auto flex-1 space-y-0.5 max-h-44 divide-y divide-outline-variant/20';
        dropdown.appendChild(listContainer);

        const renderOptions = (filter = '') => {
            listContainer.innerHTML = '';
            const lowerFilter = filter.toLowerCase();
            let count = 0;

            Array.from(select.options).forEach(opt => {
                if (filter && !opt.text.toLowerCase().includes(lowerFilter)) return;
                count++;

                const optBtn = document.createElement('button');
                optBtn.type = 'button';
                optBtn.className = `w-full px-2.5 py-1.5 rounded text-left text-xs transition-colors flex items-center justify-between cursor-pointer ${opt.value === select.value ? 'bg-primary/10 text-primary font-bold' : 'hover:bg-surface-container-low text-on-surface'}`;
                optBtn.textContent = opt.text;

                if (opt.value === select.value) {
                    optBtn.insertAdjacentHTML('beforeend', '<span class="material-symbols-outlined text-[16px] text-primary">check</span>');
                }

                optBtn.addEventListener('click', () => {
                    select.value = opt.value;
                    triggerText.textContent = opt.text;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    closeDropdown();
                });

                listContainer.appendChild(optBtn);
            });

            if (count === 0) {
                listContainer.innerHTML = '<div class="p-3 text-center text-outline text-xs">Không tìm thấy lựa chọn</div>';
            }
        };

        const filterInput = searchBox.querySelector('input');
        filterInput.addEventListener('input', () => {
            renderOptions(filterInput.value);
        });

        function openDropdown() {
            dropdown.classList.remove('hidden');
            triggerIcon.classList.add('rotate-180');
            filterInput.value = '';
            renderOptions();
            setTimeout(() => filterInput.focus(), 50);
        }

        function closeDropdown() {
            dropdown.classList.add('hidden');
            triggerIcon.classList.remove('rotate-180');
        }

        triggerBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (dropdown.classList.contains('hidden')) {
                // Close any other opened dropdowns
                document.querySelectorAll('.searchable-dropdown').forEach(d => d.classList.add('hidden'));
                openDropdown();
            } else {
                closeDropdown();
            }
        });

        dropdown.addEventListener('click', (e) => e.stopPropagation());

        document.addEventListener('click', () => {
            closeDropdown();
        });

        // Sync when select changes programmatically
        select.addEventListener('change', () => {
            const current = select.options[select.selectedIndex];
            if (current) triggerText.textContent = current.text;
        });

        wrapper.appendChild(dropdown);
        select.parentNode.insertBefore(wrapper, select.nextSibling);
    });
}

/**
 * 3. Date Range Filter with Presets
 */
export function initDateRangePickers() {
    const pickers = document.querySelectorAll('[data-date-range-picker]');

    pickers.forEach(container => {
        if (container.dataset.dateRangeInitialized) return;
        container.dataset.dateRangeInitialized = 'true';

        const triggerBtn = container.querySelector('[data-date-range-trigger]');
        const dropdown = container.querySelector('[data-date-range-dropdown]');
        const labelEl = container.querySelector('[data-date-range-label]');
        const startInput = container.querySelector('input[name="date_from"], input[data-date-from]');
        const endInput = container.querySelector('input[name="date_to"], input[data-date-to]');
        const customBox = container.querySelector('[data-custom-date-box]');
        const btnApply = container.querySelector('[data-apply-date-range]');

        if (!triggerBtn || !dropdown) return;

        function formatDate(d) {
            return d.toISOString().split('T')[0];
        }

        function formatDisplay(d) {
            const parts = d.split('-');
            if (parts.length === 3) return `${parts[2]}/${parts[1]}`;
            return d;
        }

        function setRange(start, end, presetName, labelText) {
            if (startInput) startInput.value = start;
            if (endInput) endInput.value = end;

            if (labelEl) {
                if (start && end) {
                    labelEl.textContent = `${labelText || presetName}: ${formatDisplay(start)} - ${formatDisplay(end)}`;
                } else {
                    labelEl.textContent = labelText || presetName || 'Tất cả';
                }
            }

            // Dispatch event
            const event = new CustomEvent('admin:daterange-change', {
                bubbles: true,
                detail: { start, end, preset: presetName }
            });
            container.dispatchEvent(event);

            // Close dropdown
            dropdown.classList.add('hidden');
        }

        dropdown.querySelectorAll('[data-preset]').forEach(btn => {
            btn.addEventListener('click', () => {
                const preset = btn.dataset.preset;
                const now = new Date();
                let start = '';
                let end = '';

                if (preset === 'today') {
                    start = formatDate(now);
                    end = formatDate(now);
                } else if (preset === 'yesterday') {
                    const y = new Date(now);
                    y.setDate(now.getDate() - 1);
                    start = formatDate(y);
                    end = formatDate(y);
                } else if (preset === '7days') {
                    const d7 = new Date(now);
                    d7.setDate(now.getDate() - 6);
                    start = formatDate(d7);
                    end = formatDate(now);
                } else if (preset === '30days') {
                    const d30 = new Date(now);
                    d30.setDate(now.getDate() - 29);
                    start = formatDate(d30);
                    end = formatDate(now);
                } else if (preset === 'this_month') {
                    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
                    start = formatDate(firstDay);
                    end = formatDate(now);
                } else if (preset === 'last_month') {
                    const firstDay = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                    const lastDay = new Date(now.getFullYear(), now.getMonth(), 0);
                    start = formatDate(firstDay);
                    end = formatDate(lastDay);
                } else if (preset === 'all') {
                    start = '';
                    end = '';
                } else if (preset === 'custom') {
                    if (customBox) customBox.classList.remove('hidden');
                    return;
                }

                if (customBox) customBox.classList.add('hidden');
                setRange(start, end, preset, btn.textContent.trim());
            });
        });

        btnApply?.addEventListener('click', () => {
            const start = startInput?.value || '';
            const end = endInput?.value || '';
            setRange(start, end, 'custom', 'Tùy chỉnh');
        });

        triggerBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdown.classList.toggle('hidden');
        });

        dropdown.addEventListener('click', (e) => e.stopPropagation());
        document.addEventListener('click', () => dropdown.classList.add('hidden'));
    });
}

/**
 * 4. Table Skeleton Loading Helper
 */
export function renderTableSkeleton(tbody, cols = 5, rows = 5) {
    if (!tbody) return;
    const skeletonRowsHtml = Array.from({ length: rows }).map(() => `
        <tr class="table-skeleton-row animate-pulse border-b border-outline-variant/30">
            ${Array.from({ length: cols }).map((_, i) => `
                <td class="py-3.5 px-3">
                    <div class="h-4 bg-surface-container rounded ${i === 0 ? 'w-16' : (i === 1 ? 'w-36' : (i === cols - 1 ? 'w-20 mx-auto' : 'w-24'))}"></div>
                </td>
            `).join('')}
        </tr>
    `).join('');
    tbody.innerHTML = skeletonRowsHtml;
}

/**
 * 5. Form Submit Loading & Double-Click Protection
 */
export function initFormSubmitLoading() {
    const forms = document.querySelectorAll('form:not([data-no-loading])');

    forms.forEach(form => {
        if (form.dataset.loadingInitialized) return;
        form.dataset.loadingInitialized = 'true';

        form.addEventListener('submit', function (e) {
            // If already validated and about to submit
            const submitBtn = form.querySelector('button[type="submit"]');
            if (!submitBtn || submitBtn.disabled) return;

            const originalHtml = submitBtn.innerHTML;
            const loadingText = submitBtn.dataset.loadingText || form.dataset.loadingText || 'Đang xử lý...';

            submitBtn.dataset.originalContent = originalHtml;
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-80', 'cursor-not-allowed');

            submitBtn.innerHTML = `
                <span class="inline-flex items-center gap-1.5">
                    <span class="material-symbols-outlined animate-spin text-[16px]">progress_activity</span>
                    <span>${loadingText}</span>
                </span>
            `;

            // Reset after 8s fallback in case of validation error without navigation
            setTimeout(() => {
                if (submitBtn && submitBtn.disabled) {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('opacity-80', 'cursor-not-allowed');
                    if (submitBtn.dataset.originalContent) {
                        submitBtn.innerHTML = submitBtn.dataset.originalContent;
                    }
                }
            }, 8000);
        });
    });
}

/**
 * Master Initialize all UI Enhancements
 */
export function initUiEnhancements() {
    initSearchDebounceAndClear();
    initSearchableSelects();
    initDateRangePickers();
    initFormSubmitLoading();
}

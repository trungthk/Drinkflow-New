<x-public.layout
    :title="__('public.order_check_title') . ' - DrinkFlow'"
    :description="__('public.order_check_desc')"
    :ogTitle="__('public.order_check_title')"
    :ogDescription="__('public.order_check_desc')"
    activeTab="about"
>
    <main class="flex-1 w-full max-w-5xl mx-auto px-4 py-12 sm:py-16">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8" data-order-check data-lookup-url="{{ $lookupUrl }}">
            <div class="mb-6 flex items-start gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-[#006948]">
                    <span class="material-symbols-outlined">receipt_long</span>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-900">{{ __('public.order_check_title') }}</h1>
                    <p class="mt-1 text-sm leading-relaxed text-slate-500">{{ __('public.order_check_desc') }}</p>
                </div>
            </div>

            <form data-order-check-form novalidate>
                <label for="order-check-identifier" class="mb-1.5 block text-xs font-semibold text-slate-700">{{ __('public.order_check_placeholder') }}</label>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <input id="order-check-identifier" name="identifier" type="text" minlength="2" maxlength="255" required autocomplete="off"
                        class="h-11 min-w-0 flex-1 rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm text-slate-900 outline-none transition focus:border-[#006948] focus:bg-white"
                        placeholder="{{ __('public.order_check_placeholder') }}">
                    <button type="submit" data-order-check-submit class="inline-flex h-11 items-center justify-center gap-1.5 rounded-xl bg-[#006948] px-5 text-sm font-bold text-white transition hover:bg-[#005137] disabled:cursor-not-allowed disabled:opacity-60">
                        <span data-order-check-icon class="material-symbols-outlined text-[18px]">search</span>
                        <span data-order-check-submit-text>{{ __('public.order_check_submit') }}</span>
                    </button>
                </div>
                <p data-order-check-error class="mt-2 hidden text-xs font-medium text-rose-600"></p>
            </form>

            <div data-order-check-content class="mt-6 hidden">
                <div class="mb-4 rounded-xl border border-emerald-100 bg-emerald-50 p-4">
                    <h2 data-campaign-name class="text-base font-bold text-slate-900"></h2>
                    <p data-campaign-restaurant class="mt-1 text-xs text-slate-600"></p>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-slate-600">
                        <span><strong>{{ __('public.order_check_campaign_start') }}:</strong> <span data-campaign-start></span></span>
                        <span><strong>{{ __('public.order_check_campaign_close') }}:</strong> <span data-campaign-close></span></span>
                    </div>
                </div>
                <div class="mb-4 flex border-b border-slate-200" role="tablist">
                    <button type="button" data-tab="overview" class="border-b-2 border-[#006948] px-3 py-2 text-xs font-bold text-[#006948]" role="tab" aria-selected="true">{{ __('public.order_check_overview') }}</button>
                    <button type="button" data-tab="items" class="border-b-2 border-transparent px-3 py-2 text-xs font-semibold text-slate-500" role="tab" aria-selected="false">{{ __('public.order_check_item_list') }}</button>
                </div>
                <div data-order-check-results class="space-y-3" data-panel="overview"></div>
                <div data-order-check-items class="hidden space-y-2" data-panel="items"></div>
            </div>
        </section>
        <section class="mt-10">
            <div class="mb-5 text-center">
                <h2 class="text-xl font-bold text-slate-900">{{ __('public.order_check_faq_title') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('public.order_check_faq_subtitle') }}</p>
            </div>
            <div class="grid gap-4 md:grid-cols-3">
                @foreach ([
                    ['icon' => 'help_outline', 'question' => __('public.order_check_faq_code_question'), 'answer' => __('public.order_check_faq_code_answer')],
                    ['icon' => 'currency_exchange', 'question' => __('public.order_check_faq_payment_question'), 'answer' => __('public.order_check_faq_payment_answer')],
                    ['icon' => 'local_shipping', 'question' => __('public.order_check_faq_delivery_question'), 'answer' => __('public.order_check_faq_delivery_answer')],
                ] as $faq)
                    <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <h3 class="flex items-start gap-2 text-sm font-bold text-[#006948]">
                            <span class="material-symbols-outlined text-[18px]">{{ $faq['icon'] }}</span>
                            <span>{{ $faq['question'] }}</span>
                        </h3>
                        <p class="mt-3 text-xs leading-relaxed text-slate-600">{{ $faq['answer'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>
    </main>

    <x-slot:scripts>
        <script>
            (() => {
                const root = document.querySelector('[data-order-check]');
                if (!root) return;
                const form = root.querySelector('[data-order-check-form]');
                const input = root.querySelector('#order-check-identifier');
                const submit = root.querySelector('[data-order-check-submit]');
                const submitText = root.querySelector('[data-order-check-submit-text]');
                const icon = root.querySelector('[data-order-check-icon]');
                const error = root.querySelector('[data-order-check-error]');
                const content = root.querySelector('[data-order-check-content]');
                const results = root.querySelector('[data-order-check-results]');
                const itemResults = root.querySelector('[data-order-check-items]');
                const lookupUrl = root.dataset.lookupUrl;
                const invalidMessage = @json(__('public.order_check_invalid'));
                const notFoundMessage = @json(__('public.order_check_not_found'));
                const loadingMessage = @json(__('public.order_check_loading'));
                const submitMessage = @json(__('public.order_check_submit'));
                const optionsLabel = @json(__('public.order_check_options'));
                const toppingsLabel = @json(__('public.order_check_toppings'));
                const noteLabel = @json(__('public.order_check_note'));
                const proxyOrdersLabel = @json(__('public.order_check_proxy_orders'));
                const orderedByLabel = @json(__('public.order_check_ordered_by'));

                const escapeHtml = value => String(value ?? '').replace(/[&<>'"]/g, char => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
                }[char]));
                const money = value => new Intl.NumberFormat('vi-VN').format(Number(value || 0)) + 'đ';
                const validIdentifier = value => value.length >= 2 && value.length <= 255 && /^[\p{L}\p{N}@+().,_\-\s]+$/u.test(value);
                const personText = person => [person?.name, person?.email].filter(Boolean).join(' - ');
                const personTooltip = person => personText(person) ? ` title="${escapeHtml(personText(person))}"` : '';
                const renderItem = item => `
                    <div class="rounded-lg border border-slate-200 bg-white p-3">
                        <div class="flex items-start justify-between gap-3 text-xs text-slate-700">
                            <span class="font-semibold">${escapeHtml(item.name)} × ${Number(item.quantity || 0)}</span>
                            <span class="shrink-0 font-semibold">${money(item.line_subtotal)}</span>
                        </div>
                        ${item.options?.length ? `<div class="mt-1 text-[11px] text-slate-500"><span class="font-semibold">${escapeHtml(optionsLabel)}:</span> ${item.options.map(escapeHtml).join(', ')}</div>` : ''}
                        ${item.toppings?.length ? `<div class="mt-1 text-[11px] text-slate-500"><span class="font-semibold">${escapeHtml(toppingsLabel)}:</span> ${item.toppings.map(escapeHtml).join(', ')}</div>` : ''}
                        ${item.note ? `<div class="mt-1 text-[11px] italic text-slate-500"><span class="font-semibold not-italic">${escapeHtml(noteLabel)}:</span> ${escapeHtml(item.note)}</div>` : ''}
                    </div>
                `;
                const renderProxyOrder = proxyOrder => `
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-xs font-semibold text-slate-800"${personTooltip(proxyOrder.recipient)}>${escapeHtml(personText(proxyOrder.recipient) || '')}</p>
                            </div>
                            <span class="shrink-0 text-[11px] font-semibold text-slate-600">${escapeHtml(proxyOrder.code || '')}</span>
                        </div>
                        <div class="mt-2 space-y-2">${(proxyOrder.items || []).map(renderItem).join('')}</div>
                        ${proxyOrder.note ? `<div class="mt-2 text-[11px] italic text-slate-600"><span class="font-semibold not-italic">${escapeHtml(noteLabel)}:</span> ${escapeHtml(proxyOrder.note)}</div>` : ''}
                    </div>
                `;
                const renderOrder = order => `
                    <article class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h2 class="truncate text-sm font-bold text-slate-900">${escapeHtml(order.code)}</h2>
                                <p class="mt-0.5 truncate text-xs text-slate-500"${personTooltip({ name: order.member_name, email: order.member_email })}>${escapeHtml(personText({ name: order.member_name, email: order.member_email }) || '')}</p>
                            </div>
                            <span class="shrink-0 rounded-full bg-emerald-100 px-2 py-1 text-[10px] font-semibold text-emerald-800">${escapeHtml(order.status_label || order.status)}</span>
                        </div>
                        ${order.ordered_by?.name || order.ordered_by?.email ? `<div class="mt-3 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs text-blue-900"><span class="font-semibold">${escapeHtml(orderedByLabel)}:</span> <span${personTooltip(order.ordered_by)}>${escapeHtml(personText(order.ordered_by))}</span></div>` : ''}
                        <div class="mt-3 space-y-2 border-t border-slate-200 pt-3">${(order.items || []).map(renderItem).join('')}</div>
                        ${order.note ? `<div class="mt-3 text-xs italic text-slate-600"><span class="font-semibold not-italic">${escapeHtml(noteLabel)}:</span> ${escapeHtml(order.note)}</div>` : ''}
                        ${order.proxy_orders?.length ? `<div class="mt-4 border-t border-slate-200 pt-3"><h3 class="mb-2 text-xs font-bold text-slate-700">${escapeHtml(proxyOrdersLabel)}</h3><div class="space-y-2">${order.proxy_orders.map(renderProxyOrder).join('')}</div></div>` : ''}
                        <div class="mt-3 flex justify-end border-t border-slate-200 pt-2 text-sm font-bold text-[#006948]">${money(order.final_amount)}</div>
                    </article>
                `;
                const formatDate = value => value ? new Intl.DateTimeFormat(document.documentElement.lang || 'vi-VN', {
                    dateStyle: 'medium', timeStyle: 'short'
                }).format(new Date(value)) : '-';
                const renderItemTotal = item => `
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <div class="flex items-start justify-between gap-3 text-sm">
                            <span class="font-semibold text-slate-800">${escapeHtml(item.name)}</span>
                            <span class="shrink-0 font-bold text-[#006948]">× ${Number(item.quantity || 0)}</span>
                        </div>
                        ${item.options?.length ? `<div class="mt-1 text-[11px] text-slate-500">${escapeHtml(optionsLabel)}: ${item.options.map(escapeHtml).join(', ')}</div>` : ''}
                        ${item.toppings?.length ? `<div class="mt-1 text-[11px] text-slate-500">${escapeHtml(toppingsLabel)}: ${item.toppings.map(escapeHtml).join(', ')}</div>` : ''}
                    </div>
                `;
                root.querySelectorAll('[data-tab]').forEach(tab => tab.addEventListener('click', () => {
                    const selected = tab.dataset.tab;
                    root.querySelectorAll('[data-tab]').forEach(button => {
                        const active = button.dataset.tab === selected;
                        button.classList.toggle('border-[#006948]', active);
                        button.classList.toggle('text-[#006948]', active);
                        button.classList.toggle('font-bold', active);
                        button.classList.toggle('border-transparent', !active);
                        button.classList.toggle('text-slate-500', !active);
                        button.classList.toggle('font-semibold', !active);
                        button.setAttribute('aria-selected', active ? 'true' : 'false');
                    });
                    root.querySelectorAll('[data-panel]').forEach(panel => panel.classList.toggle('hidden', panel.dataset.panel !== selected));
                }));

                form.addEventListener('submit', async event => {
                    event.preventDefault();
                    const identifier = input.value.trim();
                    error.classList.add('hidden');
                    content.classList.add('hidden');
                    results.innerHTML = '';
                    itemResults.innerHTML = '';
                    if (!validIdentifier(identifier)) {
                        error.textContent = invalidMessage;
                        error.classList.remove('hidden');
                        return;
                    }
                    submit.disabled = true;
                    icon.textContent = 'progress_activity';
                    icon.classList.add('animate-spin');
                    submitText.textContent = loadingMessage;
                    try {
                        const response = await fetch(lookupUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ identifier })
                        });
                        const payload = await response.json();
                        if (!response.ok) throw new Error(payload.message || notFoundMessage);
                        root.querySelector('[data-campaign-name]').textContent = payload.campaign.name || '';
                        root.querySelector('[data-campaign-restaurant]').textContent = payload.campaign.restaurant || '';
                        root.querySelector('[data-campaign-start]').textContent = formatDate(payload.campaign.started_at);
                        root.querySelector('[data-campaign-close]').textContent = formatDate(payload.campaign.closed_at);
                        results.innerHTML = payload.orders.map(renderOrder).join('');
                        itemResults.innerHTML = (payload.items || []).length
                            ? payload.items.map(renderItemTotal).join('')
                            : `<p class="text-sm text-slate-500">${escapeHtml(notFoundMessage)}</p>`;
                        content.classList.remove('hidden');
                    } catch (requestError) {
                        error.textContent = requestError.message || notFoundMessage;
                        error.classList.remove('hidden');
                    } finally {
                        submit.disabled = false;
                        icon.textContent = 'search';
                        icon.classList.remove('animate-spin');
                        submitText.textContent = submitMessage;
                    }
                });
            })();
        </script>
    </x-slot:scripts>
</x-public.layout>

<x-public.layout
    :title="__('public.order_check_title') . ' - DrinkFlow'"
    :description="__('public.order_check_desc')"
    :ogTitle="__('public.order_check_title')"
    :ogDescription="__('public.order_check_desc')"
    :ogImage="asset('images/og-order-check.jpg')"
    activeTab="about"
    robots="noindex, nofollow"
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
                <div class="overflow-hidden rounded-xl border border-slate-200">
                    <div class="flex items-start justify-between gap-3 bg-gradient-to-r from-emerald-50 to-white px-4 py-3">
                        <div class="min-w-0">
                            <h2 data-campaign-name class="truncate text-base font-bold text-slate-900"></h2>
                            <p class="mt-0.5 flex items-center gap-1 text-xs text-slate-500">
                                <span class="material-symbols-outlined text-[15px]">storefront</span>
                                <span data-campaign-restaurant class="truncate"></span>
                            </p>
                        </div>
                        <span data-campaign-status class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold" title="{{ __('public.order_check_campaign_status') }}"></span>
                    </div>
                    <dl class="grid grid-cols-2 divide-x divide-slate-100 border-t border-slate-100 text-xs">
                        <div class="px-4 py-2.5">
                            <dt class="text-[11px] font-medium uppercase tracking-wide text-slate-400">{{ __('public.order_check_campaign_start') }}</dt>
                            <dd data-campaign-start class="mt-0.5 font-semibold text-slate-700"></dd>
                        </div>
                        <div class="px-4 py-2.5">
                            <dt class="text-[11px] font-medium uppercase tracking-wide text-slate-400">{{ __('public.order_check_campaign_close') }}</dt>
                            <dd data-campaign-close class="mt-0.5 font-semibold text-slate-700"></dd>
                        </div>
                    </dl>
                </div>

                <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
                    <div class="inline-flex rounded-lg bg-slate-100 p-1" role="tablist">
                        <button type="button" data-tab="overview" class="inline-flex items-center gap-1.5 rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-[#006948] shadow-sm" role="tab" aria-selected="true">
                            {{ __('public.order_check_overview') }}
                            <span data-count-orders class="rounded-full bg-emerald-100 px-1.5 text-[10px] font-bold text-emerald-800"></span>
                        </button>
                        <button type="button" data-tab="items" class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-semibold text-slate-500" role="tab" aria-selected="false">
                            {{ __('public.order_check_item_list') }}
                            <span data-count-items class="rounded-full bg-slate-200 px-1.5 text-[10px] font-bold text-slate-600"></span>
                        </button>
                    </div>
                    <span data-orders-total class="text-sm font-bold text-[#006948]"></span>
                </div>
                <div data-order-check-results class="mt-3 space-y-3" data-panel="overview"></div>
                <div data-order-check-items class="mt-3 hidden" data-panel="items"></div>
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
                const noteLabel = @json(__('public.order_check_note'));
                const proxyOrdersLabel = @json(__('public.order_check_proxy_orders'));
                const orderedByLabel = @json(__('public.order_check_ordered_by'));
                const totalTemplate = @json(__('public.order_check_total', ['amount' => ':amount']));

                const escapeHtml = value => String(value ?? '').replace(/[&<>'"]/g, char => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
                }[char]));
                const money = value => new Intl.NumberFormat('vi-VN').format(Number(value || 0)) + 'đ';
                const validIdentifier = value => value.length >= 2 && value.length <= 255 && /^[\p{L}\p{N}@+().,_\-\s]+$/u.test(value);
                const personText = person => [person?.name, person?.email].filter(Boolean).join(' - ');
                const personTooltip = person => personText(person) ? ` title="${escapeHtml(personText(person))}"` : '';
                const statusTones = {
                    submitted: 'bg-sky-100 text-sky-800',
                    confirmed: 'bg-indigo-100 text-indigo-800',
                    ordering: 'bg-amber-100 text-amber-800',
                    ordered: 'bg-violet-100 text-violet-800',
                    delivering: 'bg-orange-100 text-orange-800',
                    completed: 'bg-emerald-100 text-emerald-800',
                    cancelled: 'bg-rose-100 text-rose-800',
                    scheduled: 'bg-sky-100 text-sky-800',
                    active: 'bg-emerald-600 text-white',
                    closing: 'bg-amber-100 text-amber-800',
                    closed: 'bg-slate-200 text-slate-700',
                    archived: 'bg-slate-100 text-slate-500',
                };
                const tone = status => statusTones[status] || 'bg-slate-100 text-slate-700';
                const optionChips = item => [...(item.options || []), ...(item.toppings || []).map(topping => '+ ' + topping)]
                    .map(label => `<span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-600">${escapeHtml(label)}</span>`)
                    .join('');
                const noteLine = (note, extraClass = '') => note
                    ? `<p class="mt-1 flex items-start gap-1 text-[11px] italic text-slate-500 ${extraClass}"><span class="material-symbols-outlined not-italic text-[13px]" title="${escapeHtml(noteLabel)}">sticky_note_2</span><span>${escapeHtml(note)}</span></p>`
                    : '';
                const renderItem = item => {
                    const chips = optionChips(item);
                    return `
                    <li class="flex items-start justify-between gap-3 py-2">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-800">${escapeHtml(item.name)} <span class="font-semibold text-slate-400">× ${Number(item.quantity || 0)}</span></p>
                            ${chips ? `<div class="mt-1 flex flex-wrap gap-1">${chips}</div>` : ''}
                            ${noteLine(item.note)}
                        </div>
                        <span class="shrink-0 text-sm font-semibold tabular-nums text-slate-700">${money(item.line_subtotal)}</span>
                    </li>`;
                };
                const renderProxyOrder = proxyOrder => `
                    <div class="border-l-2 border-amber-300 pl-3">
                        <div class="flex items-center justify-between gap-3 text-xs">
                            <span class="flex min-w-0 items-center gap-1 font-semibold text-slate-700"${personTooltip(proxyOrder.recipient)}>
                                <span class="material-symbols-outlined text-[15px] text-amber-600">person</span>
                                <span class="truncate">${escapeHtml(personText(proxyOrder.recipient) || '')}</span>
                            </span>
                            <span class="shrink-0 font-mono text-[11px] text-slate-400">${escapeHtml(proxyOrder.code || '')}</span>
                        </div>
                        <ul class="divide-y divide-slate-100">${(proxyOrder.items || []).map(renderItem).join('')}</ul>
                        ${noteLine(proxyOrder.note)}
                    </div>
                `;
                const renderOrder = order => {
                    const member = { name: order.member_name, email: order.member_email };
                    return `
                    <article class="rounded-xl border border-slate-200 bg-white shadow-sm">
                        <header class="flex items-start justify-between gap-3 border-b border-slate-100 px-4 py-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-mono text-sm font-bold text-slate-900">${escapeHtml(order.code)}</h3>
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold ${tone(order.status)}">${escapeHtml(order.status_label || order.status)}</span>
                                </div>
                                <p class="mt-0.5 truncate text-xs text-slate-500"${personTooltip(member)}>${escapeHtml(personText(member))}</p>
                                ${order.ordered_by?.name || order.ordered_by?.email ? `<p class="mt-1 text-[11px] text-blue-700"><span class="font-semibold">${escapeHtml(orderedByLabel)}:</span> <span${personTooltip(order.ordered_by)}>${escapeHtml(personText(order.ordered_by))}</span></p>` : ''}
                            </div>
                            <span class="shrink-0 text-base font-bold tabular-nums text-[#006948]">${money(order.final_amount)}</span>
                        </header>
                        <div class="px-4 py-1">
                            <ul class="divide-y divide-slate-100">${(order.items || []).map(renderItem).join('')}</ul>
                            ${noteLine(order.note, 'pb-2')}
                        </div>
                        ${order.proxy_orders?.length ? `<div class="space-y-3 rounded-b-xl border-t border-slate-100 bg-amber-50/40 px-4 py-3"><h4 class="text-[11px] font-bold uppercase tracking-wide text-amber-700">${escapeHtml(proxyOrdersLabel)}</h4>${order.proxy_orders.map(renderProxyOrder).join('')}</div>` : ''}
                    </article>`;
                };
                const formatDate = value => value ? new Intl.DateTimeFormat(document.documentElement.lang || 'vi-VN', {
                    dateStyle: 'medium', timeStyle: 'short'
                }).format(new Date(value)) : '-';
                const renderItemTotal = item => {
                    const chips = optionChips(item);
                    return `
                    <li class="flex items-center justify-between gap-3 px-4 py-2.5">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-800">${escapeHtml(item.name)}</p>
                            ${chips ? `<div class="mt-1 flex flex-wrap gap-1">${chips}</div>` : ''}
                        </div>
                        <span class="shrink-0 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-[#006948]">× ${Number(item.quantity || 0)}</span>
                    </li>`;
                };
                root.querySelectorAll('[data-tab]').forEach(tab => tab.addEventListener('click', () => {
                    const selected = tab.dataset.tab;
                    root.querySelectorAll('[data-tab]').forEach(button => {
                        const active = button.dataset.tab === selected;
                        button.classList.toggle('bg-white', active);
                        button.classList.toggle('shadow-sm', active);
                        button.classList.toggle('text-[#006948]', active);
                        button.classList.toggle('text-slate-500', !active);
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
                        const campaignStatus = root.querySelector('[data-campaign-status]');
                        const items = payload.items || [];
                        root.querySelector('[data-campaign-name]').textContent = payload.campaign.name || '';
                        root.querySelector('[data-campaign-restaurant]').textContent = payload.campaign.restaurant || '-';
                        campaignStatus.textContent = payload.campaign.status_label || payload.campaign.status || '-';
                        campaignStatus.className = `shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold ${tone(payload.campaign.status)}`;
                        root.querySelector('[data-campaign-start]').textContent = formatDate(payload.campaign.started_at);
                        root.querySelector('[data-campaign-close]').textContent = formatDate(payload.campaign.closed_at);
                        root.querySelector('[data-count-orders]').textContent = payload.orders.length;
                        root.querySelector('[data-count-items]').textContent = items.reduce((sum, item) => sum + Number(item.quantity || 0), 0);
                        root.querySelector('[data-orders-total]').textContent = totalTemplate.replace(':amount', money(payload.orders.reduce((sum, order) => sum + Number(order.final_amount || 0), 0)));
                        results.innerHTML = payload.orders.map(renderOrder).join('');
                        itemResults.innerHTML = items.length
                            ? `<ul class="divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white shadow-sm">${items.map(renderItemTotal).join('')}</ul>`
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

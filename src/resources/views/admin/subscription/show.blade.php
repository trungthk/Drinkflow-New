@php
    $money = static fn (int $amount): string => \App\Support\Helpers\FormatHelper::formatCurrency($amount);
    $service = app(\App\Services\Subscription\SubscriptionService::class);
    $card = 'rounded-xl border border-outline-variant bg-surface-container-lowest p-5';
@endphp
<x-admin.layout :title="__('platform.subscriptions.title')" active="subscription" :breadcrumb="__('platform.subscriptions.title')">
    <div class="space-y-6">
        <section class="pb-4 border-b border-outline-variant/40">
            <h1 class="text-2xl font-bold tracking-tight text-on-surface">{{ __('platform.subscriptions.title') }}</h1>
            <p class="mt-1 max-w-3xl text-xs text-outline">{{ __('platform.subscriptions.subtitle') }}</p>
        </section>

        <x-admin.billing-warning />

        @if ($pendingUpgrade)
            {{-- Upgrade waiting for payment: the current package stays until the invoice is paid and confirmed. --}}
            <section class="rounded-xl border border-sky-200 bg-sky-50 px-5 py-4 text-sm text-sky-950 dark:border-sky-800 dark:bg-sky-950/30 dark:text-sky-100" data-pending-upgrade="{{ $pendingUpgrade->id }}">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex items-start gap-3 min-w-0">
                        <span class="material-symbols-outlined text-[22px] text-sky-700">hourglass_top</span>
                        <div class="min-w-0">
                            <p class="font-semibold">{{ __('platform.subscriptions.upgrade_pending_title', ['package' => $pendingUpgrade->package?->name]) }}</p>
                            <p class="mt-0.5 text-xs">{{ __('platform.subscriptions.upgrade_pending_hint', [
                                'number' => $pendingUpgrade->number,
                                'amount' => $money($pendingUpgrade->remaining()),
                                'date' => $pendingUpgrade->due_at?->toAppDate(),
                            ]) }}</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($onlinePayment && $pendingUpgrade->remaining() > 0)
                            <a href="{{ route('admin.billing.pay', $pendingUpgrade) }}" class="h-9 px-4 inline-flex items-center gap-1.5 rounded-lg bg-primary text-on-primary text-xs font-semibold">
                                <span class="material-symbols-outlined text-[16px]">payments</span>{{ __('platform.billing.pay_online') }}
                            </a>
                        @endif
                        <a href="{{ route('admin.billing.download', $pendingUpgrade) }}" class="h-9 px-3 inline-flex items-center gap-1.5 rounded-lg border border-sky-300 bg-white text-xs font-semibold text-sky-900 hover:bg-sky-100 dark:bg-transparent dark:text-sky-100">
                            <span class="material-symbols-outlined text-[16px]">picture_as_pdf</span>{{ __('platform.billing.download_pdf') }}
                        </a>
                        @if ($pendingUpgrade->paid_amount === 0)
                            <form method="POST" action="{{ route('admin.subscription.upgrade.cancel') }}" data-loading-form="true"
                                data-confirm-message="{{ __('platform.subscriptions.upgrade_cancel_confirm', ['number' => $pendingUpgrade->number]) }}"
                                data-confirm-button="{{ __('platform.subscriptions.upgrade_cancel') }}" data-confirm-tone="danger">
                                @csrf
                                <button type="submit" class="h-9 px-3 rounded-lg border border-error/40 text-error text-xs font-semibold hover:bg-error-container/40 cursor-pointer">{{ __('platform.subscriptions.upgrade_cancel') }}</button>
                            </form>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        @if (session('status'))
            <div class="rounded-lg border border-primary/30 bg-primary/10 px-4 py-3 text-sm text-on-primary-fixed-variant" role="status">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-lg border border-error/30 bg-error-container px-4 py-3 text-sm text-on-error-container" role="alert">{{ $errors->first() }}</div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <section class="{{ $card }} lg:col-span-2" data-subscription-status="{{ $subscription?->status->value ?? 'none' }}">
                @if ($subscription)
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-outline">{{ __('platform.subscriptions.current_package') }}</p>
                            <h2 class="text-xl font-bold text-on-surface">{{ $subscription->package?->name }}</h2>
                            <p class="text-sm font-semibold text-primary">{{ __('platform.packages.per_month', ['price' => $money($subscription->price_snapshot)]) }}</p>
                        </div>
                        <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $subscription->cancel_at_period_end ? 'bg-amber-50 text-amber-800' : 'bg-emerald-50 text-emerald-800' }}">
                            {{ $subscription->cancel_at_period_end ? __('platform.subscriptions.ending') : __('platform.subscriptions.status.'.$subscription->status->value) }}
                        </span>
                    </div>
                    <dl class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
                        <div><dt class="text-xs text-outline">{{ __('platform.subscriptions.room_limit') }}</dt><dd class="font-semibold">{{ $subscription->room_limit_snapshot }}</dd></div>
                        <div><dt class="text-xs text-outline">{{ __('platform.subscriptions.starts_at') }}</dt><dd class="font-semibold">{{ $subscription->starts_at->toAppDate() }}</dd></div>
                        <div><dt class="text-xs text-outline">{{ $subscription->cancel_at_period_end ? __('platform.subscriptions.ends_at') : __('platform.subscriptions.renews_at') }}</dt><dd class="font-semibold">{{ $subscription->expires_at?->toAppDate() ?? '—' }}</dd></div>
                        <div><dt class="text-xs text-outline">{{ __('platform.subscriptions.credit') }}</dt><dd class="font-semibold">{{ $money($subscription->proration_credit) }}</dd></div>
                    </dl>
                    @if ($subscription->scheduledPackage)
                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900">
                            <span>
                                {{ __('platform.subscriptions.scheduled_notice', ['package' => $subscription->scheduledPackage->name, 'date' => $subscription->expires_at?->toAppDate()]) }}
                                <small class="block text-xs">{{ __('platform.subscriptions.downgrade_no_refund', ['date' => $subscription->expires_at?->toAppDate()]) }}</small>
                            </span>
                            <form method="POST" action="{{ route('admin.subscription.scheduled.cancel') }}" data-loading-form="true"
                                data-confirm-message="{{ __('platform.subscriptions.keep_current_confirm', ['package' => $subscription->package?->name]) }}"
                                data-confirm-button="{{ __('platform.subscriptions.keep_current') }}">
                                @csrf
                                <button type="submit" class="text-xs font-semibold underline cursor-pointer">{{ __('platform.subscriptions.keep_current') }}</button>
                            </form>
                        </div>
                    @endif
                    {{-- Auto-renew: the scheduler (subscriptions:process) renews the period at expires_at unless it is switched off. --}}
                    @php $autoRenew = ! $subscription->cancel_at_period_end; @endphp
                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-outline-variant px-4 py-3" data-auto-renew="{{ $autoRenew ? 'on' : 'off' }}">
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-[22px] {{ $autoRenew ? 'text-primary' : 'text-outline' }}">autorenew</span>
                            <div>
                                <p class="text-sm font-semibold text-on-surface">{{ __('platform.subscriptions.auto_renew') }}
                                    <span class="ml-1 rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $autoRenew ? 'bg-emerald-50 text-emerald-800' : 'bg-surface-container text-on-surface-variant' }}">{{ $autoRenew ? __('platform.subscriptions.auto_renew_on') : __('platform.subscriptions.auto_renew_off') }}</span>
                                </p>
                                <p class="text-xs text-outline mt-0.5">
                                    {{ $autoRenew
                                        ? __('platform.subscriptions.auto_renew_on_hint', ['date' => $subscription->expires_at?->toAppDate(), 'price' => $money($subscription->scheduledPackage?->monthly_price ?? $subscription->price_snapshot)])
                                        : __('platform.subscriptions.auto_renew_off_hint', ['date' => $subscription->expires_at?->toAppDate()]) }}
                                </p>
                            </div>
                        </div>
                        @if ($autoRenew)
                            <form method="POST" action="{{ route('admin.subscription.cancel') }}" data-loading-form="true"
                                data-confirm-title="{{ __('platform.subscriptions.auto_renew_off_title') }}"
                                data-confirm-message="{{ __('platform.subscriptions.cancel_confirm', ['date' => $subscription->expires_at?->toAppDate()]) }}"
                                data-confirm-button="{{ __('platform.subscriptions.cancel') }}" data-confirm-tone="danger">
                                @csrf
                                <button type="submit" class="h-9 px-4 rounded-lg border border-error/40 text-error text-xs font-semibold hover:bg-error-container/40 cursor-pointer">{{ __('platform.subscriptions.cancel') }}</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.subscription.resume') }}" data-loading-form="true"
                                data-confirm-title="{{ __('platform.subscriptions.auto_renew_on_title') }}"
                                data-confirm-message="{{ __('platform.subscriptions.resume_confirm', ['date' => $subscription->expires_at?->toAppDate()]) }}"
                                data-confirm-button="{{ __('platform.subscriptions.resume') }}">
                                @csrf
                                <button type="submit" class="h-9 px-4 rounded-lg bg-primary text-on-primary text-xs font-semibold cursor-pointer">{{ __('platform.subscriptions.resume') }}</button>
                            </form>
                        @endif
                    </div>
                @else
                    <x-admin.empty-state icon="workspace_premium" :title="__('platform.subscriptions.none_title')" :description="__('platform.subscriptions.none_description')" />
                @endif
            </section>
            <x-admin.room-quota :usage="$usage" />
        </div>

        @if ($subscription)
            <section class="{{ $card }}">
                <h2 class="text-sm font-bold text-on-surface">{{ __('platform.subscriptions.change_title') }}</h2>
                <p class="text-xs text-outline mb-4">{{ __('platform.subscriptions.change_hint') }}</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach ($packages as $package)
                        @php
                            $isCurrent = $package->id === $subscription->package_id && $package->monthly_price === $subscription->price_snapshot && $package->room_limit === $subscription->room_limit_snapshot;
                            $upgrade = $service->isUpgrade($subscription, $package);
                            $tooSmall = $package->room_limit < $usage['used'];
                        @endphp
                        <div class="rounded-xl border {{ $isCurrent ? 'border-primary ring-1 ring-primary' : 'border-outline-variant' }} p-4 flex flex-col gap-2">
                            <strong class="text-sm text-on-surface">{{ $package->name }}</strong>
                            <span class="text-xs font-semibold text-primary">{{ __('platform.packages.per_month', ['price' => $money($package->monthly_price)]) }}</span>
                            <span class="text-xs text-outline">{{ __('platform.packages.rooms_limit', ['count' => $package->room_limit]) }}</span>
                            @if (! $isCurrent && ! $tooSmall)
                                {{-- What happens to the money: an upgrade is invoiced first, a downgrade refunds nothing. --}}
                                <p class="text-[11px] leading-snug {{ $upgrade ? 'text-sky-800 dark:text-sky-300' : 'text-amber-800 dark:text-amber-300' }}" data-package-note="{{ $upgrade ? 'upgrade' : 'downgrade' }}">
                                    <span class="material-symbols-outlined text-[13px] align-[-2px]">{{ $upgrade ? 'receipt_long' : 'info' }}</span>
                                    {{ $upgrade ? __('platform.subscriptions.upgrade_note') : __('platform.subscriptions.downgrade_no_refund', ['date' => $subscription->expires_at?->toAppDate()]) }}
                                </p>
                            @endif
                            @if ($isCurrent)
                                <span class="mt-auto text-xs font-semibold text-primary">{{ __('platform.subscriptions.current') }}</span>
                            @elseif ($tooSmall)
                                <span class="mt-auto text-xs text-error">{{ __('platform.subscriptions.too_many_rooms', ['used' => $usage['used'], 'limit' => $package->room_limit]) }}</span>
                            @elseif ($pendingUpgrade && $pendingUpgrade->package_id === $package->id)
                                <span class="mt-auto text-xs font-semibold text-sky-800 dark:text-sky-300">{{ __('platform.subscriptions.upgrade_awaiting_payment') }}</span>
                            @else
                                <form method="POST" action="{{ route('admin.subscription.change') }}" class="mt-auto" data-loading-form="true"
                                    data-confirm-title="{{ $upgrade ? __('platform.subscriptions.upgrade') : __('platform.subscriptions.downgrade') }}"
                                    data-confirm-message="{{ $upgrade ? __('platform.subscriptions.upgrade_confirm', ['package' => $package->name]) : __('platform.subscriptions.downgrade_confirm', ['package' => $package->name, 'date' => $subscription->expires_at?->toAppDate()]) }}"
                                    data-confirm-button="{{ $upgrade ? __('platform.subscriptions.upgrade') : __('platform.subscriptions.downgrade') }}">
                                    @csrf
                                    <input type="hidden" name="package_id" value="{{ $package->id }}">
                                    <button type="submit" class="w-full h-9 rounded-lg text-xs font-semibold cursor-pointer {{ $upgrade ? 'bg-primary text-on-primary' : 'border border-outline-variant hover:bg-surface-container' }}">
                                        {{ $upgrade ? __('platform.subscriptions.upgrade') : __('platform.subscriptions.downgrade') }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="{{ $card }} !p-0 overflow-hidden">
            <div class="px-5 py-4 border-b border-outline-variant">
                <h2 class="text-sm font-bold text-on-surface">{{ __('platform.subscriptions.history') }}</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-surface-container-low text-[11px] uppercase tracking-wider text-outline">
                        <tr>
                            <th class="px-5 py-3 text-left">{{ __('platform.packages.package') }}</th>
                            <th class="px-5 py-3 text-right">{{ __('platform.subscriptions.price') }}</th>
                            <th class="px-5 py-3 text-right">{{ __('platform.subscriptions.room_limit') }}</th>
                            <th class="px-5 py-3 text-left">{{ __('validation.attributes.status') }}</th>
                            <th class="px-5 py-3 text-left">{{ __('platform.subscriptions.period') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/60">
                        @forelse ($history as $row)
                            <tr>
                                <td class="px-5 py-3">{{ $row->package?->name }}</td>
                                <td class="px-5 py-3 text-right whitespace-nowrap">{{ $money($row->price_snapshot) }}</td>
                                <td class="px-5 py-3 text-right">{{ $row->room_limit_snapshot }}</td>
                                <td class="px-5 py-3">{{ __('platform.subscriptions.status.'.$row->status->value) }}</td>
                                <td class="px-5 py-3 whitespace-nowrap">{{ $row->starts_at->toAppDate() }} → {{ ($row->ended_at ?? $row->expires_at)?->toAppDate() ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="p-4">
                                <x-admin.empty-state icon="history" :title="__('platform.subscriptions.history_empty')" />
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-admin.layout>

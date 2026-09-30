@php
    $money = static fn (int $amount): string => \App\Support\Helpers\FormatHelper::formatCurrency($amount);
    $service = app(\App\Services\Subscription\SubscriptionService::class);
    $card = 'rounded-xl border border-outline-variant bg-surface-container-lowest p-5';
@endphp
<x-admin.layout :title="__('platform.subscriptions.title')" active="subscription" :breadcrumb="__('platform.subscriptions.title')">
    <div class="max-w-7xl mx-auto space-y-6">
        <section>
            <h1 class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-on-surface">{{ __('platform.subscriptions.title') }}</h1>
            <p class="mt-1.5 max-w-3xl text-sm text-on-surface-variant">{{ __('platform.subscriptions.subtitle') }}</p>
        </section>

        <x-admin.billing-warning />

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
                            <span>{{ __('platform.subscriptions.scheduled_notice', ['package' => $subscription->scheduledPackage->name, 'date' => $subscription->expires_at?->toAppDate()]) }}</span>
                            <form method="POST" action="{{ route('admin.subscription.scheduled.cancel') }}" data-loading-form="true">
                                @csrf
                                <button type="submit" class="text-xs font-semibold underline cursor-pointer">{{ __('platform.subscriptions.keep_current') }}</button>
                            </form>
                        </div>
                    @endif
                    <div class="mt-4 flex justify-end">
                        @if ($subscription->cancel_at_period_end)
                            <form method="POST" action="{{ route('admin.subscription.resume') }}" data-loading-form="true">
                                @csrf
                                <button type="submit" class="h-9 px-4 rounded-lg bg-primary text-on-primary text-xs font-semibold cursor-pointer">{{ __('platform.subscriptions.resume') }}</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.subscription.cancel') }}" data-loading-form="true"
                                onsubmit="return confirm(@js(__('platform.subscriptions.cancel_confirm', ['date' => $subscription->expires_at?->toAppDate()])))">
                                @csrf
                                <button type="submit" class="h-9 px-4 rounded-lg border border-error/40 text-error text-xs font-semibold hover:bg-error-container/40 cursor-pointer">{{ __('platform.subscriptions.cancel') }}</button>
                            </form>
                        @endif
                    </div>
                @else
                    <h2 class="text-lg font-bold text-on-surface">{{ __('platform.subscriptions.none_title') }}</h2>
                    <p class="mt-1 text-sm text-on-surface-variant">{{ __('platform.subscriptions.none_description') }}</p>
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
                            @if ($isCurrent)
                                <span class="mt-auto text-xs font-semibold text-primary">{{ __('platform.subscriptions.current') }}</span>
                            @elseif ($tooSmall)
                                <span class="mt-auto text-xs text-error">{{ __('platform.subscriptions.too_many_rooms', ['used' => $usage['used'], 'limit' => $package->room_limit]) }}</span>
                            @else
                                <form method="POST" action="{{ route('admin.subscription.change') }}" class="mt-auto" data-loading-form="true"
                                    onsubmit="return confirm(@js($upgrade ? __('platform.subscriptions.upgrade_confirm', ['package' => $package->name]) : __('platform.subscriptions.downgrade_confirm', ['package' => $package->name, 'date' => $subscription->expires_at?->toAppDate()])))">
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
                            <tr><td colspan="5" class="px-5 py-8 text-center text-outline">{{ __('platform.subscriptions.history_empty') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-admin.layout>

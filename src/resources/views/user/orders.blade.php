<x-room.layout :room="$room" :room-user="$roomUser" :active-campaign="$activeCampaign" :user-rooms="$userRooms" :unread-notifications-count="$unreadNotificationsCount" :active-tab="'orders'" :title="__('room.orders.page_title')">
    <div class="flex flex-col w-full gap-space-md" x-data="{
        selectedOrder: null,
        qrModalOpen: false,
        qrData: {
            bankName: 'MB Bank',
            accountNumber: '0388999888',
            accountName: 'DRINKFLOW ADMIN',
            amount: 0,
            formattedAmount: '0đ',
            transferContent: '',
            qrUrl: ''
        },
        openQr(orderId, amount, formattedAmount, code) {
            this.qrData.amount = amount;
            this.qrData.formattedAmount = formattedAmount;
            this.qrData.transferContent = 'DF' + orderId + ' ' + ('{{ $roomUser->room_user_code }}' || 'USER');
            this.qrData.qrUrl = 'https://img.vietqr.io/image/MB-0388999888-compact2.png?amount=' + amount + '&addInfo=' + encodeURIComponent(this.qrData.transferContent);
            this.qrModalOpen = true;
        },
        copyText(text) {
            navigator.clipboard?.writeText(text);
            alert('{{ __('room.orders.copied_alert', ['text' => '']) }}' + text);
        }
    }">
        @if(session('status'))
            <div class="p-4 rounded-xl bg-primary-fixed text-on-primary-fixed font-semibold flex items-center gap-2 shadow-sm">
                <span class="material-symbols-outlined text-[20px]">check_circle</span>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 rounded-xl bg-error-container text-on-error-container font-semibold flex items-center gap-2 shadow-sm">
                <span class="material-symbols-outlined text-[20px]">error</span>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @php
            $activeOrder = $orders->first();
        @endphp

        @if(!$activeOrder && $orders->isEmpty())
            <div class="bg-surface-container-lowest rounded-2xl p-12 text-center shadow-sm flex flex-col items-center justify-center border border-outline-variant/30">
                <div class="w-16 h-16 rounded-full bg-surface-container-high flex items-center justify-center text-on-surface-variant mb-4">
                    <span class="material-symbols-outlined text-4xl">receipt_long</span>
                </div>
                <h3 class="text-xl font-bold text-on-surface mb-2">{{ __('room.orders.no_orders_title') }}</h3>
                <p class="text-on-surface-variant max-w-md mb-6">{{ __('room.orders.no_orders_desc') }}</p>
                <a href="{{ route('user.campaigns.index', $room->slug) }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-primary text-white font-bold shadow hover:bg-primary-container transition-all">
                    <span class="material-symbols-outlined text-[20px]">local_cafe</span>
                    <span>{{ __('room.dashboard.enter_campaign') }}</span>
                </a>
            </div>
        @else
            <!-- Active / Most Recent Order Tracking Header -->
            @if($activeOrder)
                @php
                    $status = $activeOrder->status instanceof \BackedEnum ? $activeOrder->status->value : (string) $activeOrder->status;
                    $statusSteps = ['submitted' => 1, 'confirmed' => 2, 'ordering' => 3, 'ordered' => 4, 'delivering' => 5, 'completed' => 6];
                    $currentStep = $statusSteps[$status] ?? 1;
                @endphp

                <!-- Single Active Order Constraint Banner -->
                <div class="w-full bg-secondary-container rounded-xl p-space-md shadow-sm flex items-center justify-between gap-space-md">
                    <div class="flex items-center gap-space-sm min-w-0">
                        <div class="w-8 h-8 rounded-lg bg-surface-container-lowest flex items-center justify-center flex-shrink-0 text-primary">
                            <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1;">info</span>
                        </div>
                        <div class="flex flex-col min-w-0">
                            <p class="font-label-md text-label-md text-on-secondary-fixed font-semibold truncate">
                                {{ __('room.orders.page_title') }} #DF-{{ $activeOrder->id }}
                            </p>
                            <p class="font-body-sm text-body-sm text-on-secondary-container">
                                {{ __('room.orders.subtitle', ['name' => $room->name]) }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-space-xs flex-shrink-0">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-surface-container-lowest text-on-surface-variant font-label-sm text-label-sm shadow-sm">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary animate-ping"></span>
                            {{ __('room.orders.live_status') }}
                        </span>
                    </div>
                </div>

                <!-- Order Meta & Profile Summary -->
                <div class="w-full bg-surface-container-lowest rounded-xl p-space-lg shadow-sm border border-outline-variant/30">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-space-md">
                        <div class="flex flex-col gap-space-xs">
                            <div class="flex flex-wrap items-center gap-space-sm">
                                <span class="font-headline-lg text-headline-lg text-on-surface font-bold tracking-tight">#DF-{{ $activeOrder->id }}</span>
                                <span class="px-2.5 py-0.5 rounded bg-surface-container-high text-on-surface-variant font-label-sm text-label-sm uppercase font-semibold">
                                    {{ $room->code ?? $room->slug }}
                                </span>
                                <span class="px-2.5 py-0.5 rounded bg-primary-fixed text-on-primary-fixed-variant font-label-sm text-label-sm font-semibold capitalize">
                                    {{ __('room.orders.status_' . $status) }}
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-y-1 gap-x-space-md text-on-surface-variant font-body-sm text-body-sm">
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[16px] text-secondary">schedule</span>
                                    {{ $activeOrder->created_at?->format('H:i, d/m/Y') }}
                                </span>
                                <span class="text-outline-variant">•</span>
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[16px] text-secondary">groups</span>
                                    {{ __('room.orders.room_label') }}: <strong class="text-on-surface font-semibold ml-0.5">{{ $room->name }}</strong>
                                </span>
                                <span class="text-outline-variant">•</span>
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[16px] text-secondary">storefront</span>
                                    {{ __('room.orders.campaign_label') }}: <strong class="text-on-surface font-semibold ml-0.5">{{ $activeOrder->campaign?->name ?? 'Direct' }}</strong>
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-space-md bg-surface-container-low p-space-sm rounded-xl lg:self-auto self-start">
                            <img class="w-10 h-10 rounded-full object-cover flex-shrink-0 border-2 border-white ring-2 ring-[#006948]/30 shadow-xs" src="{{ $user->avatar_url ?? 'https://lh3.googleusercontent.com/aida-public/AB6AXuDABv8Oe8xyc5YFfx-Urh180Ei9oWDlXncJYYMhGsQyXKi9hH-Ozqz3OugY2_1YBVNW7gx3_8lQ0e663-MZrk9sfuwQNx_hfyyQtK2Zhj_zZGIVtA4PdjFBpNhgR9tn9snH3UYWVQ68_CKNQt5duVHzjZFHBqTbF8GWsCP5QSCLqXnCkE_RM9NLeqxpc7hKb0xusaVGpsBgdlLGILxnD3Fq8gdCU6OgF-qluxXmwytHivLwPF5jc5JUug' }}" alt="{{ $user->name }}" loading="lazy"/>
                            <div class="flex flex-col pr-space-sm min-w-0">
                                <span class="font-label-sm text-label-sm text-on-surface-variant">{{ __('room.orders.orderer_label') }}</span>
                                <span class="font-label-md text-label-md text-on-surface font-semibold truncate">{{ $user->name }}</span>
                                <span class="font-tabular-nums text-tabular-nums text-[11px] text-secondary">{{ $roomUser->room_user_code }}</span>
                            </div>
                            <button class="p-2 rounded-lg bg-surface-container-lowest hover:bg-surface-container-high text-on-surface-variant transition-colors shadow-sm cursor-pointer" @click="copyText(window.location.href)" title="{{ __('room.orders.copy') }}">
                                <span class="material-symbols-outlined text-[18px]">share</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 6-Stage Realtime Tracking Stepper -->
                <div class="w-full bg-surface-container-lowest rounded-xl p-space-lg shadow-sm border border-outline-variant/30">
                    <div class="flex items-center justify-between pb-space-md mb-space-md border-b border-outline-variant/20">
                        <div class="flex items-center gap-space-sm">
                            <span class="material-symbols-outlined text-primary text-[22px]">timeline</span>
                            <h3 class="font-headline-sm text-headline-sm text-on-surface font-semibold">{{ __('room.orders.realtime_progress') }}</h3>
                        </div>
                        <div class="flex items-center gap-space-xs text-on-surface-variant font-body-sm text-body-sm">
                            <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                            <span>{{ __('room.orders.auto_updating') }}</span>
                        </div>
                    </div>

                    <div class="w-full overflow-x-auto py-space-xs">
                        <div class="min-w-[760px] grid grid-cols-6 gap-space-sm relative">
                            <!-- Step 1: Đã gửi -->
                            <div class="flex flex-col items-start relative group">
                                <div class="flex items-center w-full mb-space-sm">
                                    <div class="w-8 h-8 rounded-full {{ $currentStep >= 1 ? 'bg-primary text-on-primary' : 'bg-surface-container-high text-on-surface-variant' }} flex items-center justify-center flex-shrink-0 shadow-sm z-10">
                                        <span class="material-symbols-outlined text-[18px]">{{ $currentStep > 1 ? 'check' : 'send' }}</span>
                                    </div>
                                    <div class="h-1 flex-1 {{ $currentStep > 1 ? 'bg-primary' : 'bg-surface-container-high' }} rounded-full -ml-1"></div>
                                </div>
                                <span class="font-label-md text-label-md {{ $currentStep >= 1 ? 'text-primary font-bold' : 'text-on-surface-variant font-medium' }}">{{ __('room.orders.step_1_title') }}</span>
                                <span class="font-body-sm text-[11px] text-on-surface-variant">{{ __('room.orders.step_1_desc') }}</span>
                            </div>

                            <!-- Step 2: Đã xác nhận -->
                            <div class="flex flex-col items-start relative group">
                                <div class="flex items-center w-full mb-space-sm">
                                    <div class="w-8 h-8 rounded-full {{ $currentStep >= 2 ? 'bg-primary text-on-primary' : 'bg-surface-container-high text-on-surface-variant' }} flex items-center justify-center flex-shrink-0 shadow-sm z-10">
                                        <span class="material-symbols-outlined text-[18px]">{{ $currentStep > 2 ? 'check' : 'check_circle' }}</span>
                                    </div>
                                    <div class="h-1 flex-1 {{ $currentStep > 2 ? 'bg-primary' : 'bg-surface-container-high' }} rounded-full -ml-1"></div>
                                </div>
                                <span class="font-label-md text-label-md {{ $currentStep >= 2 ? 'text-primary font-bold' : 'text-on-surface-variant font-medium' }}">{{ __('room.orders.step_2_title') }}</span>
                                <span class="font-body-sm text-[11px] text-on-surface-variant">{{ __('room.orders.step_2_desc') }}</span>
                            </div>

                            <!-- Step 3: Đặt với quán -->
                            <div class="flex flex-col items-start relative group">
                                <div class="flex items-center w-full mb-space-sm">
                                    <div class="w-8 h-8 rounded-full {{ $currentStep >= 3 ? ($currentStep == 3 ? 'bg-primary-container text-on-primary ring-4 ring-primary-fixed' : 'bg-primary text-on-primary') : 'bg-surface-container-high text-on-surface-variant' }} flex items-center justify-center flex-shrink-0 shadow-sm z-10">
                                        <span class="material-symbols-outlined text-[18px] {{ $currentStep == 3 ? 'animate-spin' : '' }}">{{ $currentStep > 3 ? 'check' : 'storefront' }}</span>
                                    </div>
                                    <div class="h-1 flex-1 {{ $currentStep > 3 ? 'bg-primary' : 'bg-surface-container-high' }} rounded-full -ml-1"></div>
                                </div>
                                <span class="font-label-md text-label-md {{ $currentStep >= 3 ? 'text-primary font-bold' : 'text-on-surface-variant font-medium' }}">{{ __('room.orders.step_3_title') }}</span>
                                <span class="font-body-sm text-[11px] text-on-surface-variant">{{ __('room.orders.step_3_desc') }}</span>
                            </div>

                            <!-- Step 4: Quán pha chế -->
                            <div class="flex flex-col items-start relative group">
                                <div class="flex items-center w-full mb-space-sm">
                                    <div class="w-8 h-8 rounded-full {{ $currentStep >= 4 ? ($currentStep == 4 ? 'bg-primary-container text-on-primary ring-4 ring-primary-fixed' : 'bg-primary text-on-primary') : 'bg-surface-container-high text-on-surface-variant' }} flex items-center justify-center flex-shrink-0 shadow-sm z-10">
                                        <span class="material-symbols-outlined text-[18px]">{{ $currentStep > 4 ? 'check' : 'soup_kitchen' }}</span>
                                    </div>
                                    <div class="h-1 flex-1 {{ $currentStep > 4 ? 'bg-primary' : 'bg-surface-container-high' }} rounded-full -ml-1"></div>
                                </div>
                                <span class="font-label-md text-label-md {{ $currentStep >= 4 ? 'text-primary font-bold' : 'text-on-surface-variant font-medium' }}">{{ __('room.orders.step_4_title') }}</span>
                                <span class="font-body-sm text-[11px] text-on-surface-variant">{{ __('room.orders.step_4_desc') }}</span>
                            </div>

                            <!-- Step 5: Đang giao -->
                            <div class="flex flex-col items-start relative group">
                                <div class="flex items-center w-full mb-space-sm">
                                    <div class="w-8 h-8 rounded-full {{ $currentStep >= 5 ? ($currentStep == 5 ? 'bg-primary-container text-on-primary ring-4 ring-primary-fixed' : 'bg-primary text-on-primary') : 'bg-surface-container-high text-on-surface-variant' }} flex items-center justify-center flex-shrink-0 shadow-sm z-10">
                                        <span class="material-symbols-outlined text-[18px]">{{ $currentStep > 5 ? 'check' : 'two_wheeler' }}</span>
                                    </div>
                                    <div class="h-1 flex-1 {{ $currentStep > 5 ? 'bg-primary' : 'bg-surface-container-high' }} rounded-full -ml-1"></div>
                                </div>
                                <span class="font-label-md text-label-md {{ $currentStep >= 5 ? 'text-primary font-bold' : 'text-on-surface-variant font-medium' }}">{{ __('room.orders.step_5_title') }}</span>
                                <span class="font-body-sm text-[11px] text-on-surface-variant">{{ __('room.orders.step_5_desc') }}</span>
                            </div>

                            <!-- Step 6: Hoàn tất -->
                            <div class="flex flex-col items-start relative group">
                                <div class="flex items-center w-full mb-space-sm">
                                    <div class="w-8 h-8 rounded-full {{ $currentStep >= 6 ? 'bg-primary text-on-primary' : 'bg-surface-container-high text-on-surface-variant' }} flex items-center justify-center flex-shrink-0 shadow-sm z-10">
                                        <span class="material-symbols-outlined text-[18px]">verified</span>
                                    </div>
                                </div>
                                <span class="font-label-md text-label-md {{ $currentStep >= 6 ? 'text-primary font-bold' : 'text-on-surface-variant font-medium' }}">{{ __('room.orders.step_6_title') }}</span>
                                <span class="font-body-sm text-[11px] text-on-surface-variant">{{ __('room.orders.step_6_desc') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Details & Financial Settlement Columns -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-space-md items-start">
                    <!-- Left: Items Breakdown (7 cols) -->
                    <div class="lg:col-span-7 flex flex-col gap-space-md">
                        <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-sm border border-outline-variant/30 flex flex-col gap-space-md">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-space-sm">
                                    <span class="material-symbols-outlined text-primary text-[20px]">local_cafe</span>
                                    <h4 class="font-headline-sm text-headline-sm text-on-surface font-semibold">{{ __('room.orders.items_list') }}</h4>
                                </div>
                                <span class="px-2 py-0.5 rounded bg-surface-container text-on-surface-variant font-label-sm text-label-sm">
                                    {{ $activeOrder->items->count() }} {{ __('room.orders.items_count_suffix') }}
                                </span>
                            </div>

                            <!-- Items List -->
                            <div class="flex flex-col gap-3">
                                @forelse($activeOrder->items as $item)
                                    <div class="bg-surface-container-low rounded-xl p-space-md flex flex-col sm:flex-row gap-space-md items-start">
                                        <div class="w-20 h-20 rounded-lg bg-surface-container-high flex items-center justify-center text-primary shrink-0">
                                            <span class="material-symbols-outlined text-3xl">emoji_food_beverage</span>
                                        </div>
                                        <div class="flex-1 flex flex-col gap-space-xs min-w-0">
                                            <div class="flex items-start justify-between gap-space-sm">
                                                <div>
                                                    <h5 class="font-headline-sm text-headline-sm text-on-surface font-bold">{{ $item->item_name }}</h5>
                                                    <p class="font-label-sm text-label-sm text-on-surface-variant">{{ __('room.orders.qty_prefix') }}: {{ $item->quantity }} x {{ number_format($item->unit_price, 0, ',', '.') }}đ</p>
                                                </div>
                                                <span class="font-tabular-nums text-tabular-nums text-headline-sm font-bold text-on-surface">
                                                    {{ number_format($item->total_price, 0, ',', '.') }}đ
                                                </span>
                                            </div>

                                            <!-- Customizations tags -->
                                            <div class="flex flex-wrap gap-1.5 pt-space-xs">
                                                @if($item->size)
                                                    <span class="px-2 py-0.5 rounded bg-surface-container-highest text-on-surface font-label-sm text-label-sm flex items-center gap-1">
                                                        <span class="material-symbols-outlined text-[14px]">format_size</span>
                                                        Size {{ $item->size }}
                                                    </span>
                                                @endif
                                                @if($item->sugar_level)
                                                    <span class="px-2 py-0.5 rounded bg-surface-container-highest text-on-surface font-label-sm text-label-sm flex items-center gap-1">
                                                        <span class="material-symbols-outlined text-[14px]">water_drop</span>
                                                        {{ $item->sugar_level }} {{ __('room.orders.sugar') }}
                                                    </span>
                                                @endif
                                                @if($item->ice_level)
                                                    <span class="px-2 py-0.5 rounded bg-surface-container-highest text-on-surface font-label-sm text-label-sm flex items-center gap-1">
                                                        <span class="material-symbols-outlined text-[14px]">ac_unit</span>
                                                        {{ $item->ice_level }} {{ __('room.orders.ice') }}
                                                    </span>
                                                @endif
                                                @foreach($item->toppings as $top)
                                                    <span class="px-2 py-0.5 rounded bg-primary-fixed text-on-primary-fixed-variant font-label-sm text-label-sm flex items-center gap-1 font-medium">
                                                        <span class="material-symbols-outlined text-[14px]">add_circle</span>
                                                        {{ $top->topping_name }} (+{{ number_format($top->price, 0, ',', '.') }}đ)
                                                    </span>
                                                @endforeach
                                            </div>

                                            @if($item->note)
                                                <div class="mt-space-xs bg-surface-container-lowest rounded-lg p-space-sm flex items-start gap-space-sm border border-outline-variant/30">
                                                    <span class="material-symbols-outlined text-secondary text-[16px] mt-0.5 flex-shrink-0">edit_note</span>
                                                    <p class="font-body-sm text-body-sm text-on-surface italic">
                                                        “{{ $item->note }}”
                                                    </p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="p-4 text-center text-on-surface-variant font-body-sm">
                                        {{ __('room.orders.empty_items') }}
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Right: Financial Settlement (5 cols) -->
                    <div class="lg:col-span-5 flex flex-col gap-space-md">
                        <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-sm border border-outline-variant/30 flex flex-col gap-space-md">
                            <div class="flex items-center justify-between pb-space-xs border-b border-outline-variant/20">
                                <div class="flex items-center gap-space-sm">
                                    <span class="material-symbols-outlined text-primary text-[20px]">account_balance_wallet</span>
                                    <h4 class="font-headline-sm text-headline-sm text-on-surface font-semibold">{{ __('room.debts.page_title') }}</h4>
                                </div>
                                <span class="px-2.5 py-1 rounded {{ $activeOrder->payment_status === 'paid' ? 'bg-primary-fixed text-on-primary-fixed-variant' : 'bg-tertiary-fixed text-on-tertiary-fixed-variant' }} font-label-sm text-label-sm font-semibold flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $activeOrder->payment_status === 'paid' ? 'bg-primary' : 'bg-tertiary' }}"></span>
                                    {{ $activeOrder->payment_status === 'paid' ? __('room.orders.status_paid') : __('room.orders.status_unpaid') }}
                                </span>
                            </div>

                            <!-- Ledger Calculation List -->
                            <div class="flex flex-col gap-space-sm pt-space-xs">
                                <div class="flex items-center justify-between font-body-md text-body-md text-on-surface-variant">
                                    <span>{{ __('room.orders.subtotal') }}:</span>
                                    <span class="font-tabular-nums text-tabular-nums text-on-surface font-medium">{{ number_format($activeOrder->subtotal, 0, ',', '.') }}đ</span>
                                </div>
                                @if($activeOrder->sponsor_discount > 0)
                                    <div class="flex items-center justify-between font-body-md text-body-md text-primary font-medium bg-primary-fixed/20 p-2 rounded-lg">
                                        <span class="flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[16px]">redeem</span>
                                            {{ __('room.orders.sponsor_discount') }}:
                                        </span>
                                        <span class="font-tabular-nums text-tabular-nums font-bold">-{{ number_format($activeOrder->sponsor_discount, 0, ',', '.') }}đ</span>
                                    </div>
                                @endif
                                @if($activeOrder->delivery_fee_share > 0)
                                    <div class="flex items-center justify-between font-body-md text-body-md text-on-surface-variant">
                                        <span>{{ __('room.orders.delivery_share') }}:</span>
                                        <span class="font-tabular-nums text-tabular-nums text-on-surface font-medium">+{{ number_format($activeOrder->delivery_fee_share, 0, ',', '.') }}đ</span>
                                    </div>
                                @endif

                                <div class="h-px w-full bg-surface-container-high my-space-xs"></div>

                                <!-- Total Payable -->
                                <div class="flex items-baseline justify-between p-space-sm rounded-xl bg-surface-container-low">
                                    <div class="flex flex-col">
                                        <span class="font-label-md text-label-md text-on-surface font-bold">{{ __('room.orders.final_amount') }}</span>
                                        <span class="font-body-sm text-[11px] text-on-surface-variant">{{ __('room.orders.deducted_fund') }}</span>
                                    </div>
                                    <div class="flex items-baseline gap-0.5">
                                        <span class="font-display-lg text-display-lg font-bold text-error tracking-tight font-tabular-nums">
                                            {{ number_format($activeOrder->final_amount, 0, ',', '.') }}
                                        </span>
                                        <span class="font-label-md text-label-md font-bold text-error">đ</span>
                                    </div>
                                </div>
                            </div>

                            <!-- VietQR Payment Quick Action Box -->
                            @if($activeOrder->payment_status !== 'paid')
                                <div class="mt-space-xs bg-surface-container-low p-space-md rounded-xl flex flex-col gap-space-sm">
                                    <div class="flex items-center gap-space-sm">
                                        <span class="material-symbols-outlined text-primary text-[20px]">qr_code_scanner</span>
                                        <span class="font-label-md text-label-md text-on-surface font-semibold">{{ __('room.orders.vietqr_pay_title') }}</span>
                                    </div>
                                    <p class="font-body-sm text-body-sm text-on-surface-variant">
                                        {!! __('room.orders.vietqr_pay_desc', ['code' => '<span class="font-semibold text-on-surface">DF' . $activeOrder->id . ' ' . $roomUser->room_user_code . '</span>']) !!}
                                    </p>
                                    <button class="w-full py-2.5 px-space-md rounded-xl bg-primary hover:bg-primary-container text-on-primary font-label-md text-label-md font-bold shadow transition-all flex items-center justify-center gap-space-sm active:scale-[0.99] cursor-pointer"
                                            @click="openQr({{ $activeOrder->id }}, {{ (int)$activeOrder->final_amount }}, '{{ number_format($activeOrder->final_amount, 0, ',', '.') }}đ', '{{ $roomUser->room_user_code }}')"
                                            type="button">
                                        <span class="material-symbols-outlined text-[20px]">qr_code_2</span>
                                        <span>{{ __('room.orders.pay_now_vietqr') }} ({{ number_format($activeOrder->final_amount, 0, ',', '.') }}đ)</span>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- All Orders Table List -->
            <div class="bg-surface-container-lowest rounded-xl shadow-sm border border-outline-variant/30 overflow-hidden mt-space-md">
                <div class="p-space-md border-b border-outline-variant/20 flex items-center justify-between">
                    <h3 class="font-headline-sm text-headline-sm text-on-surface font-bold">{{ __('room.orders.room_history') }}</h3>
                    <span class="font-label-sm text-label-sm text-on-surface-variant">{{ __('room.orders.total_orders_count', ['count' => $orders->total()]) }}</span>
                </div>
                <div class="w-full overflow-x-auto">
                    <table class="w-full text-left font-body-sm text-body-sm">
                        <thead>
                            <tr class="bg-surface-container-low text-on-surface-variant font-label-sm text-label-sm uppercase tracking-wider border-b border-outline-variant/20">
                                <th class="py-3 px-space-md">{{ __('room.orders.order_code') }}</th>
                                <th class="py-3 px-space-sm">{{ __('room.orders.order_time') }}</th>
                                <th class="py-3 px-space-sm">{{ __('room.orders.items_list') }}</th>
                                <th class="py-3 px-space-sm text-right">{{ __('room.orders.final_amount') }}</th>
                                <th class="py-3 px-space-sm text-center">{{ __('room.orders.table_status') }}</th>
                                <th class="py-3 px-space-md text-center">{{ __('room.orders.table_action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="text-on-surface divide-y divide-outline-variant/20">
                            @foreach($orders as $ord)
                                <tr class="hover:bg-surface-container-low/50 transition-colors">
                                    <td class="py-4 px-space-md font-tabular-nums font-bold text-primary">#DF-{{ $ord->id }}</td>
                                    <td class="py-4 px-space-sm text-on-surface-variant whitespace-nowrap">{{ $ord->created_at?->format('H:i, d/m/Y') }}</td>
                                    <td class="py-4 px-space-sm font-medium">
                                        {{ $ord->items->pluck('item_name')->join(', ') }}
                                    </td>
                                    <td class="py-4 px-space-sm text-right font-tabular-nums font-bold text-on-surface">
                                        {{ number_format($ord->final_amount, 0, ',', '.') }}đ
                                    </td>
                                    <td class="py-4 px-space-sm text-center">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full font-label-sm text-label-sm {{ $ord->payment_status === 'paid' ? 'bg-primary-fixed text-on-primary-fixed-variant' : 'bg-error-container text-on-error-container' }}">
                                            {{ $ord->payment_status === 'paid' ? __('room.orders.status_paid') : __('room.orders.status_unpaid') }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-space-md text-center">
                                        @if($ord->payment_status !== 'paid')
                                            <button class="px-3 py-1.5 rounded-lg bg-primary text-on-primary font-label-sm text-label-sm hover:bg-primary-container transition-all inline-flex items-center gap-1 shadow-sm cursor-pointer"
                                                    @click="openQr({{ $ord->id }}, {{ (int)$ord->final_amount }}, '{{ number_format($ord->final_amount, 0, ',', '.') }}đ', '{{ $roomUser->room_user_code }}')">
                                                <span class="material-symbols-outlined text-[16px]">qr_code</span>
                                                <span>{{ __('room.orders.view_qr') }}</span>
                                            </button>
                                        @else
                                            <span class="text-on-surface-variant text-[13px]">{{ __('room.orders.status_completed') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($orders->hasPages())
                    <div class="p-space-md border-t border-outline-variant/20">
                        {{ $orders->links() }}
                    </div>
                @endif
            </div>
        @endif

        <!-- VietQR Modal -->
        <div x-show="qrModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-on-surface/50 backdrop-blur-xs transition-opacity duration-200" role="dialog">
            <div class="bg-surface-container-lowest w-full max-w-md rounded-2xl shadow-2xl border border-outline-variant overflow-hidden flex flex-col" @click.outside="qrModalOpen = false">
                <div class="p-space-md border-b border-outline-variant flex items-center justify-between bg-surface-container-low">
                    <div class="flex items-center gap-space-sm">
                        <div class="w-9 h-9 rounded-lg bg-primary-fixed flex items-center justify-center text-on-primary-fixed-variant">
                            <span class="material-symbols-outlined text-[20px]">qr_code_2</span>
                        </div>
                        <div>
                            <h3 class="font-headline-sm text-headline-sm text-on-surface font-bold">{{ __('room.orders.pay_now_vietqr') }}</h3>
                            <p class="font-body-sm text-body-sm text-on-surface-variant">{{ $room->name }}</p>
                        </div>
                    </div>
                    <button class="w-8 h-8 rounded-lg flex items-center justify-center text-on-surface-variant hover:bg-surface-container-high transition-colors cursor-pointer" @click="qrModalOpen = false">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>
                <div class="p-space-md flex flex-col gap-space-md">
                    <div class="flex flex-col items-center justify-center p-space-md bg-surface-container-low rounded-xl border border-outline-variant/60">
                        <div class="bg-surface-container-lowest p-3 rounded-xl shadow-sm border border-outline-variant flex flex-col items-center">
                            <img :src="qrData.qrUrl" alt="VietQR" class="w-48 h-48 object-contain rounded" loading="lazy"/>
                            <div class="mt-2 flex items-center gap-1 text-[11px] font-label-sm text-secondary">
                                <span class="material-symbols-outlined text-[14px]">bolt</span>
                                <span>{{ __('room.orders.scan_banking_app') }}</span>
                            </div>
                        </div>
                        <div class="mt-3 text-center">
                            <span class="font-body-sm text-body-sm text-on-surface-variant">{{ __('room.orders.payment_amount_label') }}:</span>
                            <div class="font-display-lg text-display-lg font-bold text-error tracking-tight font-tabular-nums" x-text="qrData.formattedAmount"></div>
                        </div>
                    </div>
                    <div class="flex flex-col gap-space-xs bg-surface-container-lowest border border-outline-variant rounded-xl p-space-sm">
                        <div class="flex items-center justify-between py-1 border-b border-surface-container-high text-body-sm">
                            <span class="text-on-surface-variant">{{ __('room.orders.bank_label') }}:</span>
                            <span class="font-semibold text-on-surface flex items-center gap-1">
                                <span class="px-1.5 py-0.5 rounded bg-surface-container-high text-[11px] font-bold text-primary" x-text="qrData.bankName"></span>
                            </span>
                        </div>
                        <div class="flex items-center justify-between py-1 border-b border-surface-container-high text-body-sm">
                            <span class="text-on-surface-variant">{{ __('room.orders.account_number_label') }}:</span>
                            <div class="flex items-center gap-1">
                                <span class="font-tabular-nums font-bold text-on-surface" x-text="qrData.accountNumber"></span>
                                <button class="p-1 rounded hover:bg-surface-container-high text-primary transition-colors flex items-center cursor-pointer" @click="copyText(qrData.accountNumber)">
                                    <span class="material-symbols-outlined text-[16px]">content_copy</span>
                                </button>
                            </div>
                        </div>
                        <div class="flex items-center justify-between py-1.5 bg-primary-fixed/20 px-2 rounded-lg mt-1 text-body-sm">
                            <div class="flex flex-col">
                                <span class="font-label-sm text-[11px] text-on-primary-fixed-variant font-medium">{{ __('room.orders.transfer_content_label') }}:</span>
                                <span class="font-tabular-nums font-bold text-primary tracking-wide" x-text="qrData.transferContent"></span>
                            </div>
                            <button class="p-1 rounded hover:bg-primary-fixed text-primary transition-colors flex items-center cursor-pointer" @click="copyText(qrData.transferContent)">
                                <span class="material-symbols-outlined text-[16px]">content_copy</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-room.layout>

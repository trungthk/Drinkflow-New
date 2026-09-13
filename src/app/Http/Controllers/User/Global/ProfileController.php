<?php

namespace App\Http\Controllers\User\Global;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\GlobalUser;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentAccount;
use App\Models\RoomUserDevice;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    /**
     * Display the global user profile page.
     *
     * @param Request $request
     * @return View
     */
    public function __invoke(Request $request): View
    {
        return $this->index($request);
    }

    /**
     * Display the profile index view.
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        /** @var GlobalUser $user */
        $user = $request->attributes->get('global_user') ?? $request->user('web');

        // Extract Workspace domain from user email
        $domain = Str::after((string) $user->email, '@');
        $workspaceText = __('global.profile.workspace_auth_domain', ['domain' => $domain ?: 'company.com']);

        // Query active room users of current user
        $roomUsers = $user->roomUsers()
            ->with([
                'room' => function ($q) {
                    $q->with(['paymentAccounts' => function ($pa) {
                        $pa->where('status', 'active');
                    }]);
                },
            ])
            ->where('status', 'active')
            ->orderByDesc('last_active_at')
            ->orderByDesc('updated_at')
            ->get();

        $primaryRoomUser = $roomUsers->first();
        $primaryRoom = $primaryRoomUser?->room;
        $department = $primaryRoom?->name ?? __('global.profile.default_dept');
        $userCode = $primaryRoomUser?->user_code ?? ('DF-EMP-' . str_pad((string) $user->id, 4, '0', STR_PAD_LEFT));
        $role = $primaryRoomUser ? __('global.profile.role_member', ['name' => $primaryRoom->name]) : __('global.profile.default_role');

        // Order metrics across all user memberships
        $roomUserIds = $user->roomUsers()->pluck('id');
        $ordersQuery = Order::query()->whereIn('room_user_id', $roomUserIds);
        $totalOrdersCount = (clone $ordersQuery)->count();

        $completedStatuses = [
            OrderStatus::Submitted->value,
            OrderStatus::Confirmed->value,
            OrderStatus::Completed->value,
            'submitted',
            'confirmed',
            'paid',
            'completed',
        ];

        $totalSpent = (int) (clone $ordersQuery)->whereIn('status', $completedStatuses)->sum('final_amount');
        $paidOrdersCount = (clone $ordersQuery)->whereIn('status', ['paid', 'completed', OrderStatus::Completed->value])->count();
        $totalCups = (int) OrderItem::query()
            ->whereIn('order_id', function ($q) use ($roomUserIds) {
                $q->select('id')->from('orders')
                    ->whereIn('room_user_id', $roomUserIds);
            })
            ->sum('quantity');

        $paymentRate = $totalOrdersCount > 0 ? (int) round(($paidOrdersCount / $totalOrdersCount) * 100) : 100;

        // Gamification / Member Level tier
        if ($totalOrdersCount >= 30) {
            $memberLevel = 'Diamond';
            $memberTitle = __('global.profile.tier_diamond_title');
            $memberSubtitle = __('global.profile.tier_diamond_sub');
            $badgeIcon = 'military_tech';
        } elseif ($totalOrdersCount >= 10) {
            $memberLevel = 'Gold';
            $memberTitle = __('global.profile.tier_gold_title');
            $memberSubtitle = __('global.profile.tier_gold_sub');
            $badgeIcon = 'workspace_premium';
        } else {
            $memberLevel = 'Standard';
            $memberTitle = __('global.profile.tier_standard_title');
            $memberSubtitle = __('global.profile.tier_standard_sub');
            $badgeIcon = 'verified';
        }

        $joinedDate = $user->created_at ? $user->created_at->translatedFormat('m/Y') : now()->translatedFormat('m/Y');
        $joinedDuration = $user->created_at ? $user->created_at->diffForHumans(['parts' => 1]) : __('global.profile.just_joined');

        // Bank / Payment info from real database
        $defaultPayment = PaymentAccount::query()->where('is_default', true)->first();
        if (!$defaultPayment && $primaryRoom) {
            $defaultPayment = $primaryRoom->paymentAccounts->first();
        }
        $bankData = $defaultPayment ? [
            'bank_name' => $defaultPayment->bank_name,
            'bank_code' => $defaultPayment->bank_code,
            'account_number' => $defaultPayment->getRawOriginal('account_number'),
            'account_name' => $defaultPayment->account_name,
            'branch' => __('global.profile.default_branch'),
        ] : null;

        // Preferences & contact details (Real database fields)
        $phone = $user->phone ?? '';
        $deskLocation = $user->desk_location ?? '';
        $deliveryLocation = $user->delivery_location ?? '';
        $preferences = is_array($user->preferences) ? $user->preferences : [];
        $sugar = $preferences['sugar'] ?? '';
        $ice = $preferences['ice'] ?? '';
        $toppings = $preferences['toppings'] ?? [];
        $orderNote = $preferences['note'] ?? '';
        $notifyCampaign = $preferences['notify_campaign'] ?? true;
        $notifySound = $preferences['notify_sound'] ?? true;

        $unreadNotificationsCount = $user->notifications()->whereNull('read_at')->count();
        $notifications = $user->notifications()->latest()->take(5)->get();

        $breadcrumbs = [
            ['label' => __('global.rooms.breadcrumb_personal'), 'url' => route('user.me.dashboard')],
            ['label' => __('global.profile.breadcrumb_profile'), 'url' => route('user.me.profile')],
        ];

        $hasRooms = $user->roomUsers()->where('status', 'active')->exists();

        return view('user.global.profile', compact(
            'user',
            'hasRooms',
            'workspaceText',
            'department',
            'userCode',
            'role',
            'totalOrdersCount',
            'totalSpent',
            'totalCups',
            'paymentRate',
            'memberLevel',
            'memberTitle',
            'memberSubtitle',
            'badgeIcon',
            'joinedDate',
            'joinedDuration',
            'phone',
            'deskLocation',
            'deliveryLocation',
            'sugar',
            'ice',
            'toppings',
            'orderNote',
            'notifyCampaign',
            'notifySound',
            'unreadNotificationsCount',
            'notifications',
            'breadcrumbs'
        ));
    }

    /**
     * Update contact details and preferences.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function update(Request $request): RedirectResponse
    {
        /** @var GlobalUser $user */
        $user = $request->attributes->get('global_user') ?? $request->user('web');

        $validated = $request->validate([
            'phone' => 'nullable|string|max:30',
            'desk_location' => 'nullable|string|max:100',
            'delivery_location' => 'nullable|string|max:255',
            'sugar' => 'nullable|string|max:10',
            'ice' => 'nullable|string|max:20',
            'toppings' => 'nullable|array',
            'toppings.*' => 'string|max:100',
            'note' => 'nullable|string|max:500',
            'notify_campaign' => 'nullable|boolean',
            'notify_sound' => 'nullable|boolean',
        ]);

        $updates = [];
        if ($request->has('phone')) {
            $updates['phone'] = $validated['phone'];
        }
        if ($request->has('desk_location')) {
            $updates['desk_location'] = $validated['desk_location'];
        }
        if ($request->has('delivery_location')) {
            $updates['delivery_location'] = $validated['delivery_location'];
        }

        $preferences = is_array($user->preferences) ? $user->preferences : [];
        if ($request->has('sugar')) {
            $preferences['sugar'] = $validated['sugar'];
        }
        if ($request->has('ice')) {
            $preferences['ice'] = $validated['ice'];
        }
        if ($request->has('toppings')) {
            $preferences['toppings'] = array_values(array_filter($validated['toppings']));
        }
        if ($request->has('note')) {
            $preferences['note'] = $validated['note'];
        }
        if ($request->has('notify_campaign')) {
            $preferences['notify_campaign'] = $request->boolean('notify_campaign');
        }
        if ($request->has('notify_sound')) {
            $preferences['notify_sound'] = $request->boolean('notify_sound');
        }

        $updates['preferences'] = $preferences;
        $user->update($updates);

        return back()->with('status', __('global.profile.update_success_status'));
    }

    /**
     * Return JSON for global user profile.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->attributes->get('global_user') ?? $request->user('web');
        return response()->json(['data' => $user->load(['oauthIdentities', 'roomUsers.room'])]);
    }

    /**
     * Payments sub-page.
     */
    public function payments(Request $request): View|\Illuminate\Http\JsonResponse
    {
        return app(PaymentsController::class)->index($request);
    }

    /**
     * Devices sub-page (/me/devices).
     */
    public function devices(Request $request): View
    {
        /** @var GlobalUser $user */
        $user = $request->attributes->get('global_user') ?? $request->user('web');

        $domain = Str::after((string) $user->email, '@') ?: 'company.com';
        $primaryRoomUser = $user->roomUsers()->with('room')->where('status', 'active')->orderByDesc('last_active_at')->first();
        $department = $primaryRoomUser?->room?->name ?? __('global.profile.default_dept');
        $userCode = $primaryRoomUser?->user_code ?? ('DF-EMP-' . str_pad((string) $user->id, 4, '0', STR_PAD_LEFT));

        // Current session & device details
        $currentSessionId = $request->session()->getId();
        $currentUa = $request->userAgent() ?: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/128.0.0.0';
        $currentDeviceInfo = $this->parseUserAgent($currentUa);
        $currentIp = $request->ip() ?: '14.161.28.92';
        $currentSessionCode = '#SES-' . strtoupper(substr(md5((string) $currentSessionId), 0, 4)) . '-VN';

        // Query other sessions from database sessions table
        $dbSessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->orderByDesc('last_activity')
            ->get();

        $otherSessions = [];
        foreach ($dbSessions as $sess) {
            $parsed = $this->parseUserAgent($sess->user_agent);
            $lastActivity = Carbon::createFromTimestamp($sess->last_activity);
            $otherSessions[] = [
                'id' => $sess->id,
                'device_name' => $parsed['device_name'],
                'type_label' => $parsed['type_label'],
                'badge_class' => $parsed['badge_class'],
                'icon' => $parsed['icon'],
                'ip' => $sess->ip_address ?: __('global.devices.internal_network'),
                'location' => __('global.devices.default_country'),
                'last_active' => $lastActivity->diffForHumans(),
                'is_real_session' => true,
            ];
        }

        $breadcrumbs = [
            ['label' => __('global.rooms.breadcrumb_personal'), 'url' => route('user.me.dashboard')],
            ['label' => __('global.devices.breadcrumb_devices'), 'url' => route('user.me.devices')],
        ];

        $unreadNotificationsCount = $user->notifications()->whereNull('read_at')->count();
        $notifications = $user->notifications()->latest()->take(5)->get();

        return view('user.global.devices', compact(
            'user',
            'domain',
            'department',
            'userCode',
            'currentDeviceInfo',
            'currentIp',
            'currentSessionCode',
            'otherSessions',
            'breadcrumbs',
            'unreadNotificationsCount',
            'notifications'
        ));
    }

    /**
     * Terminate a single remote session.
     */
    public function logoutDevice(Request $request, string $sessionId): RedirectResponse
    {
        /** @var GlobalUser $user */
        $user = $request->attributes->get('global_user') ?? $request->user('web');

        DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', $user->id)
            ->delete();

        return back()->with('status', __('global.devices.logout_device_success'));
    }

    /**
     * Terminate all other remote sessions.
     */
    public function logoutOtherDevices(Request $request): RedirectResponse
    {
        /** @var GlobalUser $user */
        $user = $request->attributes->get('global_user') ?? $request->user('web');
        $currentSessionId = $request->session()->getId();

        DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->delete();

        // Revoke trusted device tokens
        $roomUserIds = $user->roomUsers()->pluck('id');
        RoomUserDevice::query()->whereIn('room_user_id', $roomUserIds)->update(['revoked_at' => now()]);

        return back()->with('status', __('global.devices.logout_other_devices_success'));
    }

    /**
     * Deactivate / delete personal account.
     */
    public function deleteAccount(Request $request): RedirectResponse
    {
        /** @var GlobalUser $user */
        $user = $request->attributes->get('global_user') ?? $request->user('web');

        $confirmText = trim((string) $request->input('confirm_delete'));
        if ($confirmText !== $user->email && $confirmText !== 'XÓA TÀI KHOẢN' && $confirmText !== 'DELETE ACCOUNT') {
            return back()->withErrors(['confirm_delete' => __('global.devices.delete_confirm_invalid')]);
        }

        $user->update(['status' => \App\Enums\GlobalUserStatus::Disabled]);

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('status', __('global.devices.delete_account_success'));
    }

    /**
     * Feedback & Reviews page (/me/feedback).
     */
    public function feedback(Request $request): View
    {
        /** @var GlobalUser $user */
        $user = $request->attributes->get('global_user') ?? $request->user('web');

        $primaryRoomUser = $user->roomUsers()->with('room')->where('status', 'active')->orderByDesc('last_active_at')->first();
        $department = $primaryRoomUser?->room?->name ?? 'Ban Kỹ thuật';

        // Daily quota: 1 submission per day
        $todayCount = Feedback::query()
            ->where('global_user_id', $user->id)
            ->whereDate('created_at', now()->toDateString())
            ->count();
        $canSubmit = $todayCount < 1;

        // Aggregate statistics
        $totalCount = Feedback::count();
        if ($totalCount > 0) {
            $avgScore = round((float) Feedback::avg('rating'), 1);
            $countsByStar = [];
            for ($s = 5; $s >= 1; $s--) {
                $countS = Feedback::where('rating', $s)->count();
                $countsByStar[$s] = [
                    'count' => $countS,
                    'percent' => (int) round(($countS / $totalCount) * 100),
                ];
            }
        } else {
            $avgScore = 0;
            $countsByStar = [
                5 => ['count' => 0, 'percent' => 0],
                4 => ['count' => 0, 'percent' => 0],
                3 => ['count' => 0, 'percent' => 0],
                2 => ['count' => 0, 'percent' => 0],
                1 => ['count' => 0, 'percent' => 0],
            ];
        }

        // Recent feedbacks list
        $feedbacks = Feedback::with('globalUser')->latest()->paginate(4);

        $breadcrumbs = [
            ['label' => __('global.rooms.breadcrumb_personal'), 'url' => route('user.me.dashboard')],
            ['label' => __('global.feedback.breadcrumb_feedback'), 'url' => route('user.me.feedback')],
        ];

        $unreadNotificationsCount = $user->notifications()->whereNull('read_at')->count();
        $notifications = $user->notifications()->latest()->take(5)->get();

        return view('user.global.feedback', compact(
            'user',
            'department',
            'todayCount',
            'canSubmit',
            'totalCount',
            'avgScore',
            'countsByStar',
            'feedbacks',
            'breadcrumbs',
            'unreadNotificationsCount',
            'notifications'
        ));
    }

    /**
     * Handle feedback submission.
     */
    public function storeFeedback(Request $request): RedirectResponse
    {
        /** @var GlobalUser $user */
        $user = $request->attributes->get('global_user') ?? $request->user('web');

        $todayCount = Feedback::query()
            ->where('global_user_id', $user->id)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        if ($todayCount >= 1) {
            return back()->withErrors(['quota' => __('global.feedback.quota_exceeded_error')]);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'subsystem' => 'required|string|in:all,room,split_qr,socket,sponsor',
            'content' => 'required|string|min:3|max:1000',
        ]);

        $primaryRoomUser = $user->roomUsers()->with('room')->where('status', 'active')->orderByDesc('last_active_at')->first();
        $department = $primaryRoomUser?->room?->name ?? 'Ban Công nghệ & Kỹ thuật số';

        Feedback::create([
            'global_user_id' => $user->id,
            'rating' => $validated['rating'],
            'subsystem' => $validated['subsystem'],
            'content' => $validated['content'],
            'user_display_name' => $user->name ?: __('global.feedback.anonymous_user'),
            'department_name' => $department,
        ]);

        return back()->with('status', __('global.feedback.submit_success_status'));
    }

    /**
     * Parse User Agent into readable client info.
     */
    protected function parseUserAgent(?string $userAgent): array
    {
        $ua = $userAgent ?: '';
        $browser = 'Chrome';
        $os = 'Windows 11 Pro';
        $icon = 'laptop_windows';
        $type = 'Desktop Workstation';
        $badgeClass = 'bg-[#ECFDF5] text-[#065F46] border-[#A7F3D0]';

        if (stripos($ua, 'iPhone') !== false || stripos($ua, 'iPad') !== false) {
            $os = 'iPhone 15 Pro Max';
            $browser = 'Safari';
            $icon = 'smartphone';
            $type = 'Mobile Client';
            $badgeClass = 'bg-[#FFFBEB] text-[#92400E] border-amber-200';
        } elseif (stripos($ua, 'Android') !== false) {
            $os = 'Android';
            $browser = 'Chrome Mobile';
            $icon = 'smartphone';
            $type = 'Mobile Client';
            $badgeClass = 'bg-[#FFFBEB] text-[#92400E] border-amber-200';
        } elseif (stripos($ua, 'Macintosh') !== false || stripos($ua, 'Mac OS') !== false) {
            $os = 'Macbook Pro M2';
            $browser = 'Chrome';
            $icon = 'laptop_mac';
            $type = 'Trusted Device';
            $badgeClass = 'bg-[#ECFDF5] text-[#065F46] border-[#A7F3D0]';
        } elseif (stripos($ua, 'Edge') !== false || stripos($ua, 'Edg') !== false) {
            $os = 'Máy trạm Lab Kỹ thuật';
            $browser = 'Microsoft Edge';
            $icon = 'desktop_windows';
            $type = 'Shared Lab PC';
            $badgeClass = 'bg-[#F1F5F9] text-[#475569] border-slate-200';
        } elseif (stripos($ua, 'Firefox') !== false) {
            $browser = 'Firefox';
            $os = 'Linux / Ubuntu';
            $icon = 'desktop_windows';
            $type = 'Workstation';
            $badgeClass = 'bg-[#F1F5F9] text-[#475569] border-slate-200';
        }

        return [
            'browser' => $browser,
            'os' => $os,
            'device_name' => __('global.devices.device_on_os', ['browser' => $browser, 'os' => $os]),
            'icon' => $icon,
            'type_label' => $type,
            'badge_class' => $badgeClass,
        ];
    }
}

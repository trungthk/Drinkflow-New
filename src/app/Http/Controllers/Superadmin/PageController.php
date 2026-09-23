<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Enums\CampaignStatus;
use App\Enums\FeedbackStatus;
use App\Models\Feedback;
use App\Models\AdminAccount;
use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Models\SecurityEvent;
use App\Models\SystemNotificationChannel;
use App\Models\Version;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    /**
     * Handle the rooms operation.
     * @return View Result of the operation.
     */
    public function rooms(Request $request): View
    {
        $query = Room::query()->withCount(['roomUsers', 'campaigns', 'admins']);
        $search = trim($request->string('q')->toString());
        if ($search !== '') $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%"));
        $status = $request->string('status')->toString();
        if ($status !== '') $query->where('status', $status);
        $sort = $request->string('sort')->toString();
        match ($sort) {
            'name' => $query->orderBy('name'),
            'members' => $query->orderByDesc('room_users_count'),
            'campaigns' => $query->orderByDesc('campaigns_count'),
            default => $query->latest(),
        };
        return view('superadmin.rooms', ['rooms' => $query->paginate(\App\Constants\Pagination::ADMIN_PER_PAGE)->withQueryString(), 'filters' => compact('search', 'status', 'sort')]);
    }
    /**
     * Handle the room operation.
     * @return View Result of the operation.
     */
    public function room(): View
    {
        return view('superadmin.room-detail');
    }
    /**
     * Handle the admins operation.
     * @return View Result of the operation.
     */
    public function admins(Request $request): View
    {
        $query = AdminAccount::query()->withCount('rooms')->latest();
        $search = trim($request->string('q')->toString());
        if ($search !== '') $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        $role = $request->string('role')->toString();
        $status = $request->string('status')->toString();
        if ($role !== '') $query->where('role', $role);
        if ($status !== '') $query->where('status', $status);
        return view('superadmin.admins', ['admins' => $query->paginate(\App\Constants\Pagination::ADMIN_PER_PAGE)->withQueryString(), 'filters' => compact('search', 'role', 'status')]);
    }
    /**
     * Handle the admin operation.
     * @return View Result of the operation.
     */
    public function admin(): View
    {
        return view('superadmin.admin-detail');
    }
    /**
     * Handle the users operation.
     * @return View Result of the operation.
     */
    public function users(Request $request): View
    {
        $query = GlobalUser::query()->withCount('roomUsers')->latest();
        $search = trim($request->string('q')->toString());
        if ($search !== '') $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('normalized_name', 'like', "%".strtoupper($search)."%")->orWhere('email', 'like', "%{$search}%"));
        $status = $request->string('status')->toString();
        if ($status !== '') $query->where('status', $status);
        return view('superadmin.users', ['users' => $query->paginate(\App\Constants\Pagination::ADMIN_PER_PAGE)->withQueryString(), 'filters' => compact('search', 'status')]);
    }
    /**
     * Handle the user operation.
     * @return View Result of the operation.
     */
    public function user(): View
    {
        return view('superadmin.user-detail');
    }
    /**
     * List campaigns across every room, filtered by keyword, room and status.
     *
     * Unknown status values and non-numeric room ids are ignored rather than matched literally.
     *
     * @param Request $request Query string: q, room_id, status.
     * @return View Campaign registry page with the room list for the room filter.
     */
    public function campaigns(Request $request): View
    {
        $query = Campaign::query()->with('room:id,name,slug')->withCount('orders')->latest();
        $search = trim($request->string('q')->toString());
        if ($search !== '') $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('restaurant', 'like', "%{$search}%"));
        $roomId = filter_var($request->query('room_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
        if ($roomId !== null) $query->where('room_id', $roomId);
        $status = CampaignStatus::tryFrom($request->string('status')->toString());
        if ($status !== null) $query->where('status', $status->value);

        return view('superadmin.campaigns', [
            'campaigns' => $query->paginate(\App\Constants\Pagination::ADMIN_PER_PAGE)->withQueryString(),
            'rooms' => Room::query()->orderBy('name')->get(['id', 'name']),
            'filters' => ['search' => $search, 'room_id' => $roomId, 'status' => $status?->value ?? ''],
        ]);
    }
    /**
     * Handle the notifications operation.
     * @return View Result of the operation.
     */
    public function notifications(Request $request): View
    {
        $query = SystemNotificationChannel::query()->latest();
        $search = trim($request->string('q')->toString());
        if ($search !== '') $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('type', 'like', "%{$search}%"));
        return view('superadmin.notifications', ['channels' => $query->paginate(\App\Constants\Pagination::ADMIN_PER_PAGE)->withQueryString(), 'filters' => compact('search')]);
    }
    /**
     * Render the system settings & maintenance page.
     *
     * @return View Page with the reset confirmation phrase the reset modal asks the superadmin to type.
     */
    public function system(): View
    {
        return view('superadmin.system', ['resetPhrase' => \App\Actions\Superadmin\ResetSystemAction::CONFIRMATION_PHRASE]);
    }
    /**
     * List audit logs written through the admin console only (room admins and superadmins).
     *
     * End-user and system entries are excluded. The event filter matches an exact event key chosen
     * from the events that actually exist for admin actors, and the actor filter only accepts
     * admin/superadmin.
     *
     * @param Request $request Query string: event, actor_type, date_from, date_to.
     * @return View Audit log page with translated event options.
     */
    public function audit(Request $request): View
    {
        $query = AuditLog::query()
            ->whereIn('actor_type', AuditLog::ADMIN_ACTOR_TYPES)
            ->with(['room:id,name', 'actorAdmin:id,name,email'])
            ->latest('created_at')
            ->latest('id');
        $event = trim($request->string('event')->toString());
        if ($event !== '') $query->where('event', $event);
        $actorType = $request->string('actor_type')->toString();
        if (! in_array($actorType, AuditLog::ADMIN_ACTOR_TYPES, true)) $actorType = '';
        if ($actorType !== '') $query->where('actor_type', $actorType);
        $dateFrom = $request->string('date_from')->toString();
        $dateTo = $request->string('date_to')->toString();
        if ($dateFrom !== '') $query->whereDate('created_at', '>=', $dateFrom);
        if ($dateTo !== '') $query->whereDate('created_at', '<=', $dateTo);

        $eventOptions = AuditLog::query()
            ->whereIn('actor_type', AuditLog::ADMIN_ACTOR_TYPES)
            ->distinct()
            ->pluck('event')
            ->mapWithKeys(fn (string $key): array => [$key => AuditLog::labelForEvent($key)])
            ->sort();

        return view('superadmin.audit', [
            'audits' => $query->paginate(\App\Constants\Pagination::ADMIN_PER_PAGE)->withQueryString(),
            'eventOptions' => $eventOptions,
            'filters' => ['event' => $event, 'actor_type' => $actorType, 'date_from' => $dateFrom, 'date_to' => $dateTo],
        ]);
    }
    /**
     * Handle the security operation.
     * @return View Result of the operation.
     */
    public function security(Request $request): View
    {
        $query = SecurityEvent::query()->with('room:id,name')->latest('created_at');
        $search = trim($request->string('q')->toString());
        if ($search !== '') $query->where(fn ($q) => $q->where('type', 'like', "%{$search}%")->orWhere('ip_address', 'like', "%{$search}%"));
        $severity = $request->string('severity')->toString();
        if ($severity !== '') $query->where('severity', $severity);
        $actorType = $request->string('actor_type')->toString();
        if ($actorType !== '') $query->where('actor_type', $actorType);
        return view('superadmin.security', [
            'events' => $query->paginate(\App\Constants\Pagination::ADMIN_PER_PAGE)->withQueryString(),
            'filters' => ['search' => $search, 'severity' => $severity, 'actor_type' => $actorType],
        ]);
    }
    /**
     * Handle the socket operation.
     * @return View Result of the operation.
     */
    public function socket(): View
    {
        return view('superadmin.socket');
    }
    /**
     * Handle the queue operation.
     * @return View Result of the operation.
     */
    public function queue(Request $request): View
    {
        $query = DB::table('failed_jobs')->latest('failed_at');
        $search = trim($request->string('q')->toString());
        if ($search !== '') $query->where(fn ($q) => $q->where('queue', 'like', "%{$search}%")->orWhere('uuid', 'like', "%{$search}%"));
        return view('superadmin.queue', ['jobs' => $query->paginate(\App\Constants\Pagination::ADMIN_PER_PAGE)->withQueryString(), 'filters' => compact('search')]);
    }
    /**
     * Handle the versions operation.
     * @return View Result of the operation.
     */
    public function versions(Request $request): View
    {
        $query = Version::query()->latest('release_date');
        $search = trim($request->string('q')->toString());
        if ($search !== '') $query->where(fn ($q) => $q->where('version', 'like', "%{$search}%")->orWhere('title', 'like', "%{$search}%"));
        return view('superadmin.versions', ['versions' => $query->paginate(\App\Constants\Pagination::ADMIN_PER_PAGE)->withQueryString(), 'filters' => compact('search')]);
    }

    /**
     * Feedback moderation queue: approve or take down user feedback.
     *
     * @param Request $request Incoming request (filters: status, rating, q).
     * @return View Feedback moderation view.
     */
    public function feedbacks(Request $request): View
    {
        $status = (string) $request->query('status', FeedbackStatus::Inactive->value);
        $rating = $request->integer('rating');
        $search = trim($request->string('q')->toString());

        $query = Feedback::query()->with('globalUser:id,name,email')->latest('created_at')->latest('id');
        if (FeedbackStatus::tryFrom($status) !== null) {
            $query->where('status', $status);
        } else {
            $status = 'all';
        }
        if ($rating >= 1 && $rating <= 5) {
            $query->where('rating', $rating);
        }
        if ($search !== '') {
            $query->where(fn ($q) => $q->where('content', 'like', "%{$search}%")->orWhere('user_display_name', 'like', "%{$search}%"));
        }

        return view('superadmin.feedbacks', [
            'feedbacks' => $query->paginate(\App\Constants\Pagination::ADMIN_PER_PAGE)->withQueryString(),
            'filters' => ['status' => $status, 'rating' => $rating >= 1 && $rating <= 5 ? $rating : null, 'search' => $search],
            'pendingCount' => Feedback::query()->where('status', FeedbackStatus::Inactive->value)->count(),
        ]);
    }
}

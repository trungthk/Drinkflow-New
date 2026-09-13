<?php

namespace App\Http\Controllers\User\Global;

use App\Http\Controllers\Controller;
use App\Models\Debt;
use App\Models\GlobalUser;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BlockedAccountController extends Controller
{
    /**
     * Display the blocked account notice page.
     */
    public function show(Request $request): View
    {
        /** @var GlobalUser $user */
        $user = $request->attributes->get('global_user') ?? $request->user('web');

        $incidentCode = '#BLK-' . date('Y') . '-' . str_pad((string) $user->id, 5, '0', STR_PAD_LEFT);
        $recordedAt = now()->format('H:i • d/m/Y') . ' (GMT+7)';

        $roomUserIds = $user->roomUsers()->pluck('id');
        $dbDebts = Debt::with(['room', 'roomUser'])
            ->whereIn('room_user_id', $roomUserIds)
            ->where('remaining_amount', '>', 0)
            ->latest()
            ->get();

        $totalDebt = (int) $dbDebts->sum('remaining_amount');

        $pendingDebts = [];
        foreach ($dbDebts as $debt) {
            $pendingDebts[] = [
                'title' => __('global.blocked.debt_item_title', ['id' => $debt->id, 'room' => $debt->room?->name ?? __('global.blocked.internal_room')]),
                'subtitle' => __('global.blocked.debt_item_subtitle', ['date' => $debt->created_at ? $debt->created_at->format('d/m/Y') : __('global.common.recently')]),
                'amount' => (int) $debt->remaining_amount,
            ];
        }

        return view('user.global.blocked', compact(
            'user',
            'incidentCode',
            'recordedAt',
            'pendingDebts',
            'totalDebt'
        ));
    }

    /**
     * Submit an unlock request or appeal.
     */
    public function appeal(Request $request): RedirectResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:1000',
            'attachment_note' => 'nullable|string|max:500',
        ]);

        return back()->with('status', __('global.blocked.appeal_submitted_status'));
    }
}

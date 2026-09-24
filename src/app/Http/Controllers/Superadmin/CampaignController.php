<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Actions\Superadmin\ForceArchiveCampaignAction;
use App\Actions\Campaign\CloseCampaignAction;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Services\Audit\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CampaignController extends Controller
{
    /**
     * Handle the index operation.
     * @param Request $request Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Campaign::query()->with(['room:id,name,slug', 'paymentAccount:id,room_id,bank_name,account_name'])->withCount('orders')->latest();
        if ($request->filled('room_id'))
            $query->where('room_id', $request->integer('room_id'));
        if ($request->filled('status'))
            $query->where('status', $request->string('status')->toString());
        if ($request->filled('q'))
            $query->where(fn($q) => $q->where('name', 'like', '%' . $request->string('q') . '%')->orWhere('restaurant', 'like', '%' . $request->string('q') . '%'));
        return response()->json(['data' => $query->paginate(\App\Constants\Pagination::ADMIN_PER_PAGE)]);
    }

    /**
     * Handle the force close operation.
     * @param Campaign $campaign Parameter value.
     * @param CloseCampaignAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function forceClose(Campaign $campaign, CloseCampaignAction $action, AuditService $audit): JsonResponse
    {
        $before = ['status' => $campaign->status?->value ?? (string) $campaign->status];
        $result = $action->execute($campaign);
        $audit->record('campaign.force_closed', 'campaign', $campaign->id, $campaign->room_id, $before, ['status' => $result->status?->value ?? (string) $result->status]);
        return response()->json(['data' => $result]);
    }

    /**
     * Force-cancel a campaign from the superadmin console by archiving it (no notifications,
     * orders and debts untouched). Audit logging happens in the action.
     *
     * @param Campaign $campaign Campaign to archive.
     * @param ForceArchiveCampaignAction $action Archive action enforcing the allowed source states.
     * @return JsonResponse The archived campaign.
     */
    public function forceCancel(Campaign $campaign, ForceArchiveCampaignAction $action): JsonResponse
    {
        return response()->json(['data' => $action->execute($campaign)]);
    }
}

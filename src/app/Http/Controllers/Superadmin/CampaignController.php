<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

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
        $query = Campaign::query()->with(['room:id,name,slug', 'paymentAccount:id,room_id,bank_name,account_name'])->withCount(['orders', 'debts'])->latest();
        if ($request->filled('room_id'))
            $query->where('room_id', $request->integer('room_id'));
        if ($request->filled('status'))
            $query->where('status', $request->string('status')->toString());
        if ($request->filled('q'))
            $query->where(fn($q) => $q->where('name', 'like', '%' . $request->string('q') . '%')->orWhere('restaurant', 'like', '%' . $request->string('q') . '%'));
        return response()->json(['data' => $query->paginate(20)]);
    }

    /**
     * Handle the force close operation.
     * @param Campaign $campaign Parameter value.
     * @param CloseCampaignAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function forceClose(Campaign $campaign, CloseCampaignAction $action): JsonResponse
    {
        return response()->json(['data' => $action->execute($campaign)]);
    }

    /**
     * Handle the force cancel operation.
     * @param Campaign $campaign Parameter value.
     * @param AuditService $audit Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function forceCancel(Campaign $campaign, AuditService $audit): JsonResponse
    {
        return response()->json([
            'data' => DB::transaction(function () use ($campaign, $audit): Campaign {
                $before = ['status' => $campaign->status?->value];
                abort_if($campaign->status?->value === 'closed', 422, __('superadmin.actions.campaign_already_closed'));
                $campaign->update(['status' => 'cancelled', 'closed_at' => now()]);
                $audit->record('campaign.force_cancelled', 'campaign', $campaign->id, $campaign->room_id, $before, ['status' => 'cancelled']);
                return $campaign->fresh();
            })
        ]);
    }
}

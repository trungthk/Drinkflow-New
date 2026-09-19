<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublicOrderLookupRequest;
use App\Models\Campaign;
use App\Services\Order\PublicOrderCheckService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class OrderCheckController extends Controller
{
    /**
     * Show the signed public order-check page.
     *
     * @param Campaign $campaign Campaign from the signed URL.
     * @param string $hash URL hash segment.
     * @param PublicOrderCheckService $service Order-check service.
     * @return View Rendered lookup page.
     */
    public function page(Campaign $campaign, string $hash, PublicOrderCheckService $service): View
    {
        abort_unless($service->hashMatches($campaign, $hash) && $service->isExpired($campaign), 404);

        return view('public.order-check', [
            'campaign' => $campaign,
            'lookupUrl' => route('public.order-check.lookup', [$campaign->id, $hash]).'?'.request()->getQueryString(),
        ]);
    }

    /**
     * Look up orders belonging to the signed campaign.
     *
     * @param PublicOrderLookupRequest $request Validated lookup request.
     * @param Campaign $campaign Campaign from the signed URL.
     * @param string $hash URL hash segment.
     * @param PublicOrderCheckService $service Order-check service.
     * @return JsonResponse Matching order summaries.
     */
    public function lookup(PublicOrderLookupRequest $request, Campaign $campaign, string $hash, PublicOrderCheckService $service): JsonResponse
    {
        abort_unless($service->hashMatches($campaign, $hash) && $service->isExpired($campaign), 404);
        $orders = $service->findOrders($campaign, (string) $request->validated('identifier'));

        if ($orders->isEmpty()) {
            return response()->json(['message' => __('public.order_check_not_found')], 404);
        }

        return response()->json([
            'campaign' => [
                'name' => $campaign->name,
                'restaurant' => $campaign->restaurant,
                'started_at' => $campaign->started_at?->toIso8601String(),
                'closed_at' => $campaign->closed_at?->toIso8601String(),
            ],
            'orders' => $service->summaries($orders),
            'items' => $service->itemTotals($orders),
        ]);
    }
}

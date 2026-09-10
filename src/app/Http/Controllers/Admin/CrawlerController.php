<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Campaign\ImportCampaignItemsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CrawlerPreviewRequest;
use App\Http\Requests\ImportCampaignItemsRequest;
use App\Models\Campaign;
use App\Models\CrawlerPreview;
use App\Services\Crawler\FoodCrawlerService;
use Illuminate\Http\JsonResponse;

class CrawlerController extends Controller
{
    /**
     * Handle the preview operation.
     * @param CrawlerPreviewRequest $request Parameter value.
     * @param FoodCrawlerService $crawler Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function preview(CrawlerPreviewRequest $request, FoodCrawlerService $crawler): JsonResponse
    {
        $items = $crawler->preview($request->validated('url'));
        $preview = CrawlerPreview::create([
            'room_id' => request()->attributes->get('room')->id,
            'admin_id' => request()->user('admin')->id,
            'source_url' => $request->validated('url'),
            'items' => $items,
            'expires_at' => now()->addMinutes(30),
        ]);

        return response()->json(['data' => ['preview_id' => $preview->id, 'source_url' => $preview->source_url, 'items' => $items, 'expires_at' => $preview->expires_at]], 201);
    }

    /**
     * Handle the import operation.
     * @param ImportCampaignItemsRequest $request Parameter value.
     * @param Campaign $campaign Parameter value.
     * @param ImportCampaignItemsAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function import(ImportCampaignItemsRequest $request, Campaign $campaign, ImportCampaignItemsAction $action): JsonResponse
    {
        abort_unless($campaign->room_id === request()->attributes->get('room')->id, 404);
        if ($campaign->status?->value === 'closed') {
            abort(422, 'Campaign Ä‘Ă£ Ä‘Ă³ng.');
        }
        $items = $request->validated('items');
        if ($request->filled('preview_id')) {
            $preview = CrawlerPreview::query()->whereKey($request->integer('preview_id'))->where('room_id', $campaign->room_id)->where('admin_id', request()->user('admin')->id)->where('expires_at', '>', now())->firstOrFail();
            $sourceUrl = $preview->source_url;
        } else {
            $sourceUrl = $request->validated('source_url');
        }

        return response()->json(['data' => $action->execute($campaign, $sourceUrl, $items)], 201);
    }
}

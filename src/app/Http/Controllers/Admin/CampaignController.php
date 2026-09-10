<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Campaign\CloseCampaignAction;
use App\Actions\Campaign\CreateCampaignAction;
use App\Actions\Campaign\CreateCampaignItemAction;
use App\Actions\Campaign\CreateItemOptionAction;
use App\Actions\Campaign\DuplicateCampaignAction;
use App\Actions\Campaign\SplitCampaignBillAction;
use App\Actions\Campaign\TransitionCampaignAction;
use App\Actions\Campaign\UpdateCampaignItemAction;
use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Http\Requests\SplitBillRequest;
use App\Http\Requests\StoreCampaignItemRequest;
use App\Http\Requests\StoreCampaignRequest;
use App\Http\Requests\StoreItemOptionRequest;
use App\Http\Requests\UpdateCampaignRequest;
use App\Http\Requests\UpdateItemOptionRequest;
use App\Models\Campaign;
use App\Models\CampaignItem;
use App\Models\CampaignItemSize;
use App\Models\CampaignItemTopping;
use App\Services\Audit\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    /**
     * Handle the index operation.
     * @param Request $request Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function index(Request $request): JsonResponse
    {
        $room = $request->attributes->get('room');
        $query = Campaign::query()->where('room_id', $room->id)->withCount('orders')->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))->latest();
        return response()->json(['data' => $query->paginate(20)]);
    }

    /**
     * Handle the show operation.
     * @param Room $room Parameter value.
     * @param Campaign $campaign Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function show(Room $room, Campaign $campaign): JsonResponse
    {
        $this->assertCampaign($campaign);
        return response()->json(['data' => $campaign->load(['items.sizes', 'items.toppings', 'paymentAccount', 'orders.roomUser.globalUser'])]);
    }

    /**
     * Handle the update operation.
     * @param UpdateCampaignRequest $request Parameter value.
     * @param Room $room Parameter value.
     * @param Campaign $campaign Parameter value.
     * @param AuditService $audit Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function update(UpdateCampaignRequest $request, Room $room, Campaign $campaign, AuditService $audit): JsonResponse
    {
        $this->assertCampaign($campaign);
        $data = $request->validated();
        if (isset($data['payment_account_id']) && $data['payment_account_id'] !== null && ! $campaign->room->paymentAccounts()->whereKey($data['payment_account_id'])->where('status', 'active')->exists()) {
            abort(422, 'TĂ i khoáº£n thanh toĂ¡n khĂ´ng thuá»™c Room hoáº·c Ä‘Ă£ disabled.');
        }
        $before = $campaign->toArray();
        $campaign->update(collect($data)->except(['status'])->all());
        $audit->record('campaign.updated', 'campaign', $campaign->id, $campaign->room_id, $before, $campaign->fresh()->toArray());
        return response()->json(['data' => $campaign->fresh(['items', 'paymentAccount'])]);
    }

    /**
     * Handle the store operation.
     * @param StoreCampaignRequest $request Parameter value.
     * @param CreateCampaignAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function store(StoreCampaignRequest $request, CreateCampaignAction $action): JsonResponse
    {
        $room = request()->attributes->get('room');
        return response()->json(['data' => $action->execute($room, $request->validated(), request()->user('admin')->id)], 201);
    }

    /**
     * Handle the activate operation.
     * @param Room $room Parameter value.
     * @param Campaign $campaign Parameter value.
     * @param TransitionCampaignAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function activate(Room $room, Campaign $campaign, TransitionCampaignAction $action): JsonResponse
    {
        $this->assertCampaign($campaign);
        return response()->json(['data' => $action->activate($campaign)]);
    }

    /**
     * Handle the cancel operation.
     * @param Room $room Parameter value.
     * @param Campaign $campaign Parameter value.
     * @param TransitionCampaignAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function cancel(Room $room, Campaign $campaign, TransitionCampaignAction $action): JsonResponse
    {
        $this->assertCampaign($campaign);
        return response()->json(['data' => $action->cancel($campaign)]);
    }

    /**
     * Handle the archive operation.
     * @param Room $room Parameter value.
     * @param Campaign $campaign Parameter value.
     * @param TransitionCampaignAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function archive(Room $room, Campaign $campaign, TransitionCampaignAction $action): JsonResponse
    {
        $this->assertCampaign($campaign);
        return response()->json(['data' => $action->archive($campaign)]);
    }

    /**
     * Handle the duplicate operation.
     * @param Room $room Parameter value.
     * @param Campaign $campaign Parameter value.
     * @param DuplicateCampaignAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function duplicate(Room $room, Campaign $campaign, DuplicateCampaignAction $action): JsonResponse
    {
        $this->assertCampaign($campaign);
        return response()->json(['data' => $action->execute($campaign, request()->user('admin')->id)], 201);
    }

    /**
     * Handle the close operation.
     * @param Room $room Parameter value.
     * @param Campaign $campaign Parameter value.
     * @param CloseCampaignAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function close(Room $room, Campaign $campaign, CloseCampaignAction $action): JsonResponse
    {
        $this->assertCampaign($campaign);
        return response()->json(['data' => $action->execute($campaign)]);
    }

    /**
     * Handle the split bill operation.
     * @param SplitBillRequest $request Parameter value.
     * @param Room $room Parameter value.
     * @param Campaign $campaign Parameter value.
     * @param SplitCampaignBillAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function splitBill(SplitBillRequest $request, Room $room, Campaign $campaign, SplitCampaignBillAction $action): JsonResponse
    {
        $this->assertCampaign($campaign);
        return response()->json(['data' => $action->execute($campaign, $request->validated('method'), $request->validated('allocations', []))]);
    }

    /**
     * Handle the store item operation.
     * @param StoreCampaignItemRequest $request Parameter value.
     * @param Campaign $campaign Parameter value.
     * @param CreateCampaignItemAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function storeItem(StoreCampaignItemRequest $request, Campaign $campaign, CreateCampaignItemAction $action): JsonResponse
    {
        $this->assertCampaign($campaign);
        return response()->json(['data' => $action->execute($campaign, $request->validated())], 201);
    }

    /**
     * Handle the update item operation.
     * @param StoreCampaignItemRequest $request Parameter value.
     * @param Room $room Parameter value.
     * @param Campaign $campaign Parameter value.
     * @param CampaignItem $item Parameter value.
     * @param UpdateCampaignItemAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function updateItem(StoreCampaignItemRequest $request, Room $room, Campaign $campaign, CampaignItem $item, UpdateCampaignItemAction $action): JsonResponse
    {
        $this->assertItem($campaign, $item);
        return response()->json(['data' => $action->execute($item, $request->validated())]);
    }

    /**
     * Handle the archive item operation.
     * @param Room $room Parameter value.
     * @param Campaign $campaign Parameter value.
     * @param CampaignItem $item Parameter value.
     * @param AuditService $audit Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function archiveItem(Room $room, Campaign $campaign, CampaignItem $item, AuditService $audit): JsonResponse
    {
        $this->assertItem($campaign, $item);
        $before = $item->status;
        $item->update(['status' => 'hidden']);
        $audit->record('campaign_item.archived', 'campaign_item', $item->id, $campaign->room_id, ['status' => $before], ['status' => 'hidden']);
        return response()->json(['data' => $item->fresh()]);
    }

    /**
     * Handle the store topping operation.
     * @param StoreItemOptionRequest $request Parameter value.
     * @param Campaign $campaign Parameter value.
     * @param CampaignItem $item Parameter value.
     * @param CreateItemOptionAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function storeTopping(StoreItemOptionRequest $request, Campaign $campaign, CampaignItem $item, CreateItemOptionAction $action): JsonResponse
    {
        $this->assertItem($campaign, $item);
        return response()->json(['data' => $action->topping($item, $request->validated())], 201);
    }

    /**
     * Handle the store size operation.
     * @param StoreItemOptionRequest $request Parameter value.
     * @param Campaign $campaign Parameter value.
     * @param CampaignItem $item Parameter value.
     * @param CreateItemOptionAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function storeSize(StoreItemOptionRequest $request, Campaign $campaign, CampaignItem $item, CreateItemOptionAction $action): JsonResponse
    {
        $this->assertItem($campaign, $item);
        return response()->json(['data' => $action->size($item, $request->validated())], 201);
    }

    /**
     * Handle the update topping operation.
     * @param UpdateItemOptionRequest $request Parameter value.
     * @param Room $room Parameter value.
     * @param Campaign $campaign Parameter value.
     * @param CampaignItem $item Parameter value.
     * @param CampaignItemTopping $option Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function updateTopping(UpdateItemOptionRequest $request, Room $room, Campaign $campaign, CampaignItem $item, CampaignItemTopping $option): JsonResponse
    {
        $this->assertItem($campaign, $item);
        abort_unless($option->campaign_item_id === $item->id, 404);
        $option->update($request->validated());
        return response()->json(['data' => $option->fresh()]);
    }

    /**
     * Handle the update size operation.
     * @param UpdateItemOptionRequest $request Parameter value.
     * @param Room $room Parameter value.
     * @param Campaign $campaign Parameter value.
     * @param CampaignItem $item Parameter value.
     * @param CampaignItemSize $option Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function updateSize(UpdateItemOptionRequest $request, Room $room, Campaign $campaign, CampaignItem $item, CampaignItemSize $option): JsonResponse
    {
        $this->assertItem($campaign, $item);
        abort_unless($option->campaign_item_id === $item->id, 404);
        $option->update($request->validated());
        return response()->json(['data' => $option->fresh()]);
    }

    /**
     * Handle the delete topping operation.
     * @param Campaign $campaign Parameter value.
     * @param CampaignItem $item Parameter value.
     * @param CampaignItemTopping $option Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function deleteTopping(Campaign $campaign, CampaignItem $item, CampaignItemTopping $option): JsonResponse
    {
        $this->assertItem($campaign, $item);
        abort_unless($option->campaign_item_id === $item->id, 404);
        $option->update(['status' => 'hidden']);
        return response()->json(['data' => ['hidden' => true]]);
    }

    /**
     * Handle the delete size operation.
     * @param Campaign $campaign Parameter value.
     * @param CampaignItem $item Parameter value.
     * @param CampaignItemSize $option Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function deleteSize(Campaign $campaign, CampaignItem $item, CampaignItemSize $option): JsonResponse
    {
        $this->assertItem($campaign, $item);
        abort_unless($option->campaign_item_id === $item->id, 404);
        $option->update(['status' => 'hidden']);
        return response()->json(['data' => ['hidden' => true]]);
    }

    /**
     * Handle the aggregate operation.
     * @param Request $request Parameter value.
     * @param \App\Services\Admin\OrderAggregationService $aggregator Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function aggregate(Request $request, \App\Services\Admin\OrderAggregationService $aggregator): JsonResponse
    {
        return response()->json(['data' => $aggregator->forRoom(request()->attributes->get('room')->id, $request->integer('campaign_id'))]);
    }

    /**
     * Handle the export aggregate operation.
     * @param Request $request Parameter value.
     * @param \App\Services\Admin\OrderAggregationService $aggregator Parameter value.
     * @return mixed Result of the operation.
     */
    public function exportAggregate(Request $request, \App\Services\Admin\OrderAggregationService $aggregator)
    {
        $rows = $aggregator->forRoom(request()->attributes->get('room')->id, $request->integer('campaign_id'));
        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Item', 'Size', 'Toppings', 'Quantity']);
            foreach ($rows as $row) fputcsv($handle, [$row['name'], $row['size'], $row['toppings'], $row['quantity']]);
            fclose($handle);
        }, 'drinkflow-aggregator.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Handle the assert campaign operation.
     * @param Campaign $campaign Parameter value.
     * @return void Result of the operation.
     */
    private function assertCampaign(Campaign $campaign): void
    {
        abort_unless($campaign->room_id === request()->attributes->get('room')->id, 404);
    }

    /**
     * Handle the assert item operation.
     * @param Campaign $campaign Parameter value.
     * @param CampaignItem $item Parameter value.
     * @return void Result of the operation.
     */
    private function assertItem(Campaign $campaign, CampaignItem $item): void
    {
        $this->assertCampaign($campaign);
        abort_unless($item->campaign_id === $campaign->id, 404);
    }
}

<?php
namespace App\Http\Controllers\Admin;
use App\Actions\Campaign\CloseCampaignAction;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Actions\Campaign\CreateCampaignAction;
use App\Http\Requests\StoreCampaignRequest;
use App\Http\Requests\StoreCampaignItemRequest;
use App\Actions\Campaign\CreateCampaignItemAction;
use App\Actions\Campaign\UpdateCampaignItemAction;
use App\Actions\Campaign\CreateItemOptionAction;
use App\Http\Requests\StoreItemOptionRequest;
use Illuminate\Http\JsonResponse;
class CampaignController extends Controller {
    public function index(): JsonResponse { $room=request()->attributes->get('room'); return response()->json(['data'=>Campaign::where('room_id',$room->id)->withCount('orders')->latest()->paginate(20)]); }
    public function storeTopping(StoreItemOptionRequest $request, Campaign $campaign, \App\Models\CampaignItem $item, CreateItemOptionAction $action): JsonResponse { abort_unless($campaign->room_id===request()->attributes->get('room')->id && $item->campaign_id===$campaign->id,404); return response()->json(['data'=>$action->topping($item,$request->validated())],201); }
    public function storeSize(StoreItemOptionRequest $request, Campaign $campaign, \App\Models\CampaignItem $item, CreateItemOptionAction $action): JsonResponse { abort_unless($campaign->room_id===request()->attributes->get('room')->id && $item->campaign_id===$campaign->id,404); return response()->json(['data'=>$action->size($item,$request->validated())],201); }
    public function updateItem(StoreCampaignItemRequest $request, Campaign $campaign, \App\Models\CampaignItem $item, UpdateCampaignItemAction $action): JsonResponse { abort_unless($campaign->room_id===request()->attributes->get('room')->id && $item->campaign_id===$campaign->id,404); return response()->json(['data'=>$action->execute($item,$request->validated())]); }
    public function archiveItem(Campaign $campaign, \App\Models\CampaignItem $item): JsonResponse { abort_unless($campaign->room_id===request()->attributes->get('room')->id && $item->campaign_id===$campaign->id,404); $item->update(['status'=>'hidden']); return response()->json(['data'=>$item->fresh()]); }
    public function storeItem(StoreCampaignItemRequest $request, Campaign $campaign, CreateCampaignItemAction $action): JsonResponse { abort_unless($campaign->room_id===request()->attributes->get('room')->id,404); return response()->json(['data'=>$action->execute($campaign,$request->validated())],201); }
    public function store(StoreCampaignRequest $request, CreateCampaignAction $action): JsonResponse { $room=request()->attributes->get('room'); return response()->json(['data'=>$action->execute($room,$request->validated(),request()->user('admin')->id)],201); }
    public function close(Campaign $campaign, CloseCampaignAction $action): JsonResponse {
        abort_unless($campaign->room_id === request()->attributes->get('room')->id, 404);
        return response()->json(['data' => $action->execute($campaign)]);
    }
}

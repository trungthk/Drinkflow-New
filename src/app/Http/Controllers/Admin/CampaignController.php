<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Campaign\CloseCampaignAction;
use App\Actions\Campaign\CreateCampaignAction;
use App\Actions\Campaign\CreateCampaignItemAction;
use App\Actions\Campaign\CreateItemOptionAction;
use App\Actions\Campaign\DuplicateCampaignAction;
use App\Actions\Campaign\SplitCampaignBillAction;
use App\Actions\Campaign\TransitionCampaignAction;
use App\Actions\Campaign\UpdateCampaignItemAction;
use App\Enums\PaymentAccountStatus;
use App\Enums\CampaignStatus;
use App\Enums\RoomUserStatus;
use App\Events\RoomRealtimeEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\CloseCampaignRequest;
use App\Http\Requests\SplitBillRequest;
use App\Http\Requests\StoreCampaignItemRequest;
use App\Http\Requests\StoreCampaignImageRequest;
use App\Http\Requests\StoreCampaignRequest;
use App\Http\Requests\StoreItemOptionRequest;
use App\Http\Requests\UpdateCampaignRequest;
use App\Http\Requests\UpdateItemOptionRequest;
use App\Models\Campaign;
use App\Models\CampaignItem;
use App\Models\CampaignItemSize;
use App\Models\CampaignItemTopping;
use App\Models\Room;
use App\Services\Audit\AuditService;
use App\Services\Media\ImageUploadService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CampaignController extends Controller
{
    /**
     * Display a listing of campaigns for the current room.
     *
     * @param Request $request Incoming HTTP request.
     * @return JsonResponse Paginated campaign list.
     */
    public function index(Request $request): JsonResponse
    {
        $room = $request->attributes->get('room');
        $query = Campaign::query()
            ->where('room_id', $room->id)
            ->withCount('orders')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->latest();

        return response()->json(['data' => $query->paginate(20)]);
    }

    /**
     * Display the standalone Campaigns management view.
     *
     * @param Request $request Incoming HTTP request.
     * @param Room $room Room entity.
     * @return View Blade view.
     * @throws \Illuminate\Validation\ValidationException When filter input is invalid.
     */
    public function page(Request $request, Room $room): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(array_merge(
                ['all'],
                array_map(static fn (CampaignStatus $status): string => $status->value, CampaignStatus::cases())
            ))],
        ]);
        $query = Campaign::query()
            ->where('room_id', $room->id)
            ->withCount('orders')
            ->latest();

        $search = trim((string) ($validated['search'] ?? ''));
        if ($search !== '') {
            $normalizedSearch = mb_strtolower($search);
            $query->where(function ($campaignQuery) use ($normalizedSearch): void {
                $campaignQuery->whereRaw('LOWER(name) LIKE ?', ['%' . $normalizedSearch . '%'])
                    ->orWhereRaw('LOWER(restaurant) LIKE ?', ['%' . $normalizedSearch . '%'])
                    ->orWhereRaw('LOWER(description) LIKE ?', ['%' . $normalizedSearch . '%']);
            });
        }

        $status = (string) ($validated['status'] ?? 'all');
        if ($status !== '' && $status !== 'all') {
            $query->where('status', $status);
        }

        $campaigns = $query->paginate(20)->withQueryString();

        return view('admin.campaigns', [
            'room' => $room,
            'campaigns' => $campaigns,
            'statusFilters' => collect(CampaignStatus::cases())
                ->filter(static fn (CampaignStatus $status): bool => in_array($status, [
                    CampaignStatus::Active,
                    CampaignStatus::Scheduled,
                    CampaignStatus::Closed,
                    CampaignStatus::Archived,
                ], true))
                ->map(static fn (CampaignStatus $status): array => [
                    'value' => $status->value,
                    'label' => __('admin.filter_'.$status->value),
                ])->values()->all(),
            'filters' => [
                'search' => $search,
                'status' => $status !== '' ? $status : 'all',
            ],
        ]);
    }

    /**
     * Show the campaign creation interface.
     *
     * @param Request $request Incoming HTTP request.
     * @param Room $room Room entity.
     * @return View|JsonResponse Blade view or JSON response.
     */
    public function create(Request $request, Room $room): View|JsonResponse
    {
        $room = $request->attributes->get('room') ?? $room;
        $paymentAccounts = $room->paymentAccounts()->where('status', PaymentAccountStatus::Active)->get();
        $settings = $room->roomSettings()
            ->whereIn('key', ['campaign_title_template', 'max_campaign_budget', 'default_payment_account_id', 'default_sponsor'])
            ->get()
            ->keyBy('key');
        $titleTemplate = (string) ($settings->get('campaign_title_template')?->value ?? ('['.$room->name.'] Trà chiều & Cafe {date}'));
        $creatorName = (string) ($request->user('admin')?->name ?? '');
        $campaignDefaults = [
            'name' => strtr($titleTemplate, [
                '{date}' => now()->format('d/m/Y'),
                '{time}' => now()->format('H:i'),
                '{day_of_week}' => now()->translatedFormat('l'),
                '{creator_name}' => $creatorName,
            ]),
            'max_budget' => (int) ($settings->get('max_campaign_budget')?->value ?? 2_000_000),
            'payment_account_id' => (int) ($settings->get('default_payment_account_id')?->value ?? 0),
            'sponsor_name' => (string) ($settings->get('default_sponsor')?->value ?? ''),
        ];
        if (! $paymentAccounts->contains('id', $campaignDefaults['payment_account_id'])) {
            $campaignDefaults['payment_account_id'] = (int) ($paymentAccounts->first()?->id ?? 0);
        }
        $roomUsers = $room->roomUsers()->with('globalUser')->where('status', RoomUserStatus::Active)->get();
        $previousCampaigns = Campaign::query()
            ->where('room_id', $room->id)
            ->whereHas('items')
            ->with(['items.sizes', 'items.toppings'])
            ->latest()
            ->take(10)
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'room' => $room,
                'payment_accounts' => $paymentAccounts,
                'room_users' => $roomUsers,
                'previous_campaigns' => $previousCampaigns,
                'campaign_defaults' => $campaignDefaults,
            ]);
        }

        return view('admin.campaign-create', [
            'room' => $room,
            'paymentAccounts' => $paymentAccounts,
            'roomUsers' => $roomUsers,
            'previousCampaigns' => $previousCampaigns,
            'campaignDefaults' => $campaignDefaults,
        ]);
    }

    /**
     * Retrieve previous campaigns with menu items for fast reuse.
     *
     * @param Request $request Incoming HTTP request.
     * @param Room $room Room entity.
     * @return JsonResponse List of previous campaigns and their menu items.
     */
    public function previousMenus(Request $request, Room $room): JsonResponse
    {
        $campaigns = Campaign::query()
            ->where('room_id', $room->id)
            ->whereHas('items')
            ->with(['items.sizes', 'items.toppings'])
            ->latest()
            ->take(15)
            ->get();

        return response()->json(['data' => $campaigns]);
    }

    /**
     * Display the specified campaign details or JSON representation.
     *
     * @param Request $request Incoming HTTP request.
     * @param Room $room Room entity.
     * @param Campaign $campaign Campaign entity.
     * @param \App\Services\Admin\AdminCampaignDetailService $detailService Campaign detail service.
     * @return JsonResponse|View Response payload or Blade view.
     */
    public function show(Request $request, Room $room, Campaign $campaign, \App\Services\Admin\AdminCampaignDetailService $detailService): JsonResponse|View
    {
        $this->assertCampaign($campaign);

        if ($request->expectsJson()) {
            $campaign->load(['items.sizes', 'items.toppings', 'paymentAccount', 'orders.roomUser.globalUser', 'orders.items', 'debts.roomUser.globalUser']);
            return response()->json(['data' => $campaign]);
        }

        $data = $detailService->getCampaignViewData($room, $campaign);
        $viewName = ($request->query('view') === 'live' || (in_array($campaign->status, ['active', 'closing', 'scheduled']) && $request->query('view') !== 'detail'))
            ? 'admin.campaign-live'
            : 'admin.campaign-detail';

        return view($viewName, $data);
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
        if (isset($data['payment_account_id']) && $data['payment_account_id'] !== null && ! $campaign->room->paymentAccounts()->whereKey($data['payment_account_id'])->where('status', PaymentAccountStatus::Active)->exists()) {
            abort(422, __('admin.invalid_payment_account'));
        }
        $before = $campaign->toArray();
        $campaign->update(collect($data)->except(['status'])->all());
        $audit->record('campaign.updated', 'campaign', $campaign->id, $campaign->room_id, $before, $campaign->fresh()->toArray());
        $this->publishCampaignEvent('campaign.updated', $campaign);
        return response()->json(['data' => $campaign->fresh(['items', 'paymentAccount'])]);
    }

    /**
     * Permanently delete an archived campaign that has no financial history.
     *
     * @param Room $room Current room.
     * @param Campaign $campaign Campaign to delete.
     * @return JsonResponse Deletion result.
     */
    public function destroy(Room $room, Campaign $campaign): JsonResponse
    {
        $this->assertCampaign($campaign);
        abort_unless(in_array($campaign->status, [CampaignStatus::Draft, CampaignStatus::Active, CampaignStatus::Closing, CampaignStatus::Archived], true), 422, __('admin.campaign_delete_archived_only'));
        abort_if($campaign->orders()->exists() || $campaign->debts()->exists(), 422, __('admin.campaign_delete_has_history'));

        $campaign->delete();
        $this->publishCampaignEvent('campaign.deleted', $campaign);

        return response()->json(['data' => ['deleted' => true]]);
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
     * Upload and optimize a temporary menu image for the campaign creator.
     *
     * @param StoreCampaignImageRequest $request Validated image upload request.
     * @param ImageUploadService $imageUploadService Shared image optimization service.
     * @return JsonResponse Public URL of the optimized image.
     */
    public function uploadImage(StoreCampaignImageRequest $request, ImageUploadService $imageUploadService): JsonResponse
    {
        $imageUrl = $imageUploadService->uploadCampaignImage($request->file('image'));

        return response()->json(['data' => ['url' => url($imageUrl)]], 201);
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
        $updated = $action->cancel($campaign);
        $this->publishCampaignEvent('campaign.deleted', $updated);

        return response()->json(['data' => $updated]);
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
        $updated = $action->archive($campaign);
        $this->publishCampaignEvent('campaign.deleted', $updated);

        return response()->json(['data' => $updated]);
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
     * Handle the close campaign operation with optional allow_debt.
     *
     * @param Request $request Incoming HTTP request.
     * @param Room $room Room entity.
     * @param Campaign $campaign Campaign entity.
     * @param CloseCampaignAction $action Close campaign action.
     * @return JsonResponse Response containing updated campaign payload.
     */
    public function close(CloseCampaignRequest $request, Room $room, Campaign $campaign, CloseCampaignAction $action): JsonResponse
    {
        $this->assertCampaign($campaign);
        $data = $request->validated();
        $allowDebt = (bool) ($data['allow_debt'] ?? true);
        $reason = $data['reason'] ?? null;
        $reasonString = is_string($reason) && trim($reason) !== '' ? trim($reason) : null;
        return response()->json(['data' => $action->execute($campaign, $allowDebt, $reasonString)]);
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
        $item = $action->execute($campaign, $request->validated());
        $this->publishMenuEvent('campaign.menu.updated', $campaign, $item);

        return response()->json(['data' => $item], 201);
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
        $updated = $action->execute($item, $request->validated());
        $this->publishMenuEvent('campaign.menu.updated', $campaign, $updated);

        return response()->json(['data' => $updated]);
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
        $this->publishMenuEvent('campaign.menu.deleted', $campaign, $item);
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

    /**
     * Publish a campaign lifecycle change to the room's authorized socket clients.
     *
     * @param string $event Realtime event name.
     * @param Campaign $campaign Changed campaign.
     * @return void
     */
    private function publishCampaignEvent(string $event, Campaign $campaign): void
    {
        RoomRealtimeEvent::dispatch($event, $campaign->room_id, [
            'campaign_id' => $campaign->id,
            'status' => $campaign->status?->value,
        ]);
    }

    /**
     * Publish a menu change to the room's authorized socket clients.
     *
     * @param string $event Realtime event name.
     * @param Campaign $campaign Parent campaign.
     * @param CampaignItem $item Changed menu item.
     * @return void
     */
    private function publishMenuEvent(string $event, Campaign $campaign, CampaignItem $item): void
    {
        RoomRealtimeEvent::dispatch($event, $campaign->room_id, [
            'campaign_id' => $campaign->id,
            'item_id' => $item->id,
            'status' => $item->status instanceof \BackedEnum ? $item->status->value : (string) $item->status,
        ]);
    }
}

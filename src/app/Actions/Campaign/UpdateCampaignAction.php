<?php

declare(strict_types=1);

namespace App\Actions\Campaign;

use App\Enums\CampaignItemStatus;
use App\Enums\CampaignStatus;
use App\Enums\PaymentAccountStatus;
use App\Events\CampaignCreated;
use App\Events\CampaignUpdated;
use App\Models\Campaign;
use App\Models\CampaignItem;
use App\Models\PaymentAccount;
use App\Models\Room;
use App\Models\RoomUser;
use App\Services\Audit\AuditService;
use App\Services\Media\ImageUploadService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateCampaignAction
{
    /**
     * Create the action instance.
     *
     * @param CreateCampaignItemAction $createItemAction Item creation action.
     * @param AuditService $auditService Audit logger service.
     * @param ImageUploadService $imageUploadService Image upload and storage cleanup service.
     */
    public function __construct(
        private readonly CreateCampaignItemAction $createItemAction,
        private readonly AuditService $auditService,
        private readonly ImageUploadService $imageUploadService
    ) {
    }

    /**
     * Update an existing campaign and synchronize its menu items.
     *
     * @param Campaign $campaign Campaign instance to update.
     * @param array<string, mixed> $data Validated input payload.
     * @param int|null $adminId Identifier of the admin performing the update.
     * @return Campaign Fresh campaign instance with relations loaded.
     * @throws ValidationException When business invariants are violated.
     */
    public function execute(Campaign $campaign, array $data, ?int $adminId = null): Campaign
    {
        /** @var Room $room */
        $room = $campaign->room;

        $sponsorType = (string) ($data['sponsor_type'] ?? $campaign->sponsor_type ?? Campaign::SPONSOR_TYPE_NONE);
        if ($sponsorType === Campaign::SPONSOR_TYPE_NONE) {
            $data['sponsor_allocations'] = [];
        } elseif (array_key_exists('sponsor_allocations', $data) && $sponsorType === Campaign::SPONSOR_TYPE_FULL) {
            /** @var array<int, array<string, mixed>> $allocationsList */
            $allocationsList = $data['sponsor_allocations'] ?? [];
            $allocations = collect($allocationsList);
            $percentageTotal = (float) $allocations->sum(static fn(array $allocation): float => (float) ($allocation['percentage'] ?? 0));
            $roomUserIds = $allocations->pluck('room_user_id')->map(static fn(mixed $id): int => (int) $id);

            if ($allocations->isEmpty() || abs($percentageTotal - 100.0) > 0.01) {
                throw ValidationException::withMessages([
                    'sponsor_allocations' => __('admin.sponsor_percentage_total_invalid'),
                ]);
            }
            if ($roomUserIds->count() !== $roomUserIds->unique()->count()) {
                throw ValidationException::withMessages([
                    'sponsor_allocations' => __('admin.sponsor_duplicate_user'),
                ]);
            }
            $roomUsersCount = RoomUser::query()
                ->where('room_id', $room->id)
                ->whereIn('id', $roomUserIds->all())
                ->count();
            if ($roomUsersCount !== $roomUserIds->count()) {
                throw ValidationException::withMessages([
                    'sponsor_allocations' => __('admin.sponsor_user_not_in_room'),
                ]);
            }
        }

        if (!empty($data['payment_account_id'])) {
            $accountValid = PaymentAccount::query()
                ->whereKey($data['payment_account_id'])
                ->where('room_id', $room->id)
                ->where('status', PaymentAccountStatus::Active)
                ->exists();

            if (!$accountValid) {
                throw ValidationException::withMessages([
                    'payment_account_id' => __('admin.invalid_payment_account'),
                ]);
            }
        }

        if (array_key_exists('discount', $data) && $data['discount'] !== null) {
            $deliveryFee = array_key_exists('delivery_fee', $data) ? (int) $data['delivery_fee'] : (int) ($campaign->delivery_fee ?? 0);
            $grossSubtotal = (int) $campaign->orders()->whereNotIn('status', [\App\Enums\OrderStatus::Cancelled->value])->sum('subtotal');
            $discount = (int) $data['discount'];
            $maxAllowedDiscount = $grossSubtotal + $deliveryFee;
            if ($grossSubtotal > 0 && $discount > $maxAllowedDiscount) {
                throw ValidationException::withMessages([
                    'discount' => __('admin.discount_cannot_exceed_subtotal_plus_fee', [
                        'max' => number_format($maxAllowedDiscount, 0, ',', '.'),
                    ]),
                ]);
            }
        }

        $hasItems = array_key_exists('items', $data);
        /** @var array<int, array<string, mixed>> $items */
        $items = $data['items'] ?? [];
        unset($data['items']);

        $previousStatus = $campaign->status;

        $updated = DB::transaction(function () use ($campaign, $data, $hasItems, $items, $previousStatus): Campaign {
            $before = $campaign->toArray();
            $updateData = collect($data)->all();
            if (isset($data['status'])) {
                $statusValue = $data['status'] instanceof \BackedEnum ? $data['status']->value : (string) $data['status'];
                if ($statusValue === CampaignStatus::Active->value && empty($campaign->started_at)) {
                    $updateData['started_at'] = now();
                }
            }
            $campaign->update($updateData);

            if ($hasItems) {
                $existingItems = $campaign->items()->with(['toppings', 'sizes'])->get()->keyBy('id');
                $processedItemIds = [];

                foreach ($items as $sortOrder => $itemData) {
                    $itemId = isset($itemData['id']) ? (int) $itemData['id'] : null;
                    /** @var CampaignItem|null $item */
                    $item = ($itemId !== null && $existingItems->has($itemId)) ? $existingItems->get($itemId) : null;
                    $price = (int) ($itemData['price'] ?? $itemData['base_price'] ?? 0);

                    if ($item !== null) {
                        $oldImageUrl = $item->image_url;
                        $newImageUrl = $itemData['image_url'] ?? null;
                        if (!empty($oldImageUrl) && $oldImageUrl !== $newImageUrl) {
                            $this->imageUploadService->deleteFile($oldImageUrl);
                        }

                        $item->update([
                            'name' => $itemData['name'],
                            'category' => $itemData['category'] ?? null,
                            'description' => $itemData['description'] ?? null,
                            'image_url' => $newImageUrl,
                            'base_price' => $price,
                            'status' => $itemData['status'] ?? $item->status ?? CampaignItemStatus::Active->value,
                            'sort_order' => $sortOrder,
                        ]);
                        $item->toppings()->delete();
                        $item->sizes()->delete();
                    } else {
                        $item = $this->createItemAction->execute($campaign, [
                            'name' => $itemData['name'],
                            'category' => $itemData['category'] ?? null,
                            'description' => $itemData['description'] ?? null,
                            'image_url' => $itemData['image_url'] ?? null,
                            'base_price' => $price,
                            'status' => $itemData['status'] ?? CampaignItemStatus::Active->value,
                            'sort_order' => $sortOrder,
                        ]);
                    }

                    $processedItemIds[] = $item->id;

                    foreach ($itemData['toppings'] ?? [] as $toppingOrder => $topping) {
                        $item->toppings()->create([
                            'name' => $topping['name'],
                            'price' => (int) ($topping['price'] ?? 0),
                            'sort_order' => $toppingOrder,
                        ]);
                    }

                    $options = $itemData['options'] ?? $itemData['sizes'] ?? [];
                    foreach ($options as $optionOrder => $option) {
                        $item->sizes()->create([
                            'name' => $option['name'],
                            'price_delta' => (int) ($option['price_delta'] ?? 0),
                            'sort_order' => $optionOrder,
                        ]);
                    }
                }

                $itemsToDelete = $existingItems->keys()->diff($processedItemIds);
                if ($itemsToDelete->isNotEmpty()) {
                    $deletedItems = $existingItems->only($itemsToDelete->all());
                    foreach ($deletedItems as $deletedItem) {
                        if (!empty($deletedItem->image_url)) {
                            $this->imageUploadService->deleteFile($deletedItem->image_url);
                        }
                    }
                    CampaignItem::query()->whereIn('id', $itemsToDelete->all())->delete();
                }
            }

            /** @var Campaign $freshCampaign */
            $freshCampaign = $campaign->fresh(['items.toppings', 'items.sizes', 'paymentAccount', 'room']);
            $this->auditService->record('campaign.updated', 'campaign', $campaign->id, $campaign->room_id, $before, $freshCampaign->toArray());

            return $freshCampaign;
        });

        if ($this->wentLive($previousStatus, $updated->status)) {
            // Draft/scheduled -> active is a go-live: announce it like a newly created campaign.
            CampaignCreated::dispatch($updated);
        } elseif (in_array($updated->status, [CampaignStatus::Active, CampaignStatus::Scheduled, CampaignStatus::Closing], true)) {
            CampaignUpdated::dispatch($updated);
        }

        return $updated;
    }

    /**
     * Determine whether an update moved a campaign from an unpublished state to active.
     *
     * @param CampaignStatus|null $previous Status before the update.
     * @param CampaignStatus|null $current Status after the update.
     * @return bool True when the campaign has just gone live.
     */
    private function wentLive(?CampaignStatus $previous, ?CampaignStatus $current): bool
    {
        return $current === CampaignStatus::Active
            && in_array($previous, [CampaignStatus::Draft, CampaignStatus::Scheduled], true);
    }
}

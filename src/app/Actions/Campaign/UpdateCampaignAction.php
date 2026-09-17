<?php

declare(strict_types=1);

namespace App\Actions\Campaign;

use App\Enums\CampaignItemStatus;
use App\Enums\PaymentAccountStatus;
use App\Models\Campaign;
use App\Models\CampaignItem;
use App\Models\PaymentAccount;
use App\Models\Room;
use App\Models\RoomUser;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateCampaignAction
{
    /**
     * Create the action instance.
     *
     * @param CreateCampaignItemAction $createItemAction Item creation action.
     * @param AuditService $auditService Audit logger service.
     */
    public function __construct(
        private readonly CreateCampaignItemAction $createItemAction,
        private readonly AuditService $auditService
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
        $settings = $room->roomSettings()->whereIn('key', ['max_campaign_budget'])->get()->keyBy('key');
        if ($settings->has('max_campaign_budget') && $settings->get('max_campaign_budget')?->value !== null) {
            $maxCampaignBudget = (int) $settings->get('max_campaign_budget')->value;
            if ($maxCampaignBudget > 0 && array_key_exists('max_budget', $data) && $data['max_budget'] !== null && (int) $data['max_budget'] > $maxCampaignBudget) {
                throw ValidationException::withMessages([
                    'max_budget' => __('admin.campaign_budget_exceeds_limit', [
                        'limit' => number_format($maxCampaignBudget, 0, ',', '.'),
                    ]),
                ]);
            }
        }

        $sponsorType = (string) ($data['sponsor_type'] ?? $campaign->sponsor_type ?? 'none');
        if ($sponsorType === 'none') {
            $data['sponsor_allocations'] = [];
        } elseif (array_key_exists('sponsor_allocations', $data) && $sponsorType === 'full') {
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

        return DB::transaction(function () use ($campaign, $data, $hasItems, $items): Campaign {
            $before = $campaign->toArray();
            $campaign->update(collect($data)->except(['status'])->all());

            if ($hasItems) {
                $existingItems = $campaign->items()->with(['toppings', 'sizes'])->get()->keyBy('id');
                $processedItemIds = [];

                foreach ($items as $sortOrder => $itemData) {
                    $itemId = isset($itemData['id']) ? (int) $itemData['id'] : null;
                    /** @var CampaignItem|null $item */
                    $item = ($itemId !== null && $existingItems->has($itemId)) ? $existingItems->get($itemId) : null;
                    $price = (int) ($itemData['price'] ?? $itemData['base_price'] ?? 0);

                    if ($item !== null) {
                        $item->update([
                            'name' => $itemData['name'],
                            'category' => $itemData['category'] ?? null,
                            'description' => $itemData['description'] ?? null,
                            'image_url' => $itemData['image_url'] ?? null,
                            'base_price' => $price,
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
                            'status' => CampaignItemStatus::Active->value,
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
                    CampaignItem::query()->whereIn('id', $itemsToDelete->all())->delete();
                }
            }

            /** @var Campaign $freshCampaign */
            $freshCampaign = $campaign->fresh(['items.toppings', 'items.sizes', 'paymentAccount', 'room']);
            $this->auditService->record('campaign.updated', 'campaign', $campaign->id, $campaign->room_id, $before, $freshCampaign->toArray());

            $statusValue = $freshCampaign->status instanceof \BackedEnum ? $freshCampaign->status->value : (string) $freshCampaign->status;
            if (in_array($statusValue, ['active', 'scheduled', 'closing'], true)) {
                \App\Events\CampaignUpdated::dispatch($freshCampaign);
            }

            return $freshCampaign;
        });
    }
}

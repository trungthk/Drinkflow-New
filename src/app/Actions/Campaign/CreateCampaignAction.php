<?php

declare(strict_types=1);

namespace App\Actions\Campaign;

use App\Events\CampaignCreated;
use App\Enums\CampaignStatus;
use App\Models\AdminAccount;
use App\Models\Campaign;
use App\Models\PaymentAccount;
use App\Models\Room;
use App\Models\RoomUser;
use App\Enums\CampaignItemStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateCampaignAction
{
    /**
     * Create the campaign action.
     *
     * @param CreateCampaignItemAction $createItemAction Reusable campaign-item creator.
     * @return void
     */
    public function __construct(private readonly CreateCampaignItemAction $createItemAction)
    {
    }

    /**
     * Create a new campaign for a given room.
     *
     * @param Room $room Room entity.
     * @param array<string, mixed> $data Campaign configuration parameters.
     * @param ?int $adminId Creating admin account ID.
     * @return Campaign Created campaign instance.
     * @throws ValidationException If payment account does not belong to the room.
     */
    public function execute(Room $room, array $data, ?int $adminId = null): Campaign
    {
        $settings = $room->roomSettings()->whereIn('key', ['campaign_title_template', 'max_campaign_budget'])->get()->keyBy('key');
        $titleTemplate = (string) ($settings->get('campaign_title_template')?->value ?? ('[' . $room->name . '] Trà chiều & Cafe {date}'));
        $creatorName = $adminId !== null ? (string) (AdminAccount::query()->whereKey($adminId)->value('name') ?? '') : '';
        $defaultName = strtr($titleTemplate, [
            '{date}' => now()->format('d/m/Y'),
            '{time}' => now()->format('H:i'),
            '{day_of_week}' => now()->translatedFormat('l'),
            '{creator_name}' => $creatorName,
        ]);
        $data['name'] = trim((string) ($data['name'] ?? '')) !== '' ? $data['name'] : $defaultName;
        if (!array_key_exists('max_budget', $data) || $data['max_budget'] === null) {
            $data['max_budget'] = (int) ($settings->get('max_campaign_budget')?->value ?? 70_000);
        }
        if (($data['sponsor_type'] ?? Campaign::SPONSOR_TYPE_NONE) === Campaign::SPONSOR_TYPE_NONE) {
            $data['sponsor_allocations'] = [];
        } else {
            $allocations = collect($data['sponsor_allocations'] ?? []);
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
        if (!empty($data['payment_account_id']) && !PaymentAccount::whereKey($data['payment_account_id'])->where('room_id', $room->id)->exists()) {
            throw ValidationException::withMessages([
                'payment_account_id' => __('admin.payment_account_not_in_room'),
            ]);
        }
        /** @var array<int, array<string, mixed>> $items */
        $items = $data['items'] ?? [];
        unset($data['items']);

        $campaign = DB::transaction(function () use ($room, $data, $adminId, $items): Campaign {
            $status = $data['status'] ?? CampaignStatus::Draft;
            $statusValue = $status instanceof CampaignStatus ? $status->value : (string) $status;
            $campaign = Campaign::create(array_merge($data, [
                'room_id' => $room->id,
                'creator_admin_id' => $adminId,
                'status' => $status,
                'started_at' => $statusValue === CampaignStatus::Active->value ? ($data['started_at'] ?? now()) : null,
            ]));

            foreach ($items as $sortOrder => $itemData) {
                $item = $this->createItemAction->execute($campaign, [
                    'name' => $itemData['name'],
                    'category' => $itemData['category'] ?? null,
                    'description' => $itemData['description'] ?? null,
                    'image_url' => $itemData['image_url'] ?? null,
                    'base_price' => $itemData['price'],
                    'status' => $itemData['status'] ?? CampaignItemStatus::Active->value,
                    'sort_order' => $sortOrder,
                ]);

                foreach ($itemData['toppings'] ?? [] as $toppingOrder => $topping) {
                    $item->toppings()->create([
                        'name' => $topping['name'],
                        'price' => $topping['price'],
                        'sort_order' => $toppingOrder,
                    ]);
                }

                foreach ($itemData['options'] ?? [] as $optionOrder => $option) {
                    $item->sizes()->create([
                        'name' => $option['name'],
                        'price_delta' => $option['price_delta'],
                        'sort_order' => $optionOrder,
                    ]);
                }
            }

            return $campaign->load(['items.toppings', 'items.sizes']);
        });
        CampaignCreated::dispatch($campaign->load('room'));

        return $campaign;
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Campaign;

use App\Events\CampaignCreated;
use App\Models\AdminAccount;
use App\Models\Campaign;
use App\Models\PaymentAccount;
use App\Models\Room;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateCampaignAction
{
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
        $titleTemplate = (string) ($settings->get('campaign_title_template')?->value ?? ('['.$room->name.'] Trà chiều & Cafe {date}'));
        $maxCampaignBudget = (int) ($settings->get('max_campaign_budget')?->value ?? 2_000_000);
        $creatorName = $adminId !== null ? (string) (AdminAccount::query()->whereKey($adminId)->value('name') ?? '') : '';
        $defaultName = strtr($titleTemplate, [
            '{date}' => now()->format('d/m/Y'),
            '{time}' => now()->format('H:i'),
            '{day_of_week}' => now()->translatedFormat('l'),
            '{creator_name}' => $creatorName,
        ]);
        $data['name'] = trim((string) ($data['name'] ?? '')) !== '' ? $data['name'] : $defaultName;
        if (! array_key_exists('max_budget', $data) || $data['max_budget'] === null) {
            $data['max_budget'] = $maxCampaignBudget;
        } elseif ((int) $data['max_budget'] > $maxCampaignBudget) {
            throw ValidationException::withMessages([
                'max_budget' => __('admin.campaign_budget_exceeds_limit', [
                    'limit' => number_format($maxCampaignBudget, 0, ',', '.'),
                ]),
            ]);
        }
        if (! empty($data['payment_account_id']) && ! PaymentAccount::whereKey($data['payment_account_id'])->where('room_id', $room->id)->exists()) {
            throw ValidationException::withMessages([
                'payment_account_id' => __('admin.payment_account_not_in_room'),
            ]);
        }
        $campaign = DB::transaction(fn (): Campaign => Campaign::create(array_merge($data, [
            'room_id' => $room->id,
            'creator_admin_id' => $adminId,
            'status' => $data['status'] ?? 'draft',
        ])));
        CampaignCreated::dispatch($campaign->load('room'));

        return $campaign;
    }
}

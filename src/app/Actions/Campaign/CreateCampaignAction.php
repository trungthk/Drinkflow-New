<?php

declare(strict_types=1);

namespace App\Actions\Campaign;

use App\Events\CampaignCreated;
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


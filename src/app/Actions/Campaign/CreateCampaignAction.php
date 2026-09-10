<?php

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
     * Handle the execute operation.
     * @param Room $room Parameter value.
     * @param array $data Parameter value.
     * @param ?int $adminId Parameter value.
     * @return Campaign Result of the operation.
     */
    public function execute(Room $room, array $data, ?int $adminId = null): Campaign
    {
        if (! empty($data['payment_account_id']) && ! PaymentAccount::whereKey($data['payment_account_id'])->where('room_id', $room->id)->exists()) {
            throw ValidationException::withMessages(['payment_account_id' => 'TĂ i khoáº£n thanh toĂ¡n khĂ´ng thuá»™c Room.']);
        }
        $campaign = DB::transaction(fn (): Campaign => Campaign::create(array_merge($data, ['room_id' => $room->id, 'creator_admin_id' => $adminId, 'status' => $data['status'] ?? 'draft'])));
        CampaignCreated::dispatch($campaign->load('room'));

        return $campaign;
    }
}

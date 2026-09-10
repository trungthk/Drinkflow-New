<?php
namespace App\Actions\Campaign;
use App\Enums\CampaignStatus;
use App\Models\Campaign;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class CloseCampaignAction {
    public function execute(Campaign $campaign): Campaign {
        return DB::transaction(function () use ($campaign): Campaign {
            $campaign = Campaign::query()->whereKey($campaign->id)->lockForUpdate()->firstOrFail();
            if (!in_array($campaign->status, [CampaignStatus::Active, CampaignStatus::Closing], true)) throw ValidationException::withMessages(['campaign' => 'Campaign không thể đóng ở trạng thái hiện tại.']);
            $campaign->update(['status' => CampaignStatus::Closing]);
            foreach ($campaign->orders()->whereIn('status', ['submitted','confirmed','ordering','ordered','delivering','completed'])->get() as $order) {
                $campaign->debts()->updateOrCreate(['room_user_id' => $order->room_user_id], ['room_id' => $campaign->room_id, 'original_amount' => $order->final_amount, 'sponsor_amount' => $order->sponsor_amount, 'adjustment_amount' => 0, 'paid_amount' => 0, 'remaining_amount' => $order->final_amount, 'status' => 'unpaid']);
            }
            $campaign->update(['status' => CampaignStatus::Closed, 'closed_at' => now()]);
            return $campaign->fresh();
        });
    }
}

<?php

namespace App\Actions\Campaign;

use App\Models\Campaign;
use App\Models\Debt;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SplitCampaignBillAction
{
    public function execute(Campaign $campaign, string $method, array $custom = []): array
    {
        return DB::transaction(function () use ($campaign, $method, $custom): array {
            $campaign = Campaign::query()->with(['orders' => fn ($query) => $query->whereNotIn('status', ['cancelled']), 'orders.roomUser'])->lockForUpdate()->findOrFail($campaign->id);
            if (! in_array($campaign->status?->value, ['closed', 'closing'], true)) {
                throw ValidationException::withMessages(['campaign' => 'Chỉ split bill sau khi campaign đã đóng.']);
            }
            $byUser = $campaign->orders->groupBy('room_user_id');
            if ($byUser->isEmpty()) {
                throw ValidationException::withMessages(['campaign' => 'Campaign chưa có order hợp lệ.']);
            }
            $gross = $byUser->map(fn ($orders) => (int) $orders->sum(fn ($order) => $order->subtotal + $order->delivery_amount - $order->discount_amount));
            $orderNet = $byUser->map(fn ($orders) => (int) $orders->sum('final_amount'));
            $totalNet = (int) $orderNet->sum();
            $allocations = match ($method) {
                'by_order' => $orderNet,
                'sponsor_first' => $this->equalize($byUser->keys()->all(), $totalNet),
                'equal' => $this->equalize($byUser->keys()->all(), $totalNet),
                'flat_price' => $this->flatPrice($byUser->keys()->all(), $campaign->flat_price, $totalNet),
                'custom' => collect($custom)->mapWithKeys(fn ($amount, $userId) => [(int) $userId => (int) $amount]),
                default => collect(),
            };
            if ((int) $allocations->sum() !== $totalNet) {
                throw ValidationException::withMessages(['allocations' => 'Tổng phân bổ phải bằng tổng tiền order.']);
            }

            $result = [];
            foreach ($byUser as $roomUserId => $orders) {
                $amount = (int) ($allocations[$roomUserId] ?? 0);
                $sponsor = max(0, (int) $gross[$roomUserId] - $amount);
                $debt = Debt::query()->where('campaign_id', $campaign->id)->where('room_user_id', $roomUserId)->lockForUpdate()->first();
                $paid = (int) ($debt?->paid_amount ?? 0);
                $remaining = max(0, $amount - $paid);
                $status = $remaining === 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');
                $debt = Debt::updateOrCreate(
                    ['campaign_id' => $campaign->id, 'room_user_id' => $roomUserId],
                    ['room_id' => $campaign->room_id, 'original_amount' => (int) $gross[$roomUserId], 'sponsor_amount' => $sponsor, 'adjustment_amount' => 0, 'paid_amount' => $paid, 'remaining_amount' => $remaining, 'status' => $status],
                );
                app(AuditService::class)->record('campaign.bill_split', 'debt', $debt->id, $campaign->room_id, [], ['remaining_amount' => $remaining, 'sponsor_amount' => $sponsor], ['method' => $method, 'campaign_id' => $campaign->id]);
                $result[] = ['room_user_id' => (int) $roomUserId, 'gross_amount' => (int) $gross[$roomUserId], 'sponsor_amount' => $sponsor, 'amount' => $amount, 'paid_amount' => $paid, 'remaining_amount' => $remaining, 'status' => $status];
            }

            return ['campaign_id' => $campaign->id, 'method' => $method, 'total' => $totalNet, 'allocations' => $result];
        });
    }

    private function equalize(array $ids, int $total): \Illuminate\Support\Collection
    {
        $base = intdiv($total, count($ids));
        $remainder = $total % count($ids);
        return collect($ids)->values()->mapWithKeys(fn ($id, $index) => [(int) $id => $base + ($index < $remainder ? 1 : 0)]);
    }

    private function flatPrice(array $ids, ?int $flatPrice, int $total): \Illuminate\Support\Collection
    {
        if (! $flatPrice || $flatPrice < 0) {
            throw ValidationException::withMessages(['flat_price' => 'Campaign chưa cấu hình flat price hợp lệ.']);
        }
        $amounts = collect($ids)->mapWithKeys(fn ($id) => [(int) $id => $flatPrice]);
        $difference = $total - (int) $amounts->sum();
        if ($difference !== 0) {
            $first = (int) $ids[0];
            $amounts[$first] += $difference;
        }
        return $amounts;
    }
}

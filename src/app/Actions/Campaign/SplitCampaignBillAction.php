<?php

declare(strict_types=1);

namespace App\Actions\Campaign;

use App\Models\Campaign;
use App\Models\Debt;
use App\Services\Audit\AuditService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SplitCampaignBillAction
{
    /**
     * Execute the bill splitting calculation and assign debts to room members.
     *
     * @param Campaign $campaign Campaign instance to split bills for.
     * @param string $method Splitting algorithm ('by_order', 'sponsor_first', 'equal', 'flat_price', 'custom').
     * @param array<int|string, int> $custom Custom allocation map [room_user_id => amount].
     * @return array<string, mixed> Bill splitting calculation result payload.
     * @throws ValidationException If campaign is not closed, has no orders, or allocations don't match total.
     */
    public function execute(Campaign $campaign, string $method, array $custom = []): array
    {
        return DB::transaction(function () use ($campaign, $method, $custom): array {
            $campaign = Campaign::query()->with(['orders' => fn ($query) => $query->whereNotIn('status', ['cancelled']), 'orders.roomUser'])->lockForUpdate()->findOrFail($campaign->id);
            if (! in_array($campaign->status?->value, ['closed', 'closing'], true)) {
                throw ValidationException::withMessages([
                    'campaign' => __('admin.split_bill_only_closed'),
                ]);
            }
            $byUser = $campaign->orders->groupBy('room_user_id');
            if ($byUser->isEmpty()) {
                throw ValidationException::withMessages([
                    'campaign' => __('admin.campaign_no_valid_orders'),
                ]);
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
                throw ValidationException::withMessages([
                    'allocations' => __('admin.allocations_must_equal_total'),
                ]);
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
                    ['room_id' => $campaign->room_id, 'original_amount' => (int) $gross[$roomUserId], 'sponsor_amount' => $sponsor, 'sponsor_type' => $campaign->sponsor_type, 'sponsor_description' => $campaign->sponsor_description, 'adjustment_amount' => 0, 'paid_amount' => $paid, 'remaining_amount' => $remaining, 'status' => $status],
                );
                app(AuditService::class)->record('campaign.bill_split', 'debt', $debt->id, $campaign->room_id, [], ['remaining_amount' => $remaining, 'sponsor_amount' => $sponsor], ['method' => $method, 'campaign_id' => $campaign->id]);
                $result[] = ['room_user_id' => (int) $roomUserId, 'gross_amount' => (int) $gross[$roomUserId], 'sponsor_amount' => $sponsor, 'amount' => $amount, 'paid_amount' => $paid, 'remaining_amount' => $remaining, 'status' => $status];
            }

            return ['campaign_id' => $campaign->id, 'method' => $method, 'total' => $totalNet, 'allocations' => $result];
        });
    }

    /**
     * Evenly distribute total amount across users.
     *
     * @param array<int|string> $ids Array of user IDs.
     * @param int $total Total amount to distribute.
     * @return Collection Collection mapping userId => distributed amount.
     */
    private function equalize(array $ids, int $total): Collection
    {
        $base = intdiv($total, count($ids));
        $remainder = $total % count($ids);
        return collect($ids)->values()->mapWithKeys(fn ($id, $index) => [(int) $id => $base + ($index < $remainder ? 1 : 0)]);
    }

    /**
     * Compute flat price allocation with rounding difference applied to first user.
     *
     * @param array<int|string> $ids Array of user IDs.
     * @param ?int $flatPrice Configured flat price amount.
     * @param int $total Total amount to distribute.
     * @return Collection Collection mapping userId => allocated amount.
     * @throws ValidationException If flat price is not positive integer.
     */
    private function flatPrice(array $ids, ?int $flatPrice, int $total): Collection
    {
        if (! $flatPrice || $flatPrice < 0) {
            throw ValidationException::withMessages([
                'flat_price' => __('admin.invalid_flat_price'),
            ]);
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

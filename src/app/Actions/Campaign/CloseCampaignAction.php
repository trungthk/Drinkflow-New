<?php

declare(strict_types=1);

namespace App\Actions\Campaign;

use App\Enums\CampaignStatus;
use App\Enums\OrderStatus;
use App\Events\CampaignClosed;
use App\Models\Campaign;
use App\Models\Debt;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CloseCampaignAction
{
    /**
     * Execute the close campaign operation and generate debts according to sponsorship and proportional rules.
     *
     * @param Campaign $campaign Campaign instance to close.
     * @param bool $allowDebt Whether to automatically create debt records.
     * @param ?string $reason Optional reason why the campaign was closed.
     * @return Campaign Closed campaign instance.
     * @throws ValidationException If campaign is not in active or closing state.
     */
    public function execute(Campaign $campaign, bool $allowDebt = true, ?string $reason = null): Campaign
    {
        $closed = DB::transaction(function () use ($campaign, $allowDebt, $reason): Campaign {
            $campaign = Campaign::query()->whereKey($campaign->id)->lockForUpdate()->firstOrFail();

            if (! in_array($campaign->status, [CampaignStatus::Active, CampaignStatus::Closing], true)) {
                throw ValidationException::withMessages([
                    'campaign' => __('admin.campaign_closed_invalid_state'),
                ]);
            }

            $campaign->update(['status' => CampaignStatus::Closing]);

            if ($allowDebt) {
                $orders = $campaign->orders()
                    ->whereIn('status', ['submitted', 'confirmed', 'ordering', 'ordered', 'delivering', 'completed'])
                    ->with(['roomUser.globalUser'])
                    ->get();

                $grossSubtotal = (int) $orders->sum('subtotal');
                $deliveryFee = (int) ($campaign->delivery_fee ?? 0);
                $discount = (int) ($campaign->discount ?? 0);
                $netCampaignTotal = max(0, $grossSubtotal + $deliveryFee - $discount);

                $sponsorAllocations = collect($campaign->sponsor_allocations ?? []);
                $isFullSponsor = $campaign->sponsor_type === 'full'
                    || ($sponsorAllocations->isNotEmpty() && abs((float) $sponsorAllocations->sum('percentage') - 100.0) < 0.01);

                if ($isFullSponsor && $sponsorAllocations->isNotEmpty()) {
                    // -------------------------------------------------------------
                    // TRƯỜNG HỢP 1: ĐƠN ĐƯỢC TÀI TRỢ FULL (100%) THEO TỶ LỆ SPONSORS
                    // -------------------------------------------------------------
                    // 1. Cập nhật các đơn hàng của user: không phải trả tiền (final_amount = 0) và completed
                    foreach ($orders as $order) {
                        $orderSubtotal = (int) $order->subtotal;
                        $orderRatio = $grossSubtotal > 0 ? ($orderSubtotal / $grossSubtotal) : 0;
                        $orderDeliveryFee = (int) round($deliveryFee * $orderRatio);
                        $orderDiscount = (int) round($discount * $orderRatio);
                        $orderGross = max(0, $orderSubtotal + $orderDeliveryFee - $orderDiscount);

                        $order->update([
                            'delivery_amount' => $orderDeliveryFee,
                            'discount_amount' => $orderDiscount,
                            'sponsor_amount' => $orderGross,
                            'final_amount' => 0,
                            'status' => OrderStatus::Completed->value,
                        ]);
                    }

                    // 2. Ghi nợ cho các NHÀ TÀI TRỢ theo tỷ lệ % đã đăng ký
                    $sponsorDebts = [];
                    $allocatedTotal = 0;
                    foreach ($sponsorAllocations as $alloc) {
                        $roomUserId = (int) ($alloc['room_user_id'] ?? 0);
                        $percentage = (float) ($alloc['percentage'] ?? 0);
                        if ($roomUserId <= 0 || $percentage <= 0) {
                            continue;
                        }
                        $sponsorAmount = (int) round(($netCampaignTotal * $percentage) / 100);
                        $grossPart = (int) round(($grossSubtotal * $percentage) / 100);
                        $feePart = (int) round(($deliveryFee * $percentage) / 100);
                        $discPart = (int) round(($discount * $percentage) / 100);

                        $allocatedTotal += $sponsorAmount;
                        $sponsorDebts[] = [
                            'room_user_id' => $roomUserId,
                            'percentage' => $percentage,
                            'amount' => $sponsorAmount,
                            'gross_part' => $grossPart,
                            'fee_part' => $feePart,
                            'disc_part' => $discPart,
                        ];
                    }

                    // Điều chỉnh sai số làm tròn vào sponsor đầu tiên
                    $diff = $netCampaignTotal - $allocatedTotal;
                    if ($diff !== 0 && count($sponsorDebts) > 0) {
                        $sponsorDebts[0]['amount'] = max(0, $sponsorDebts[0]['amount'] + $diff);
                    }

                    foreach ($sponsorDebts as $sp) {
                        $note = sprintf(
                            "Tài trợ %s%% chiến dịch #%s (%s) - Thực trả: %s ₫ [Món: %s ₫, Phí ship: +%s ₫, Giảm giá: -%s ₫]",
                            (string) $sp['percentage'],
                            (string) ($campaign->code ?? $campaign->id),
                            (string) ($campaign->restaurant ?: $campaign->name),
                            number_format($sp['amount'], 0, ',', '.'),
                            number_format($sp['gross_part'], 0, ',', '.'),
                            number_format($sp['fee_part'], 0, ',', '.'),
                            number_format($sp['disc_part'], 0, ',', '.')
                        );

                        Debt::updateOrCreate(
                            [
                                'campaign_id' => $campaign->id,
                                'room_user_id' => $sp['room_user_id'],
                            ],
                            [
                                'room_id' => $campaign->room_id,
                                'original_amount' => $sp['gross_part'],
                                'sponsor_amount' => 0,
                                'sponsor_type' => $campaign->sponsor_type,
                                'sponsor_description' => $campaign->sponsor_description,
                                'adjustment_amount' => $sp['fee_part'] - $sp['disc_part'],
                                'paid_amount' => 0,
                                'remaining_amount' => $sp['amount'],
                                'status' => $sp['amount'] === 0 ? 'paid' : 'unpaid',
                                'note' => $note,
                            ]
                        );
                    }
                } else {
                    // -------------------------------------------------------------
                    // TRƯỜNG HỢP 2: KHÔNG TÀI TRỢ HOẶC TÀI TRỢ MỘT PHẦN
                    // -------------------------------------------------------------
                    $groupedOrders = $orders->groupBy('room_user_id');
                    $userCalculations = [];
                    $totalUserPayables = 0;

                    foreach ($groupedOrders as $roomUserId => $userOrders) {
                        $userSubtotal = (int) $userOrders->sum('subtotal');
                        $userInitialSponsor = (int) $userOrders->sum('sponsor_amount');

                        // Quy tắc tam suất tính phí ship và giảm giá cho từng user
                        if ($grossSubtotal > 0) {
                            $ratio = $userSubtotal / $grossSubtotal;
                            $userDeliveryFee = (int) round($deliveryFee * $ratio);
                            $userDiscount = (int) round($discount * $ratio);
                        } else {
                            $ratio = count($groupedOrders) > 0 ? (1 / count($groupedOrders)) : 0;
                            $userDeliveryFee = (int) round($deliveryFee * $ratio);
                            $userDiscount = (int) round($discount * $ratio);
                        }

                        $userGrossWithFee = max(0, $userSubtotal + $userDeliveryFee - $userDiscount);
                        $userFinalAmount = max(0, $userGrossWithFee - $userInitialSponsor);
                        $totalUserPayables += $userFinalAmount;

                        $userCalculations[] = [
                            'room_user_id' => (int) $roomUserId,
                            'orders' => $userOrders,
                            'subtotal' => $userSubtotal,
                            'delivery_fee' => $userDeliveryFee,
                            'discount' => $userDiscount,
                            'sponsor_amount' => $userInitialSponsor,
                            'final_amount' => $userFinalAmount,
                        ];
                    }

                    // Bù trừ sai số làm tròn nếu không tài trợ
                    if (($campaign->sponsor_type === 'none' || empty($campaign->sponsor_type)) && count($userCalculations) > 0) {
                        $diff = $netCampaignTotal - $totalUserPayables;
                        if ($diff !== 0) {
                            $userCalculations[0]['final_amount'] = max(0, $userCalculations[0]['final_amount'] + $diff);
                        }
                    }

                    // Cập nhật từng Order và ghi nợ Debt cho từng user
                    foreach ($userCalculations as $calc) {
                        $orderCodes = $calc['orders']->pluck('code')->filter()->implode(', ');
                        if (empty($orderCodes)) {
                            $orderCodes = '#' . $calc['orders']->pluck('id')->implode(', #');
                        }

                        // Phân bổ lại cho từng đơn hàng của user
                        foreach ($calc['orders'] as $ord) {
                            $ordSubtotal = (int) $ord->subtotal;
                            $ordRatio = $calc['subtotal'] > 0 ? ($ordSubtotal / $calc['subtotal']) : 1;
                            $ordFee = (int) round($calc['delivery_fee'] * $ordRatio);
                            $ordDisc = (int) round($calc['discount'] * $ordRatio);
                            $ordGross = max(0, $ordSubtotal + $ordFee - $ordDisc);
                            $ordFinal = max(0, $ordGross - (int) $ord->sponsor_amount);

                            $ord->update([
                                'delivery_amount' => $ordFee,
                                'discount_amount' => $ordDisc,
                                'final_amount' => $ordFinal,
                                'status' => OrderStatus::Completed->value,
                            ]);
                        }

                        if ($calc['final_amount'] > 0 || $calc['subtotal'] > 0) {
                            $noteParts = [];
                            $noteParts[] = "Món: " . number_format($calc['subtotal'], 0, ',', '.') . " ₫";
                            if ($calc['delivery_fee'] > 0) {
                                $noteParts[] = "Ship: +" . number_format($calc['delivery_fee'], 0, ',', '.') . " ₫";
                            }
                            if ($calc['discount'] > 0) {
                                $noteParts[] = "Giảm: -" . number_format($calc['discount'], 0, ',', '.') . " ₫";
                            }
                            if ($calc['sponsor_amount'] > 0) {
                                $noteParts[] = "Tài trợ: -" . number_format($calc['sponsor_amount'], 0, ',', '.') . " ₫";
                            }

                            $note = sprintf(
                                "Đơn %s chiến dịch #%s (%s) - Thực trả: %s ₫ [%s]",
                                $orderCodes,
                                (string) ($campaign->code ?? $campaign->id),
                                (string) ($campaign->restaurant ?: $campaign->name),
                                number_format($calc['final_amount'], 0, ',', '.'),
                                implode(', ', $noteParts)
                            );

                            Debt::updateOrCreate(
                                [
                                    'campaign_id' => $campaign->id,
                                    'room_user_id' => $calc['room_user_id'],
                                ],
                                [
                                    'room_id' => $campaign->room_id,
                                    'original_amount' => $calc['subtotal'],
                                    'sponsor_amount' => $calc['sponsor_amount'],
                                    'sponsor_type' => $campaign->sponsor_type,
                                    'sponsor_description' => $campaign->sponsor_description,
                                    'adjustment_amount' => $calc['delivery_fee'] - $calc['discount'],
                                    'paid_amount' => 0,
                                    'remaining_amount' => $calc['final_amount'],
                                    'status' => $calc['final_amount'] === 0 ? 'paid' : 'unpaid',
                                    'note' => $note,
                                ]
                            );
                        }
                    }
                }
            }

            // Chuyển toàn bộ các đơn hàng còn đang active thuộc campaign sang completed
            $campaign->orders()
                ->whereIn('status', OrderStatus::activeValues())
                ->update(['status' => OrderStatus::Completed->value]);

            $campaign->update([
                'status' => CampaignStatus::Closed,
                'closed_at' => now(),
            ]);

            app(AuditService::class)->record(
                'campaign.closed',
                'campaign',
                $campaign->id,
                $campaign->room_id,
                ['status' => CampaignStatus::Active->value],
                ['status' => CampaignStatus::Closed->value, 'allow_debt' => $allowDebt, 'reason' => $reason]
            );

            return $campaign->fresh();
        });

        CampaignClosed::dispatch($closed->load('room'));

        return $closed;
    }
}


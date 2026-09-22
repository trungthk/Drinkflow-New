<?php

declare(strict_types=1);

namespace App\Actions\Campaign;

use App\Enums\CampaignStatus;
use App\Enums\DebtStatus;
use App\Enums\OrderStatus;
use App\Events\CampaignClosed;
use App\Models\Campaign;
use App\Models\Debt;
use App\Models\Order;
use App\Services\Audit\AuditService;
use App\Support\Helpers\FormatHelper;
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
                    ->whereIn('status', [...OrderStatus::activeValues(), OrderStatus::Completed->value])
                    ->with(['roomUser.globalUser', 'items'])
                    ->get();

                $priceBasis = (string) ($campaign->self_paid_price_basis ?: Campaign::SELF_PAID_PRICE_BASIS_ORIGINAL);
                $grossSubtotal = (int) $orders->sum('subtotal');
                $deliveryFee = (int) ($campaign->delivery_fee ?? 0);
                $discount = (int) ($campaign->discount ?? 0);
                $netCampaignTotal = max(0, $grossSubtotal + $deliveryFee - $discount);
                // Nợ trả riêng (self-paid) ghi thẳng cho người đặt, không đi qua sponsor. Gom theo room_user_id
                // vì một người có thể có nhiều order (kể cả order dùm) trong cùng chiến dịch.
                $selfPaidDebtsByUser = [];

                $sponsorAllocations = collect($campaign->sponsor_allocations ?? []);
                $isFullSponsor = $campaign->sponsor_type === Campaign::SPONSOR_TYPE_FULL
                    || ($sponsorAllocations->isNotEmpty() && abs((float) $sponsorAllocations->sum('percentage') - 100.0) < 0.01);

                if ($isFullSponsor && $sponsorAllocations->isNotEmpty()) {
                    // -------------------------------------------------------------
                    // TRƯỜNG HỢP 1: ĐƠN ĐƯỢC TÀI TRỢ FULL (100%) THEO TỶ LỆ SPONSORS
                    // -------------------------------------------------------------
                    // 1. Cập nhật các đơn hàng của user: sponsor chỉ gánh phần không "trả riêng"; phần trả riêng
                    //    (nếu có) vẫn ghi nợ trực tiếp cho người đặt qua $selfPaidDebtsByUser bên dưới.
                    $sponsorPoolTotal = 0;
                    $sponsorGrossSubtotalTotal = 0;
                    $sponsorFeeTotal = 0;
                    $sponsorDiscountTotal = 0;
                    foreach ($orders as $order) {
                        $orderSubtotal = (int) $order->subtotal;
                        $orderRatio = $grossSubtotal > 0 ? ($orderSubtotal / $grossSubtotal) : 0;
                        $orderDeliveryFee = (int) round($deliveryFee * $orderRatio);
                        $orderDiscount = (int) round($discount * $orderRatio);

                        [$selfPaidSubtotal, $sponsorableSubtotal] = $this->splitSelfPaidSubtotal($order);
                        [$selfPaidFee, $selfPaidDiscount, $sponsorFee, $sponsorDiscount] = $this->splitFeeAndDiscount(
                            $priceBasis, $orderSubtotal, $selfPaidSubtotal, $orderDeliveryFee, $orderDiscount
                        );
                        $selfPaidDebt = max(0, $selfPaidSubtotal + $selfPaidFee - $selfPaidDiscount);
                        $orderGross = max(0, $sponsorableSubtotal + $sponsorFee - $sponsorDiscount);
                        $sponsorPoolTotal += $orderGross;
                        $sponsorGrossSubtotalTotal += $sponsorableSubtotal;
                        $sponsorFeeTotal += $sponsorFee;
                        $sponsorDiscountTotal += $sponsorDiscount;

                        if ($selfPaidDebt > 0) {
                            $selfPaidDebtsByUser[(int) $order->room_user_id] = ($selfPaidDebtsByUser[(int) $order->room_user_id] ?? 0) + $selfPaidDebt;
                        }

                        $order->update([
                            'delivery_amount' => $orderDeliveryFee,
                            'discount_amount' => $orderDiscount,
                            'sponsor_amount' => $orderGross,
                            'final_amount' => $selfPaidDebt,
                            'status' => OrderStatus::Completed->value,
                        ]);
                    }

                    // 2. Ghi nợ cho các NHÀ TÀI TRỢ theo tỷ lệ % đã đăng ký (chỉ trên phần sponsor thực sự gánh)
                    $sponsorDebts = [];
                    $allocatedTotal = 0;
                    foreach ($sponsorAllocations as $alloc) {
                        $roomUserId = (int) ($alloc['room_user_id'] ?? 0);
                        $percentage = (float) ($alloc['percentage'] ?? 0);
                        if ($roomUserId <= 0 || $percentage <= 0) {
                            continue;
                        }
                        $sponsorAmount = (int) round(($sponsorPoolTotal * $percentage) / 100);
                        $grossPart = (int) round(($sponsorGrossSubtotalTotal * $percentage) / 100);
                        $feePart = (int) round(($sponsorFeeTotal * $percentage) / 100);
                        $discPart = (int) round(($sponsorDiscountTotal * $percentage) / 100);

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
                    $diff = $sponsorPoolTotal - $allocatedTotal;
                    if ($diff !== 0 && count($sponsorDebts) > 0) {
                        $sponsorDebts[0]['amount'] = max(0, $sponsorDebts[0]['amount'] + $diff);
                    }

                    // Gộp nợ sponsor + nợ trả riêng của cùng một room_user_id (trường hợp hiếm: sponsor cũng
                    // tự đặt món trả riêng) vào một Debt duy nhất trước khi ghi, tránh updateOrCreate ghi đè nhau.
                    $debtWrites = [];
                    foreach ($sponsorDebts as $sp) {
                        $note = sprintf(
                            "Tài trợ %s%% chiến dịch #%s (%s) - Thực trả: %s [Món: %s, Phí ship: +%s, Giảm giá: -%s]",
                            (string) $sp['percentage'],
                            (string) $campaign->code,
                            (string) ($campaign->restaurant ?: $campaign->name),
                            FormatHelper::formatCurrency($sp['amount']),
                            FormatHelper::formatCurrency($sp['gross_part']),
                            FormatHelper::formatCurrency($sp['fee_part']),
                            FormatHelper::formatCurrency($sp['disc_part'])
                        );

                        $debtWrites[$sp['room_user_id']] = [
                            'original_amount' => $sp['gross_part'],
                            'adjustment_amount' => $sp['fee_part'] - $sp['disc_part'],
                            'remaining_amount' => $sp['amount'],
                            'note' => $note,
                        ];
                    }
                    foreach ($selfPaidDebtsByUser as $roomUserId => $selfPaidAmount) {
                        $selfPaidNote = sprintf(
                            "Trả riêng chiến dịch #%s (%s) - Không được tài trợ: %s",
                            (string) $campaign->code,
                            (string) ($campaign->restaurant ?: $campaign->name),
                            FormatHelper::formatCurrency($selfPaidAmount)
                        );
                        if (isset($debtWrites[$roomUserId])) {
                            $debtWrites[$roomUserId]['original_amount'] += $selfPaidAmount;
                            $debtWrites[$roomUserId]['remaining_amount'] += $selfPaidAmount;
                            $debtWrites[$roomUserId]['note'] .= ' | ' . $selfPaidNote;
                        } else {
                            $debtWrites[$roomUserId] = [
                                'original_amount' => $selfPaidAmount,
                                'adjustment_amount' => 0,
                                'remaining_amount' => $selfPaidAmount,
                                'note' => $selfPaidNote,
                            ];
                        }
                    }

                    foreach ($debtWrites as $roomUserId => $write) {
                        Debt::updateOrCreate(
                            [
                                'campaign_id' => $campaign->id,
                                'room_user_id' => $roomUserId,
                            ],
                            [
                                'room_id' => $campaign->room_id,
                                'original_amount' => $write['original_amount'],
                                'sponsor_amount' => 0,
                                'sponsor_type' => $campaign->sponsor_type,
                                'sponsor_description' => $campaign->sponsor_description,
                                'adjustment_amount' => $write['adjustment_amount'],
                                'paid_amount' => 0,
                                'remaining_amount' => $write['remaining_amount'],
                                'status' => $write['remaining_amount'] === 0 ? DebtStatus::Paid : DebtStatus::Unpaid,
                                'note' => $write['note'],
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
                        } else {
                            $ratio = count($groupedOrders) > 0 ? (1 / count($groupedOrders)) : 0;
                        }
                        $userDeliveryFee = (int) round($deliveryFee * $ratio);
                        $userDiscount = (int) round($discount * $ratio);

                        // Phân bổ phí ship/giảm giá cho từng đơn của user, đồng thời tách phần "trả riêng"
                        // (không sponsor) theo cấu hình self_paid_price_basis của chiến dịch.
                        $orderResults = [];
                        $userSelfPaidSubtotal = 0;
                        $userSelfPaidDebt = 0;
                        $userSponsorGross = 0;
                        foreach ($userOrders as $ord) {
                            $ordSubtotal = (int) $ord->subtotal;
                            $ordRatio = $userSubtotal > 0 ? ($ordSubtotal / $userSubtotal) : (count($userOrders) > 0 ? 1 / count($userOrders) : 0);
                            $ordFee = (int) round($userDeliveryFee * $ordRatio);
                            $ordDisc = (int) round($userDiscount * $ordRatio);

                            [$selfPaidSubtotal, $sponsorableSubtotal] = $this->splitSelfPaidSubtotal($ord);
                            [$selfPaidFee, $selfPaidDisc, $sponsorFee, $sponsorDisc] = $this->splitFeeAndDiscount(
                                $priceBasis, $ordSubtotal, $selfPaidSubtotal, $ordFee, $ordDisc
                            );
                            $ordSelfPaidDebt = max(0, $selfPaidSubtotal + $selfPaidFee - $selfPaidDisc);
                            $ordSponsorGross = max(0, $sponsorableSubtotal + $sponsorFee - $sponsorDisc);
                            $ordFinal = max(0, $ordSponsorGross - (int) $ord->sponsor_amount) + $ordSelfPaidDebt;

                            $orderResults[] = ['order' => $ord, 'fee' => $ordFee, 'disc' => $ordDisc, 'final' => $ordFinal];
                            $userSelfPaidSubtotal += $selfPaidSubtotal;
                            $userSelfPaidDebt += $ordSelfPaidDebt;
                            $userSponsorGross += $ordSponsorGross;
                        }

                        $userFinalAmount = max(0, $userSponsorGross - $userInitialSponsor) + $userSelfPaidDebt;
                        $totalUserPayables += $userFinalAmount;

                        $userCalculations[] = [
                            'room_user_id' => (int) $roomUserId,
                            'orders' => $userOrders,
                            'order_results' => $orderResults,
                            'subtotal' => $userSubtotal,
                            'self_paid_subtotal' => $userSelfPaidSubtotal,
                            'self_paid_debt' => $userSelfPaidDebt,
                            'delivery_fee' => $userDeliveryFee,
                            'discount' => $userDiscount,
                            'sponsor_amount' => $userInitialSponsor,
                            'final_amount' => $userFinalAmount,
                        ];
                    }

                    // Bù trừ sai số làm tròn nếu không tài trợ
                    if (($campaign->sponsor_type === Campaign::SPONSOR_TYPE_NONE || empty($campaign->sponsor_type)) && count($userCalculations) > 0) {
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

                        foreach ($calc['order_results'] as $result) {
                            $result['order']->update([
                                'delivery_amount' => $result['fee'],
                                'discount_amount' => $result['disc'],
                                'final_amount' => $result['final'],
                                'status' => OrderStatus::Completed->value,
                            ]);
                        }

                        if ($calc['final_amount'] > 0 || $calc['subtotal'] > 0) {
                            $noteParts = [];
                            $noteParts[] = "Món: " . FormatHelper::formatCurrency($calc['subtotal']);
                            if ($calc['delivery_fee'] > 0) {
                                $noteParts[] = "Ship: +" . FormatHelper::formatCurrency($calc['delivery_fee']);
                            }
                            if ($calc['discount'] > 0) {
                                $noteParts[] = "Giảm: -" . FormatHelper::formatCurrency($calc['discount']);
                            }
                            if ($calc['sponsor_amount'] > 0) {
                                $noteParts[] = "Tài trợ: -" . FormatHelper::formatCurrency($calc['sponsor_amount']);
                            }
                            if ($calc['self_paid_debt'] > 0) {
                                $noteParts[] = "Trả riêng: " . FormatHelper::formatCurrency($calc['self_paid_debt']);
                            }

                            $note = sprintf(
                                "Đơn %s chiến dịch #%s (%s) - Thực trả: %s [%s]",
                                $orderCodes,
                                (string) $campaign->code,
                                (string) ($campaign->restaurant ?: $campaign->name),
                                FormatHelper::formatCurrency($calc['final_amount']),
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
                                    'status' => $calc['final_amount'] === 0 ? DebtStatus::Paid : DebtStatus::Unpaid,
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

    /**
     * Split an order's subtotal into the "trả riêng" (self-paid) portion and the portion
     * still eligible for sponsorship, based on each item's `is_self_paid` flag.
     *
     * @param Order $order Order whose items must already be eager-loaded.
     * @return array{0: int, 1: int} [selfPaidSubtotal, sponsorableSubtotal]
     */
    private function splitSelfPaidSubtotal(Order $order): array
    {
        $selfPaidSubtotal = (int) $order->items
            ->where('is_self_paid', true)
            ->sum('line_subtotal');

        return [$selfPaidSubtotal, max(0, (int) $order->subtotal - $selfPaidSubtotal)];
    }

    /**
     * Split a delivery fee / discount pair between the self-paid and sponsorable portions
     * of an order (or a user's grouped orders), according to the campaign's configured
     * self-paid price basis.
     *
     * - `original`: self-paid items pay their raw price only; the sponsorable portion
     *   absorbs 100% of the shared delivery fee and discount.
     * - `campaign_prorated`: the fee and discount are split proportionally between the
     *   self-paid and sponsorable portions, based on their share of the subtotal.
     *
     * @param string $priceBasis One of Campaign::SELF_PAID_PRICE_BASIS_*.
     * @param int $subtotal Total subtotal (self-paid + sponsorable).
     * @param int $selfPaidSubtotal Portion of $subtotal that is self-paid.
     * @param int $fee Delivery fee already allocated to this subtotal.
     * @param int $discount Discount already allocated to this subtotal.
     * @return array{0: int, 1: int, 2: int, 3: int} [selfPaidFee, selfPaidDiscount, sponsorFee, sponsorDiscount]
     */
    private function splitFeeAndDiscount(string $priceBasis, int $subtotal, int $selfPaidSubtotal, int $fee, int $discount): array
    {
        if ($selfPaidSubtotal <= 0) {
            return [0, 0, $fee, $discount];
        }

        if ($priceBasis !== Campaign::SELF_PAID_PRICE_BASIS_CAMPAIGN_PRORATED) {
            // 'original' (mặc định): món trả riêng không cộng ship, không trừ giảm giá chung.
            return [0, 0, $fee, $discount];
        }

        $selfPaidRatio = $subtotal > 0 ? ($selfPaidSubtotal / $subtotal) : 0;
        $selfPaidFee = (int) round($fee * $selfPaidRatio);
        $selfPaidDiscount = (int) round($discount * $selfPaidRatio);

        return [$selfPaidFee, $selfPaidDiscount, $fee - $selfPaidFee, $discount - $selfPaidDiscount];
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\DebtStatus;
use App\Models\Debt;
use App\Models\Room;
use App\Models\RoomUser;
use App\Support\Helpers\FormatHelper;

class AdminDebtService
{
    /**
     * Get aggregate financial totals for the Room Debt & Ledger page.
     *
     * @param Room $room Room entity.
     * @return array<string, int> Ledger metrics.
     */
    public function getLedgerSummary(Room $room): array
    {
        $totalSpent = (int) Debt::where('room_id', $room->id)->sum('original_amount');
        $totalCollected = (int) Debt::where('room_id', $room->id)->where('status', DebtStatus::Paid->value)->sum('original_amount');
        $storeDebtPending = (int) Debt::where('room_id', $room->id)->whereIn('status', DebtStatus::outstandingValues())->sum('remaining_amount');

        return [
            'totalSpent' => $totalSpent,
            'totalCollected' => $totalCollected,
            'storeDebtPending' => $storeDebtPending,
            'memberDebtRemaining' => $storeDebtPending,
        ];
    }

    /**
     * List the members that have at least one debt in the room, for the ledger user filter.
     *
     * @param Room $room Room entity.
     * @return array<int, array{value: int, label: string, name: string, search: string}> Filter options ordered by name.
     */
    public function getMemberFilterOptions(Room $room): array
    {
        return RoomUser::query()
            ->whereIn('id', Debt::query()->where('room_id', $room->id)->select('room_user_id'))
            ->with('globalUser:id,name,email')
            ->get()
            ->map(static function (RoomUser $roomUser): array {
                $name = (string) ($roomUser->globalUser?->name ?? $roomUser->display_name ?? 'Member #'.$roomUser->id);
                $email = (string) ($roomUser->globalUser?->email ?? '');

                return [
                    'value' => $roomUser->id,
                    'label' => $email !== '' ? $name.' · '.$email : $name,
                    'name' => $name,
                    // Everything the dropdown search should match: name, email and member code.
                    'search' => trim(implode(' ', array_filter([$name, $email, (string) $roomUser->user_code, (string) $roomUser->display_name]))),
                ];
            })
            ->sortBy(static fn (array $option): string => mb_strtolower($option['name']))
            ->values()
            ->all();
    }

    /**
     * Build the localized, display-ready detail payload for the debt detail modal.
     *
     * @param Debt $debt Debt with roomUser.globalUser, campaign, payments and adjustments loaded.
     * @return array<string, mixed> Detail data (strings are already formatted for the current locale).
     */
    public function formatDetail(Debt $debt): array
    {
        $money = static fn (int|float|null $amount): string => FormatHelper::formatCurrency((int) ($amount ?? 0));
        $time = static fn ($date): string => $date ? FormatHelper::formatDateTime($date, 'H:i d/m/Y') : '';
        $translate = static function (string $key, ?string $value): string {
            $value = (string) $value;
            $line = $value !== '' ? __($key.$value) : '';

            return $line !== $key.$value ? $line : $value;
        };
        $status = $debt->status instanceof \BackedEnum ? $debt->status->value : (string) $debt->status;
        $sponsorType = (string) $debt->sponsor_type;

        return [
            'code' => (string) $debt->code,
            'status' => $status,
            'status_label' => __('admin.status_'.$status),
            'member' => (string) ($debt->roomUser?->globalUser?->name ?? $debt->roomUser?->display_name ?? 'Member #'.$debt->room_user_id),
            'member_meta' => collect([$debt->roomUser?->globalUser?->email, $debt->roomUser?->user_code])->filter()->implode(' · '),
            'campaign' => (string) ($debt->campaign?->name ?? 'N/A'),
            'created_at' => $time($debt->created_at),
            'payment_requested_at' => $time($debt->payment_requested_at),
            'payment_content' => (string) $debt->payment_content,
            'note' => (string) $debt->note,
            'original' => $money($debt->original_amount),
            'sponsor' => (int) $debt->sponsor_amount > 0 ? $money($debt->sponsor_amount) : '',
            'sponsor_type' => in_array($sponsorType, ['', 'none'], true) ? '' : $translate('admin.sponsor_type_', $sponsorType),
            'sponsor_description' => (string) $debt->sponsor_description,
            'adjustment' => (int) $debt->adjustment_amount !== 0 ? $money($debt->adjustment_amount) : '',
            'paid' => $money($debt->paid_amount),
            'remaining' => $money($debt->remaining_amount),
            'payments' => $debt->payments->sortByDesc('paid_at')->map(fn ($payment): array => [
                'paid_at' => $time($payment->paid_at),
                'amount' => $money($payment->amount),
                'method' => $translate('admin.debt_method_', $payment->payment_method),
                'reference' => (string) $payment->reference,
            ])->values()->all(),
            'adjustments' => $debt->adjustments->sortByDesc('created_at')->map(fn ($adjustment): array => [
                'created_at' => $time($adjustment->created_at),
                'type' => $translate('admin.debt_adjust_', $adjustment->type),
                'amount' => $money($adjustment->amount),
                'reason' => (string) $adjustment->reason,
                'before' => $money($adjustment->before_amount),
                'after' => $money($adjustment->after_amount),
            ])->values()->all(),
        ];
    }
}

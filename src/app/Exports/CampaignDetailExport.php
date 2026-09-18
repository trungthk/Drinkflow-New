<?php

declare(strict_types=1);

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

/** Export one dataset from the campaign detail tabs. */
class CampaignDetailExport implements FromArray, WithHeadings, ShouldAutoSize
{
    /** @var array<int, array<int, mixed>> */
    private array $rows;

    /** @var array<int, string> */
    private array $headerRow;

    /**
     * @param string $dataset Dataset key.
     * @param array<string, mixed> $data Campaign detail data.
     */
    public function __construct(string $dataset, array $data)
    {
        [$this->headerRow, $this->rows] = $this->build($dataset, $data);
    }

    /** @return array<int, array<int, mixed>> */
    public function array(): array
    {
        return $this->rows;
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return $this->headerRow;
    }

    /** @return array{0: array<int, string>, 1: array<int, array<int, mixed>>} */
    private function build(string $dataset, array $data): array
    {
        $items = collect($data['aggregatedItems'] ?? []);
        $orders = collect($data['orders'] ?? []);
        $departments = collect($data['departmentGroups'] ?? []);
        $debts = $data['campaign']->debts ?? collect();
        $users = collect($data[$dataset === 'declined' ? 'declinedUsers' : 'unresponsiveUsers'] ?? []);

        return match ($dataset) {
            'aggregated' => [[__('admin.order_no'), __('admin.item_name_customization'), __('admin.toppings_label'), __('admin.quantity'), __('admin.unit_price'), __('admin.total_amount'), __('admin.notes')], $items->values()->map(fn (array $item, int $index): array => [$index + 1, trim($item['name'].' '.($item['size'] ? '('.$item['size'].')' : '')), $item['toppings']->unique()->join('; '), $item['quantity'], $item['unit_price'], $item['total_amount'], $item['notes']->unique()->join('; ')])->all()],
            'orders' => [[__('admin.order_no'), __('admin.member'), __('admin.email'), __('admin.order_items_detail'), __('admin.payable'), __('admin.th_status')], $orders->values()->map(fn ($order, int $index): array => [$order->code ?: $index + 1, $order->roomUser?->display_name ?? __('admin.member'), $order->roomUser?->globalUser?->email ?? '', $order->items->map(fn ($item): string => $item->quantity.'x '.$item->item_name.($item->size_name ? ' ('.$item->size_name.')' : '').($item->toppings->isNotEmpty() ? ' + '.$item->toppings->pluck('topping_name')->join(', ') : '').($item->note ? ' - '.$item->note : ''))->join('; '), (int) $order->final_amount, $order->status?->value ?? (string) $order->status])->all()],
            'departments' => [[__('admin.member_department'), __('admin.item_name_customization'), __('admin.quantity'), __('admin.total_amount'), __('admin.member')], $departments->flatMap(fn (array $department): Collection => $department['items']->values()->map(fn (array $item): array => [$department['department'], trim($item['name'].' '.($item['size'] ? '('.$item['size'].')' : '')), $item['quantity'], $item['total_amount'], $item['members']->join('; ') ]))->values()->all()],
            'debts' => [[__('admin.member'), __('admin.email'), __('admin.original_amount'), __('admin.paid_amount'), __('admin.remaining_debt'), __('admin.th_status')], collect($debts)->map(fn ($debt): array => [$debt->roomUser?->display_name ?? __('admin.member'), $debt->roomUser?->globalUser?->email ?? '', (int) $debt->original_amount, (int) $debt->paid_amount, (int) $debt->remaining_amount, $debt->status?->value ?? (string) $debt->status])->all()],
            'declined', 'unresponsive' => [[__('admin.member_full_name'), __('admin.member_code'), __('admin.member_department'), __('admin.email')], $users->map(fn ($user): array => [$user->globalUser?->name ?? $user->display_name, $user->user_code, $user->globalUser?->desk_location ?? '', $user->globalUser?->email ?? ''])->all()],
            default => throw new \InvalidArgumentException('Unsupported campaign export dataset.'),
        };
    }
}

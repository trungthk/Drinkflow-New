<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Enums\RoomUserStatus;
use App\Events\ProxyOrdersCreated;
use App\Models\Campaign;
use App\Models\Order;
use App\Models\RoomUser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateProxyOrdersAction
{
    public function __construct(private readonly CreateOrderAction $createOrderAction)
    {
    }

    /**
     * Split a cart containing mixed proxy items into one Parent Order (for the requester)
     * and N Child Orders (one per unique proxy_user_code recipient).
     *
     * If all items belong to proxied users (none for the requester), a zero-item Parent Order
     * is still created as an ownership placeholder, per the confirmed design decision.
     *
     * @param Campaign $campaign         Active campaign.
     * @param RoomUser $requesterRoomUser Room user who is placing the order.
     * @param array<string, mixed> $data  Validated checkout payload (items[], payment_method, note).
     * @return Order The created parent order.
     * @throws ValidationException When a proxy_user_code cannot be resolved inside the campaign room.
     */
    public function execute(Campaign $campaign, RoomUser $requesterRoomUser, array $data): Order
    {
        // Split items into requester's own and proxied groups
        $items = $data['items'] ?? [];

        /** @var array<int, array<string, mixed>> $ownItems */
        $ownItems = [];
        /** @var array<string, array<int, array<string, mixed>>> $proxyGroups keyed by proxy_user_code */
        $proxyGroups = [];

        foreach ($items as $item) {
            $code = isset($item['proxy_user_code']) && $item['proxy_user_code'] !== ''
                ? (string) $item['proxy_user_code']
                : null;

            if ($code === null) {
                $ownItems[] = $item;
            } else {
                $proxyGroups[$code][] = $item;
            }
        }

        // Resolve all proxy room users upfront (fail fast before any DB write)
        /** @var array<string, RoomUser> $proxyRoomUsers keyed by proxy_user_code */
        $proxyRoomUsers = [];
        foreach (array_keys($proxyGroups) as $code) {
            $proxyRoomUser = RoomUser::query()
                ->where('room_id', $campaign->room_id)
                ->where('user_code', $code)
                ->where('status', RoomUserStatus::Active->value)
                ->first();

            if ($proxyRoomUser === null) {
                throw ValidationException::withMessages([
                    'items' => __('room.campaign.proxy_user_not_found', ['code' => $code]),
                ]);
            }

            // Prevent ordering for oneself via proxy_user_code
            if ($proxyRoomUser->id === $requesterRoomUser->id) {
                throw ValidationException::withMessages([
                    'items' => __('room.campaign.proxy_self_not_allowed'),
                ]);
            }

            $proxyRoomUsers[$code] = $proxyRoomUser;
        }

        /** @var array{parent: Order, children: Collection<int, Order>} $created */
        $created = DB::transaction(function () use ($campaign, $requesterRoomUser, $data, $ownItems, $proxyGroups, $proxyRoomUsers): array {
            // Create parent order (may have zero items when requester ordered only for others)
            $parentData = array_merge($data, ['items' => $ownItems]);
            $parentOrder = $this->createOrderAction->execute($campaign, $requesterRoomUser, $parentData, null, true);

            /** @var Collection<int, Order> $childOrders */
            $childOrders = collect();
            foreach ($proxyGroups as $code => $proxyItems) {
                $proxyRoomUser = $proxyRoomUsers[$code];
                $childData = array_merge($data, ['items' => $proxyItems]);
                $childOrders->push($this->createOrderAction->execute($campaign, $proxyRoomUser, $childData, $parentOrder->id));
            }

            return ['parent' => $parentOrder, 'children' => $childOrders];
        });

        $parentOrder = $created['parent'];
        $childOrders = $created['children'];

        // Dispatch notification event (DB + realtime) for all proxy recipients
        if ($childOrders->isNotEmpty()) {
            ProxyOrdersCreated::dispatch($parentOrder, $childOrders);
        }

        return $parentOrder;
    }
}

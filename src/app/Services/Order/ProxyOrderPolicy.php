<?php

declare(strict_types=1);

namespace App\Services\Order;

use App\Models\RoomUser;
use Illuminate\Validation\ValidationException;

class ProxyOrderPolicy
{
    /**
     * Whether a proxy recipient code points at the member who is placing the order.
     *
     * @param RoomUser $requester Member ordering on behalf of someone else.
     * @param string|null $proxyUserCode Recipient member code (user_code) entered by the requester.
     * @return bool True when the recipient is the requester.
     */
    public function isSelf(RoomUser $requester, ?string $proxyUserCode): bool
    {
        $code = mb_strtolower(trim((string) $proxyUserCode));

        return $code !== '' && $code === mb_strtolower((string) $requester->user_code);
    }

    /**
     * Reject a proxy recipient that is the requester: ordering for yourself is done with your own items.
     *
     * @param RoomUser $requester Member ordering on behalf of someone else.
     * @param string|null $proxyUserCode Recipient member code.
     * @param string $field Validation error key to report.
     * @return void
     * @throws ValidationException When the recipient is the requester.
     */
    public function assertNotSelf(RoomUser $requester, ?string $proxyUserCode, string $field = 'items'): void
    {
        if ($this->isSelf($requester, $proxyUserCode)) {
            throw ValidationException::withMessages([$field => __('room.campaign.proxy_self_not_allowed')]);
        }
    }

    /**
     * A member may order on behalf of others only when at least one item is for themselves.
     *
     * @param array<int, array<string, mixed>> $items Cart or checkout items; an item with a `proxy_user_code` is for someone else.
     * @param string $field Validation error key to report.
     * @return void
     * @throws ValidationException When items are ordered for others but none is for the requester.
     */
    public function assertHasOwnItems(array $items, string $field = 'items'): void
    {
        $proxyItems = 0;
        $ownItems = 0;
        foreach ($items as $item) {
            if (! empty($item['proxy_user_code'])) {
                $proxyItems++;
            } else {
                $ownItems++;
            }
        }

        if ($proxyItems > 0 && $ownItems === 0) {
            throw ValidationException::withMessages([$field => __('room.campaign.proxy_requires_own_order')]);
        }
    }
}

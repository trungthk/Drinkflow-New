<?php

declare(strict_types=1);

namespace App\Http\Resources\Member;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A member's own room membership (no internal IDs of the global account).
 *
 * @mixin \App\Models\RoomUser
 */
class MembershipResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request Incoming request.
     * @return array<string, mixed> Membership fields.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_code' => $this->user_code,
            'display_name' => $this->display_name,
            'status' => $this->status,
            'joined_at' => $this->joined_at,
            'room' => new RoomResource($this->whenLoaded('room')),
        ];
    }
}

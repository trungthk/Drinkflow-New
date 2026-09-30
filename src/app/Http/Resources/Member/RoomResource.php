<?php

declare(strict_types=1);

namespace App\Http\Resources\Member;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Room as members see it: public fields only (no settings, owner or internal columns).
 *
 * @mixin \App\Models\Room
 */
class RoomResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request Incoming request.
     * @return array<string, mixed> Public room fields.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'avatar_url' => $this->avatar_url,
            'campaigns_count' => $this->whenCounted('campaigns'),
        ];
    }
}

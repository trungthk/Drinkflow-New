<?php

declare(strict_types=1);

namespace App\Http\Resources\Member;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Campaign header fields shown to members next to their own orders (no other members' orders).
 *
 * @mixin \App\Models\Campaign
 */
class CampaignSummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request Incoming request.
     * @return array<string, mixed> Campaign summary.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'restaurant' => $this->restaurant,
            'status' => $this->status,
            'deadline' => $this->deadline,
            'delivery_fee' => $this->delivery_fee,
            'discount' => $this->discount,
            'sponsor_type' => $this->sponsor_type,
            'sponsor_name' => $this->sponsor_name,
        ];
    }
}

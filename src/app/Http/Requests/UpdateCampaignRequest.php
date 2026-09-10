<?php

namespace App\Http\Requests;

class UpdateCampaignRequest extends StoreCampaignRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), ['status' => ['sometimes', 'in:draft,scheduled,active,closed,cancelled,archived']]);
    }
}

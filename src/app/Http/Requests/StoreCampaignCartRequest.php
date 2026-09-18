<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignCartRequest extends FormRequest
{
    use AuthorizesUserAndAdmin;

    /**
     * Determine whether the current room member may update the cart.
     *
     * @return bool True when the user and room are active.
     */
    public function authorize(): bool
    {
        return $this->authorizeActiveRoomUser();
    }

    /**
     * Get the validation rules for one configured cart item.
     *
     * @return array<string, array<int, mixed>> Validation rules.
     */
    public function rules(): array
    {
        return [
            'item_id'         => ['required', 'integer'],
            'size_id'         => ['nullable', 'integer'],
            'topping_ids'     => ['nullable', 'array'],
            'topping_ids.*'   => ['integer'],
            'note'            => ['nullable', 'string', 'max:500'],
            'quantity'        => ['required', 'integer', 'min:1', 'max:99'],
            'proxy_user_code' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Get translated field names for validation feedback.
     *
     * @return array<string, string> Translated attributes.
     */
    public function attributes(): array
    {
        return [
            'item_id'         => __('room.campaign.validation.item'),
            'size_id'         => __('room.campaign.validation.size'),
            'topping_ids'     => __('room.campaign.validation.toppings'),
            'topping_ids.*'   => __('room.campaign.validation.topping'),
            'note'            => __('room.campaign.validation.note'),
            'quantity'        => __('room.campaign.validation.quantity'),
            'proxy_user_code' => __('room.campaign.validation.proxy_user_code'),
        ];
    }

    /**
     * Get translated validation messages for the cart payload.
     *
     * @return array<string, string> Validation messages.
     */
    public function messages(): array
    {
        return [
            'item_id.required' => __('room.campaign.validation.item_required'),
            'item_id.integer' => __('room.campaign.validation.item_integer'),
            'size_id.integer' => __('room.campaign.validation.size_integer'),
            'topping_ids.array' => __('room.campaign.validation.toppings_array'),
            'topping_ids.*.integer' => __('room.campaign.validation.topping_integer'),
            'note.string' => __('room.campaign.validation.note_string'),
            'note.max' => __('room.campaign.validation.note_max'),
            'quantity.required' => __('room.campaign.validation.quantity_required'),
            'quantity.integer' => __('room.campaign.validation.quantity_integer'),
            'quantity.min' => __('room.campaign.validation.quantity_min'),
            'quantity.max' => __('room.campaign.validation.quantity_max'),
        ];
    }
}

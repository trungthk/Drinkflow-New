<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderItemsRequest extends FormRequest
{
    use AuthorizesUserAndAdmin;

    /**
     * Only an active admin of the room may edit a member's order items.
     *
     * @return bool True when the current admin manages the room.
     */
    public function authorize(): bool
    {
        return $this->authorizeActiveRoomAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Lines have no proxy (order-for-someone-else) field: the whole order stays billed to its member.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'items'                 => ['required', 'array', 'min:1', 'max:50'],
            'items.*.item_id'       => ['required', 'integer'],
            'items.*.quantity'      => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.size_id'       => ['nullable', 'integer'],
            'items.*.topping_ids'   => ['nullable', 'array'],
            'items.*.topping_ids.*' => ['integer', 'distinct'],
            'items.*.note'          => ['nullable', 'string', 'max:500'],
            'items.*.is_self_paid'  => ['nullable', 'boolean'],
            'note'                  => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'items' => __('admin.on_behalf_items'),
        ];
    }
}

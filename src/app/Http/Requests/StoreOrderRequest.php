<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreOrderRequest extends FormRequest {
    public function authorize(): bool { return (bool) $this->user('web'); }
    public function rules(): array { return ['items' => ['required','array','min:1'], 'items.*.item_id' => ['required','integer','distinct'], 'items.*.quantity' => ['required','integer','min:1','max:99'], 'items.*.size_id' => ['nullable','integer'], 'items.*.topping_ids' => ['nullable','array'], 'items.*.topping_ids.*' => ['integer','distinct'], 'items.*.ice_percent' => ['nullable','integer','min:0','max:100'], 'items.*.sugar_percent' => ['nullable','integer','min:0','max:100'], 'items.*.note' => ['nullable','string','max:500'], 'payment_method' => ['nullable','string','max:30'], 'note' => ['nullable','string','max:1000']]; }
}

<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreItemOptionRequest extends FormRequest { public function authorize(): bool{return (bool)$this->user('admin');} public function rules(): array{return ['name'=>['required','string','max:120'],'price'=>['nullable','integer','min:0'],'price_delta'=>['nullable','integer','min:0'],'status'=>['nullable','in:active,hidden'],'sort_order'=>['nullable','integer','min:0']];} }

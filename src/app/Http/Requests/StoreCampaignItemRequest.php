<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreCampaignItemRequest extends FormRequest { public function authorize(): bool{return (bool)$this->user('admin');} public function rules(): array{return ['name'=>['required','string','max:200'],'category'=>['nullable','string','max:100'],'description'=>['nullable','string','max:2000'],'image_url'=>['nullable','url','max:1000'],'base_price'=>['required','integer','min:0'],'status'=>['nullable','in:active,hidden,sold_out,temporarily_unavailable'],'sort_order'=>['nullable','integer','min:0']];} }

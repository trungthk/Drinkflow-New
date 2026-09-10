<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreCampaignRequest extends FormRequest { public function authorize(): bool{return (bool)$this->user('admin');} public function rules(): array{return ['name'=>['required','string','max:160'],'restaurant'=>['required','string','max:160'],'sponsor_name'=>['nullable','string','max:160'],'deadline'=>['nullable','date','after:now'],'max_budget'=>['nullable','integer','min:0'],'flat_price'=>['nullable','integer','min:0'],'delivery_fee'=>['nullable','integer','min:0'],'discount'=>['nullable','integer','min:0'],'payment_account_id'=>['nullable','integer'],'description'=>['nullable','string','max:5000'],'status'=>['nullable','in:draft,scheduled,active']];} }

<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StorePaymentAccountRequest extends FormRequest { public function authorize(): bool{return (bool)$this->user('admin');} public function rules(): array{$required=$this->isMethod('post')?'required':'sometimes';return ['bank_code'=>[$required,'string','max:30'],'bank_name'=>[$required,'string','max:120'],'account_number'=>[$required,'string','max:40'],'account_name'=>[$required,'string','max:160'],'is_default'=>['sometimes','boolean'],'status'=>['sometimes','in:active,disabled']];} }

<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class DebtAdjustmentRequest extends FormRequest { public function authorize(): bool{return (bool)$this->user('admin');} public function rules(): array{return ['type'=>['required','in:increase,decrease,waive,correction'],'amount'=>['required','integer','min:0'],'reason'=>['required','string','max:1000']];} }

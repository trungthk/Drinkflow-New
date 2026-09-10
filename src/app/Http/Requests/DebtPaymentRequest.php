<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class DebtPaymentRequest extends FormRequest { public function authorize(): bool{return (bool)$this->user('admin');} public function rules(): array{return ['amount'=>['required','integer','min:1'],'payment_method'=>['required','string','max:30'],'reference'=>['nullable','string','max:120']];} }

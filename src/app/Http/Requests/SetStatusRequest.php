<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class SetStatusRequest extends FormRequest { public function authorize(): bool{return (bool)($this->user('admin')||$this->user('web'));} public function rules(): array{return ['status'=>['required','string']];} }

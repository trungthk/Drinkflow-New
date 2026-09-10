<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class UpdateOrderStatusRequest extends FormRequest { public function authorize(): bool{return (bool)$this->user('admin');} public function rules(): array{return ['status'=>['required','in:submitted,confirmed,ordering,ordered,delivering,completed,cancelled']];} }

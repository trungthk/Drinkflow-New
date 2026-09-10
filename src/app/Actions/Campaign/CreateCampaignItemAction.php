<?php
namespace App\Actions\Campaign;
use App\Models\Campaign; use App\Models\CampaignItem; use Illuminate\Support\Facades\DB; use Illuminate\Validation\ValidationException;
class CreateCampaignItemAction { public function execute(Campaign $campaign,array $data): CampaignItem { if($campaign->status?->value==='closed'||$campaign->status?->value==='cancelled') throw ValidationException::withMessages(['campaign'=>'Campaign đã đóng.']); return DB::transaction(fn()=> $campaign->items()->create(array_merge($data,['normalized_name'=>$this->normalize($data['name']),'status'=>$data['status']??'active']))); } private function normalize(string $value): string{return strtoupper(trim(preg_replace('/\s+/',' ',iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$value)?:$value)));} }

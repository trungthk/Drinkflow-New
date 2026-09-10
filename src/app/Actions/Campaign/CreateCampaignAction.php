<?php
namespace App\Actions\Campaign;
use App\Models\Campaign; use App\Models\PaymentAccount; use App\Models\Room; use Illuminate\Support\Facades\DB; use Illuminate\Validation\ValidationException;
class CreateCampaignAction { public function execute(Room $room, array $data, ?int $adminId=null): Campaign { if(!empty($data['payment_account_id'])&&!PaymentAccount::whereKey($data['payment_account_id'])->where('room_id',$room->id)->exists()) throw ValidationException::withMessages(['payment_account_id'=>'Tài khoản thanh toán không thuộc Room.']); return DB::transaction(fn()=>Campaign::create(array_merge($data,['room_id'=>$room->id,'creator_admin_id'=>$adminId,'status'=>$data['status']??'draft']))); } }

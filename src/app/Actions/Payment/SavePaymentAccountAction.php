<?php
namespace App\Actions\Payment;
use App\Models\PaymentAccount; use App\Models\Room; use Illuminate\Support\Facades\DB;
class SavePaymentAccountAction { public function execute(Room $room,array $data,?PaymentAccount $account=null): PaymentAccount { return DB::transaction(function()use($room,$data,$account){$account=$account?:new PaymentAccount(['room_id'=>$room->id]);$account->fill($data);$account->room_id=$room->id;if($account->is_default&&$account->status==='active')PaymentAccount::where('room_id',$room->id)->where('id','!=',$account->id?:0)->update(['is_default'=>false]);$account->save();return $account->fresh();}); } }

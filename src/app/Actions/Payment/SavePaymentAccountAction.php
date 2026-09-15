<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Models\PaymentAccount;
use App\Models\Room;
use Illuminate\Support\Facades\DB;

class SavePaymentAccountAction
{
    /**
     * Lưu hoặc cập nhật tài khoản thanh toán cho phòng.
     *
     * @param  Room              $room    Phòng chứa tài khoản.
     * @param  array             $data    Dữ liệu điền vào tài khoản.
     * @param  PaymentAccount|null  $account Tài khoản cần cập nhật (null = tạo mới).
     * @return PaymentAccount Tài khoản đã lưu và được làm mới.
     */
    public function execute(Room $room, array $data, ?PaymentAccount $account = null): PaymentAccount
    {
        return DB::transaction(function () use ($room, $data, $account): PaymentAccount {
            $account          = $account ?: new PaymentAccount(['room_id' => $room->id]);
            $account->fill($data);
            $account->room_id = $room->id;

            if ($account->is_default) {
                PaymentAccount::where('room_id', $room->id)->lockForUpdate()->get(['id']);
                PaymentAccount::where('room_id', $room->id)
                    ->where('id', '!=', $account->id ?: 0)
                    ->update(['is_default' => false]);
            }

            $account->save();

            return $account->fresh();
        });
    }
}

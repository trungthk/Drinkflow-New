<?php

namespace Database\Seeders;

use App\Models\AdminAccount;
use App\Models\Campaign;
use App\Models\CampaignItem;
use App\Models\CampaignItemSize;
use App\Models\CampaignItemTopping;
use App\Models\Debt;
use App\Models\GlobalUser;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentAccount;
use App\Models\Room;
use App\Models\RoomUser;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /** Seed a small, repeatable workspace for local development and demos. */
    public function run(): void
    {
        $admin = AdminAccount::updateOrCreate(
            ['email' => 'admin@drinkflow.local'],
            ['name' => 'DrinkFlow Admin', 'password' => Hash::make('password'), 'role' => 'admin', 'status' => 'active'],
        );
        $superadmin = AdminAccount::updateOrCreate(
            ['email' => 'superadmin@drinkflow.local'],
            ['name' => 'DrinkFlow Superadmin', 'password' => Hash::make('password'), 'role' => 'superadmin', 'status' => 'active'],
        );

        $room = Room::updateOrCreate(
            ['slug' => 'van-phong-chinh'],
            ['name' => 'Văn phòng chính', 'description' => 'Không gian đặt đồ uống cho toàn văn phòng.', 'status' => 'active', 'timezone' => 'Asia/Ho_Chi_Minh', 'language' => 'vi'],
        );
        $secondRoom = Room::updateOrCreate(
            ['slug' => 'marketing'],
            ['name' => 'Marketing', 'description' => 'Phòng Marketing và các chiến dịch nội bộ.', 'status' => 'active', 'timezone' => 'Asia/Ho_Chi_Minh', 'language' => 'vi'],
        );
        $admin->rooms()->syncWithoutDetaching([$room->id, $secondRoom->id]);
        $superadmin->rooms()->syncWithoutDetaching([$room->id, $secondRoom->id]);

        $payment = PaymentAccount::updateOrCreate(
            ['room_id' => $room->id, 'account_number' => '0123456789'],
            ['bank_code' => 'VCB', 'bank_name' => 'Vietcombank', 'account_name' => 'DRINKFLOW COMPANY', 'is_default' => true, 'status' => 'active'],
        );

        $alice = GlobalUser::updateOrCreate(
            ['email' => 'alice@drinkflow.local'],
            ['name' => 'Nguyễn An', 'normalized_name' => 'nguyen an', 'status' => 'active'],
        );
        $bob = GlobalUser::updateOrCreate(
            ['email' => 'bob@drinkflow.local'],
            ['name' => 'Trần Bình', 'normalized_name' => 'tran binh', 'status' => 'active'],
        );
        $aliceRoomUser = RoomUser::updateOrCreate(
            ['room_id' => $room->id, 'global_user_id' => $alice->id],
            ['user_code' => 'NA', 'display_name' => 'Nguyễn An', 'normalized_name' => 'nguyen an', 'status' => 'active'],
        );
        $bobRoomUser = RoomUser::updateOrCreate(
            ['room_id' => $room->id, 'global_user_id' => $bob->id],
            ['user_code' => 'TB', 'display_name' => 'Trần Bình', 'normalized_name' => 'tran binh', 'status' => 'active'],
        );

        $campaign = Campaign::updateOrCreate(
            ['room_id' => $room->id, 'name' => 'Trà sữa thứ Sáu'],
            ['restaurant' => 'The Coffee House', 'creator_admin_id' => $admin->id, 'sponsor_name' => 'Công ty hỗ trợ 20k/người', 'deadline' => now()->addDays(2), 'delivery_fee' => 15000, 'discount' => 0, 'payment_account_id' => $payment->id, 'description' => 'Đặt trước 10:30 để giao cùng một chuyến.', 'status' => 'active', 'started_at' => now()->subHour()],
        );
        $milkTea = CampaignItem::updateOrCreate(
            ['campaign_id' => $campaign->id, 'name' => 'Trà sữa ô long'],
            ['normalized_name' => 'tra sua o long', 'category' => 'Trà sữa', 'description' => 'Ô long rang, sữa tươi và trân châu đen.', 'base_price' => 45000, 'status' => 'active', 'sort_order' => 1],
        );
        $coffee = CampaignItem::updateOrCreate(
            ['campaign_id' => $campaign->id, 'name' => 'Cà phê sữa đá'],
            ['normalized_name' => 'ca phe sua da', 'category' => 'Cà phê', 'description' => 'Cà phê rang xay, sữa đặc và đá.', 'base_price' => 39000, 'status' => 'active', 'sort_order' => 2],
        );
        foreach ([[$milkTea, 'M', 0, 1], [$milkTea, 'L', 10000, 2], [$coffee, 'M', 0, 1]] as [$item, $name, $delta, $sort]) {
            CampaignItemSize::updateOrCreate(['campaign_item_id' => $item->id, 'name' => $name], ['price_delta' => $delta, 'status' => 'active', 'sort_order' => $sort]);
        }
        foreach ([[$milkTea, 'Trân châu đen', 7000], [$milkTea, 'Thạch dừa', 5000]] as [$item, $name, $price]) {
            CampaignItemTopping::updateOrCreate(['campaign_item_id' => $item->id, 'name' => $name], ['price' => $price, 'status' => 'active', 'sort_order' => 1]);
        }

        $order = Order::updateOrCreate(
            ['campaign_id' => $campaign->id, 'room_user_id' => $aliceRoomUser->id],
            ['room_id' => $room->id, 'payment_method' => 'transfer', 'subtotal' => 52000, 'delivery_amount' => 7500, 'discount_amount' => 0, 'sponsor_amount' => 20000, 'final_amount' => 39500, 'status' => 'completed', 'note' => 'Ít đá, 70% đường', 'submitted_at' => now()->subDay(), 'completed_at' => now()->subDay()->addHours(2)],
        );
        OrderItem::updateOrCreate(
            ['order_id' => $order->id, 'campaign_item_id' => $milkTea->id],
            ['item_name' => $milkTea->name, 'size_name' => 'M', 'unit_price' => 45000, 'quantity' => 1, 'ice_percent' => 50, 'sugar_percent' => 70, 'line_subtotal' => 52000],
        );
        Debt::updateOrCreate(
            ['campaign_id' => $campaign->id, 'room_user_id' => $aliceRoomUser->id],
            ['room_id' => $room->id, 'original_amount' => 39500, 'sponsor_amount' => 20000, 'adjustment_amount' => 0, 'paid_amount' => 0, 'remaining_amount' => 39500, 'status' => 'unpaid', 'note' => 'Chờ thanh toán'],
        );

        foreach ([[$alice, $aliceRoomUser], [$bob, $bobRoomUser]] as [$user, $roomUser]) {
            DB::table('user_notifications')->where('global_user_id', $user->id)->where('type', 'campaign_opened')->delete();
            DB::table('user_notifications')->updateOrInsert(
                ['global_user_id' => $user->id, 'type' => 'campaign.created'],
                ['room_user_id' => $roomUser->id, 'title' => 'Chiến dịch mới mở', 'body' => 'Trà sữa thứ Sáu đang nhận đơn.', 'data' => json_encode(['campaign_id' => $campaign->id]), 'created_at' => now(), 'updated_at' => now()],
            );
        }

        User::firstOrCreate(['email' => 'test@example.com'], ['name' => 'Test User', 'password' => Hash::make('password')]);
    }
}

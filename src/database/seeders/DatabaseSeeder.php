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

    /** Seed a rich, repeatable workspace for local testing, demos, and join-room flows. */
    public function run(): void
    {
        // 1. Core Admins
        $admin = AdminAccount::updateOrCreate(
            ['email' => 'admin@drinkflow.local'],
            ['name' => 'DrinkFlow Admin', 'password' => Hash::make('password'), 'role' => 'admin', 'status' => 'active'],
        );
        $superadmin = AdminAccount::updateOrCreate(
            ['email' => 'superadmin@drinkflow.local'],
            ['name' => 'DrinkFlow Superadmin', 'password' => Hash::make('password'), 'role' => 'superadmin', 'status' => 'active'],
        );

        // 2. Demo Rooms
        $techRoom = Room::updateOrCreate(
            ['slug' => 'cong-nghe'],
            [
                'name' => 'Phòng Công Nghệ & Kỹ Thuật',
                'description' => 'Không gian đặt đồ uống và trà chiều cho team Kỹ thuật & Phát triển sản phẩm.',
                'status' => 'active',
                'timezone' => 'Asia/Ho_Chi_Minh',
                'language' => 'vi',
            ],
        );

        $mainRoom = Room::updateOrCreate(
            ['slug' => 'van-phong-chinh'],
            [
                'name' => 'Văn phòng chính',
                'description' => 'Không gian đặt đồ uống cho toàn văn phòng.',
                'status' => 'active',
                'timezone' => 'Asia/Ho_Chi_Minh',
                'language' => 'vi',
            ],
        );

        $marketingRoom = Room::updateOrCreate(
            ['slug' => 'marketing'],
            [
                'name' => 'Marketing & Truyền thông',
                'description' => 'Phòng Marketing và các chiến dịch nội bộ.',
                'status' => 'active',
                'timezone' => 'Asia/Ho_Chi_Minh',
                'language' => 'vi',
            ],
        );

        $admin->rooms()->syncWithoutDetaching([$techRoom->id, $mainRoom->id, $marketingRoom->id]);
        $superadmin->rooms()->syncWithoutDetaching([$techRoom->id, $mainRoom->id, $marketingRoom->id]);

        // 3. Payment Accounts
        $techPayment = PaymentAccount::updateOrCreate(
            ['room_id' => $techRoom->id, 'account_number' => '0388999888'],
            ['bank_code' => 'MB', 'bank_name' => 'MB Bank', 'account_name' => 'DRINKFLOW TECH FUND', 'is_default' => true, 'status' => 'active'],
        );

        $mainPayment = PaymentAccount::updateOrCreate(
            ['room_id' => $mainRoom->id, 'account_number' => '0123456789'],
            ['bank_code' => 'VCB', 'bank_name' => 'Vietcombank', 'account_name' => 'DRINKFLOW COMPANY', 'is_default' => true, 'status' => 'active'],
        );

        // 4. Global Users & Demo Room Members
        $alice = GlobalUser::updateOrCreate(
            ['email' => 'alice@drinkflow.local'],
            ['name' => 'Nguyễn An', 'normalized_name' => 'nguyen an', 'status' => 'active'],
        );
        $bob = GlobalUser::updateOrCreate(
            ['email' => 'bob@drinkflow.local'],
            ['name' => 'Trần Bình', 'normalized_name' => 'tran binh', 'status' => 'active'],
        );

        $aliceTechUser = RoomUser::updateOrCreate(
            ['room_id' => $techRoom->id, 'global_user_id' => $alice->id],
            ['user_code' => 'TECH-NA', 'display_name' => 'Nguyễn An', 'normalized_name' => 'nguyen an', 'status' => 'active'],
        );
        $bobTechUser = RoomUser::updateOrCreate(
            ['room_id' => $techRoom->id, 'global_user_id' => $bob->id],
            ['user_code' => 'TECH-TB', 'display_name' => 'Trần Bình', 'normalized_name' => 'tran binh', 'status' => 'active'],
        );

        $aliceMainUser = RoomUser::updateOrCreate(
            ['room_id' => $mainRoom->id, 'global_user_id' => $alice->id],
            ['user_code' => 'HQ-NA', 'display_name' => 'Nguyễn An', 'normalized_name' => 'nguyen an', 'status' => 'active'],
        );

        // 5. Active Campaign for Tech Room
        $techCampaign = Campaign::updateOrCreate(
            ['room_id' => $techRoom->id, 'name' => 'Trà chiều Thứ 6 - Highlands & Phúc Long'],
            [
                'restaurant' => 'Phúc Long & Highlands Coffee',
                'creator_admin_id' => $admin->id,
                'sponsor_name' => 'Quỹ Tech hỗ trợ 25.000đ/người',
                'deadline' => now()->addHours(3),
                'delivery_fee' => 15000,
                'discount' => 0,
                'payment_account_id' => $techPayment->id,
                'description' => 'Chốt đơn lúc 15:30 chiều nay. Đơn trên 200k freeship toàn bộ.',
                'status' => 'active',
                'started_at' => now()->subHour(),
            ],
        );

        // Menu Items for Tech Campaign
        $itemsData = [
            [
                'name' => 'Trà sen vàng',
                'category' => 'Trà trái cây',
                'desc' => 'Trà Ô long kết hợp hạt sen bùi béo và lớp kem phô mai sánh mịn.',
                'price' => 49000,
                'sizes' => [['M', 0], ['L', 10000]],
                'toppings' => [['Hạt sen', 10000], ['Củ năng', 8000], ['Trân châu trắng', 7000]],
            ],
            [
                'name' => 'Phin sữa đá truyền thống',
                'category' => 'Cà phê',
                'desc' => 'Hạt Robusta rang đậm vị pha phin truyền thống, sữa đặc thơm ngọt.',
                'price' => 35000,
                'sizes' => [['M', 0], ['L', 6000]],
                'toppings' => [['Thạch cà phê', 8000], ['Kem béo phin', 10000]],
            ],
            [
                'name' => 'Freeze trà xanh',
                'category' => 'Freeze & Đá xay',
                'desc' => 'Đá xay trà xanh matcha thơm ngậy kết hợp thạch trà xanh giòn dai.',
                'price' => 55000,
                'sizes' => [['M', 0], ['L', 10000]],
                'toppings' => [['Thạch trà xanh', 10000], ['Whipped Cream', 8000]],
            ],
            [
                'name' => 'Trà đào cam sả',
                'category' => 'Trà trái cây',
                'desc' => 'Trà đen ủ lạnh với nước cốt cam vàng mọng nước, sả thơm và đào ngâm giòn.',
                'price' => 45000,
                'sizes' => [['M', 0], ['L', 10000]],
                'toppings' => [['Đào miếng', 10000], ['Thạch nha đam', 7000]],
            ],
            [
                'name' => 'Trà sữa ô long nướng',
                'category' => 'Trà sữa',
                'desc' => 'Vị trà đậm đà rang kỹ, sữa tươi thanh béo ngọt vừa phải.',
                'price' => 42000,
                'sizes' => [['M', 0], ['L', 8000]],
                'toppings' => [['Trân châu đen', 7000], ['Pudding trứng', 8000]],
            ],
            [
                'name' => 'Bánh Mousse Cacao',
                'category' => 'Bánh & Snack',
                'desc' => 'Bánh ngọt mềm mịn đậm vị sô cô la Bỉ hảo hạng.',
                'price' => 35000,
                'sizes' => [],
                'toppings' => [],
            ],
        ];

        foreach ($itemsData as $idx => $data) {
            $item = CampaignItem::updateOrCreate(
                ['campaign_id' => $techCampaign->id, 'name' => $data['name']],
                [
                    'normalized_name' => mb_strtolower($data['name'], 'UTF-8'),
                    'category' => $data['category'],
                    'description' => $data['desc'],
                    'base_price' => $data['price'],
                    'status' => 'active',
                    'sort_order' => $idx + 1,
                ],
            );

            foreach ($data['sizes'] as $sIdx => [$sName, $delta]) {
                CampaignItemSize::updateOrCreate(
                    ['campaign_item_id' => $item->id, 'name' => $sName],
                    ['price_delta' => $delta, 'status' => 'active', 'sort_order' => $sIdx + 1],
                );
            }

            foreach ($data['toppings'] as $tIdx => [$tName, $tPrice]) {
                CampaignItemTopping::updateOrCreate(
                    ['campaign_item_id' => $item->id, 'name' => $tName],
                    ['price' => $tPrice, 'status' => 'active', 'sort_order' => $tIdx + 1],
                );
            }
        }

        // 6. Existing Active Campaign for Main Room
        $mainCampaign = Campaign::updateOrCreate(
            ['room_id' => $mainRoom->id, 'name' => 'Trà sữa thứ Sáu'],
            [
                'restaurant' => 'The Coffee House',
                'creator_admin_id' => $admin->id,
                'sponsor_name' => 'Công ty hỗ trợ 20k/người',
                'deadline' => now()->addDays(2),
                'delivery_fee' => 15000,
                'discount' => 0,
                'payment_account_id' => $mainPayment->id,
                'description' => 'Đặt trước 10:30 để giao cùng một chuyến.',
                'status' => 'active',
                'started_at' => now()->subHour(),
            ],
        );

        $milkTea = CampaignItem::updateOrCreate(
            ['campaign_id' => $mainCampaign->id, 'name' => 'Trà sữa ô long'],
            ['normalized_name' => 'tra sua o long', 'category' => 'Trà sữa', 'description' => 'Ô long rang, sữa tươi và trân châu đen.', 'base_price' => 45000, 'status' => 'active', 'sort_order' => 1],
        );
        $coffee = CampaignItem::updateOrCreate(
            ['campaign_id' => $mainCampaign->id, 'name' => 'Cà phê sữa đá'],
            ['normalized_name' => 'ca phe sua da', 'category' => 'Cà phê', 'description' => 'Cà phê rang xay, sữa đặc và đá.', 'base_price' => 39000, 'status' => 'active', 'sort_order' => 2],
        );

        foreach ([[$milkTea, 'M', 0, 1], [$milkTea, 'L', 10000, 2], [$coffee, 'M', 0, 1]] as [$cItem, $cName, $cDelta, $cSort]) {
            CampaignItemSize::updateOrCreate(['campaign_item_id' => $cItem->id, 'name' => $cName], ['price_delta' => $cDelta, 'status' => 'active', 'sort_order' => $cSort]);
        }
        foreach ([[$milkTea, 'Trân châu đen', 7000], [$milkTea, 'Thạch dừa', 5000]] as [$cItem, $tName, $tPrice]) {
            CampaignItemTopping::updateOrCreate(['campaign_item_id' => $cItem->id, 'name' => $tName], ['price' => $tPrice, 'status' => 'active', 'sort_order' => 1]);
        }

        // 7. Orders & Debts Demo
        $order = Order::updateOrCreate(
            ['campaign_id' => $mainCampaign->id, 'room_user_id' => $aliceMainUser->id],
            ['room_id' => $mainRoom->id, 'payment_method' => 'transfer', 'subtotal' => 52000, 'delivery_amount' => 7500, 'discount_amount' => 0, 'sponsor_amount' => 20000, 'final_amount' => 39500, 'status' => 'completed', 'note' => 'Ít đá, 70% đường', 'submitted_at' => now()->subDay(), 'completed_at' => now()->subDay()->addHours(2)],
        );
        OrderItem::updateOrCreate(
            ['order_id' => $order->id, 'campaign_item_id' => $milkTea->id],
            ['item_name' => $milkTea->name, 'size_name' => 'M', 'unit_price' => 45000, 'quantity' => 1, 'ice_percent' => 50, 'sugar_percent' => 70, 'line_subtotal' => 52000],
        );
        Debt::updateOrCreate(
            ['campaign_id' => $mainCampaign->id, 'room_user_id' => $aliceMainUser->id],
            ['room_id' => $mainRoom->id, 'original_amount' => 39500, 'sponsor_amount' => 20000, 'adjustment_amount' => 0, 'paid_amount' => 0, 'remaining_amount' => 39500, 'status' => 'unpaid', 'note' => 'Chờ thanh toán'],
        );

        // 8. Notifications Demo
        foreach ([[$alice, $aliceTechUser], [$bob, $bobTechUser]] as [$gUser, $rUser]) {
            DB::table('user_notifications')->where('global_user_id', $gUser->id)->where('type', 'campaign.created')->delete();
            DB::table('user_notifications')->updateOrInsert(
                ['global_user_id' => $gUser->id, 'type' => 'campaign.created', 'room_user_id' => $rUser->id],
                ['title' => 'Chiến dịch mới mở', 'body' => 'Trà chiều Thứ 6 - Highlands & Phúc Long đang nhận đơn.', 'data' => json_encode(['campaign_id' => $techCampaign->id]), 'created_at' => now(), 'updated_at' => now()],
            );
        }

        User::firstOrCreate(['email' => 'test@example.com'], ['name' => 'Test User', 'password' => Hash::make('password')]);
    }
}

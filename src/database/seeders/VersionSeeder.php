<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AdminAccount;
use App\Models\Version;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class VersionSeeder extends Seeder
{
    /**
     * Nạp dữ liệu phiên bản ban đầu của hệ thống vào cơ sở dữ liệu.
     */
    public function run(): void
    {
        $superadmin = AdminAccount::query()->where('role', 'superadmin')->first();

        $versions = [
            [
                'version' => 'v2.3.0',
                'title' => 'Multi-room Ordering, Proxy Orders & VietQR Settlement',
                'changelog' => 'Bản phát hành chính thức v2.3.0 hoàn thiện nền tảng đặt đồ uống đa phòng cho môi trường production: xác thực Google OAuth và thiết bị tin cậy; mã đơn hàng chuẩn ORD{room_id}-YYYYMMDD-sequence; đặt món giúp thành viên khác với đơn cha/con, công nợ và thông báo realtime đúng người nhận; VietQR và quy trình xác nhận thanh toán công nợ; cùng hệ thống migration schema hợp nhất, an toàn khi triển khai mới.',
                'release_date' => Carbon::createFromFormat('d/m/Y', '18/09/2026')->toDateString(),
                'force_refresh' => false,
                'important' => true,
                'created_by_admin_id' => $superadmin?->id,
            ]
        ];

        foreach ($versions as $item) {
            Version::updateOrCreate(
                ['version' => $item['version']],
                $item
            );
        }

        Version::clearLatestVersionCache();
    }
}

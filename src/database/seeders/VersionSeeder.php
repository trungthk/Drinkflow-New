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
                'title' => 'Multi-room Authentication & VietQR Direct Split',
                'changelog' => 'Bản cập nhật v2.3.0 mang đến giải pháp xác thực bảo mật đa phòng (Multi-room) qua Google OAuth, tự động nhận diện thiết bị tin cậy và hoàn thiện tính năng tạo mã VietQR chia bill tự động chính xác cho môi trường doanh nghiệp.',
                'release_date' => Carbon::createFromFormat('d/m/Y', '10/09/2026')->toDateString(),
                'force_refresh' => false,
                'important' => true,
                'created_by_admin_id' => $superadmin?->id,
            ],
            [
                'version' => 'v2.2.1',
                'title' => 'Sửa lỗi Socket.IO reconnection & tối ưu UI bàn chốt đơn',
                'changelog' => 'Bản vá tập trung vào độ ổn định kết nối realtime và cải thiện giao diện theo dõi đơn hàng của Host.',
                'release_date' => Carbon::createFromFormat('d/m/Y', '24/08/2026')->toDateString(),
                'force_refresh' => false,
                'important' => false,
                'created_by_admin_id' => $superadmin?->id,
            ],
            [
                'version' => 'v2.2.0',
                'title' => 'Ra mắt tính năng Tài trợ (Sponsor Pool) và tích lũy voucher phòng',
                'changelog' => 'Hỗ trợ các chương trình tài trợ nội bộ, teambuilding và quỹ trà chiều phòng ban với khả năng phân bổ ngân sách tự động.',
                'release_date' => Carbon::createFromFormat('d/m/Y', '12/08/2026')->toDateString(),
                'force_refresh' => false,
                'important' => true,
                'created_by_admin_id' => $superadmin?->id,
            ],
            [
                'version' => 'v2.1.0',
                'title' => 'Hỗ trợ Menu tuỳ chỉnh & Đổi topping động theo thời gian thực',
                'changelog' => 'Cải tiến trải nghiệm tìm kiếm món ăn, đồ uống không dấu và lọc theo mức giá linh hoạt.',
                'release_date' => Carbon::createFromFormat('d/m/Y', '18/07/2026')->toDateString(),
                'force_refresh' => false,
                'important' => false,
                'created_by_admin_id' => $superadmin?->id,
            ],
            [
                'version' => 'v2.0.0',
                'title' => 'Nâng cấp toàn diện giao diện Enterprise Design System v2',
                'changelog' => 'Tái cấu trúc toàn diện hệ thống với Laravel 11, Tailwind CSS và kiến trúc phòng ban tập trung.',
                'release_date' => Carbon::createFromFormat('d/m/Y', '01/06/2026')->toDateString(),
                'force_refresh' => true,
                'important' => true,
                'created_by_admin_id' => $superadmin?->id,
            ],
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

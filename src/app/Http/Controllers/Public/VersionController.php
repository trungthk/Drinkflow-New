<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Version;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class VersionController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, ?string $version = null): View
    {
        // Query versions from database or fallback to rich default published list
        $versionsQuery = Version::query()->orderByDesc('release_date')->orderByDesc('id')->get();

        if ($versionsQuery->isEmpty()) {
            $allVersions = collect($this->getDefaultVersions());
        } else {
            $defaultVersions = collect($this->getDefaultVersions())->keyBy('version');
            $allVersions = $versionsQuery->map(function ($item) use ($defaultVersions) {
                $fallback = $defaultVersions->get($item->version);
                return (object) [
                    'version' => $item->version,
                    'release_date' => $item->release_date?->format('d/m/Y') ?? ($fallback->release_date ?? '10/09/2026'),
                    'title' => $item->title ?? ($fallback->title ?? 'Bản cập nhật DrinkFlow'),
                    'badge' => $fallback->badge ?? 'Release',
                    'important' => (bool) ($item->important ?? ($fallback->important ?? false)),
                    'force_refresh' => (bool) ($item->force_refresh ?? ($fallback->force_refresh ?? false)),
                    'summary' => $fallback->summary ?? 'Thông tin chi tiết về bản cập nhật DrinkFlow.',
                    'commit' => $fallback->commit ?? '#'.substr(md5($item->version), 0, 7),
                    'author' => $fallback->author ?? 'DrinkFlow Core Team',
                    'status' => $fallback->status ?? 'Ổn định (Production)',
                    'features' => $fallback->features ?? [],
                    'improvements' => $fallback->improvements ?? [],
                    'bugfixes' => $fallback->bugfixes ?? [],
                    'security' => $fallback->security ?? [],
                    'changelog' => $item->changelog ?? ($fallback->changelog ?? ''),
                ];
            });
        }

        $latestVersion = $allVersions->first();

        if ($version) {
            $currentIndex = $allVersions->search(fn($item) => $item->version === $version);
            if ($currentIndex === false) {
                abort(404, 'Phiên bản không tồn tại');
            }
            $currentVersion = $allVersions->get($currentIndex);
        } else {
            $currentIndex = 0;
            $currentVersion = $latestVersion;
        }

        // Newer version (index - 1) and older version (index + 1)
        $nextVersion = ($currentIndex > 0) ? $allVersions->get($currentIndex - 1) : null;
        $prevVersion = ($currentIndex < $allVersions->count() - 1) ? $allVersions->get($currentIndex + 1) : null;

        $appVersion = config('app.version', 'v2.3.0');
        $googleAuthUrl = route('auth.google');
        $landingUrl = route('landing');
        $termsUrl = url('/terms');
        $versionsUrl = url('/versions');

        return view('public.versions', [
            'versions' => $allVersions,
            'currentVersion' => $currentVersion,
            'latestVersion' => $latestVersion,
            'nextVersion' => $nextVersion,
            'prevVersion' => $prevVersion,
            'appVersion' => $appVersion,
            'googleAuthUrl' => $googleAuthUrl,
            'landingUrl' => $landingUrl,
            'termsUrl' => $termsUrl,
            'versionsUrl' => $versionsUrl,
        ]);
    }

    /**
     * Provide default published versions verbatim from design spec.
     */
    private function getDefaultVersions(): array
    {
        return [
            (object) [
                'version' => 'v2.3.0',
                'release_date' => '10/09/2026',
                'badge' => 'Latest',
                'important' => true,
                'force_refresh' => false,
                'title' => 'Multi-room Authentication & VietQR Direct Split',
                'summary' => 'Bản cập nhật v2.3.0 mang đến giải pháp xác thực bảo mật đa phòng (Multi-room) qua Google OAuth, tự động nhận diện thiết bị tin cậy và hoàn thiện tính năng tạo mã VietQR chia bill tự động chính xác cho môi trường doanh nghiệp.',
                'commit' => '#a78f3c2',
                'author' => 'DrinkFlow Core Team',
                'status' => 'Ổn định (Production)',
                'features' => [
                    (object) [
                        'title' => 'Xác thực Google Workspace OAuth 2.0',
                        'description' => 'Đăng nhập một chạm bằng email doanh nghiệp (@company.com), tự động định tuyến nhân viên vào đúng kênh phòng ban đã phân quyền từ trước.',
                    ],
                    (object) [
                        'title' => 'Quản lý Multi-room đồng thời',
                        'description' => 'Cho phép một tài khoản tham gia và theo dõi đồng thời nhiều phiên đặt nước khác nhau trong ngày (Ví dụ: Nhóm Dự án A và Nhóm Tầng 4) mà không cần thoát tài khoản.',
                    ],
                    (object) [
                        'title' => 'Sinh mã VietQR động theo từng đơn',
                        'description' => 'Mã QR ngân hàng chuẩn NAPAS chứa sẵn số tiền chiết tính và nội dung chuyển khoản chuẩn cú pháp, tự động quét xác nhận đã thanh toán theo thời gian thực.',
                    ],
                ],
                'improvements' => [
                    (object) [
                        'title' => 'Thiết bị tin cậy (Trusted Devices)',
                        'description' => 'Ghi nhớ thiết bị an toàn trong dải mạng nội bộ văn phòng, giảm số lần yêu cầu xác minh 2FA phiền toái trong 30 ngày làm việc.',
                    ],
                    (object) [
                        'title' => 'Hiệu năng Socket.IO Realtime',
                        'description' => 'Tái kiến trúc cơ chế phát broadcast, giảm độ trễ cập nhật trạng thái đơn hàng xuống dưới 50ms khi có trên 100 người cùng chốt đơn cùng thời điểm.',
                    ],
                    (object) [
                        'title' => 'Xuất báo cáo sao kê CSV/Excel',
                        'description' => 'Cho phép Trưởng phòng hoặc Host tải dữ liệu chi tiết danh sách đồ uống, tiền ship và mã tham chiếu để đối soát với hóa đơn xuất từ cửa hàng.',
                    ],
                ],
                'bugfixes' => [
                    (object) [
                        'title' => 'Ngăn chặn gửi đơn trùng lặp',
                        'description' => 'Khắc phục triệt để lỗi đơn bị nhân đôi khi người dùng bấm gửi order nhiều lần liên tiếp do tín hiệu kết nối mạng Wi-Fi chập chờn.',
                    ],
                    (object) [
                        'title' => 'Chia phí ship và tiền tip chính xác',
                        'description' => 'Sửa sai số thuật toán làm tròn khi phân bổ tiền ship cho các đơn hàng có người tham gia áp mã giảm giá cá nhân đặc thù.',
                    ],
                    (object) [
                        'title' => 'Khôi phục số lượng tồn kho tự động',
                        'description' => 'Tự động hoàn lại số lượng tồn món giới hạn khi người dùng thao tác xóa món hoặc rời khỏi phòng trước giờ chốt sổ.',
                    ],
                ],
                'security' => [
                    (object) [
                        'title' => 'Cross-room Authorization Block',
                        'description' => 'Thiết lập rào chắn kiểm tra quyền nghiêm ngặt, chặn hoàn toàn nguy cơ xem trộm danh mục hoặc sửa đổi đơn hàng giữa các phòng ban nội bộ khác nhau khi chưa có mã mời.',
                    ],
                    (object) [
                        'title' => 'Mã hóa Token chuẩn AES-256',
                        'description' => 'Mã hóa toàn bộ JWT và dữ liệu phiên làm việc lưu tạm trên bộ nhớ trình duyệt nhằm triệt tiêu nguy cơ tấn công chiếm quyền phiên (Session Hijacking).',
                    ],
                ],
                'changelog' => '',
            ],
            (object) [
                'version' => 'v2.2.1',
                'release_date' => '24/08/2026',
                'badge' => 'Maintenance',
                'important' => false,
                'force_refresh' => false,
                'title' => 'Sửa lỗi Socket.IO reconnection & tối ưu UI bàn chốt đơn',
                'summary' => 'Bản vá tập trung vào độ ổn định kết nối realtime và cải thiện giao diện theo dõi đơn hàng của Host.',
                'commit' => '#f92c10b',
                'author' => 'DrinkFlow Core Team',
                'status' => 'Ổn định (Production)',
                'features' => [],
                'improvements' => [
                    (object) [
                        'title' => 'Tự động kết nối lại Socket.IO',
                        'description' => 'Tự động kết nối lại khi mạng chập chờn hoặc thiết bị sleep mà không làm mất giỏ hàng đang chọn.',
                    ],
                    (object) [
                        'title' => 'Tối ưu hóa UI danh sách món',
                        'description' => 'Tăng tốc độ hiển thị và cuộn trang mượt mà khi phòng có trên 50 thành viên đặt đồng thời.',
                    ],
                ],
                'bugfixes' => [
                    (object) [
                        'title' => 'Sửa lỗi tính tiền khi có voucher giảm giá quán',
                        'description' => 'Khắc phục trường hợp giảm giá không trừ đúng tỷ lệ phần trăm trên tổng bill phòng.',
                    ],
                ],
                'security' => [],
                'changelog' => '',
            ],
            (object) [
                'version' => 'v2.2.0',
                'release_date' => '12/08/2026',
                'badge' => 'Feature',
                'important' => true,
                'force_refresh' => false,
                'title' => 'Ra mắt tính năng Tài trợ (Sponsor Pool) và tích lũy voucher phòng',
                'summary' => 'Hỗ trợ các chương trình tài trợ nội bộ, teambuilding và quỹ trà chiều phòng ban với khả năng phân bổ ngân sách tự động.',
                'commit' => '#d41e78a',
                'author' => 'DrinkFlow Core Team',
                'status' => 'Ổn định (Production)',
                'features' => [
                    (object) [
                        'title' => 'Quỹ tài trợ nội bộ (Sponsor Pool)',
                        'description' => 'Host hoặc Quản lý dự án có thể nạp ngân sách tài trợ cố định theo ly hoặc phần trăm giá trị đơn hàng.',
                    ],
                    (object) [
                        'title' => 'Khấu trừ tự động trên hóa đơn',
                        'description' => 'Hệ thống tự động trừ tiền tài trợ vào bill của mỗi thành viên tham gia đặt món.',
                    ],
                ],
                'improvements' => [
                    (object) [
                        'title' => 'Giao diện hiển thị số dư tài trợ',
                        'description' => 'Hiển thị minh bạch số tiền còn lại trong quỹ tài trợ của từng chiến dịch.',
                    ],
                ],
                'bugfixes' => [],
                'security' => [],
                'changelog' => '',
            ],
            (object) [
                'version' => 'v2.1.0',
                'release_date' => '18/07/2026',
                'badge' => 'Feature',
                'important' => false,
                'force_refresh' => false,
                'title' => 'Hỗ trợ Menu tuỳ chỉnh & Đổi topping động theo thời gian thực',
                'summary' => 'Cải tiến trải nghiệm tìm kiếm món ăn, đồ uống không dấu và lọc theo mức giá linh hoạt.',
                'commit' => '#c03b67e',
                'author' => 'DrinkFlow Core Team',
                'status' => 'Ổn định (Production)',
                'features' => [
                    (object) [
                        'title' => 'Smart Search tìm kiếm không dấu',
                        'description' => 'Tìm kiếm nhanh mọi món trà sữa, cà phê không cần gõ dấu tiếng Việt chính xác.',
                    ],
                    (object) [
                        'title' => 'Tùy biến đường, đá và topping linh hoạt',
                        'description' => 'Giao diện chọn mức đường, lượng đá trực quan và lưu ghi chú riêng cho từng ly.',
                    ],
                ],
                'improvements' => [],
                'bugfixes' => [],
                'security' => [],
                'changelog' => '',
            ],
            (object) [
                'version' => 'v2.0.0',
                'release_date' => '01/06/2026',
                'badge' => 'Major',
                'important' => true,
                'force_refresh' => true,
                'title' => 'Nâng cấp toàn diện giao diện Enterprise Design System v2',
                'summary' => 'Tái cấu trúc toàn diện hệ thống với Laravel 11, Tailwind CSS và kiến trúc phòng ban tập trung.',
                'commit' => '#b11a94f',
                'author' => 'DrinkFlow Core Team',
                'status' => 'Ổn định (Production)',
                'features' => [
                    (object) [
                        'title' => 'Enterprise Design System mới',
                        'description' => 'Bộ giao diện hoàn toàn mới với bảng màu hiện đại, tối ưu hiển thị trên cả desktop và thiết bị di động.',
                    ],
                    (object) [
                        'title' => 'Bảng điều khiển quản trị chiến dịch gom đơn',
                        'description' => 'Host có thể theo dõi tiến độ gom đơn thời gian thực và quản lý danh sách thành viên tham gia.',
                    ],
                ],
                'improvements' => [],
                'bugfixes' => [],
                'security' => [
                    (object) [
                        'title' => 'Nâng cấp bảo mật SAML/OAuth 2.0',
                        'description' => 'Chuyển đổi sang giao thức bảo mật đăng nhập doanh nghiệp chuẩn mới.',
                    ],
                ],
                'changelog' => '',
            ],
        ];
    }
}

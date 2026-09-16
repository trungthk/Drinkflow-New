<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('admin_notifications')) {
            return;
        }

        $notifications = DB::table('admin_notifications')
            ->whereNull('body')
            ->orWhere('body', '')
            ->get();

        foreach ($notifications as $n) {
            $type = (string) $n->type;
            $data = json_decode((string) ($n->data ?? '{}'), true) ?: [];
            $orderId = $data['order_id'] ?? null;
            $orderCode = $data['order_code'] ?? ($orderId ? ('#' . $orderId) : '');
            $userName = $data['user_name'] ?? 'Thành viên';

            $body = match (true) {
                $type === 'order.payment_submitted' => $userName . ' đã gửi yêu cầu xác nhận thanh toán cho đơn hàng ' . $orderCode . '.',
                $type === 'order.price_adjusted' => 'Đơn hàng ' . $orderCode . ' đã được điều chỉnh giá' . (!empty($data['reason']) ? (': ' . $data['reason']) : '.'),
                $type === 'order.updated' => 'Thông tin đơn hàng ' . $orderCode . ' đã được cập nhật.',
                $type === 'order.created' => 'Có đơn hàng mới ' . $orderCode . ' vừa được gửi trong phòng.',
                in_array($type, ['order.status', 'order.status_changed'], true) => 'Trạng thái đơn hàng ' . $orderCode . ' đã được cập nhật.',
                $type === 'order.deleted' => 'Đơn hàng ' . $orderCode . ' đã bị hủy/xóa.',
                $type === 'campaign.created' => 'Chiến dịch đặt món mới đã được khởi tạo thành công.',
                $type === 'campaign.closed' => 'Chiến dịch đặt món đã được chốt và tự động tính công nợ.',
                $type === 'campaign.cancelled' => 'Chiến dịch đặt món đã bị hủy.',
                $type === 'debt.payment_recorded' => 'Đã ghi nhận giao dịch thanh toán công nợ của thành viên.',
                $type === 'debt.payment_approved' => 'Đã duyệt thành công yêu cầu thanh toán công nợ.',
                $type === 'debt.adjusted' => 'Công nợ của thành viên đã được điều chỉnh số dư.',
                $type === 'room_settings.updated' => 'Cấu hình cài đặt phòng ban đã được cập nhật.',
                $type === 'payment_account.created' => 'Tài khoản thanh toán mới đã được thêm vào phòng.',
                $type === 'payment_account.updated' => 'Tài khoản thanh toán của phòng đã được cập nhật.',
                $type === 'payment_account.deleted' => 'Tài khoản thanh toán đã được xóa khỏi phòng.',
                $type === 'notification_channel.created' => 'Kênh thông báo mới đã được kết nối.',
                $type === 'notification_channel.updated' => 'Cấu hình kênh thông báo đã được cập nhật.',
                $type === 'notification_channel.deleted' => 'Kênh thông báo đã bị ngắt kết nối khỏi phòng.',
                default => 'Hoạt động quản trị mới đã diễn ra trong phòng.',
            };

            DB::table('admin_notifications')
                ->where('id', $n->id)
                ->update(['body' => $body]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down needed for backfilling notification body.
    }
};

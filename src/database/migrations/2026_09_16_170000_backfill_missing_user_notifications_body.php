<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $notifications = DB::table('user_notifications')
            ->whereNull('body')
            ->orWhere('body', '')
            ->get();

        foreach ($notifications as $n) {
            $data = json_decode((string) ($n->data ?? '{}'), true) ?: [];
            $orderId = $data['order_id'] ?? null;
            $orderCode = $data['order_code'] ?? ($orderId ? ('#' . $orderId) : '');

            $body = match (true) {
                $n->type === 'order.created' => 'Đơn hàng ' . $orderCode . ' đã được ghi nhận thành công.',
                in_array($n->type, ['order.status', 'order.updated'], true) => 'Trạng thái đơn hàng ' . $orderCode . ' đã được cập nhật thành ' . ($data['status'] ?? 'mới') . '.',
                $n->type === 'order.price_adjusted' => 'Đơn hàng ' . $orderCode . ' đã được điều chỉnh giá' . (!empty($data['reason']) ? (': ' . $data['reason']) : '.'),
                $n->type === 'order.deleted' => 'Đơn hàng ' . $orderCode . ' đã bị hủy/xóa.',
                $n->type === 'campaign.created' => 'Chiến dịch đặt món mới đã bắt đầu, hãy vào chọn món cùng mọi người!',
                $n->type === 'campaign.closed' => 'Chiến dịch đặt món đã kết thúc và chốt sổ.',
                $n->type === 'campaign.cancelled' => 'Chiến dịch đặt món đã bị hủy bởi quản trị viên.',
                in_array($n->type, ['debt.reminder', 'payment.reminder', 'payment.due'], true) => 'Bạn có khoản công nợ cần hoàn tất thanh toán.',
                in_array($n->type, ['debt.payment_approved', 'payment.confirmed'], true) => 'Khoản thanh toán công nợ của bạn đã được quản trị viên duyệt.',
                in_array($n->type, ['debt.updated', 'debt.adjusted'], true) => 'Khoản công nợ của bạn đã có cập nhật mới.',
                $n->type === 'device.new' => 'Phát hiện thiết bị mới đăng nhập vào tài khoản của bạn.',
                $n->type === 'security.alert' => 'Cảnh báo bảo mật tài khoản người dùng.',
                default => (string) ($n->title ?: 'Bạn có thông báo mới từ hệ thống.'),
            };

            DB::table('user_notifications')
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

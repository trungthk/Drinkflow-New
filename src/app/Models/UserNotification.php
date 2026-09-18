<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotification extends Model
{
    protected $fillable = ['global_user_id', 'room_user_id', 'type', 'title', 'body', 'data', 'read_at'];

    protected function casts(): array
    {
        return ['data' => 'array', 'read_at' => 'datetime'];
    }

    /**
     * Bootstrap model events to guarantee every notification has a meaningful body text.
     */
    protected static function booted(): void
    {
        static::creating(function (UserNotification $notification): void {
            if (empty($notification->body)) {
                $type = (string) $notification->type;
                $data = is_array($notification->data) ? $notification->data : [];
                $orderId = $data['order_id'] ?? null;
                $orderCode = $data['order_code'] ?? '';

                $notification->body = match (true) {
                    $type === 'order.created' => 'Đơn hàng ' . $orderCode . ' đã được ghi nhận thành công.',
                    $type === NotificationType::OrderProxyReceived->value => 'Đơn hàng ' . $orderCode . ' đã được ' . ($data['orderer_name'] ?? 'ai đó') . ' đặt giúp bạn.',
                    in_array($type, ['order.status', 'order.updated'], true) => 'Trạng thái đơn hàng ' . $orderCode . ' đã được cập nhật thành ' . ($data['status'] ?? 'mới') . '.',
                    $type === 'order.price_adjusted' => 'Đơn hàng ' . $orderCode . ' đã được điều chỉnh giá' . (!empty($data['reason']) ? (': ' . $data['reason']) : '.'),
                    $type === 'order.deleted' => 'Đơn hàng ' . $orderCode . ' đã bị hủy/xóa.',
                    $type === 'campaign.created' => 'Chiến dịch đặt món mới đã bắt đầu, hãy vào chọn món cùng mọi người!',
                    $type === 'campaign.closed' => 'Chiến dịch đặt món đã kết thúc và chốt sổ.',
                    $type === 'campaign.cancelled' => 'Chiến dịch đặt món đã bị hủy bởi quản trị viên.',
                    in_array($type, ['debt.reminder', 'payment.reminder', 'payment.due'], true) => 'Bạn có khoản công nợ cần hoàn tất thanh toán.',
                    in_array($type, ['debt.payment_approved', 'payment.confirmed'], true) => 'Khoản thanh toán công nợ của bạn đã được quản trị viên duyệt.',
                    in_array($type, ['debt.updated', 'debt.adjusted'], true) => 'Khoản công nợ của bạn đã có cập nhật mới.',
                    $type === 'device.new' => 'Phát hiện thiết bị mới đăng nhập vào tài khoản của bạn.',
                    $type === 'security.alert' => 'Cảnh báo bảo mật tài khoản người dùng.',
                    default => (string) ($notification->title ?: 'Bạn có thông báo mới từ hệ thống.'),
                };
            }
        });
    }

    public function globalUser(): BelongsTo
    {
        return $this->belongsTo(GlobalUser::class);
    }

    public function roomUser(): BelongsTo
    {
        return $this->belongsTo(RoomUser::class);
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminNotification extends Model
{
    /** @var array<int, string> */
    protected $fillable = ['admin_id', 'room_id', 'audit_log_id', 'type', 'title', 'body', 'data', 'read_at'];

    /**
     * Define attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['data' => 'array', 'read_at' => 'datetime'];
    }

    /**
     * Bootstrap model events to guarantee every admin notification has a meaningful body text.
     */
    protected static function booted(): void
    {
        static::creating(function (AdminNotification $notification): void {
            $type = (string) $notification->type;
            $data = is_array($notification->data) ? $notification->data : [];

            if (empty($notification->title)) {
                $notification->title = match (true) {
                    $type === 'order.payment_submitted' => 'Xác nhận thanh toán đơn hàng',
                    $type === 'order.price_adjusted' => 'Điều chỉnh giá đơn hàng',
                    $type === 'order.updated' => 'Cập nhật đơn hàng',
                    $type === 'order.created' => 'Đơn hàng mới',
                    in_array($type, ['order.status', 'order.status_changed'], true) => 'Cập nhật trạng thái đơn hàng',
                    $type === 'order.deleted' => 'Đơn hàng đã xóa',
                    $type === 'campaign.created' => 'Chiến dịch mới',
                    $type === 'campaign.closed' => 'Đóng chiến dịch',
                    $type === 'campaign.cancelled' => 'Hủy chiến dịch',
                    $type === 'debt.payment_recorded' => 'Ghi nhận thanh toán công nợ',
                    $type === 'debt.payment_approved' => 'Duyệt thanh toán công nợ',
                    $type === 'debt.adjusted' => 'Điều chỉnh công nợ',
                    $type === 'room_settings.updated' => 'Cập nhật cài đặt phòng',
                    str_starts_with($type, 'payment_account.') => 'Quản lý tài khoản thanh toán',
                    str_starts_with($type, 'notification_channel.') => 'Cấu hình kênh thông báo',
                    default => 'Hoạt động quản trị phòng',
                };
            }

            if (empty($notification->body)) {
                $orderId = $data['order_id'] ?? null;
                $orderCode = $data['order_code'] ?? '';
                $userName = $data['user_name'] ?? 'Thành viên';

                $notification->body = match (true) {
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
            }
        });
    }

    /**
     * Get the recipient admin.
     *
     * @return BelongsTo<AdminAccount, $this>
     */
    public function admin(): BelongsTo { return $this->belongsTo(AdminAccount::class); }

    /**
     * Get the related room.
     *
     * @return BelongsTo<Room, $this>
     */
    public function room(): BelongsTo { return $this->belongsTo(Room::class); }

    /**
     * Get the source audit log.
     *
     * @return BelongsTo<AuditLog, $this>
     */
    public function auditLog(): BelongsTo { return $this->belongsTo(AuditLog::class); }
}

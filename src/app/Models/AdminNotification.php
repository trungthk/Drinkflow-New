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
                $after = is_array($data['after'] ?? null) ? $data['after'] : [];
                $before = is_array($data['before'] ?? null) ? $data['before'] : [];
                $metadata = is_array($data['metadata'] ?? null) ? $data['metadata'] : [];
                $orderId = $data['order_id'] ?? null;
                $orderCode = $data['order_code'] ?? $after['code'] ?? $before['code'] ?? '';
                $userName = $data['user_name'] ?? 'Thành viên';
                $itemName = $after['name'] ?? $before['name'] ?? '';
                $newStatus = $after['status'] ?? '';
                $newStatusLabel = self::statusLabel($newStatus);
                $priceAdjustReason = $data['reason'] ?? $metadata['reason'] ?? $before['reason'] ?? null;

                $notification->body = match (true) {
                    $type === 'order.payment_submitted' => $userName . ' đã gửi yêu cầu xác nhận thanh toán cho đơn hàng ' . $orderCode . '.',
                    $type === 'order.price_adjusted' => 'Đơn hàng ' . $orderCode . ' vừa được điều chỉnh giá' . ($priceAdjustReason ? (': ' . $priceAdjustReason) : '.'),
                    $type === 'order.updated' => 'Thông tin đơn hàng ' . $orderCode . ' vừa được cập nhật.',
                    $type === 'order.created' => 'Có đơn hàng mới ' . $orderCode . ' vừa được gửi trong phòng.',
                    in_array($type, ['order.status', 'order.status_changed', 'order.status_updated'], true) => 'Đơn hàng ' . $orderCode . ' vừa chuyển sang trạng thái "' . $newStatusLabel . '".',
                    $type === 'order.bulk_status_updated' => 'Đơn hàng ' . $orderCode . ' vừa được xác nhận hàng loạt sang trạng thái "' . $newStatusLabel . '".',
                    $type === 'order.bulk_cancelled' => 'Đơn hàng ' . $orderCode . ' vừa bị hủy hàng loạt.',
                    $type === 'order.deleted' => 'Đơn hàng ' . $orderCode . ' vừa bị quản trị viên xóa.',
                    $type === 'campaign.created' => !empty($after['name'])
                        ? ('Chiến dịch "' . $after['name'] . '" vừa mới được tạo.')
                        : 'Chiến dịch đặt món mới đã được khởi tạo thành công.',
                    $type === 'campaign.fee_adjusted' => 'Chiến dịch vừa mới cập nhật giảm giá & chi phí giao hàng.',
                    $type === 'campaign.updated' => 'Chiến dịch vừa mới cập nhật thông tin.',
                    $type === 'campaign.closed', $type === 'campaign.force_closed' => 'Chiến dịch vừa mới đóng.',
                    $type === 'campaign.cancelled', $type === 'campaign.force_cancelled' => 'Chiến dịch vừa mới bị hủy.',
                    $type === 'campaign.bill_split' => 'Hóa đơn chiến dịch vừa được chia cho các thành viên tham gia.',
                    $type === 'campaign.deadline_extended' => 'Chiến dịch vừa mới được gia hạn thời gian chốt đơn.',
                    $type === 'campaign.notification_resent' => 'Thông báo chiến dịch vừa được gửi lại cho thành viên.',
                    $type === 'campaign_item.status_updated' => $itemName !== ''
                        ? ('Món "' . $itemName . '" vừa được ' . ($newStatus === 'active' ? 'bật bán trở lại' : 'ẩn khỏi') . ' thực đơn chiến dịch.')
                        : 'Chiến dịch vừa mới cập nhật danh sách menu.',
                    $type === 'campaign_item.archived' => $itemName !== ''
                        ? ('Món "' . $itemName . '" vừa bị xóa khỏi thực đơn chiến dịch.')
                        : 'Chiến dịch vừa mới cập nhật danh sách menu.',
                    $type === 'campaign_item_import.previewed' => 'Đã xem trước ' . ($metadata['item_count'] ?? '') . ' món từ nguồn nhập menu.',
                    $type === 'campaign_item_import.completed' => 'Đã nhập ' . ($metadata['item_count'] ?? '') . ' món vào thực đơn chiến dịch.',
                    $type === 'debt.deleted' => 'Một khoản công nợ vừa bị xóa do đơn hàng liên quan đã bị xóa.',
                    $type === 'debt.status_updated' => 'Công nợ vừa được chuyển sang trạng thái "' . $newStatusLabel . '".',
                    $type === 'debt.payment_recorded' => 'Đã ghi nhận giao dịch thanh toán công nợ của thành viên.',
                    $type === 'debt.payment_approved' => 'Đã duyệt thành công yêu cầu thanh toán công nợ.',
                    $type === 'debt.adjusted' => 'Công nợ của thành viên vừa được điều chỉnh số dư.',
                    $type === 'debt.exported' => 'Danh sách công nợ vừa được xuất file.',
                    $type === 'room.settings_updated', $type === 'room_settings.updated' => 'Cấu hình cài đặt phòng ban vừa được cập nhật.',
                    $type === 'room_user.status_updated' => 'Trạng thái thành viên vừa được cập nhật thành "' . $newStatusLabel . '".',
                    $type === 'room_user.device_revoked' => 'Một thiết bị đăng nhập của thành viên vừa bị thu hồi quyền truy cập.',
                    $type === 'room_user.membership_removed' => 'Một thành viên vừa bị xóa khỏi phòng.',
                    $type === 'payment_account.created' => 'Tài khoản thanh toán mới vừa được thêm vào phòng.',
                    $type === 'payment_account.updated' => 'Tài khoản thanh toán của phòng vừa được cập nhật.',
                    $type === 'payment_account.deleted' => 'Tài khoản thanh toán vừa bị xóa khỏi phòng.',
                    $type === 'notification_channel.created' => 'Kênh thông báo mới vừa được kết nối.',
                    $type === 'notification_channel.updated' => 'Cấu hình kênh thông báo vừa được cập nhật.',
                    $type === 'notification_channel.disabled', $type === 'notification_channel.deleted' => 'Kênh thông báo vừa bị ngắt kết nối khỏi phòng.',
                    default => 'Hoạt động quản trị mới đã diễn ra trong phòng.',
                };
            }
        });
    }

    /**
     * Translate a raw status value into its display label, falling back to the raw value.
     *
     * @param string $status Raw status value (order/item/debt/room-user status, etc.).
     * @return string Localized label, or the raw value if no translation exists.
     */
    private static function statusLabel(string $status): string
    {
        if ($status === '') {
            return $status;
        }

        $key = 'admin.status_' . $status;
        $label = __($key);

        return $label === $key ? $status : $label;
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

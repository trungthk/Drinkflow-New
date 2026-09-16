<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Models\AdminNotification;
use App\Models\UserNotification;

class NotificationPresentationService
{
    /**
     * Build locale-aware display data for a notification header item.
     *
     * @param UserNotification|AdminNotification $notification Notification model.
     * @return array{title: string, body: string, icon: string} Presentation data.
     */
    public function present(UserNotification|AdminNotification $notification): array
    {
        $type = (string) $notification->type;
        $data = is_array($notification->data) ? $notification->data : [];
        $titleKey = $this->titleKey($type, $notification instanceof AdminNotification);

        $title = $titleKey !== null ? __($titleKey) : (string) ($notification->title ?? '');
        if ($title === $titleKey || $title === '') {
            $title = (string) ($notification->title ?? __('global.notifications.default_title'));
        }

        $body = $this->body($notification, $type, $data);

        return [
            'title' => $title,
            'body' => $body,
            'icon' => $this->icon($type),
        ];
    }

    /**
     * Resolve the translation key for a notification title.
     *
     * @param string $type Notification type.
     * @param bool $admin Whether the notification belongs to an admin.
     * @return string|null Translation key or null for a stored/custom title.
     */
    private function titleKey(string $type, bool $admin): ?string
    {
        if ($admin) {
            $key = 'admin.audit_event_' . str_replace('.', '_', $type);
            return __($key) === $key ? null : $key;
        }

        return match ($type) {
            'campaign.created' => 'messages.campaign_created_title',
            'campaign.closed' => 'messages.campaign_closed_title',
            'campaign.cancelled' => 'messages.campaign_cancelled_title',
            'order.created' => 'messages.order_created',
            'order.updated', 'order.status' => 'messages.order_status_updated',
            'order.deleted' => 'messages.order_deleted_title',
            'payment.reminder', 'payment.due', 'debt.reminder' => 'messages.payment_reminder',
            default => null,
        };
    }

    /**
     * Resolve a locale-aware body while preserving rich campaign payloads.
     *
     * @param UserNotification|AdminNotification $notification Notification model.
     * @param string $type Notification type.
     * @param array<string, mixed> $data Structured notification data.
     * @return string Body text.
     */
    private function body(UserNotification|AdminNotification $notification, string $type, array $data): string
    {
        if ($notification instanceof AdminNotification) {
            if (!empty($notification->body)) {
                return (string) $notification->body;
            }

            $auditKey = 'admin.audit_event_' . str_replace('.', '_', $type);
            $translated = __($auditKey);
            if ($translated !== $auditKey) {
                return $translated;
            }

            return (string) ($notification->title ?? __('admin.system_updated'));
        }

        $orderId = $data['order_id'] ?? null;
        $orderCode = $data['order_code'] ?? ($orderId ? ('#' . $orderId) : '');

        if ($type === 'order.created' && $orderId !== null) {
            return __('messages.order_created_body', ['order_id' => $orderId]);
        }
        if (in_array($type, ['order.updated', 'order.status'], true) && $orderId !== null) {
            return __('messages.order_status_updated_body', [
                'order_id' => $orderId,
                'status' => (string) ($data['status'] ?? ''),
            ]);
        }
        if ($type === 'order.deleted') {
            return __('messages.order_deleted_body');
        }

        return (string) ($notification->body ?? '');
    }

    /**
     * Resolve the header icon for a notification type.
     *
     * @param string $type Notification type.
     * @return string Material Symbols icon name.
     */
    private function icon(string $type): string
    {
        return match (true) {
            str_starts_with($type, 'campaign.') => 'local_fire_department',
            str_starts_with($type, 'order.') => 'check_circle',
            str_starts_with($type, 'payment.'), str_starts_with($type, 'debt.') => 'payments',
            str_starts_with($type, 'security.'), $type === 'device.new' => 'security',
            default => 'notifications',
        };
    }
}

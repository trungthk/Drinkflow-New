<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Models\AdminAccount;
use App\Services\Notification\AdminNotificationService;
use App\Services\Notification\NotificationPresentationService;
use Illuminate\View\View;

/**
 * Shares the header bell data (the signed-in account's unread notifications) with the superadmin layout.
 */
class SuperadminLayoutComposer
{
    /** Number of unread notifications previewed in the header dropdown. */
    private const DROPDOWN_LIMIT = 5;

    /**
     * @param AdminNotificationService $notifications Admin notification service.
     * @param NotificationPresentationService $presentation Localized title/body/icon for each notification.
     */
    public function __construct(
        private readonly AdminNotificationService $notifications,
        private readonly NotificationPresentationService $presentation,
    ) {
    }

    /**
     * Share unread notifications, their total count and their display data with the layout.
     *
     * @param View $view Superadmin layout view.
     * @return void
     */
    public function compose(View $view): void
    {
        $admin = auth('admin')->user();
        if (!$admin instanceof AdminAccount) {
            $view->with(['headerNotifications' => collect(), 'headerNotificationPresentations' => [], 'headerUnreadCount' => 0]);

            return;
        }

        $unread = $this->notifications->unreadForAdmin($admin, self::DROPDOWN_LIMIT);
        $presentations = [];
        foreach ($unread as $notification) {
            $presentations[$notification->id] = $this->presentation->present($notification);
        }

        $view->with([
            'headerNotifications' => $unread,
            'headerNotificationPresentations' => $presentations,
            'headerUnreadCount' => $this->notifications->unreadCountForAdmin($admin),
        ]);
    }
}

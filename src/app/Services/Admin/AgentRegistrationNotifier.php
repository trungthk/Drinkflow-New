<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\Permission;
use App\Services\Notification\SuperadminNotificationService;
use App\Services\Notification\SystemNotificationChannelDispatcher;
use Illuminate\Support\Facades\Log;

/**
 * Announce a new Agent self-registration to the platform team.
 *
 * Runs after the registration committed, so a rolled-back sign-up never notifies anybody. Delivery
 * happens on three channels: the Superadmin inbox (bell + notifications page), the realtime gateway
 * (through the notification service) and the configured outbound channels (Slack/Telegram/ChatWork/
 * webhook). A failing outbound channel is logged and never breaks the registration itself.
 *
 * Only Superadmins holding `agent.approve` get an inbox copy (a full-access account holds every
 * permission), because only they can open the review queue the alert links to.
 */
class AgentRegistrationNotifier
{
    /** Notification type shown in the Superadmin inbox. */
    public const TYPE = 'agent.registered';

    public function __construct(
        private readonly SuperadminNotificationService $notifications,
        private readonly SystemNotificationChannelDispatcher $channels,
    ) {
    }

    /**
     * Notify the active Superadmins allowed to review registrations about a pending one.
     *
     * @param array{id?: int|null, name: string, email: string, company?: string|null, package?: string|null, registered_at?: string|null} $agent Registered Agent data.
     * @return int Number of Superadmin inbox rows created.
     */
    public function notifyNewRegistration(array $agent): int
    {
        $title = __('platform.registrations.notify_title', ['name' => $agent['name']]);
        $body = $agent['company']
            ? __('platform.registrations.notify_body_with_company', ['email' => $agent['email'], 'company' => $agent['company']])
            : __('platform.registrations.notify_body', ['email' => $agent['email']]);

        $created = $this->notifications->notifySuperadmins(self::TYPE, $title, $body, [
            'admin_id' => $agent['id'] ?? null,
            'name' => $agent['name'],
            'email' => $agent['email'],
            'company' => $agent['company'] ?? null,
            'package' => $agent['package'] ?? null,
            'registered_at' => $agent['registered_at'] ?? now()->toIso8601String(),
            // Relative, so the inbox link works whatever host the console is opened on.
            'url' => self::reviewUrl($agent['id'] ?? null, false),
        ], [], Permission::AgentApprove)->count();

        $this->dispatchOutbound($title, $agent);

        return $created;
    }

    /**
     * Push the alert to the configured outbound channels.
     *
     * @param string $title Translated notification title.
     * @param array{name: string, email: string, company?: string|null, package?: string|null} $agent Registered Agent data.
     * @return void
     */
    private function dispatchOutbound(string $title, array $agent): void
    {
        try {
            $this->channels->dispatch([
                'event' => self::TYPE,
                'title' => $title,
                'message' => implode("\n", array_filter([
                    __('platform.registrations.notify_field_agent') . ': ' . $agent['name'],
                    __('platform.registrations.notify_field_email') . ': ' . $agent['email'],
                    $agent['company'] ? __('platform.registrations.notify_field_company') . ': ' . $agent['company'] : null,
                    $agent['package'] ? __('platform.registrations.notify_field_package') . ': ' . $agent['package'] : null,
                    __('platform.registrations.notify_field_action') . ': ' . self::reviewUrl($agent['id'] ?? null),
                ])),
            ]);
        } catch (\Throwable $exception) {
            // Outbound delivery is best-effort: the inbox copy above is already stored.
            Log::warning('Agent registration notification could not reach an outbound channel.', [
                'channel_exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Review page of the registration, or the queue when the Agent ID is unknown.
     *
     * @param int|null $adminId Registered Agent ID.
     * @param bool $absolute Absolute URL (outbound channels) or a path (inbox).
     * @return string URL or path.
     */
    public static function reviewUrl(?int $adminId, bool $absolute = true): string
    {
        return $adminId !== null
            ? route('superadmin.registrations.show', $adminId, $absolute)
            : route('superadmin.registrations.index', [], $absolute);
    }
}

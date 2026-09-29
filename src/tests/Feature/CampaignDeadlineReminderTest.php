<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CampaignStatus;
use App\Enums\NotificationType;
use App\Enums\OrderStatus;
use App\Events\AdminNotificationCreated;
use App\Events\UserNotificationCreated;
use App\Listeners\PublishRealtimeEvent;
use App\Models\Admin;
use App\Models\AdminNotification;
use App\Models\Campaign;
use App\Models\CampaignParticipant;
use App\Models\GlobalUser;
use App\Models\Order;
use App\Models\Room;
use App\Models\RoomUser;
use App\Models\UserNotification;
use App\Services\Notification\NotificationPresentationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CampaignDeadlineReminderTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private Room $room;

    private Campaign $campaign;

    private RoomUser $pending;

    private RoomUser $ordered;

    private RoomUser $declined;

    private RoomUser $blocked;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('campaign.deadline_reminder_minutes', 15);

        $this->admin = Admin::create([
            'name' => 'Room Admin',
            'email' => 'reminder-admin@example.test',
            'password' => Hash::make('secret'),
            'status' => 'active',
        ]);
        $this->room = Room::create(['name' => 'Reminder Room', 'slug' => 'reminder-room', 'status' => 'active']);
        $this->admin->rooms()->attach($this->room);

        $this->campaign = Campaign::create([
            'room_id' => $this->room->id,
            'name' => 'Trà chiều',
            'restaurant' => 'Cafe',
            'status' => CampaignStatus::Active,
            'deadline' => now()->addMinutes(10),
        ]);

        $this->pending = $this->member('pending');
        $this->ordered = $this->member('ordered');
        $this->declined = $this->member('declined');
        $this->blocked = $this->member('blocked', 'blocked');

        Order::create([
            'room_id' => $this->room->id,
            'campaign_id' => $this->campaign->id,
            'room_user_id' => $this->ordered->id,
            'subtotal' => 30000,
            'final_amount' => 30000,
            'status' => OrderStatus::Submitted,
        ]);
        CampaignParticipant::create([
            'campaign_id' => $this->campaign->id,
            'room_user_id' => $this->declined->id,
            'status' => CampaignParticipant::STATUS_DECLINED,
            'declined_at' => now(),
        ]);
    }

    private function member(string $name, string $status = 'active'): RoomUser
    {
        $user = GlobalUser::create(['name' => ucfirst($name), 'email' => "{$name}@example.test", 'status' => 'active']);

        return RoomUser::create(['room_id' => $this->room->id, 'global_user_id' => $user->id, 'display_name' => ucfirst($name), 'status' => $status]);
    }

    private function remind(): void
    {
        $this->artisan('drinkflow:remind-campaign-deadlines')->assertSuccessful();
    }

    private function reminderCount(): int
    {
        return UserNotification::query()->where('type', NotificationType::CampaignDeadlineReminder->value)->count();
    }

    public function test_only_members_who_have_not_ordered_or_declined_are_reminded(): void
    {
        Event::fake([UserNotificationCreated::class, AdminNotificationCreated::class]);

        $this->remind();

        $notifications = UserNotification::query()->where('type', NotificationType::CampaignDeadlineReminder->value)->get();
        $this->assertSame([$this->pending->id], $notifications->pluck('room_user_id')->all());
        $this->assertSame(route('user.campaigns.order-page', [$this->room, $this->campaign]), $notifications->first()->link);
        Event::assertDispatchedTimes(UserNotificationCreated::class, 1);

        $adminNotification = AdminNotification::query()->where('type', NotificationType::CampaignDeadlineReminder->value)->sole();
        $this->assertSame($this->admin->id, $adminNotification->admin_id);
        $this->assertSame($this->room->id, $adminNotification->room_id);
        $this->assertSame(1, $adminNotification->data['pending_count']);
        $this->assertSame(3, $adminNotification->data['total_count']);
        Event::assertDispatched(AdminNotificationCreated::class, fn (AdminNotificationCreated $event): bool => $event->notification->is($adminNotification));
    }

    public function test_reminder_is_sent_once_per_deadline(): void
    {
        $this->remind();
        $this->remind();

        $this->assertSame(1, $this->reminderCount());
        $this->assertSame(1, AdminNotification::query()->where('type', NotificationType::CampaignDeadlineReminder->value)->count());
    }

    public function test_extending_the_deadline_allows_a_new_reminder(): void
    {
        $this->remind();

        $this->campaign->update(['deadline' => $this->campaign->deadline->copy()->addMinutes(20)]);
        $this->remind();
        $this->assertSame(1, $this->reminderCount(), 'The new deadline is outside the reminder window.');

        $this->travel(21)->minutes();
        $this->remind();
        $this->assertSame(2, $this->reminderCount());
    }

    public function test_campaigns_outside_the_window_or_not_orderable_are_skipped(): void
    {
        $this->campaign->update(['deadline' => now()->addMinutes(30)]);
        $this->remind();

        $this->campaign->update(['deadline' => now()->subMinute()]);
        $this->remind();

        $this->campaign->update(['deadline' => null]);
        $this->remind();

        $this->campaign->update(['deadline' => now()->addMinutes(10)]);
        $this->campaign->forceFill(['ordering_locked_at' => now()])->save();
        $this->remind();

        $this->campaign->forceFill(['ordering_locked_at' => null, 'status' => CampaignStatus::Closed])->save();
        $this->remind();

        $this->assertSame(0, $this->reminderCount());
        $this->assertSame(0, AdminNotification::query()->count());
    }

    public function test_reminder_can_be_turned_off(): void
    {
        config()->set('campaign.deadline_reminder_minutes', 0);

        $this->remind();

        $this->assertSame(0, $this->reminderCount());
    }

    public function test_admin_notification_is_forwarded_to_the_private_admin_socket_channel(): void
    {
        config()->set('services.realtime.url', 'http://realtime.test');
        config()->set('services.realtime.internal_secret', 'test-secret');
        Http::fake(['http://realtime.test/internal/emit' => Http::response(['delivered' => true], 202)]);
        $notification = AdminNotification::create([
            'admin_id' => $this->admin->id,
            'room_id' => $this->room->id,
            'type' => NotificationType::CampaignDeadlineReminder->value,
            'title' => 'Closing soon',
            'body' => 'Hurry',
            'data' => ['pending_count' => 1],
        ]);

        app(PublishRealtimeEvent::class)->handle(new AdminNotificationCreated($notification));

        Http::assertSent(fn ($request): bool => $request['event'] === 'admin.notification.created'
            && $request['room_id'] === 0
            && $request['user_channel'] === 'admin:'.$this->admin->id
            && $request['payload']['room_id'] === $this->room->id
            && $request['payload']['data']['pending_count'] === 1);
    }

    public function test_reminders_are_presented_in_the_current_locale_and_link_to_a_live_campaign(): void
    {
        $this->remind();
        $userNotification = UserNotification::query()->where('type', NotificationType::CampaignDeadlineReminder->value)->sole();
        $adminNotification = AdminNotification::query()->where('type', NotificationType::CampaignDeadlineReminder->value)->sole();
        $time = $this->campaign->deadline->copy()->timezone(config('app.timezone'))->format('H:i');

        app()->setLocale('en');
        $presentation = app(NotificationPresentationService::class);

        $user = $presentation->present($userNotification);
        $this->assertSame(__('messages.campaign_deadline_reminder_title'), $user['title']);
        $this->assertSame(__('messages.campaign_deadline_reminder_body', ['campaign' => 'Trà chiều', 'time' => $time]), $user['body']);
        $this->assertSame(route('user.campaigns.order-page', [$this->room, $this->campaign]), $user['link']);
        $this->assertSame('alarm', $user['icon']);

        $admin = $presentation->present($adminNotification);
        $this->assertSame(__('admin.campaign_deadline_reminder_body', ['campaign' => 'Trà chiều', 'time' => $time, 'pending' => 1, 'total' => 3]), $admin['body']);

        $this->campaign->forceFill(['status' => CampaignStatus::Closed])->save();
        $this->assertNull($presentation->present($userNotification->fresh())['link']);
    }
}

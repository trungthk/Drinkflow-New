<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Campaign\DeclineCampaignAction;
use App\Enums\CampaignStatus;
use App\Events\RoomRealtimeEvent;
use App\Events\UserNotificationCreated;
use App\Listeners\PublishRealtimeEvent;
use App\Models\Campaign;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Models\RoomUser;
use App\Models\UserNotification;
use App\Services\Notification\UserNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RealtimeFlowTest extends TestCase
{
    use RefreshDatabase;

    /** Verify a declined campaign response persists and notifies the room. */
    public function test_user_declining_campaign_persists_response_and_dispatches_realtime_event(): void
    {
        Event::fake([RoomRealtimeEvent::class]);
        $user = GlobalUser::create(['name' => 'Member', 'normalized_name' => 'MEMBER', 'email' => 'member@example.com']);
        $room = Room::create(['name' => 'Engineering', 'slug' => 'engineering']);
        $roomUser = RoomUser::create(['room_id' => $room->id, 'global_user_id' => $user->id, 'user_code' => 'ENG-001', 'display_name' => 'Member', 'normalized_name' => 'MEMBER', 'status' => 'active']);
        $campaign = Campaign::create(['room_id' => $room->id, 'name' => 'Lunch', 'restaurant' => 'Cafe', 'status' => CampaignStatus::Active]);

        app(DeclineCampaignAction::class)->execute($campaign, $roomUser);

        $this->assertDatabaseHas('campaign_participants', ['campaign_id' => $campaign->id, 'room_user_id' => $roomUser->id, 'status' => 'declined']);
        Event::assertDispatched(RoomRealtimeEvent::class, fn (RoomRealtimeEvent $event): bool => $event->name === 'campaign.participant.declined' && $event->roomId === $room->id && $event->payload['room_user_id'] === $roomUser->id);
    }

    /** Verify a room event is forwarded to the internal Socket.IO gateway. */
    public function test_room_realtime_event_is_forwarded_to_gateway(): void
    {
        config()->set('services.realtime.url', 'http://realtime.test');
        config()->set('services.realtime.internal_secret', 'test-secret');
        Http::fake(['http://realtime.test/internal/emit' => Http::response(['delivered' => true], 202)]);

        app(PublishRealtimeEvent::class)->handle(new RoomRealtimeEvent('campaign.menu.updated', 12, ['campaign_id' => 9, 'item_id' => 2]));

        Http::assertSent(fn ($request): bool => $request->url() === 'http://realtime.test/internal/emit'
            && $request['event'] === 'campaign.menu.updated'
            && $request['room_id'] === 12
            && $request->hasHeader('X-Realtime-Secret', 'test-secret'));
    }

    /** Verify persisted user notifications are emitted to the recipient's private socket channel. */
    public function test_user_notification_service_dispatches_private_realtime_notification(): void
    {
        Event::fake([UserNotificationCreated::class]);
        $user = GlobalUser::create(['name' => 'Recipient', 'normalized_name' => 'RECIPIENT', 'email' => 'recipient@example.com']);
        $room = Room::create(['name' => 'Sales', 'slug' => 'sales']);
        $roomUser = RoomUser::create(['room_id' => $room->id, 'global_user_id' => $user->id, 'user_code' => 'SALES-001', 'display_name' => 'Recipient', 'normalized_name' => 'RECIPIENT', 'status' => 'active']);

        $notification = app(UserNotificationService::class)->toRoomUser($roomUser, 'campaign.created', 'New campaign', 'Order now', ['room_id' => $room->id]);

        Event::assertDispatched(UserNotificationCreated::class, fn (UserNotificationCreated $event): bool => $event->notification->is($notification) && $event->notification->global_user_id === $user->id);
    }

    /** Verify notification socket payloads are restricted to the recipient's global-user channel. */
    public function test_user_notification_event_is_forwarded_to_private_socket_channel(): void
    {
        config()->set('services.realtime.url', 'http://realtime.test');
        config()->set('services.realtime.internal_secret', 'test-secret');
        Http::fake(['http://realtime.test/internal/emit' => Http::response(['delivered' => true], 202)]);
        $user = GlobalUser::create(['name' => 'Socket Recipient', 'normalized_name' => 'SOCKET RECIPIENT', 'email' => 'socket-recipient@example.com']);
        $notification = UserNotification::create(['global_user_id' => $user->id, 'type' => 'campaign.created', 'title' => 'Campaign new', 'body' => 'Order now', 'data' => ['room_id' => 7]]);

        app(PublishRealtimeEvent::class)->handle(new UserNotificationCreated($notification));

        Http::assertSent(fn ($request): bool => $request['event'] === 'notification.created'
            && $request['room_id'] === 0
            && $request['user_channel'] === 'global_user:'.$user->id
            && $request['payload']['id'] === $notification->id);
    }
}

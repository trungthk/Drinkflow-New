<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\CampaignCancelled;
use App\Events\CampaignClosed;
use App\Events\CampaignCreated;
use App\Events\CampaignDelivering;
use App\Events\CampaignUpdated;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class EventListenerRegistrationTest extends TestCase
{
    /**
     * Every listener must be registered exactly once per event.
     *
     * Laravel auto-discovers App\Listeners\*::handle() on top of the explicit Event::listen() calls, which used to
     * fire each notification (web + gateway) twice.
     *
     * @return void
     */
    public function test_each_listener_is_registered_once_per_event(): void
    {
        Artisan::call('event:list', ['--json' => true]);
        $events = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertNotEmpty($events);
        foreach ($events as $event) {
            $listeners = array_map(
                static fn (string $listener): string => preg_replace('/( \(ShouldQueue\))?$|@handle/', '', $listener),
                $event['listeners']
            );
            $this->assertSame(
                array_values(array_unique($listeners)),
                array_values($listeners),
                "Duplicate listener registration for {$event['event']}: ".implode(', ', $event['listeners'])
            );
        }
    }

    /**
     * Campaign lifecycle events keep exactly their notification listener and the realtime publisher.
     *
     * @return void
     */
    public function test_campaign_events_have_one_notification_listener_each(): void
    {
        foreach ([CampaignCreated::class, CampaignUpdated::class, CampaignDelivering::class, CampaignClosed::class, CampaignCancelled::class] as $event) {
            $this->assertCount(2, app('events')->getListeners($event), "{$event} should have one notifier and one realtime publisher.");
        }
    }

    /**
     * The gateway body of the campaign-updated notification uses the short wording.
     *
     * @return void
     */
    public function test_campaign_updated_body_wording(): void
    {
        app()->setLocale('vi');
        $this->assertSame('Thông tin chiến dịch vừa mới được cập nhật.', __('messages.campaign_updated_body'));
    }
}

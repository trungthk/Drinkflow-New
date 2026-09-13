<?php

namespace App\Providers;

use App\Events\CampaignClosed;
use App\Events\CampaignCreated;
use App\Events\OrderCreated;
use App\Events\OrderUpdated;
use App\Events\OrderDeleted;
use App\Listeners\CreateOrderNotification;
use App\Listeners\CreateOrderStatusNotification;
use App\Listeners\NotifyCampaignClosed;
use App\Listeners\NotifyCampaignCreated;
use App\Listeners\NotifyOrderDeleted;
use App\Listeners\PublishRealtimeEvent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(OrderCreated::class, CreateOrderNotification::class);
        Event::listen(OrderUpdated::class, CreateOrderStatusNotification::class);
        Event::listen(OrderDeleted::class, NotifyOrderDeleted::class);
        Event::listen(OrderCreated::class, PublishRealtimeEvent::class);
        Event::listen(OrderUpdated::class, PublishRealtimeEvent::class);
        Event::listen(OrderDeleted::class, PublishRealtimeEvent::class);
        Event::listen(CampaignCreated::class, NotifyCampaignCreated::class);
        Event::listen(CampaignCreated::class, PublishRealtimeEvent::class);
        Event::listen(CampaignClosed::class, NotifyCampaignClosed::class);
        Event::listen(CampaignClosed::class, PublishRealtimeEvent::class);

        \Illuminate\Support\Facades\RateLimiter::for('contact-submission', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by($request->ip());
        });
    }
}

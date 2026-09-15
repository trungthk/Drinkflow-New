<?php

namespace App\Providers;

use App\Services\FoodCrawler\Contracts\FoodCrawlerProviderInterface;
use App\Services\FoodCrawler\Contracts\BrowserTransportInterface;
use App\Services\FoodCrawler\Browser\PuppeteerBrowserTransport;
use App\Services\FoodCrawler\ProviderResolver;
use App\Events\CampaignClosed;
use App\Events\CampaignCancelled;
use App\Events\CampaignCreated;
use App\Events\OrderCreated;
use App\Events\OrderUpdated;
use App\Events\OrderDeleted;
use App\Events\RoomRealtimeEvent;
use App\Events\RoomMembershipUpdated;
use App\Events\UserNotificationCreated;
use App\Listeners\CreateOrderNotification;
use App\Listeners\CreateOrderStatusNotification;
use App\Listeners\NotifyCampaignClosed;
use App\Listeners\NotifyCampaignCancelled;
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
        $this->app->bind(BrowserTransportInterface::class, PuppeteerBrowserTransport::class);
        $this->app->singleton(ProviderResolver::class, function (): ProviderResolver {
            $providers = array_map(
                fn (string $provider): FoodCrawlerProviderInterface => $this->app->make($provider),
                config('food-crawler.providers', []),
            );

            return new ProviderResolver($providers);
        });
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
        Event::listen(CampaignCancelled::class, NotifyCampaignCancelled::class);
        Event::listen(RoomRealtimeEvent::class, PublishRealtimeEvent::class);
        Event::listen(RoomMembershipUpdated::class, PublishRealtimeEvent::class);
        Event::listen(UserNotificationCreated::class, PublishRealtimeEvent::class);

        \Illuminate\Support\Facades\RateLimiter::for('contact-submission', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by($request->ip() ?: '127.0.0.1');
        });

        \Illuminate\Support\Facades\RateLimiter::for('feedback-submission', function (\Illuminate\Http\Request $request) {
            $userKey = $request->user('web')?->id ? 'user:'.$request->user('web')->id : ($request->ip() ?: '127.0.0.1');
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by($userKey);
        });

        \Illuminate\Support\Facades\RateLimiter::for('crawler-preview', function (\Illuminate\Http\Request $request) {
            $adminKey = $request->user('admin')?->id ? 'admin:'.$request->user('admin')->id : ($request->ip() ?: '127.0.0.1');
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(10)->by($adminKey);
        });

        \Illuminate\Support\Facades\RateLimiter::for('room-join', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(15)->by($request->ip() ?: '127.0.0.1');
        });

        \Illuminate\Support\Facades\RateLimiter::for('admin-login', function (\Illuminate\Http\Request $request) {
            $email = \Illuminate\Support\Str::lower((string) $request->input('email', ''));
            $ip = $request->ip() ?: '127.0.0.1';
            return [
                \Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by($email . '|' . $ip),
                \Illuminate\Cache\RateLimiting\Limit::perMinute(20)->by($ip),
            ];
        });

        \Illuminate\Support\Facades\RateLimiter::for('auth-login', function (\Illuminate\Http\Request $request) {
            $email = \Illuminate\Support\Str::lower((string) $request->input('email', ''));
            $ip = $request->ip() ?: '127.0.0.1';
            return [
                \Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by($email . '|' . $ip),
                \Illuminate\Cache\RateLimiting\Limit::perMinute(20)->by($ip),
            ];
        });

        \Illuminate\Support\Facades\RateLimiter::for('admin-forgot-password', function (\Illuminate\Http\Request $request) {
            $email = \Illuminate\Support\Str::lower((string) $request->input('email', ''));
            $ip = $request->ip() ?: '127.0.0.1';
            return [
                \Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by($email . '|' . $ip),
                \Illuminate\Cache\RateLimiting\Limit::perMinute(15)->by($ip),
            ];
        });

        \Illuminate\Support\Facades\RateLimiter::for('admin-verify-otp', function (\Illuminate\Http\Request $request) {
            $email = \Illuminate\Support\Str::lower((string) ($request->session()->get('admin_reset_email') ?: $request->input('email', '')));
            $ip = $request->ip() ?: '127.0.0.1';
            return [
                \Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by($email . '|' . $ip),
                \Illuminate\Cache\RateLimiting\Limit::perMinute(15)->by($ip),
            ];
        });

        \Illuminate\Support\Facades\RateLimiter::for('admin-reset-password', function (\Illuminate\Http\Request $request) {
            $email = \Illuminate\Support\Str::lower((string) ($request->input('email') ?: $request->query('email') ?: $request->session()->get('admin_reset_email', '')));
            $ip = $request->ip() ?: '127.0.0.1';
            return [
                \Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by($email . '|' . $ip),
                \Illuminate\Cache\RateLimiting\Limit::perMinute(15)->by($ip),
            ];
        });

        \Illuminate\Support\Facades\RateLimiter::for('user-auth-google', function (\Illuminate\Http\Request $request) {
            $ip = $request->ip() ?: '127.0.0.1';
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(15)->by($ip);
        });

        \Illuminate\Support\Facades\RateLimiter::for('user-auth-logout', function (\Illuminate\Http\Request $request) {
            $ip = $request->ip() ?: '127.0.0.1';
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(20)->by($ip);
        });

        \Illuminate\Support\Facades\View::share('locales', \App\Constants\AppLocale::SUPPORTED);

        \Illuminate\Support\Facades\Blade::directive('formatDate', function ($expression) {
            return "<?php echo \App\Support\Helpers\FormatHelper::formatDate($expression); ?>";
        });

        \Illuminate\Support\Facades\Blade::directive('formatDateTime', function ($expression) {
            return "<?php echo \App\Support\Helpers\FormatHelper::formatDateTime($expression); ?>";
        });

        \Carbon\Carbon::macro('toAppDate', function () {
            /** @var \Carbon\Carbon $this */
            return \App\Support\Helpers\FormatHelper::formatDate($this);
        });

        \Carbon\Carbon::macro('toAppDateTime', function () {
            /** @var \Carbon\Carbon $this */
            return \App\Support\Helpers\FormatHelper::formatDateTime($this);
        });
    }
}

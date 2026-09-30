<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\User\SetGlobalUserStatusAction;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Services\User\UserSessionService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * UPG-02.1: "log out other devices" (and blocking/deleting an account) must invalidate the
 * "remember me" cookies of the other browsers, while the current session stays signed in.
 */
class RememberCookieRevocationTest extends TestCase
{
    use RefreshDatabase;

    private GlobalUser $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->user = GlobalUser::create(['name' => 'Remember Me', 'normalized_name' => 'REMEMBER ME', 'email' => 'remember@company.com', 'status' => 'active']);
        $this->user->forceFill(['remember_token' => 'old-remember-token'])->save();
        $room = Room::create(['name' => 'Room', 'slug' => 'remember-room']);
        app(\App\Actions\User\JoinRoomAction::class)->execute($this->user, $room, 'device-a', 'hash-a');
    }

    /**
     * "remember me" cookie as another browser holds it (id|token|password hash).
     *
     * @param string $token Remember token in the cookie.
     * @return array{0: string, 1: string} Cookie name and value.
     */
    private function rememberCookie(string $token): array
    {
        $guard = Auth::guard('web');
        $hash = (fn (string $password): string => $this->hashPasswordForCookie($password))->call($guard, (string) $this->user->getAuthPassword());

        return [$guard->getRecallerName(), $this->user->id.'|'.$token.'|'.$hash];
    }

    /**
     * Whether a fresh browser holding only this cookie is signed in.
     *
     * @param string $token Remember token in the cookie.
     * @return bool True when the cookie still logs the browser in.
     */
    private function cookieLogsIn(string $token): bool
    {
        $this->app['auth']->forgetGuards();
        $this->flushSession();
        [$name, $value] = $this->rememberCookie($token);
        $this->withCookie($name, $value)->get(route('user.me.dashboard'));

        return Auth::guard('web')->check();
    }

    public function test_old_cookie_works_until_other_devices_are_logged_out(): void
    {
        $this->assertTrue($this->cookieLogsIn('old-remember-token'));

        app(UserSessionService::class)->logoutOtherDevices($this->user, 'current-session', '');

        $this->assertNotSame('old-remember-token', $this->user->fresh()->getRememberToken());
        $this->assertFalse($this->cookieLogsIn('old-remember-token'));
    }

    public function test_current_session_keeps_a_valid_remember_cookie(): void
    {
        $response = $this->actingAs($this->user, 'web')->post(route('user.me.devices.logout-all'));

        $response->assertRedirect();
        $this->assertAuthenticatedAs($this->user->fresh(), 'web');
        $newToken = (string) $this->user->fresh()->getRememberToken();
        $this->assertNotSame('old-remember-token', $newToken);
        $cookie = collect($response->headers->getCookies())->first(fn ($cookie) => $cookie->getName() === Auth::guard('web')->getRecallerName());
        $this->assertNotNull($cookie, 'The current session received a new remember cookie.');
        $this->assertTrue($this->cookieLogsIn($newToken));
    }

    public function test_blocking_the_account_invalidates_remember_cookies(): void
    {
        app(SetGlobalUserStatusAction::class)->execute($this->user, 'blocked');

        $this->assertNotSame('old-remember-token', $this->user->fresh()->getRememberToken());
    }
}

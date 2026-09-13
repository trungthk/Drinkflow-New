<?php

namespace Tests\Feature;

use App\Actions\User\JoinRoomAction;
use App\Models\GlobalUser;
use App\Models\Room;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GlobalDevicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_accessing_me_devices_redirects_to_login(): void
    {
        $response = $this->get('/me/devices');
        $response->assertRedirect(route('auth.google'));
    }

    public function test_authenticated_user_can_view_me_devices_page(): void
    {
        $user = GlobalUser::create([
            'name' => 'Trung Lê',
            'normalized_name' => 'TRUNG LE',
            'email' => 'trung.lt@company.com',
            'status' => 'active',
        ]);

        $room = Room::create(['name' => 'Ban Công nghệ & Kỹ thuật số', 'slug' => 'tech-team']);
        app(JoinRoomAction::class)->execute($user, $room, 'device-1', 'hash-1');

        $response = $this->actingAs($user, 'web')->get('/me/devices');

        $response->assertOk();
        $response->assertSee('Bảo mật tài khoản &amp; Thiết bị đăng nhập', false);
        $response->assertSee('trung.lt@company.com');
        $response->assertSee('Thiết bị hiện tại của bạn');
        $response->assertSee('Các phiên đăng nhập &amp; Thiết bị khác', false);
        $response->assertSee('Đăng xuất phiên làm việc từ xa');
        $response->assertSee('Hủy bỏ tài khoản DrinkFlow');
    }

    public function test_user_can_logout_a_specific_session(): void
    {
        $user = GlobalUser::create([
            'name' => 'Trung Lê',
            'normalized_name' => 'TRUNG LE',
            'email' => 'trung.lt@company.com',
            'status' => 'active',
        ]);

        DB::table('sessions')->insert([
            'id' => 'session-remote-123',
            'user_id' => $user->id,
            'ip_address' => '118.70.144.12',
            'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)',
            'payload' => 'dummy',
            'last_activity' => time(),
        ]);

        $response = $this->withoutMiddleware(ValidateCsrfToken::class)
            ->actingAs($user, 'web')
            ->post('/me/devices/logout/session-remote-123');

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseMissing('sessions', [
            'id' => 'session-remote-123',
        ]);
    }

    public function test_user_can_logout_all_other_sessions(): void
    {
        $user = GlobalUser::create([
            'name' => 'Trung Lê',
            'normalized_name' => 'TRUNG LE',
            'email' => 'trung.lt@company.com',
            'status' => 'active',
        ]);

        DB::table('sessions')->insert([
            [
                'id' => 'remote-session-a',
                'user_id' => $user->id,
                'ip_address' => '118.70.144.12',
                'user_agent' => 'Safari on iPhone',
                'payload' => 'dummy',
                'last_activity' => time(),
            ],
            [
                'id' => 'remote-session-b',
                'user_id' => $user->id,
                'ip_address' => '14.161.28.88',
                'user_agent' => 'Chrome on Mac',
                'payload' => 'dummy',
                'last_activity' => time(),
            ],
        ]);

        $response = $this->withoutMiddleware(ValidateCsrfToken::class)
            ->actingAs($user, 'web')
            ->post('/me/devices/logout-all');

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseMissing('sessions', ['id' => 'remote-session-a']);
        $this->assertDatabaseMissing('sessions', ['id' => 'remote-session-b']);
    }

    public function test_user_cannot_delete_account_with_invalid_confirmation(): void
    {
        $user = GlobalUser::create([
            'name' => 'Trung Lê',
            'normalized_name' => 'TRUNG LE',
            'email' => 'trung.lt@company.com',
            'status' => 'active',
        ]);

        $response = $this->withoutMiddleware(ValidateCsrfToken::class)
            ->actingAs($user, 'web')
            ->post('/me/account/delete', [
                'confirm_delete' => 'sai_email@company.com',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['confirm_delete']);

        $user->refresh();
        $this->assertEquals('active', $user->status->value);
    }

    public function test_user_can_delete_account_with_valid_confirmation(): void
    {
        $user = GlobalUser::create([
            'name' => 'Trung Lê',
            'normalized_name' => 'TRUNG LE',
            'email' => 'trung.lt@company.com',
            'status' => 'active',
        ]);

        $response = $this->withoutMiddleware(ValidateCsrfToken::class)
            ->actingAs($user, 'web')
            ->post('/me/account/delete', [
                'confirm_delete' => 'XÓA TÀI KHOẢN',
            ]);

        $response->assertRedirect('/');

        $user->refresh();
        $this->assertEquals('disabled', $user->status->value);
    }
}

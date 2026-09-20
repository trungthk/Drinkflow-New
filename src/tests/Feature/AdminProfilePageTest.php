<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Models\AdminAccount;
use App\Support\Helpers\FormatHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminProfilePageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The profile header shows the creation and last-login dates, and the 2FA modal has a header icon.
     */
    public function test_profile_shows_created_and_last_login_and_two_factor_modal_icon(): void
    {
        $admin = AdminAccount::create([
            'name' => 'Room Admin',
            'email' => 'profile-admin@example.test',
            'password' => Hash::make('secret'),
            'role' => AdminRole::Admin,
            'status' => 'active',
        ]);
        $admin->forceFill(['last_login_at' => now()->subDay()])->save();

        $created = $admin->fresh()->created_at->format(FormatHelper::getDateTimeFormat());
        $lastLogin = $admin->fresh()->last_login_at->format(FormatHelper::getDateTimeFormat());

        $html = $this->actingAs($admin, 'admin')
            ->get(route('admin.profile'))
            ->assertOk()
            ->assertSee(__('admin.profile_created_at').':')
            ->assertSee($created)
            ->assertSee(__('admin.profile_last_login').':')
            ->assertSee($lastLogin)
            ->assertSee('shield_lock')
            ->getContent();

        $this->assertSame(substr_count($html, '<div'), substr_count($html, '</div>'));

        $admin->forceFill(['last_login_at' => null])->save();
        $this->actingAs($admin->fresh(), 'admin')
            ->get(route('admin.profile'))
            ->assertOk()
            ->assertSee(__('admin.profile_never_logged_in'));
    }
}

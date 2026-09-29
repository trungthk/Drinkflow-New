<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminStatus;
use App\Enums\SuperadminStatus;
use App\Models\Admin;
use App\Models\Superadmin;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * T01: Admin (Agent) and Superadmin accounts live in separate tables and models.
 */
class AdminSuperadminSeparationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    public function test_admins_and_superadmins_have_their_own_tables(): void
    {
        $this->assertFalse(Schema::hasTable('admin_accounts'));
        $this->assertTrue(Schema::hasTable('admins'));
        $this->assertTrue(Schema::hasTable('superadmins'));
        $this->assertSame('admins', (new Admin())->getTable());
        $this->assertSame('superadmins', (new Superadmin())->getTable());
        $this->assertFalse(Schema::hasColumn('superadmins', 'role'));
    }

    public function test_admin_status_lifecycle_values(): void
    {
        $this->assertSame(
            ['pending', 'active', 'suspended', 'rejected', 'cancelled'],
            array_map(static fn (AdminStatus $status): string => $status->value, AdminStatus::cases()),
        );
        $this->assertTrue(AdminStatus::Active->canSignIn());
        foreach ([AdminStatus::Pending, AdminStatus::Suspended, AdminStatus::Rejected, AdminStatus::Cancelled] as $status) {
            $this->assertFalse($status->canSignIn(), $status->value);
        }
    }

    public function test_superadmin_model_hashes_password_encrypts_phone_and_requires_unique_email(): void
    {
        $superadmin = Superadmin::create([
            'name' => 'Platform Owner',
            'email' => 'owner@drinkflow.test',
            'password' => 'Secret123!',
            'phone' => '0900000001',
            'status' => SuperadminStatus::Active,
        ]);

        $this->assertTrue(Hash::check('Secret123!', (string) $superadmin->password));
        $this->assertSame('0900000001', $superadmin->fresh()->phone);
        $this->assertNotSame('0900000001', DB::table('superadmins')->where('id', $superadmin->id)->value('phone'));
        $this->assertSame(SuperadminStatus::Active, $superadmin->fresh()->status);
        $this->assertArrayNotHasKey('password', $superadmin->toArray());

        $this->expectException(QueryException::class);
        Superadmin::create(['name' => 'Copy', 'email' => 'owner@drinkflow.test', 'password' => 'Secret123!']);
    }

    public function test_only_active_admins_can_sign_in(): void
    {
        config()->set('captcha.disable', true);

        foreach (AdminStatus::cases() as $status) {
            $admin = Admin::create([
                'name' => 'Agent '.$status->value,
                'email' => "agent-{$status->value}@drinkflow.test",
                'password' => 'CorrectPassword123!',
                'status' => $status,
            ]);

            $response = $this->post('/admin/login', ['email' => $admin->email, 'password' => 'CorrectPassword123!']);

            if ($status->canSignIn()) {
                $response->assertSessionHasNoErrors();
                $this->assertAuthenticatedAs($admin, 'admin');
                $this->post('/admin/logout');
            } else {
                $response->assertSessionHasErrors('email');
                $this->assertGuest('admin');
            }
        }
    }

    public function test_superadmin_can_only_set_manageable_admin_statuses(): void
    {
        $root = $this->createSuperadmin(['name' => 'Root', 'email' => 'root-t01@drinkflow.test', 'password' => 'password123', 'status' => SuperadminStatus::Active]);
        $agent = Admin::create(['name' => 'Agent', 'email' => 'agent-t01@drinkflow.test', 'password' => 'password123', 'status' => AdminStatus::Active]);

        // Review decisions (pending/rejected) have their own flow; legacy values no longer exist.
        foreach (['pending', 'rejected', 'blocked', 'disabled', 'inactive'] as $status) {
            $this->actingAs($root, 'superadmin')
                ->patchJson("/superadmin/admins/{$agent->id}/status", ['status' => $status])
                ->assertStatus(422);
        }
        foreach (['suspended', 'active', 'cancelled'] as $status) {
            $this->actingAs($root, 'superadmin')
                ->patchJson("/superadmin/admins/{$agent->id}/status", ['status' => $status])
                ->assertOk()
                ->assertJsonPath('data.status', $status);
        }
    }
}

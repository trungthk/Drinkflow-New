<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\PermissionScope;
use App\Models\Admin;
use App\Models\PermissionRecord;
use App\Models\Room;
use App\Models\Superadmin;
use App\Models\SuperadminRole;
use App\Models\Version;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T76: the reference seed no longer creates sample Agents and sample Rooms.
 *
 * A fresh install starts with the permission catalogue, the default Full role, the sample packages and
 * the system versions only; the first real Agent is created by a Superadmin.
 */
class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Run the reference seeder.
     *
     * @return void
     */
    private function seedReference(): void
    {
        $this->seed(DatabaseSeeder::class);
    }

    public function test_seeding_creates_no_sample_agent_and_no_sample_room(): void
    {
        $this->seedReference();

        $this->assertSame(0, Admin::query()->count());
        $this->assertSame(0, Room::query()->count());
    }

    public function test_seeding_creates_the_full_role_with_every_permission(): void
    {
        $this->seedReference();

        $role = SuperadminRole::query()->where('code', 'full')->firstOrFail();
        $this->assertSame('Full', $role->name);
        $grants = $role->grants();
        $this->assertCount(count(Permission::cases()), $grants);
        foreach (Permission::cases() as $permission) {
            $this->assertSame(PermissionScope::All->value, $grants[$permission->value] ?? null, $permission->value);
        }
    }

    public function test_seeding_keeps_the_reference_data_it_should_keep(): void
    {
        $this->seedReference();

        $this->assertSame(3, \App\Models\Package::query()->count());
        $this->assertTrue(\App\Models\Package::query()->where('code', 'starter')->exists());
        $this->assertTrue(Version::query()->exists());
        $this->assertSame(count(Permission::cases()), PermissionRecord::query()->count());
    }

    public function test_seeded_platform_owner_holds_the_full_role_and_every_permission(): void
    {
        $this->seedReference();

        $email = (string) config('platform_owner.email');
        $owner = Superadmin::query()->where('email', $email)->firstOrFail();
        $this->assertSame(SuperadminRole::query()->where('code', 'full')->value('id'), $owner->superadmin_role_id);
        $this->assertSame(count(Permission::cases()), $owner->permissions()->count());
        $this->assertTrue($owner->hasPermission(Permission::AgentManage));
    }

    public function test_seeding_is_repeatable(): void
    {
        $this->seedReference();
        $ownerId = (int) Superadmin::query()->value('id');
        $roleId = (int) SuperadminRole::query()->where('code', 'full')->value('id');

        $this->seedReference();

        $this->assertSame(1, Superadmin::query()->count());
        $this->assertSame($ownerId, (int) Superadmin::query()->value('id'));
        $this->assertSame(1, SuperadminRole::query()->where('code', 'full')->count());
        $this->assertSame($roleId, (int) SuperadminRole::query()->where('code', 'full')->value('id'));
        $this->assertSame(count(Permission::cases()), PermissionRecord::query()->count());
        $this->assertSame(0, Admin::query()->count());
        $this->assertSame(0, Room::query()->count());
    }
}

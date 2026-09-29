<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Superadmin\AssignAgentAction;
use App\Enums\Permission;
use App\Enums\PermissionScope;
use App\Models\Admin;
use App\Models\PermissionRecord;
use App\Models\Room;
use App\Models\Superadmin;
use App\Models\SuperadminAdmin;
use App\Services\Authorization\AgentScope;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * T07: `superadmin_admins` links Superadmins to the Agents they manage.
 */
class SuperadminAgentAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private AssignAgentAction $action;

    private Superadmin $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = app(AssignAgentAction::class);
        $this->owner = $this->createSuperadmin(['name' => 'Owner', 'email' => 'owner@drinkflow.test', 'password' => 'password123', 'status' => 'active']);
    }

    /**
     * Superadmin holding the given permissions.
     *
     * @param string $email Account email.
     * @param array<string, PermissionScope> $grants Permission key => scope.
     * @return Superadmin Superadmin.
     */
    private function superadminWith(string $email, array $grants = []): Superadmin
    {
        $superadmin = Superadmin::create(['name' => 'Scoped', 'email' => $email, 'password' => 'password123', 'status' => 'active']);
        foreach ($grants as $key => $scope) {
            $superadmin->permissions()->attach(PermissionRecord::query()->where('key', $key)->value('id'), ['scope' => $scope->value]);
        }

        return $superadmin;
    }

    private function agentWithRoom(string $slug): Admin
    {
        $admin = Admin::create(['name' => 'Agent '.$slug, 'email' => "agent-{$slug}@drinkflow.test", 'password' => 'password123', 'status' => 'active']);
        $admin->rooms()->attach(Room::create(['name' => 'Room '.$slug, 'slug' => $slug, 'status' => 'active']));

        return $admin;
    }

    public function test_schema_has_assignment_columns_and_constraints(): void
    {
        $this->assertTrue(Schema::hasColumns('superadmin_admins', [
            'superadmin_id', 'admin_id', 'is_primary', 'assigned_at', 'assigned_by_superadmin_id', 'created_at', 'updated_at',
        ]));

        $agent = $this->agentWithRoom('schema');
        $this->action->assign($this->owner, $agent, $this->owner);

        // Duplicate pair.
        $this->expectException(QueryException::class);
        DB::table('superadmin_admins')->insert(['superadmin_id' => $this->owner->id, 'admin_id' => $agent->id, 'is_primary' => false, 'assigned_at' => now()]);
    }

    public function test_database_refuses_a_second_primary_superadmin(): void
    {
        $agent = $this->agentWithRoom('two-primaries');
        $other = $this->superadminWith('other@drinkflow.test');
        $this->action->assign($this->owner, $agent, $this->owner, primary: true);

        $this->expectException(QueryException::class);
        DB::table('superadmin_admins')->insert(['superadmin_id' => $other->id, 'admin_id' => $agent->id, 'is_primary' => true, 'assigned_at' => now()]);
    }

    public function test_assign_records_who_and_when_and_is_idempotent(): void
    {
        $agent = $this->agentWithRoom('assign');
        $member = $this->superadminWith('member@drinkflow.test');

        $assignment = $this->action->assign($member, $agent, $this->owner);
        $this->action->assign($member, $agent, $this->owner);

        $this->assertSame(1, SuperadminAdmin::query()->count());
        $this->assertFalse($assignment->is_primary);
        $this->assertSame($this->owner->id, $assignment->assigned_by_superadmin_id);
        $this->assertNotNull($assignment->assigned_at);
        $this->assertSame([$agent->id], $member->managedAdminIds());
        $this->assertSame([$member->id], $agent->superadmins()->pluck('superadmins.id')->all());
        $this->assertSame(1, DB::table('audit_logs')->where('event', 'agent.assigned')->where('target_id', $agent->id)->count());
    }

    public function test_new_primary_demotes_the_previous_one(): void
    {
        $agent = $this->agentWithRoom('primary');
        $first = $this->superadminWith('first@drinkflow.test');
        $second = $this->superadminWith('second@drinkflow.test');

        $this->action->assign($first, $agent, $this->owner, primary: true);
        $this->action->assign($second, $agent, $this->owner, primary: true);

        $primaries = SuperadminAdmin::query()->where('admin_id', $agent->id)->where('is_primary', true)->pluck('superadmin_id')->all();
        $this->assertSame([$second->id], $primaries);
        // The previous primary keeps managing the Agent.
        $this->assertSame([$agent->id], $first->managedAdminIds());
        $this->assertDatabaseHas('audit_logs', ['event' => 'agent.assignment_updated', 'target_id' => $agent->id]);
    }

    public function test_unassign_removes_access_and_is_audited(): void
    {
        $agent = $this->agentWithRoom('unassign');
        $member = $this->superadminWith('member@drinkflow.test', [Permission::AgentView->value => PermissionScope::Managed]);
        $this->action->assign($member, $agent, $this->owner, primary: true);
        $this->assertTrue(app(AgentScope::class)->allows($member, Permission::AgentView, $agent->id));

        $this->assertTrue($this->action->unassign($member, $agent));
        $this->assertFalse($this->action->unassign($member, $agent));

        $this->assertSame([], $member->managedAdminIds());
        $this->assertFalse(app(AgentScope::class)->allows($member, Permission::AgentView, $agent->id));
        $this->assertSame(1, DB::table('audit_logs')->where('event', 'agent.unassigned')->count());
    }

    public function test_managed_scope_reaches_only_assigned_agents_and_their_rooms(): void
    {
        $assigned = $this->agentWithRoom('mine');
        $foreign = $this->agentWithRoom('theirs');
        $member = $this->superadminWith('member@drinkflow.test', [
            Permission::AgentView->value => PermissionScope::Managed,
            Permission::RoomView->value => PermissionScope::Managed,
        ]);
        $this->action->assign($member, $assigned, $this->owner);
        $this->actingAs($member, 'superadmin');

        $this->getJson(route('superadmin.admins.index'))->assertOk()->assertJsonCount(1, 'data.data');
        $this->getJson(route('superadmin.admins.show', $assigned))->assertOk();
        $this->getJson(route('superadmin.admins.show', $foreign))->assertForbidden();
        $this->getJson(route('superadmin.rooms.index'))->assertOk()->assertJsonCount(1, 'data.data');
        $this->getJson(route('superadmin.rooms.show', $assigned->rooms()->first()))->assertOk();
        $this->getJson(route('superadmin.rooms.show', $foreign->rooms()->first()))->assertForbidden();
    }

    public function test_assignments_follow_deleted_accounts(): void
    {
        $agent = $this->agentWithRoom('cascade');
        $member = $this->superadminWith('member@drinkflow.test');
        $assigner = $this->superadminWith('assigner@drinkflow.test');
        $this->action->assign($member, $agent, $assigner);

        // Deleting the assigner keeps the assignment without its author.
        $assigner->delete();
        $this->assertNull(SuperadminAdmin::query()->firstOrFail()->assigned_by_superadmin_id);

        $agent->delete();
        $this->assertSame(0, SuperadminAdmin::query()->count());

        $other = $this->agentWithRoom('cascade-2');
        $this->action->assign($member, $other, $this->owner);
        $member->delete();
        $this->assertSame(0, SuperadminAdmin::query()->count());
    }
}

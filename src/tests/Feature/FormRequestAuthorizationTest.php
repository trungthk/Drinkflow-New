<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\GlobalUserStatus;
use App\Enums\RoomStatus;
use App\Enums\RoomUserStatus;
use App\Http\Requests\AdminPasswordRequest;
use App\Http\Requests\JoinRoomByCodeRequest;
use App\Http\Requests\MergeGlobalUsersRequest;
use App\Http\Requests\StoreAdminRequest;
use App\Http\Requests\StoreCampaignRequest;
use App\Http\Requests\StoreFeedbackRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateGlobalProfileRequest;
use App\Models\AdminAccount;
use App\Models\Campaign;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Models\RoomUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Tests\TestCase;

class FormRequestAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_request_authorizes_only_active_superadmin(): void
    {
        $superadmin = AdminAccount::create([
            'name' => 'Superadmin User',
            'email' => 'super@drinkflow.test',
            'password' => 'secret123',
            'role' => AdminRole::SuperAdmin,
            'status' => 'active',
        ]);

        $inactiveSuperadmin = AdminAccount::create([
            'name' => 'Inactive Superadmin',
            'email' => 'inactive-super@drinkflow.test',
            'password' => 'secret123',
            'role' => AdminRole::SuperAdmin,
            'status' => \App\Enums\AdminStatus::Inactive,
        ]);

        $regularAdmin = AdminAccount::create([
            'name' => 'Regular Admin',
            'email' => 'regular@drinkflow.test',
            'password' => 'secret123',
            'role' => AdminRole::Admin,
            'status' => 'active',
        ]);

        // Active superadmin should pass
        $this->actingAs($superadmin, 'admin');
        $request = StoreRoomRequest::create('/superadmin/rooms', 'POST');
        $request->setUserResolver(fn () => $superadmin);
        $this->assertTrue($request->authorize());

        // Inactive superadmin should fail
        $this->actingAs($inactiveSuperadmin, 'admin');
        $request2 = StoreRoomRequest::create('/superadmin/rooms', 'POST');
        $request2->setUserResolver(fn () => $inactiveSuperadmin);
        $this->assertFalse($request2->authorize());

        // Regular admin should fail superadmin requests
        $this->actingAs($regularAdmin, 'admin');
        $request3 = StoreRoomRequest::create('/superadmin/rooms', 'POST');
        $request3->setUserResolver(fn () => $regularAdmin);
        $this->assertFalse($request3->authorize());
    }

    public function test_room_admin_request_authorizes_active_admin_assigned_to_active_room(): void
    {
        $activeRoom = Room::create([
            'name' => 'Marketing Room',
            'slug' => 'marketing-room',
            'status' => RoomStatus::Active->value,
        ]);

        $inactiveRoom = Room::create([
            'name' => 'Inactive Room',
            'slug' => 'inactive-room',
            'status' => RoomStatus::Inactive->value,
        ]);

        $assignedAdmin = AdminAccount::create([
            'name' => 'Assigned Admin',
            'email' => 'assigned@drinkflow.test',
            'password' => 'secret123',
            'role' => AdminRole::Admin,
            'status' => 'active',
        ]);
        $assignedAdmin->rooms()->attach($activeRoom->id);

        $unassignedAdmin = AdminAccount::create([
            'name' => 'Unassigned Admin',
            'email' => 'unassigned@drinkflow.test',
            'password' => 'secret123',
            'role' => AdminRole::Admin,
            'status' => 'active',
        ]);

        // Assigned admin on active room
        $this->actingAs($assignedAdmin, 'admin');
        $request = StoreCampaignRequest::create("/admin/{$activeRoom->slug}/campaigns", 'POST');
        $request->setUserResolver(fn () => $assignedAdmin);
        $route = new Route('POST', 'admin/{room}/campaigns', []);
        $route->bind($request);
        $route->setParameter('room', $activeRoom);
        $request->setRouteResolver(fn () => $route);
        $this->assertTrue($request->authorize());

        // Unassigned admin on active room
        $this->actingAs($unassignedAdmin, 'admin');
        $request2 = StoreCampaignRequest::create("/admin/{$activeRoom->slug}/campaigns", 'POST');
        $request2->setUserResolver(fn () => $unassignedAdmin);
        $request2->setRouteResolver(fn () => $route);
        $this->assertFalse($request2->authorize());

        // Assigned admin on inactive room
        $inactiveRoute = new Route('POST', 'admin/{room}/campaigns', []);
        $inactiveRoute->bind($request);
        $inactiveRoute->setParameter('room', $inactiveRoom);
        $request3 = StoreCampaignRequest::create("/admin/{$inactiveRoom->slug}/campaigns", 'POST');
        $request3->setUserResolver(fn () => $assignedAdmin);
        $request3->setRouteResolver(fn () => $inactiveRoute);
        $this->assertFalse($request3->authorize());
    }

    public function test_global_user_request_authorizes_active_global_user(): void
    {
        $activeUser = GlobalUser::create([
            'name' => 'Active User',
            'normalized_name' => 'ACTIVE USER',
            'email' => 'active-user@drinkflow.test',
            'status' => GlobalUserStatus::Active->value,
        ]);

        $blockedUser = GlobalUser::create([
            'name' => 'Blocked User',
            'normalized_name' => 'BLOCKED USER',
            'email' => 'blocked-user@drinkflow.test',
            'status' => GlobalUserStatus::Blocked->value,
        ]);

        // Active global user
        $this->actingAs($activeUser, 'web');
        $request = UpdateGlobalProfileRequest::create('/me/profile', 'POST');
        $request->setUserResolver(fn () => $activeUser);
        $this->assertTrue($request->authorize());

        // Blocked user
        $this->actingAs($blockedUser, 'web');
        $request2 = UpdateGlobalProfileRequest::create('/me/profile', 'POST');
        $request2->setUserResolver(fn () => $blockedUser);
        $this->assertFalse($request2->authorize());

        // Guest
        \Illuminate\Support\Facades\Auth::guard('web')->logout();
        $request3 = UpdateGlobalProfileRequest::create('/me/profile', 'POST');
        $request3->setUserResolver(fn () => null);
        $this->assertFalse($request3->authorize());
    }

    public function test_room_user_request_authorizes_active_member_in_active_room(): void
    {
        $room = Room::create([
            'name' => 'Dev Room',
            'slug' => 'dev-room',
            'status' => RoomStatus::Active->value,
        ]);

        $user = GlobalUser::create([
            'name' => 'Room Member',
            'normalized_name' => 'ROOM MEMBER',
            'email' => 'member@drinkflow.test',
            'status' => GlobalUserStatus::Active->value,
        ]);

        $roomUser = RoomUser::create([
            'room_id' => $room->id,
            'global_user_id' => $user->id,
            'user_code' => 'MEMBER1',
            'display_name' => 'Room Member',
            'normalized_name' => 'ROOM MEMBER',
            'status' => RoomUserStatus::Active->value,
        ]);

        $campaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Coffee Break',
            'restaurant' => 'Highlands',
            'status' => 'active',
        ]);

        $this->actingAs($user, 'web');
        $request = StoreOrderRequest::create("/rooms/{$room->slug}/campaigns/{$campaign->id}/orders", 'POST');
        $request->setUserResolver(fn () => $user);
        $route = new Route('POST', 'rooms/{room}/campaigns/{campaign}/orders', []);
        $route->bind($request);
        $route->setParameter('room', $room);
        $route->setParameter('campaign', $campaign);
        $request->setRouteResolver(fn () => $route);

        $this->assertTrue($request->authorize());

        // Inactive member
        $roomUser->update(['status' => RoomUserStatus::Blocked->value]);
        $this->assertFalse($request->authorize());

        // Inactive room
        $roomUser->update(['status' => RoomUserStatus::Active->value]);
        $room->update(['status' => RoomStatus::Inactive->value]);
        $this->assertFalse($request->authorize());
    }
}

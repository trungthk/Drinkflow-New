<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AdminAccount;
use App\Models\AdminNotification;
use App\Models\GlobalUser;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PruneNotificationsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that expired read notifications (admin and user) are deleted,
     * while unread notifications remain untouched.
     *
     * @return void
     */
    public function test_command_deletes_expired_read_notifications_and_keeps_unread(): void
    {
        config()->set('retention.read_admin_notifications_days', 30);
        config()->set('retention.read_user_notifications_days', 30);

        $admin      = AdminAccount::create(['name' => 'Admin', 'email' => 'notif-prune@example.test', 'password' => 'password', 'role' => 'admin', 'status' => 'active']);
        $globalUser = GlobalUser::create(['name' => 'User', 'email' => 'notif-user@example.test', 'status' => 'active']);

        // Admin: old read (pruned) | unread old (kept) | recently read (kept)
        $oldReadAdmin   = AdminNotification::create(['admin_id' => $admin->id, 'type' => 'audit', 'title' => 'Old Read Admin',   'read_at' => now()->subDays(31)]);
        $unreadAdmin    = AdminNotification::create(['admin_id' => $admin->id, 'type' => 'audit', 'title' => 'Unread Admin',     'created_at' => now()->subDays(90)]);
        $freshReadAdmin = AdminNotification::create(['admin_id' => $admin->id, 'type' => 'audit', 'title' => 'Fresh Read Admin', 'read_at' => now()->subDays(5)]);

        // User: old read (pruned) | unread old (kept) | recently read (kept)
        $oldReadUser   = UserNotification::create(['global_user_id' => $globalUser->id, 'type' => 'order.created', 'title' => 'Old Read User',   'read_at' => now()->subDays(31)]);
        $unreadUser    = UserNotification::create(['global_user_id' => $globalUser->id, 'type' => 'order.created', 'title' => 'Unread User',     'created_at' => now()->subDays(90)]);
        $freshReadUser = UserNotification::create(['global_user_id' => $globalUser->id, 'type' => 'order.created', 'title' => 'Fresh Read User', 'read_at' => now()->subDays(5)]);

        $this->artisan('drinkflow:prune-notifications')->assertSuccessful();

        // Expired read notifications are gone
        $this->assertDatabaseMissing('admin_notifications', ['id' => $oldReadAdmin->id]);
        $this->assertDatabaseMissing('user_notifications',  ['id' => $oldReadUser->id]);

        // Unread notifications remain
        $this->assertDatabaseHas('admin_notifications', ['id' => $unreadAdmin->id]);
        $this->assertDatabaseHas('user_notifications',  ['id' => $unreadUser->id]);

        // Recently read notifications remain
        $this->assertDatabaseHas('admin_notifications', ['id' => $freshReadAdmin->id]);
        $this->assertDatabaseHas('user_notifications',  ['id' => $freshReadUser->id]);
    }
}
<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if ($user = auth('web')->user()) {
        $room = $user->roomUsers()->where('status', 'active')->with('room')->first();
        if ($room?->room) return redirect()->route('user.dashboard', $room->room);
        // A newly authenticated account may not have joined a Room yet. Send
        // it to the authenticated profile/onboarding screen instead of
        // rendering the anonymous landing page again.
        return redirect()->route('user.profile.page');
    }
    return view('landing');
});

Route::get('/auth/google', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'callback'])->name('auth.google.callback');
Route::get('/rooms/{room}', [\App\Http\Controllers\User\RoomController::class, 'show'])->name('user.rooms.show');
Route::get('/rooms/{room}/join', \App\Http\Controllers\User\JoinPageController::class)->name('user.rooms.join.show');
Route::post('/rooms/{room}/join', [\App\Http\Controllers\User\RoomController::class, 'join'])->name('user.rooms.join');
Route::post('/admin/login', [\App\Http\Controllers\Admin\AuthController::class, 'login'])->name('admin.login');
Route::get('/admin/login', [\App\Http\Controllers\Admin\AuthController::class, 'loginPage'])->name('admin.login.page');
Route::get('/admin', [\App\Http\Controllers\Admin\AuthController::class, 'landing'])->middleware('auth:admin')->name('admin.landing');
Route::post('/admin/logout', [\App\Http\Controllers\Admin\AuthController::class, 'logout'])->middleware('auth:admin')->name('admin.logout');

Route::middleware(['global.user', 'room.user'])->group(function () {
    Route::get('/rooms/{room}/dashboard', \App\Http\Controllers\User\DashboardController::class)->name('user.dashboard');
    Route::get('/rooms/{room}/orders', [\App\Http\Controllers\User\OrderController::class, 'index'])->name('user.orders.index');
    Route::get('/rooms/{room}/campaigns', [\App\Http\Controllers\User\CampaignController::class, 'index'])->name('user.campaigns.index');
    Route::get('/rooms/{room}/campaigns/{campaign}', [\App\Http\Controllers\User\CampaignController::class, 'show'])->name('user.campaigns.show');
    Route::get('/rooms/{room}/campaigns/{campaign}/order', \App\Http\Controllers\User\CampaignOrderPageController::class)->name('user.campaigns.order-page');
    Route::post('/rooms/{room}/campaigns/{campaign}/orders', [\App\Http\Controllers\User\OrderController::class, 'store'])
        ->name('user.orders.store');
    Route::get('/rooms/{room}/orders/{order}', [\App\Http\Controllers\User\OrderController::class, 'show'])->name('user.orders.show');
    Route::get('/rooms/{room}/orders/{order}/view', \App\Http\Controllers\User\OrderPageController::class)->name('user.orders.page');
    Route::get('/rooms/{room}/orders/{order}/payment', [\App\Http\Controllers\User\OrderController::class, 'payment'])->name('user.orders.payment');
    Route::get('/rooms/{room}/debts', [\App\Http\Controllers\User\DebtController::class, 'index'])->name('user.debts.index');
    Route::get('/rooms/{room}/analytics', [\App\Http\Controllers\User\AnalyticsController::class, 'room'])->name('user.analytics.room');
    Route::get('/rooms/{room}/socket-token', \App\Http\Controllers\User\SocketTokenController::class)->name('user.socket-token');
});

Route::middleware(['global.user'])->group(function () {
    Route::get('/profile', [\App\Http\Controllers\User\ProfileController::class, 'show'])->name('user.profile.show');
    Route::get('/rooms', [\App\Http\Controllers\User\ProfileController::class, 'rooms'])->name('user.rooms.index');
    Route::get('/history', [\App\Http\Controllers\User\HistoryController::class, 'index'])->name('user.history.index');
    Route::get('/history/page', \App\Http\Controllers\User\HistoryPageController::class)->name('user.history.page');
    Route::get('/analytics', [\App\Http\Controllers\User\AnalyticsController::class, 'global'])->name('user.analytics.global');
    Route::get('/notifications', [\App\Http\Controllers\User\NotificationController::class, 'index'])->name('user.notifications.index');
    Route::patch('/notifications/{notification}/read', [\App\Http\Controllers\User\NotificationController::class, 'read'])->name('user.notifications.read');
    Route::get('/profile/page', \App\Http\Controllers\User\ProfilePageController::class)->name('user.profile.page');
});

Route::middleware(['auth:admin', 'admin.room'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class])
    ->prefix('admin/{room}')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'page'])->name('admin.dashboard.page');
    Route::get('/manage', [\App\Http\Controllers\Admin\DashboardController::class, 'manage'])->name('admin.manage.page');
    Route::get('/dashboard/data', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/socket-token', \App\Http\Controllers\Admin\SocketTokenController::class)->name('admin.socket-token');
    Route::get('/campaigns', [\App\Http\Controllers\Admin\CampaignController::class, 'index'])->name('admin.campaigns.index');
    Route::get('/campaigns/{campaign}', [\App\Http\Controllers\Admin\CampaignController::class, 'show'])->name('admin.campaigns.show');
    Route::get('/orders', [\App\Http\Controllers\Admin\OrderController::class, 'index'])->name('admin.orders.index');
    Route::get('/orders/aggregate', [\App\Http\Controllers\Admin\CampaignController::class, 'aggregate'])->name('admin.orders.aggregate');
    Route::get('/orders/aggregate/export', [\App\Http\Controllers\Admin\CampaignController::class, 'exportAggregate'])->name('admin.orders.aggregate.export');
    Route::get('/orders/{order}', [\App\Http\Controllers\Admin\OrderController::class, 'show'])->name('admin.orders.show');
    Route::patch('/orders/{order}', [\App\Http\Controllers\Admin\OrderController::class, 'update'])->name('admin.orders.update');
    Route::patch('/orders/{order}/status', [\App\Http\Controllers\Admin\OrderController::class, 'updateStatus'])->name('admin.orders.status');
    Route::post('/orders/{order}/cancel', [\App\Http\Controllers\Admin\OrderController::class, 'cancel'])->name('admin.orders.cancel');
    Route::post('/orders/{order}/unlock', [\App\Http\Controllers\Admin\OrderController::class, 'unlock'])->name('admin.orders.unlock');
    Route::delete('/orders/{order}', [\App\Http\Controllers\Admin\OrderController::class, 'destroy'])->name('admin.orders.destroy');
    Route::post('/campaigns', [\App\Http\Controllers\Admin\CampaignController::class, 'store'])->name('admin.campaigns.store');
    Route::patch('/campaigns/{campaign}', [\App\Http\Controllers\Admin\CampaignController::class, 'update'])->name('admin.campaigns.update');
    Route::post('/campaigns/{campaign}/activate', [\App\Http\Controllers\Admin\CampaignController::class, 'activate'])->name('admin.campaigns.activate');
    Route::post('/campaigns/{campaign}/cancel', [\App\Http\Controllers\Admin\CampaignController::class, 'cancel'])->name('admin.campaigns.cancel');
    Route::post('/campaigns/{campaign}/archive', [\App\Http\Controllers\Admin\CampaignController::class, 'archive'])->name('admin.campaigns.archive');
    Route::post('/campaigns/{campaign}/duplicate', [\App\Http\Controllers\Admin\CampaignController::class, 'duplicate'])->name('admin.campaigns.duplicate');
    Route::post('/campaigns/{campaign}/split-bill', [\App\Http\Controllers\Admin\CampaignController::class, 'splitBill'])->name('admin.campaigns.split-bill');
    Route::post('/campaigns/{campaign}/items', [\App\Http\Controllers\Admin\CampaignController::class, 'storeItem'])->name('admin.campaign-items.store');
    Route::patch('/campaigns/{campaign}/items/{item}', [\App\Http\Controllers\Admin\CampaignController::class, 'updateItem'])->name('admin.campaign-items.update');
    Route::delete('/campaigns/{campaign}/items/{item}', [\App\Http\Controllers\Admin\CampaignController::class, 'archiveItem'])->name('admin.campaign-items.archive');
    Route::post('/campaigns/{campaign}/items/{item}/toppings', [\App\Http\Controllers\Admin\CampaignController::class, 'storeTopping'])->name('admin.campaign-item-toppings.store');
    Route::post('/campaigns/{campaign}/items/{item}/sizes', [\App\Http\Controllers\Admin\CampaignController::class, 'storeSize'])->name('admin.campaign-item-sizes.store');
    Route::patch('/campaigns/{campaign}/items/{item}/toppings/{option}', [\App\Http\Controllers\Admin\CampaignController::class, 'updateTopping'])->name('admin.campaign-item-toppings.update');
    Route::patch('/campaigns/{campaign}/items/{item}/sizes/{option}', [\App\Http\Controllers\Admin\CampaignController::class, 'updateSize'])->name('admin.campaign-item-sizes.update');
    Route::delete('/campaigns/{campaign}/items/{item}/toppings/{option}', [\App\Http\Controllers\Admin\CampaignController::class, 'deleteTopping'])->name('admin.campaign-item-toppings.delete');
    Route::delete('/campaigns/{campaign}/items/{item}/sizes/{option}', [\App\Http\Controllers\Admin\CampaignController::class, 'deleteSize'])->name('admin.campaign-item-sizes.delete');
    Route::post('/campaigns/{campaign}/close', [\App\Http\Controllers\Admin\CampaignController::class, 'close'])->name('admin.campaigns.close');
    Route::post('/crawler/preview', [\App\Http\Controllers\Admin\CrawlerController::class, 'preview'])->name('admin.crawler.preview');
    Route::post('/campaigns/{campaign}/crawler/import', [\App\Http\Controllers\Admin\CrawlerController::class, 'import'])->name('admin.crawler.import');
    Route::get('/debts', [\App\Http\Controllers\Admin\DebtController::class, 'index'])->name('admin.debts.index');
    Route::get('/debts/{debt}', [\App\Http\Controllers\Admin\DebtController::class, 'show'])->name('admin.debts.show');
    Route::post('/debts/{debt}/payments', [\App\Http\Controllers\Admin\DebtController::class, 'pay'])->name('admin.debts.pay');
    Route::post('/debts/{debt}/adjust', [\App\Http\Controllers\Admin\DebtController::class, 'adjust'])->name('admin.debts.adjust');
    Route::patch('/debts/{debt}/status', [\App\Http\Controllers\Admin\DebtController::class, 'status'])->name('admin.debts.status');
    Route::get('/room-users', [\App\Http\Controllers\Admin\RoomUserController::class, 'index'])->name('admin.room-users.index');
    Route::get('/room-users/{roomUser}', [\App\Http\Controllers\Admin\RoomUserController::class, 'show'])->name('admin.room-users.show');
    Route::patch('/room-users/{roomUser}/status', [\App\Http\Controllers\Admin\RoomUserController::class, 'status'])->name('admin.room-users.status');
    Route::post('/room-users/{roomUser}/devices/{device}/revoke', [\App\Http\Controllers\Admin\RoomUserController::class, 'revokeDevice'])->name('admin.room-user-devices.revoke');
    Route::get('/payment-accounts', [\App\Http\Controllers\Admin\PaymentAccountController::class, 'index'])->name('admin.payment-accounts.index');
    Route::post('/payment-accounts', [\App\Http\Controllers\Admin\PaymentAccountController::class, 'store'])->name('admin.payment-accounts.store');
    Route::patch('/payment-accounts/{account}', [\App\Http\Controllers\Admin\PaymentAccountController::class, 'update'])->name('admin.payment-accounts.update');
    Route::delete('/payment-accounts/{account}', [\App\Http\Controllers\Admin\PaymentAccountController::class, 'destroy'])->name('admin.payment-accounts.destroy');
    Route::get('/settings', [\App\Http\Controllers\Admin\RoomSettingsController::class, 'show'])->name('admin.settings.show');
    Route::patch('/settings', [\App\Http\Controllers\Admin\RoomSettingsController::class, 'update'])->name('admin.settings.update');
    Route::get('/notification-channels', [\App\Http\Controllers\Admin\NotificationChannelController::class, 'index'])->name('admin.notification-channels.index');
    Route::post('/notification-channels', [\App\Http\Controllers\Admin\NotificationChannelController::class, 'store'])->name('admin.notification-channels.store');
    Route::patch('/notification-channels/{channel}', [\App\Http\Controllers\Admin\NotificationChannelController::class, 'update'])->name('admin.notification-channels.update');
    Route::post('/notification-channels/{channel}/test', [\App\Http\Controllers\Admin\NotificationChannelController::class, 'test'])->name('admin.notification-channels.test');
    Route::delete('/notification-channels/{channel}', [\App\Http\Controllers\Admin\NotificationChannelController::class, 'destroy'])->name('admin.notification-channels.destroy');
    Route::get('/reports', [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('admin.reports.index');
    Route::get('/audit', [\App\Http\Controllers\Admin\AuditController::class, 'index'])->name('admin.audit.index');
});

Route::middleware(['auth:admin', 'superadmin'])->prefix('superadmin')->group(function () {
    Route::get('/', [\App\Http\Controllers\Superadmin\DashboardController::class, 'index'])->name('superadmin.dashboard');
    Route::get('/rooms/page', [\App\Http\Controllers\Superadmin\PageController::class, 'rooms'])->name('superadmin.rooms.page');
    Route::get('/rooms', [\App\Http\Controllers\Superadmin\RoomController::class, 'index'])->name('superadmin.rooms.index');
    Route::post('/rooms', [\App\Http\Controllers\Superadmin\RoomController::class, 'store'])->name('superadmin.rooms.store');
    Route::get('/rooms/{room}/page', [\App\Http\Controllers\Superadmin\PageController::class, 'room'])->name('superadmin.rooms.detail.page');
    Route::get('/rooms/{room}', [\App\Http\Controllers\Superadmin\RoomController::class, 'show'])->name('superadmin.rooms.show');
    Route::match(['put', 'patch'], '/rooms/{room}', [\App\Http\Controllers\Superadmin\RoomController::class, 'update'])->name('superadmin.rooms.update');
    Route::patch('/rooms/{room}/status', [\App\Http\Controllers\Superadmin\RoomController::class, 'status'])->name('superadmin.rooms.status');
    Route::get('/admins/page', [\App\Http\Controllers\Superadmin\PageController::class, 'admins'])->name('superadmin.admins.page');
    Route::get('/admins', [\App\Http\Controllers\Superadmin\AdminController::class, 'index'])->name('superadmin.admins.index');
    Route::post('/admins', [\App\Http\Controllers\Superadmin\AdminController::class, 'store'])->name('superadmin.admins.store');
    Route::get('/admins/{admin}/page', [\App\Http\Controllers\Superadmin\PageController::class, 'admin'])->name('superadmin.admins.detail.page');
    Route::get('/admins/{admin}', [\App\Http\Controllers\Superadmin\AdminController::class, 'show'])->name('superadmin.admins.show');
    Route::match(['put', 'patch'], '/admins/{admin}', [\App\Http\Controllers\Superadmin\AdminController::class, 'update'])->name('superadmin.admins.update');
    Route::patch('/admins/{admin}/status', [\App\Http\Controllers\Superadmin\AdminController::class, 'status'])->name('superadmin.admins.status');
    Route::patch('/admins/{admin}/role', [\App\Http\Controllers\Superadmin\AdminController::class, 'role'])->name('superadmin.admins.role');
    Route::put('/admins/{admin}/rooms', [\App\Http\Controllers\Superadmin\AdminController::class, 'rooms'])->name('superadmin.admins.rooms');
    Route::post('/admins/{admin}/reset-password', [\App\Http\Controllers\Superadmin\AdminController::class, 'resetPassword'])->name('superadmin.admins.reset-password');
    Route::delete('/admins/{admin}', [\App\Http\Controllers\Superadmin\AdminController::class, 'destroy'])->name('superadmin.admins.destroy');
    Route::get('/global-users/page', [\App\Http\Controllers\Superadmin\PageController::class, 'users'])->name('superadmin.global-users.page');
    Route::get('/global-users', [\App\Http\Controllers\Superadmin\GlobalUserController::class, 'index'])->name('superadmin.global-users.index');
    Route::get('/global-users/{globalUser}/page', [\App\Http\Controllers\Superadmin\PageController::class, 'user'])->name('superadmin.global-users.detail.page');
    Route::get('/global-users/{globalUser}', [\App\Http\Controllers\Superadmin\GlobalUserController::class, 'show'])->name('superadmin.global-users.show');
    Route::patch('/global-users/{globalUser}/status', [\App\Http\Controllers\Superadmin\GlobalUserController::class, 'status'])->name('superadmin.global-users.status');
    Route::delete('/global-users/{globalUser}/memberships/{roomUser}', [\App\Http\Controllers\Superadmin\GlobalUserController::class, 'removeMembership'])->name('superadmin.global-users.memberships.remove');
    Route::post('/global-users/{globalUser}/memberships/{roomUser}/devices/{device}/revoke', [\App\Http\Controllers\Superadmin\GlobalUserController::class, 'revokeDevice'])->name('superadmin.global-users.devices.revoke');
    Route::post('/global-users/merge', [\App\Http\Controllers\Superadmin\GlobalUserController::class, 'merge'])->name('superadmin.global-users.merge');
    Route::get('/campaigns/page', [\App\Http\Controllers\Superadmin\PageController::class, 'campaigns'])->name('superadmin.campaigns.page');
    Route::get('/campaigns', [\App\Http\Controllers\Superadmin\CampaignController::class, 'index'])->name('superadmin.campaigns.index');
    Route::post('/campaigns/{campaign}/force-close', [\App\Http\Controllers\Superadmin\CampaignController::class, 'forceClose'])->name('superadmin.campaigns.force-close');
    Route::post('/campaigns/{campaign}/force-cancel', [\App\Http\Controllers\Superadmin\CampaignController::class, 'forceCancel'])->name('superadmin.campaigns.force-cancel');
    Route::get('/debts/page', [\App\Http\Controllers\Superadmin\PageController::class, 'debts'])->name('superadmin.debts.page');
    Route::get('/debts', [\App\Http\Controllers\Superadmin\DebtController::class, 'index'])->name('superadmin.debts.index');
    Route::get('/debts/export', [\App\Http\Controllers\Superadmin\DebtController::class, 'export'])->name('superadmin.debts.export');
    Route::get('/notifications/page', [\App\Http\Controllers\Superadmin\PageController::class, 'notifications'])->name('superadmin.notifications.page');
    Route::get('/notifications', [\App\Http\Controllers\Superadmin\NotificationController::class, 'index'])->name('superadmin.notifications.index');
    Route::post('/notifications', [\App\Http\Controllers\Superadmin\NotificationController::class, 'store'])->name('superadmin.notifications.store');
    Route::match(['put', 'patch'], '/notifications/{channel}', [\App\Http\Controllers\Superadmin\NotificationController::class, 'update'])->name('superadmin.notifications.update');
    Route::delete('/notifications/{channel}', [\App\Http\Controllers\Superadmin\NotificationController::class, 'destroy'])->name('superadmin.notifications.destroy');
    Route::get('/system/page', [\App\Http\Controllers\Superadmin\PageController::class, 'system'])->name('superadmin.system.page');
    Route::get('/system', [\App\Http\Controllers\Superadmin\SystemController::class, 'index'])->name('superadmin.system.index');
    Route::put('/system/settings', [\App\Http\Controllers\Superadmin\SystemController::class, 'settings'])->name('superadmin.system.settings');
    Route::get('/system/maintenance', [\App\Http\Controllers\Superadmin\SystemController::class, 'maintenance'])->name('superadmin.system.maintenance');
    Route::put('/system/maintenance', [\App\Http\Controllers\Superadmin\SystemController::class, 'maintenance'])->name('superadmin.system.maintenance.update');
    Route::post('/system/reset', [\App\Http\Controllers\Superadmin\SystemController::class, 'reset'])->name('superadmin.system.reset');
    Route::get('/audit-logs/page', [\App\Http\Controllers\Superadmin\PageController::class, 'audit'])->name('superadmin.audit.page');
    Route::get('/audit-logs', [\App\Http\Controllers\Superadmin\AuditController::class, 'index'])->name('superadmin.audit-logs.index');
    Route::get('/security-events/page', [\App\Http\Controllers\Superadmin\PageController::class, 'security'])->name('superadmin.security.page');
    Route::get('/security-events', [\App\Http\Controllers\Superadmin\SecurityController::class, 'index'])->name('superadmin.security-events.index');
    Route::get('/socket/page', [\App\Http\Controllers\Superadmin\PageController::class, 'socket'])->name('superadmin.socket.page');
    Route::get('/socket', [\App\Http\Controllers\Superadmin\SocketMonitoringController::class, 'index'])->name('superadmin.socket.index');
    Route::get('/socket-token', \App\Http\Controllers\Superadmin\SocketTokenController::class)->name('superadmin.socket-token');
    Route::get('/queue/page', [\App\Http\Controllers\Superadmin\PageController::class, 'queue'])->name('superadmin.queue.page');
    Route::get('/queue/failed', [\App\Http\Controllers\Superadmin\QueueController::class, 'index'])->name('superadmin.queue.failed.index');
    Route::post('/queue/failed/{failedJob}/retry', [\App\Http\Controllers\Superadmin\QueueController::class, 'retry'])->name('superadmin.queue.failed.retry');
    Route::delete('/queue/failed/{failedJob}', [\App\Http\Controllers\Superadmin\QueueController::class, 'forget'])->name('superadmin.queue.failed.forget');
    Route::get('/versions/page', [\App\Http\Controllers\Superadmin\PageController::class, 'versions'])->name('superadmin.versions.page');
    Route::get('/versions', [\App\Http\Controllers\Superadmin\VersionController::class, 'index'])->name('superadmin.versions.index');
    Route::post('/versions', [\App\Http\Controllers\Superadmin\VersionController::class, 'store'])->name('superadmin.versions.store');
    Route::match(['put', 'patch'], '/versions/{version}', [\App\Http\Controllers\Superadmin\VersionController::class, 'update'])->name('superadmin.versions.update');
    Route::delete('/versions/{version}', [\App\Http\Controllers\Superadmin\VersionController::class, 'destroy'])->name('superadmin.versions.destroy');
});

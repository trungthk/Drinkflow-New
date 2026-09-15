<?php

use Illuminate\Support\Facades\Route;

// Admin Authentication & Password Recovery Routes
Route::get('/admin/login', [\App\Http\Controllers\Admin\AuthController::class, 'loginPage'])
    ->middleware('throttle:admin-login')
    ->name('admin.login.page');
Route::post('/admin/login', [\App\Http\Controllers\Admin\AuthController::class, 'login'])
    ->middleware('throttle:admin-login')
    ->name('admin.login');
Route::get('/admin/forgot-password', [\App\Http\Controllers\Admin\AuthController::class, 'forgotPasswordPage'])
    ->middleware('throttle:admin-forgot-password')
    ->name('admin.forgot-password.page');
Route::post('/admin/forgot-password', [\App\Http\Controllers\Admin\AuthController::class, 'sendResetOtp'])
    ->middleware('throttle:admin-forgot-password')
    ->name('admin.forgot-password.send');
Route::get('/admin/verify-otp', [\App\Http\Controllers\Admin\AuthController::class, 'verifyOtpPage'])
    ->middleware('throttle:admin-verify-otp')
    ->name('admin.verify-otp.page');
Route::post('/admin/verify-otp', [\App\Http\Controllers\Admin\AuthController::class, 'verifyOtp'])
    ->middleware('throttle:admin-verify-otp')
    ->name('admin.verify-otp.submit');
Route::get('/admin/reset-password', [\App\Http\Controllers\Admin\AuthController::class, 'resetPasswordPage'])
    ->middleware('throttle:admin-reset-password')
    ->name('admin.reset-password.page');
Route::post('/admin/reset-password', [\App\Http\Controllers\Admin\AuthController::class, 'resetPassword'])
    ->middleware('throttle:admin-reset-password')
    ->name('admin.reset-password.submit');
Route::get('/admin', [\App\Http\Controllers\Admin\AuthController::class, 'landing'])->middleware('auth:admin')->name('admin.landing');
Route::post('/admin/logout', [\App\Http\Controllers\Admin\AuthController::class, 'logout'])->middleware(['auth:admin', 'throttle:admin-login'])->name('admin.logout');
Route::middleware('auth:admin')->prefix('admin/profile')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\ProfileController::class, 'show'])->name('admin.profile');
    Route::patch('/', [\App\Http\Controllers\Admin\ProfileController::class, 'update'])->name('admin.profile.update');
    Route::post('/avatar', [\App\Http\Controllers\Admin\ProfileController::class, 'uploadAvatar'])->name('admin.profile.avatar');
    Route::patch('/password', [\App\Http\Controllers\Admin\ProfileController::class, 'updatePassword'])->name('admin.profile.password');
    Route::patch('/two-factor', [\App\Http\Controllers\Admin\ProfileController::class, 'updateTwoFactor'])->name('admin.profile.two-factor');
});

Route::middleware(['auth:admin', 'admin.room'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class])
    ->prefix('admin/{room}')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'page'])->name('admin.dashboard.page');
    Route::get('/manage', [\App\Http\Controllers\Admin\DashboardController::class, 'manage'])->name('admin.manage.page');
    Route::get('/dashboard/data', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/socket-token', \App\Http\Controllers\Admin\SocketTokenController::class)->name('admin.socket-token');
    Route::post('/notifications/read-all', [\App\Http\Controllers\Admin\NotificationController::class, 'markAllRead'])->name('admin.notifications.read-all');

    // Dedicated Standalone Page Views
    Route::get('/campaigns/list', [\App\Http\Controllers\Admin\CampaignController::class, 'page'])->name('admin.campaigns.page');
    Route::get('/orders/manage', [\App\Http\Controllers\Admin\OrderController::class, 'page'])->name('admin.orders.page');
    Route::get('/debts/ledger', [\App\Http\Controllers\Admin\DebtController::class, 'page'])->name('admin.debts.page');
    Route::get('/room-users/directory', [\App\Http\Controllers\Admin\RoomUserController::class, 'page'])->name('admin.room-users.page');
    Route::get('/payment-accounts/settings', [\App\Http\Controllers\Admin\PaymentAccountController::class, 'page'])->name('admin.payment-accounts.page');
    Route::get('/settings/general', [\App\Http\Controllers\Admin\RoomSettingsController::class, 'page'])->name('admin.settings.page');
    Route::get('/notification-channels/integrations', [\App\Http\Controllers\Admin\NotificationChannelController::class, 'page'])->name('admin.notification-channels.page');
    Route::get('/reports/analytics', [\App\Http\Controllers\Admin\ReportController::class, 'page'])->name('admin.reports.page');
    Route::get('/audit/logs', [\App\Http\Controllers\Admin\AuditController::class, 'page'])->name('admin.audit.page');

    // Campaign CRUD & Lifecycle API
    Route::get('/campaigns', [\App\Http\Controllers\Admin\CampaignController::class, 'index'])->name('admin.campaigns.index');
    Route::get('/campaigns/create', [\App\Http\Controllers\Admin\CampaignController::class, 'create'])->name('admin.campaigns.create');
    Route::get('/campaigns/previous-menus', [\App\Http\Controllers\Admin\CampaignController::class, 'previousMenus'])->name('admin.campaigns.previous-menus');
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
    Route::delete('/campaigns/{campaign}', [\App\Http\Controllers\Admin\CampaignController::class, 'destroy'])->name('admin.campaigns.destroy');
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
    Route::post('/crawler/preview', [\App\Http\Controllers\Admin\CrawlerController::class, 'preview'])
        ->middleware('throttle:crawler-preview')
        ->name('admin.crawler.preview');
    Route::post('/campaigns/{campaign}/crawler/import', [\App\Http\Controllers\Admin\CrawlerController::class, 'import'])->name('admin.crawler.import');
    Route::get('/debts', [\App\Http\Controllers\Admin\DebtController::class, 'index'])->name('admin.debts.index');
    Route::get('/debts/export', [\App\Http\Controllers\Admin\DebtController::class, 'export'])->name('admin.debts.export');
    Route::post('/debts/settle', [\App\Http\Controllers\Admin\DebtController::class, 'settle'])->name('admin.debts.settle');
    Route::post('/debts/remind', [\App\Http\Controllers\Admin\DebtController::class, 'remind'])->name('admin.debts.remind');
    Route::get('/debts/{debt}', [\App\Http\Controllers\Admin\DebtController::class, 'show'])->name('admin.debts.show');
    Route::post('/debts/{debt}/payments', [\App\Http\Controllers\Admin\DebtController::class, 'pay'])->name('admin.debts.pay');
    Route::post('/debts/{debt}/adjust', [\App\Http\Controllers\Admin\DebtController::class, 'adjust'])->name('admin.debts.adjust');
    Route::patch('/debts/{debt}/status', [\App\Http\Controllers\Admin\DebtController::class, 'status'])->name('admin.debts.status');
    Route::get('/room-users', [\App\Http\Controllers\Admin\RoomUserController::class, 'index'])->name('admin.room-users.index');
    Route::get('/room-users/{roomUser}', [\App\Http\Controllers\Admin\RoomUserController::class, 'show'])->name('admin.room-users.show');
    Route::patch('/room-users/{roomUser}/status', [\App\Http\Controllers\Admin\RoomUserController::class, 'status'])->name('admin.room-users.status');
    Route::delete('/room-users/{roomUser}', [\App\Http\Controllers\Admin\RoomUserController::class, 'destroy'])->name('admin.room-users.destroy');
    Route::post('/room-users/{roomUser}/devices/{device}/revoke', [\App\Http\Controllers\Admin\RoomUserController::class, 'revokeDevice'])->name('admin.room-user-devices.revoke');
    Route::get('/payment-accounts', [\App\Http\Controllers\Admin\PaymentAccountController::class, 'index'])->name('admin.payment-accounts.index');
    Route::post('/payment-accounts', [\App\Http\Controllers\Admin\PaymentAccountController::class, 'store'])->name('admin.payment-accounts.store');
    Route::patch('/payment-accounts/{account}', [\App\Http\Controllers\Admin\PaymentAccountController::class, 'update'])->name('admin.payment-accounts.update');
    Route::delete('/payment-accounts/{account}', [\App\Http\Controllers\Admin\PaymentAccountController::class, 'destroy'])->name('admin.payment-accounts.destroy');
    Route::get('/payment-accounts/{account}/qr', [\App\Http\Controllers\Admin\PaymentAccountController::class, 'qr'])->name('admin.payment-accounts.qr');
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

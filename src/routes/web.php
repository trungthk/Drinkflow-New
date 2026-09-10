<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/auth/google', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'callback'])->name('auth.google.callback');
Route::post('/admin/login', [\App\Http\Controllers\Admin\AuthController::class, 'login'])->name('admin.login');
Route::post('/admin/logout', [\App\Http\Controllers\Admin\AuthController::class, 'logout'])->middleware('auth:admin')->name('admin.logout');

Route::middleware(['auth:web', 'global.user', 'room.user'])->group(function () {
    Route::get('/rooms/{room}/orders', [\App\Http\Controllers\User\OrderController::class, 'index'])->name('user.orders.index');
    Route::get('/rooms/{room}/campaigns', [\App\Http\Controllers\User\CampaignController::class, 'index'])->name('user.campaigns.index');
    Route::get('/rooms/{room}/campaigns/{campaign}', [\App\Http\Controllers\User\CampaignController::class, 'show'])->name('user.campaigns.show');
    Route::post('/rooms/{room}/campaigns/{campaign}/orders', [\App\Http\Controllers\User\OrderController::class, 'store'])
        ->name('user.orders.store');
});

Route::middleware(['auth:web', 'global.user'])->group(function () {
    Route::get('/profile', [\App\Http\Controllers\User\ProfileController::class, 'show'])->name('user.profile.show');
    Route::get('/rooms', [\App\Http\Controllers\User\ProfileController::class, 'rooms'])->name('user.rooms.index');
});

Route::middleware(['auth:admin', 'admin.room'])->prefix('admin/{room}')->group(function () {
    Route::get('/campaigns', [\App\Http\Controllers\Admin\CampaignController::class, 'index'])->name('admin.campaigns.index');
    Route::get('/orders', [\App\Http\Controllers\Admin\OrderController::class, 'index'])->name('admin.orders.index');
    Route::patch('/orders/{order}/status', [\App\Http\Controllers\Admin\OrderController::class, 'updateStatus'])->name('admin.orders.status');
    Route::post('/campaigns', [\App\Http\Controllers\Admin\CampaignController::class, 'store'])->name('admin.campaigns.store');
    Route::post('/campaigns/{campaign}/items', [\App\Http\Controllers\Admin\CampaignController::class, 'storeItem'])->name('admin.campaign-items.store');
    Route::patch('/campaigns/{campaign}/items/{item}', [\App\Http\Controllers\Admin\CampaignController::class, 'updateItem'])->name('admin.campaign-items.update');
    Route::delete('/campaigns/{campaign}/items/{item}', [\App\Http\Controllers\Admin\CampaignController::class, 'archiveItem'])->name('admin.campaign-items.archive');
    Route::post('/campaigns/{campaign}/items/{item}/toppings', [\App\Http\Controllers\Admin\CampaignController::class, 'storeTopping'])->name('admin.campaign-item-toppings.store');
    Route::post('/campaigns/{campaign}/items/{item}/sizes', [\App\Http\Controllers\Admin\CampaignController::class, 'storeSize'])->name('admin.campaign-item-sizes.store');
    Route::post('/campaigns/{campaign}/close', [\App\Http\Controllers\Admin\CampaignController::class, 'close'])->name('admin.campaigns.close');
    Route::post('/debts/{debt}/payments', [\App\Http\Controllers\Admin\DebtController::class, 'pay'])->name('admin.debts.pay');
    Route::post('/debts/{debt}/adjust', [\App\Http\Controllers\Admin\DebtController::class, 'adjust'])->name('admin.debts.adjust');
    Route::get('/room-users', [\App\Http\Controllers\Admin\RoomUserController::class, 'index'])->name('admin.room-users.index');
    Route::patch('/room-users/{roomUser}/status', [\App\Http\Controllers\Admin\RoomUserController::class, 'status'])->name('admin.room-users.status');
    Route::get('/payment-accounts', [\App\Http\Controllers\Admin\PaymentAccountController::class, 'index'])->name('admin.payment-accounts.index');
    Route::post('/payment-accounts', [\App\Http\Controllers\Admin\PaymentAccountController::class, 'store'])->name('admin.payment-accounts.store');
    Route::patch('/payment-accounts/{account}', [\App\Http\Controllers\Admin\PaymentAccountController::class, 'update'])->name('admin.payment-accounts.update');
});

Route::middleware(['auth:admin', 'superadmin'])->prefix('superadmin')->group(function () {
    Route::get('/global-users', [\App\Http\Controllers\Superadmin\GlobalUserController::class, 'index'])->name('superadmin.global-users.index');
    Route::patch('/global-users/{globalUser}/status', [\App\Http\Controllers\Superadmin\GlobalUserController::class, 'status'])->name('superadmin.global-users.status');
});

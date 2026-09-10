<?php

use Illuminate\Support\Facades\Route;

Route::get('/auth/google', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'callback'])->name('auth.google.callback');
Route::get('/rooms/{room}', [\App\Http\Controllers\User\RoomController::class, 'show'])->name('user.rooms.show');
Route::get('/rooms/{room}/join', \App\Http\Controllers\User\JoinPageController::class)->name('user.rooms.join.show');
Route::post('/rooms/{room}/join', [\App\Http\Controllers\User\RoomController::class, 'join'])->name('user.rooms.join');

Route::middleware(['global.user', 'room.user'])->group(function () {
    Route::get('/rooms/{room}/dashboard', \App\Http\Controllers\User\DashboardController::class)->name('user.dashboard');
    Route::get('/rooms/{room}/orders', [\App\Http\Controllers\User\OrderController::class, 'index'])->name('user.orders.index');
    Route::get('/rooms/{room}/campaigns', [\App\Http\Controllers\User\CampaignController::class, 'index'])->name('user.campaigns.index');
    Route::get('/rooms/{room}/campaigns/{campaign}', [\App\Http\Controllers\User\CampaignController::class, 'show'])->name('user.campaigns.show');
    Route::get('/rooms/{room}/campaigns/{campaign}/order', \App\Http\Controllers\User\CampaignOrderPageController::class)->name('user.campaigns.order-page');
    Route::post('/rooms/{room}/campaigns/{campaign}/orders', [\App\Http\Controllers\User\OrderController::class, 'store'])->name('user.orders.store');
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

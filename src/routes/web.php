<?php

use Illuminate\Support\Facades\Route;

Route::get('/', \App\Http\Controllers\Public\LandingController::class)->name('landing');

Route::get('/terms', \App\Http\Controllers\Public\TermsController::class)->name('terms');

Route::get('/versions/{version?}', \App\Http\Controllers\Public\VersionController::class)->name('versions');

Route::get('/contact', [\App\Http\Controllers\Public\ContactController::class, 'index'])->name('contact');
Route::post('/contact', [\App\Http\Controllers\Public\ContactController::class, 'store'])
    ->middleware('throttle:contact-submission')
    ->name('contact.store');

Route::get('/lang/{locale}', function (string $locale) {
    if (\App\Constants\AppLocale::isValid($locale)) {
        session(['locale' => $locale]);
    }
    return redirect()->back();
})->name('locale.switch');

require __DIR__.'/user.php';
require __DIR__.'/admin.php';
require __DIR__.'/superadmin.php';

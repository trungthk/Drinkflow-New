<?php

use Illuminate\Support\Facades\Route;

Route::get('/', \App\Http\Controllers\Public\LandingController::class)->name('landing');

Route::get('/robots.txt', [\App\Http\Controllers\Public\SeoController::class, 'robots'])->name('seo.robots');
Route::get('/sitemap.xml', [\App\Http\Controllers\Public\SeoController::class, 'sitemap'])->name('seo.sitemap');

Route::get('/terms', \App\Http\Controllers\Public\TermsController::class)->name('terms');

Route::get('/versions/{version?}', \App\Http\Controllers\Public\VersionController::class)->name('versions');

Route::get('/contact', [\App\Http\Controllers\Public\ContactController::class, 'index'])->name('contact');
Route::post('/contact', [\App\Http\Controllers\Public\ContactController::class, 'store'])
    ->middleware('throttle:contact-submission')
    ->name('contact.store');

Route::get('/check-order/{campaign}/{hash}', [\App\Http\Controllers\Public\OrderCheckController::class, 'page'])
    ->middleware('signed')
    ->name('public.order-check');
Route::post('/check-order/{campaign}/{hash}', [\App\Http\Controllers\Public\OrderCheckController::class, 'lookup'])
    ->middleware(['signed', 'throttle:public-order-check'])
    ->name('public.order-check.lookup');

Route::get('/guides', [\App\Http\Controllers\Public\GuideController::class, 'index'])->name('public.guides');
Route::get('/guides/{slug}', [\App\Http\Controllers\Public\GuideController::class, 'show'])
    ->where('slug', '[a-z0-9\-]+')
    ->name('public.guides.show');

foreach (\App\Support\Helpers\LocaleUrl::prefixedLocales() as $localeCode) {
    Route::prefix($localeCode)->name($localeCode.'.')->group(function (): void {
        Route::get('/', \App\Http\Controllers\Public\LandingController::class)->name('landing');
        Route::get('/terms', \App\Http\Controllers\Public\TermsController::class)->name('terms');
        Route::get('/versions/{version?}', \App\Http\Controllers\Public\VersionController::class)->name('versions');
        Route::get('/contact', [\App\Http\Controllers\Public\ContactController::class, 'index'])->name('contact');
    });
}

Route::get('/lang/{locale}', function (string $locale) {
    if (\App\Constants\AppLocale::isValid($locale)) {
        session(['locale' => $locale]);
    }
    $to = request()->query('to');
    if (is_string($to) && str_starts_with($to, '/') && ! str_starts_with($to, '//') && ! str_contains($to, '\\')) {
        return redirect($to);
    }
    return redirect()->back();
})->name('locale.switch');

require __DIR__.'/user.php';
require __DIR__.'/admin.php';
require __DIR__.'/superadmin.php';

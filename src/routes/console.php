<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 1. Prune expired audit logs daily at 02:00
Schedule::command('drinkflow:prune-audit-logs')
    ->dailyAt('02:00')
    ->withoutOverlapping();

// 2. Prune read admin & user notifications daily at 02:30
Schedule::command('drinkflow:prune-notifications')
    ->dailyAt('02:30')
    ->withoutOverlapping();

// 3. Prune crawler previews and storage logs daily at 03:00
Schedule::command('drinkflow:prune-crawler-and-logs')
    ->dailyAt('03:00')
    ->withoutOverlapping();


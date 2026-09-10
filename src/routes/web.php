<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if ($user = auth('web')->user()) {
        /** @var \App\Models\GlobalUser $user */
        $room = $user->roomUsers()->where('status', 'active')->with('room')->first();
        if ($room?->room) return redirect()->route('user.dashboard', $room->room);
        // A newly authenticated account may not have joined a Room yet. Send
        // it to the authenticated profile/onboarding screen instead of
        // rendering the anonymous landing page again.
        return redirect()->route('user.profile.page');
    }
    return view('landing');
});

require __DIR__.'/user.php';
require __DIR__.'/admin.php';
require __DIR__.'/superadmin.php';

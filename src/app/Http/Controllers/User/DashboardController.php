<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    /**
     * Invoke the controller.
     * @param Request $request Parameter value.
     * @return View Result of the operation.
     */
    public function __invoke(Request $request): View
    {
        return view('user.dashboard', [
            'room' => $request->attributes->get('room'),
            'roomUser' => $request->attributes->get('room_user'),
        ]);
    }
}

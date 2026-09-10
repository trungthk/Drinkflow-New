<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ProfilePageController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('user.profile', ['user' => $request->attributes->get('global_user')]);
    }
}

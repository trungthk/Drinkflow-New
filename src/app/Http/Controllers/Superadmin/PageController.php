<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function rooms(): View { return view('superadmin.rooms'); }
    public function room(): View { return view('superadmin.room-detail'); }
    public function admins(): View { return view('superadmin.admins'); }
    public function admin(): View { return view('superadmin.admin-detail'); }
    public function users(): View { return view('superadmin.users'); }
    public function user(): View { return view('superadmin.user-detail'); }
    public function campaigns(): View { return view('superadmin.campaigns'); }
    public function debts(): View { return view('superadmin.debts'); }
    public function notifications(): View { return view('superadmin.notifications'); }
    public function system(): View { return view('superadmin.system'); }
    public function audit(): View { return view('superadmin.audit'); }
    public function security(): View { return view('superadmin.security'); }
    public function socket(): View { return view('superadmin.socket'); }
    public function queue(): View { return view('superadmin.queue'); }
    public function versions(): View { return view('superadmin.versions'); }
}

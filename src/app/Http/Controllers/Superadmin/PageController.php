<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    /**
     * Handle the rooms operation.
     * @return View Result of the operation.
     */
    public function rooms(): View { return view('superadmin.rooms'); }
    /**
     * Handle the room operation.
     * @return View Result of the operation.
     */
    public function room(): View { return view('superadmin.room-detail'); }
    /**
     * Handle the admins operation.
     * @return View Result of the operation.
     */
    public function admins(): View { return view('superadmin.admins'); }
    /**
     * Handle the admin operation.
     * @return View Result of the operation.
     */
    public function admin(): View { return view('superadmin.admin-detail'); }
    /**
     * Handle the users operation.
     * @return View Result of the operation.
     */
    public function users(): View { return view('superadmin.users'); }
    /**
     * Handle the user operation.
     * @return View Result of the operation.
     */
    public function user(): View { return view('superadmin.user-detail'); }
    /**
     * Handle the campaigns operation.
     * @return View Result of the operation.
     */
    public function campaigns(): View { return view('superadmin.campaigns'); }
    /**
     * Handle the debts operation.
     * @return View Result of the operation.
     */
    public function debts(): View { return view('superadmin.debts'); }
    /**
     * Handle the notifications operation.
     * @return View Result of the operation.
     */
    public function notifications(): View { return view('superadmin.notifications'); }
    /**
     * Handle the system operation.
     * @return View Result of the operation.
     */
    public function system(): View { return view('superadmin.system'); }
    /**
     * Handle the audit operation.
     * @return View Result of the operation.
     */
    public function audit(): View { return view('superadmin.audit'); }
    /**
     * Handle the security operation.
     * @return View Result of the operation.
     */
    public function security(): View { return view('superadmin.security'); }
    /**
     * Handle the socket operation.
     * @return View Result of the operation.
     */
    public function socket(): View { return view('superadmin.socket'); }
    /**
     * Handle the queue operation.
     * @return View Result of the operation.
     */
    public function queue(): View { return view('superadmin.queue'); }
    /**
     * Handle the versions operation.
     * @return View Result of the operation.
     */
    public function versions(): View { return view('superadmin.versions'); }
}

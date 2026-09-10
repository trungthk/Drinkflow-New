<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DebtController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $roomUser = $request->attributes->get('room_user');

        return response()->json(['data' => $roomUser->debts()->with('campaign')->latest()->paginate(20)]);
    }
}

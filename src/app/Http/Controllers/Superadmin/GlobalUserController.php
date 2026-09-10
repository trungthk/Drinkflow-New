<?php
namespace App\Http\Controllers\Superadmin;
use App\Actions\User\SetGlobalUserStatusAction; use App\Http\Controllers\Controller; use App\Http\Requests\SetStatusRequest; use App\Models\GlobalUser; use Illuminate\Http\JsonResponse;
class GlobalUserController extends Controller { public function index(): JsonResponse { return response()->json(['data'=>GlobalUser::withCount('roomUsers')->latest()->paginate(50)]); } public function status(SetStatusRequest $request,GlobalUser $globalUser,SetGlobalUserStatusAction $action): JsonResponse { return response()->json(['data'=>$action->execute($globalUser,$request->validated('status'))]); } }

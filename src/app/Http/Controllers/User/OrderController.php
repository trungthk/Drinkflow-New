<?php
namespace App\Http\Controllers\User;
use App\Actions\Order\CreateOrderAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Campaign;
use Illuminate\Http\JsonResponse;
class OrderController extends Controller {
    public function index(\Illuminate\Http\Request $request): JsonResponse { $roomUser=$request->attributes->get('room_user'); $query=$roomUser->orders()->with(['items.toppings','campaign'])->latest(); if($request->filled('status')) $query->where('status',$request->string('status')); if($request->filled('from')) $query->whereDate('created_at','>=',$request->date('from')); if($request->filled('to')) $query->whereDate('created_at','<=',$request->date('to')); return response()->json(['data'=>$query->paginate(20)]); }
    public function store(StoreOrderRequest $request, Campaign $campaign, CreateOrderAction $action): JsonResponse {
        $order = $action->execute($campaign, $request->attributes->get('room_user'), $request->validated());
        return response()->json(['data' => $order], 201);
    }
}

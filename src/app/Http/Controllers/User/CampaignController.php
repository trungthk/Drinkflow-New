<?php
namespace App\Http\Controllers\User;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\JsonResponse;
class CampaignController extends Controller { public function index(): JsonResponse { $room=request()->attributes->get('room'); $campaigns=Campaign::query()->where('room_id',$room->id)->whereIn('status',['active','scheduled'])->with(['items'=>fn($q)=>$q->where('status','active')->with(['sizes','toppings'])])->orderBy('deadline')->paginate(20); return response()->json(['data'=>$campaigns]); } public function show(Campaign $campaign): JsonResponse { abort_unless($campaign->room_id===request()->attributes->get('room')->id && in_array($campaign->status?->value,['active','scheduled'],true),404); return response()->json(['data'=>$campaign->load(['items'=>fn($q)=>$q->where('status','active')->with(['sizes','toppings'])])]); } }

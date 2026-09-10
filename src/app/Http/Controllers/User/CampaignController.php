<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CampaignController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $room = $request->attributes->get('room');
        $query = Campaign::query()->where('room_id', $room->id)->whereIn('status', ['active', 'scheduled']);
        $campaigns = $query->with(['items' => function ($q) use ($request) {
            $q->where('status', 'active')->when($request->filled('q'), fn ($items) => $items->where(function ($search) use ($request) {
                $term = trim($request->string('q')->toString());
                $search->where('name', 'like', "%{$term}%")->orWhere('normalized_name', 'like', '%'.strtoupper(Str::ascii($term)).'%');
            }))->when($request->filled('category'), fn ($items) => $items->where('category', $request->string('category')))->with(['sizes', 'toppings']);
        }])->when($request->filled('q'), fn ($campaigns) => $campaigns->whereHas('items', fn ($items) => $items->where('status', 'active')->where(function ($search) use ($request) {
            $term = trim($request->string('q')->toString());
            $search->where('name', 'like', "%{$term}%")->orWhere('normalized_name', 'like', '%'.strtoupper(Str::ascii($term)).'%');
        })))->orderBy('deadline')->paginate(20);

        return response()->json(['data' => $campaigns]);
    }

    public function show(Campaign $campaign, Request $request): JsonResponse
    {
        abort_unless($campaign->room_id === $request->attributes->get('room')->id && in_array($campaign->status?->value, ['active', 'scheduled'], true), 404);

        return response()->json(['data' => $campaign->load(['items' => fn ($q) => $q->where('status', 'active')->with(['sizes', 'toppings']), 'room'])]);
    }
}

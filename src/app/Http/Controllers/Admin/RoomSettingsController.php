<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Room\UpdateRoomSettingsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateRoomSettingsRequest;
use App\Models\Room;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomSettingsController extends Controller
{
    /**
     * Display current room settings.
     */
    public function show(Room $room, UpdateRoomSettingsAction $action): JsonResponse
    {
        return response()->json(['data' => $action->payload($room)]);
    }

    /**
     * Display the standalone Room Settings configuration page.
     *
     * @param Request $request Incoming request.
     * @param Room $room Room entity.
     * @param UpdateRoomSettingsAction $action Settings action.
     * @param \App\Services\Common\BankService $bankService Bank catalogue service.
     * @return View Blade view.
     */
    public function page(Request $request, Room $room, UpdateRoomSettingsAction $action, \App\Services\Common\BankService $bankService): View
    {
        $settings = $action->payload($room);
        $accounts = $room->paymentAccounts()->latest()->get();
        $banks = $bankService->getAllBanks();

        return view('admin.settings', [
            'room' => $room,
            'settings' => $settings,
            'accounts' => $accounts,
            'banks' => $banks,
        ]);
    }

    /**
     * Update room settings.
     */
    public function update(UpdateRoomSettingsRequest $request, Room $room, UpdateRoomSettingsAction $action): JsonResponse
    {
        $before = $action->payload($room);
        $after = $action->execute($room, $request->validated(), $before);

        return response()->json(['data' => $after]);
    }
}

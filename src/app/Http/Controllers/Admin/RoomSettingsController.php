<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateRoomSettingsRequest;
use App\Models\PaymentAccount;
use App\Models\Room;
use App\Models\RoomSetting;
use App\Services\Audit\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class RoomSettingsController extends Controller
{
    public function show(): JsonResponse
    {
        $room = request()->attributes->get('room');
        return response()->json(['data' => $this->payload($room)]);
    }

    public function update(UpdateRoomSettingsRequest $request, AuditService $audit): JsonResponse
    {
        $room = request()->attributes->get('room');
        $data = $request->validated();
        if (array_key_exists('default_payment_account_id', $data) && $data['default_payment_account_id'] !== null) {
            abort_unless(PaymentAccount::query()->whereKey($data['default_payment_account_id'])->where('room_id', $room->id)->where('status', 'active')->exists(), 422, 'Tài khoản thanh toán không thuộc Room hoặc đã disabled.');
        }
        $before = $this->payload($room);
        $updated = DB::transaction(function () use ($room, $data): Room {
            $room->update(collect($data)->only(['name', 'description', 'avatar_url', 'timezone', 'language'])->all());
            if (array_key_exists('default_sponsor', $data)) RoomSetting::updateOrCreate(['room_id' => $room->id, 'key' => 'default_sponsor'], ['value' => $data['default_sponsor'], 'type' => 'string', 'is_secret' => false]);
            if (array_key_exists('default_payment_account_id', $data)) RoomSetting::updateOrCreate(['room_id' => $room->id, 'key' => 'default_payment_account_id'], ['value' => $data['default_payment_account_id'] === null ? null : (string) $data['default_payment_account_id'], 'type' => 'integer', 'is_secret' => false]);
            return $room->fresh();
        });
        $after = $this->payload($updated);
        $audit->record('room.settings_updated', 'room', $room->id, $room->id, $before, $after);
        return response()->json(['data' => $after]);
    }

    private function payload(Room $room): array
    {
        $settings = $room->roomSettings()->get()->keyBy('key');
        return array_merge($room->only(['id', 'name', 'slug', 'description', 'avatar_url', 'timezone', 'language', 'status']), [
            'default_sponsor' => $settings->get('default_sponsor')?->value,
            'default_payment_account_id' => ($settings->get('default_payment_account_id')?->value !== null ? (int) $settings->get('default_payment_account_id')->value : null),
        ]);
    }
}

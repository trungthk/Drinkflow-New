<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Enums\GlobalUserStatus;
use App\Enums\RoomUserStatus;
use App\Models\Concerns\HasNormalizedName;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Models\RoomUser;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminAddRoomUserAction
{
    /**
     * Khởi tạo Action với AuditService.
     *
     * @param AuditService $auditService Dịch vụ ghi nhật ký kiểm toán.
     */
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    /**
     * Thêm hoặc tạo mới thành viên vào phòng.
     *
     * @param Room $room Phòng mục tiêu.
     * @param array<string, mixed> $data Dữ liệu thành viên gửi lên.
     * @return RoomUser Bản ghi thành viên phòng sau khi tạo/kích hoạt.
     * @throws ValidationException Nếu dữ liệu không hợp lệ hoặc người dùng đã có trong phòng.
     */
    public function execute(Room $room, array $data): RoomUser
    {
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $name = trim((string) ($data['name'] ?? ''));
        $phone = isset($data['phone']) ? trim((string) $data['phone']) : null;
        $deskLocation = isset($data['desk_location']) ? trim((string) $data['desk_location']) : null;

        if ($email === '' || $name === '') {
            throw ValidationException::withMessages([
                'email' => __('validation.required', ['attribute' => 'email']),
            ]);
        }

        return DB::transaction(function () use ($room, $email, $name, $phone, $deskLocation): RoomUser {
            $isNewGlobalUser = false;
            $globalUser = GlobalUser::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();

            if (!$globalUser) {
                $isNewGlobalUser = true;
                $globalUser = GlobalUser::create([
                    'name' => $name,
                    'normalized_name' => HasNormalizedName::normalizeString($name),
                    'email' => $email,
                    'phone' => $phone ?: null,
                    'desk_location' => $deskLocation ?: null,
                    'status' => GlobalUserStatus::Active,
                ]);
            } else {
                $updateData = [];
                if ($phone && empty($globalUser->phone)) {
                    $updateData['phone'] = $phone;
                }
                if ($deskLocation && empty($globalUser->desk_location)) {
                    $updateData['desk_location'] = $deskLocation;
                }
                if (!empty($updateData)) {
                    $globalUser->update($updateData);
                }
            }

            $existingRoomUser = RoomUser::query()
                ->where('room_id', $room->id)
                ->where('global_user_id', $globalUser->id)
                ->first();

            if ($existingRoomUser) {
                if ($existingRoomUser->status === RoomUserStatus::Active) {
                    throw ValidationException::withMessages([
                        'email' => __('admin.user_already_in_room'),
                    ]);
                }

                $existingRoomUser->update([
                    'status' => RoomUserStatus::Active,
                    'display_name' => $name,
                    'normalized_name' => HasNormalizedName::normalizeString($name),
                    'last_active_at' => now(),
                ]);

                $roomUser = $existingRoomUser->fresh();
            } else {
                $finalUserCode = $this->generateUniqueCode($room, $name);

                $roomUser = RoomUser::create([
                    'room_id' => $room->id,
                    'global_user_id' => $globalUser->id,
                    'user_code' => $finalUserCode,
                    'display_name' => $name,
                    'normalized_name' => HasNormalizedName::normalizeString($name),
                    'status' => RoomUserStatus::Active,
                    'joined_at' => now(),
                    'last_active_at' => now(),
                ]);
            }

            $this->auditService->record(
                'room_user.created',
                'room_user',
                $roomUser->id,
                $room->id,
                [],
                [
                    'global_user_id' => $globalUser->id,
                    'email' => $globalUser->email,
                    'display_name' => $roomUser->display_name,
                    'user_code' => $roomUser->user_code,
                    'is_new_global_user' => $isNewGlobalUser,
                ]
            );

            return $roomUser->load('globalUser');
        });
    }

    /**
     * Tự động sinh mã người dùng duy nhất trong phòng.
     *
     * @param Room $room Phòng mục tiêu.
     * @param string $name Tên người dùng.
     * @return string Mã người dùng duy nhất.
     */
    private function generateUniqueCode(Room $room, string $name): string
    {
        $normalized = HasNormalizedName::normalizeString($name);
        $base = preg_replace('/[^A-Z0-9]/', '', $normalized) ?: 'USER';
        $code = $base;
        $suffix = 1;

        while ($room->roomUsers()->where('user_code', $code)->exists()) {
            $code = $base . (++$suffix);
        }

        return $code;
    }
}

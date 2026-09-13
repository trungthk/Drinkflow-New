<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use App\Enums\GlobalUserStatus;
use App\Enums\RoomStatus;
use App\Enums\RoomUserStatus;
use App\Models\AdminAccount;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Models\RoomUser;

/**
 * Trait cung cấp các phương thức xác thực phân quyền cơ bản (Authorization)
 * cho các đối tượng Form Request trong toàn bộ hệ thống DrinkFlow.
 *
 * @mixin \Illuminate\Foundation\Http\FormRequest
 */
trait AuthorizesUserAndAdmin
{
    /**
     * Xác thực người dùng Quản trị viên (Admin) đang đăng nhập và có trạng thái kích hoạt (Active).
     *
     * @return bool True nếu là Admin đang active, ngược lại false.
     */
    protected function authorizeActiveAdmin(): bool
    {
        /** @var AdminAccount|null $admin */
        $admin = $this->user('admin');

        return $admin !== null && $admin->isActive();
    }

    /**
     * Xác thực người dùng Quản trị viên cấp cao (Superadmin) đang đăng nhập và có trạng thái kích hoạt.
     *
     * @return bool True nếu là Superadmin đang active, ngược lại false.
     */
    protected function authorizeActiveSuperadmin(): bool
    {
        /** @var AdminAccount|null $admin */
        $admin = $this->user('admin');

        return $admin !== null && $admin->isActive() && $admin->isSuperadmin();
    }

    /**
     * Xác thực Admin đang active có quyền quản trị đối với Room cụ thể.
     * Cho phép truy cập nếu là Superadmin, hoặc là Room Admin được phân công (assign) vào phòng đang active.
     *
     * @param  \App\Models\Room|string|int|null  $room  Room instance, slug hoặc id.
     * @return bool True nếu có quyền quản trị phòng, ngược lại false.
     */
    protected function authorizeActiveRoomAdmin(Room|string|int|null $room = null): bool
    {
        /** @var AdminAccount|null $admin */
        $admin = $this->user('admin');

        if ($admin === null || !$admin->isActive()) {
            return false;
        }

        if ($admin->isSuperadmin()) {
            return true;
        }

        $resolvedRoom = $this->resolveRoomModel($room);
        if ($resolvedRoom === null) {
            return true;
        }

        $roomStatus = $resolvedRoom->status instanceof \BackedEnum ? $resolvedRoom->status->value : (string) $resolvedRoom->status;
        if ($roomStatus !== RoomStatus::Active->value && $roomStatus !== 'active') {
            return false;
        }

        return $admin->rooms()->whereKey($resolvedRoom->id)->exists();
    }

    /**
     * Xác thực người dùng toàn hệ thống (Global User) đã đăng nhập và đang ở trạng thái kích hoạt (Active).
     *
     * @return bool True nếu Global User đang active, ngược lại false.
     */
    protected function authorizeActiveGlobalUser(): bool
    {
        /** @var GlobalUser|null $user */
        $user = $this->user('web') ?? $this->attributes->get('global_user');

        if ($user === null) {
            return false;
        }

        $userStatus = $user->status instanceof \BackedEnum ? $user->status->value : (string) ($user->status ?? '');

        return $userStatus === GlobalUserStatus::Active->value || $userStatus === 'active';
    }

    /**
     * Xác thực người dùng trong phòng (User Room) đang active, phòng đang active và đã được gán (assigned/joined) cho user.
     *
     * @param  \App\Models\Room|string|int|null  $room  Room instance, slug hoặc id.
     * @return bool True nếu người dùng hợp lệ trong phòng, ngược lại false.
     */
    protected function authorizeActiveRoomUser(Room|string|int|null $room = null): bool
    {
        if (!$this->authorizeActiveGlobalUser()) {
            return false;
        }

        /** @var GlobalUser $user */
        $user = $this->user('web') ?? $this->attributes->get('global_user');

        $resolvedRoom = $this->resolveRoomModel($room);
        if ($resolvedRoom === null) {
            return false;
        }

        $roomStatus = $resolvedRoom->status instanceof \BackedEnum ? $resolvedRoom->status->value : (string) $resolvedRoom->status;
        if ($roomStatus !== RoomStatus::Active->value && $roomStatus !== 'active') {
            return false;
        }

        /** @var RoomUser|null $roomUser */
        $roomUser = $this->attributes->get('room_user') ?? $user->roomUsers()->where('room_id', $resolvedRoom->id)->first();
        if ($roomUser === null) {
            return false;
        }

        $roomUserStatus = $roomUser->status instanceof \BackedEnum ? $roomUser->status->value : (string) ($roomUser->status ?? '');

        return $roomUserStatus === RoomUserStatus::Active->value || $roomUserStatus === 'active';
    }

    /**
     * Tìm và giải quyết đối tượng Room từ tham số truyền vào hoặc ngữ cảnh Route / Attributes.
     *
     * @param  \App\Models\Room|string|int|null  $room  Room instance, slug hoặc id.
     * @return \App\Models\Room|null Đối tượng Room nếu tìm thấy, ngược lại null.
     */
    protected function resolveRoomModel(Room|string|int|null $room = null): ?Room
    {
        $target = $room ?? $this->route('room') ?? $this->attributes->get('room');

        if ($target instanceof Room) {
            return $target;
        }

        if (is_numeric($target)) {
            return Room::query()->where('id', (int) $target)->first();
        }

        if (is_string($target) && $target !== '') {
            return Room::query()->where('slug', $target)->orWhere('id', is_numeric($target) ? (int) $target : 0)->first();
        }

        return null;
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\AdminNotification;
use App\Models\Room;
use Illuminate\Http\Request;

class AuditService
{
    /**
     * Handle the record operation.
     * @param string $event Parameter value.
     * @param string $targetType Parameter value.
     * @param int $targetId Parameter value.
     * @param ?int $roomId Parameter value.
     * @param array $before Parameter value.
     * @param array $after Parameter value.
     * @param array $metadata Parameter value.
     * @return AuditLog Result of the operation.
     */
    public function record(string $event, string $targetType, int $targetId, ?int $roomId = null, array $before = [], array $after = [], array $metadata = []): AuditLog
    {
        $request = app(Request::class);
        $admin = $request->user('admin');
        $actor = $admin ?? $request->user('web');
        $actorType = $admin ? ($admin->isSuperadmin() ? 'superadmin' : 'admin') : ($actor ? 'user' : 'system');
        $auditLog = AuditLog::create(['actor_type' => $actorType, 'actor_id' => $actor?->id, 'event' => $event, 'target_type' => $targetType, 'target_id' => $targetId, 'room_id' => $roomId, 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'before_data' => $before, 'after_data' => $after, 'metadata' => $metadata, 'created_at' => now()]);

        if ($admin) {
            $auditLog->admins()->syncWithoutDetaching([$admin->id]);
        }

        if ($roomId && \Illuminate\Support\Facades\Schema::hasTable('admin_notifications')) {
            $room = Room::find($roomId);
            $room?->admins()->each(function ($recipient) use ($auditLog, $roomId, $event, $targetType, $before, $after, $metadata): void {
                AdminNotification::create([
                    'admin_id' => $recipient->id,
                    'room_id' => $roomId,
                    'audit_log_id' => $auditLog->id,
                    'type' => $event,
                    'title' => $event,
                    'body' => null,
                    'data' => [
                        'audit_log_id' => $auditLog->id,
                        'target_type' => $targetType,
                        'before' => $before,
                        'after' => $after,
                        'metadata' => $metadata,
                    ],
                ]);
            });
        }

        return $auditLog;
    }
}

<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Admin;
use App\Models\Room;
use App\Services\Room\RoomOwnershipService;
use Illuminate\Console\Command;

class RoomOwnership extends Command
{
    /** @var string */
    protected $signature = 'rooms:ownership
        {--room= : Room slug or ID to assign}
        {--admin= : Admin ID or email that becomes the owner}';

    /** @var string */
    protected $description = 'List rooms without an owning Agent, or assign an owner (--room and --admin).';

    /**
     * Report unresolved room ownership, or assign one owner explicitly.
     *
     * The ownership migration only maps rooms that have exactly one Admin; this command is how the
     * remaining rooms are decided by a person instead of guessed.
     *
     * @param RoomOwnershipService $ownership Ownership service.
     * @return int Process exit code.
     */
    public function handle(RoomOwnershipService $ownership): int
    {
        $roomKey = (string) $this->option('room');
        $adminKey = (string) $this->option('admin');
        if ($roomKey !== '' || $adminKey !== '') {
            return $this->assign($ownership, $roomKey, $adminKey);
        }

        $rooms = $ownership->unresolved();
        if ($rooms->isEmpty()) {
            $this->info('Every room has an owner.');

            return self::SUCCESS;
        }

        $this->warn($rooms->count().' room(s) without owner:');
        $this->table(['ID', 'Slug', 'Name', 'Status', 'Admins (candidates)'], $rooms->map(static fn (Room $room): array => [
            $room->id,
            $room->slug,
            $room->name,
            $room->status->value,
            $room->admins->map(static fn (Admin $admin): string => "#{$admin->id} {$admin->email}")->implode(', ') ?: '—',
        ])->all());
        $this->line('Assign with: php artisan rooms:ownership --room=<slug|id> --admin=<id|email>');

        return self::SUCCESS;
    }

    /**
     * Assign the owner given on the command line.
     *
     * @param RoomOwnershipService $ownership Ownership service.
     * @param string $roomKey Room slug or ID.
     * @param string $adminKey Admin ID or email.
     * @return int Process exit code.
     */
    private function assign(RoomOwnershipService $ownership, string $roomKey, string $adminKey): int
    {
        $room = (new Room())->resolveRouteBinding($roomKey);
        $admin = ctype_digit($adminKey)
            ? Admin::query()->find((int) $adminKey)
            : Admin::query()->where('email', mb_strtolower($adminKey))->first();
        if (! $room instanceof Room || $admin === null) {
            $this->error('Both --room and --admin must match an existing room and admin.');

            return self::FAILURE;
        }

        $ownership->assign($room, $admin);
        $this->info("Room {$room->slug} is now owned by {$admin->email}.");

        return self::SUCCESS;
    }
}

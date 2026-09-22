<?php

declare(strict_types=1);

namespace App\Services\System;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Actively verifies the default filesystem disk is writable by round-tripping a tiny probe file.
 */
class StorageHealthService
{
    private const PROBE_DIRECTORY = 'health-check';

    /**
     * Write, read back and delete a small probe file on the default disk.
     *
     * @return array{disk: string, status: 'ok'|'error', free_bytes: int|null}
     */
    public function check(): array
    {
        $disk = (string) config('filesystems.default', 'local');
        $path = self::PROBE_DIRECTORY.'/'.Str::uuid()->toString().'.txt';
        $token = Str::random(24);

        try {
            $storage = Storage::disk($disk);
            $storage->put($path, $token);
            $roundTripped = $storage->get($path) === $token;
            $storage->delete($path);

            return [
                'disk' => $disk,
                'status' => $roundTripped ? 'ok' : 'error',
                'free_bytes' => $this->freeBytes($disk),
            ];
        } catch (Throwable) {
            return ['disk' => $disk, 'status' => 'error', 'free_bytes' => null];
        }
    }

    /**
     * Best-effort free-space reading; only meaningful for a local filesystem disk.
     *
     * @param string $disk Disk name from filesystems.php.
     * @return int|null Free bytes, or null when the disk is not a local path (e.g. S3) or unreadable.
     */
    private function freeBytes(string $disk): ?int
    {
        $root = config("filesystems.disks.{$disk}.root");
        if (! is_string($root) || $root === '' || ! is_dir($root)) {
            return null;
        }

        $free = @disk_free_space($root);

        return $free === false ? null : (int) $free;
    }
}

<?php

declare(strict_types=1);

namespace App\Services\System;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Actively verifies the default filesystem disk is writable by round-tripping a tiny probe file,
 * and reports its driver plus the capacity of the volume it lives on.
 */
class StorageHealthService
{
    private const PROBE_DIRECTORY = 'health-check';

    /**
     * Write, read back and delete a small probe file on the default disk.
     *
     * @return array{disk: string, driver: string, status: 'ok'|'error', free_bytes: int|null, used_bytes: int|null, total_bytes: int|null}
     *  Byte counts are null when the disk is not a local path (e.g. S3) or cannot be read.
     */
    public function check(): array
    {
        $disk = (string) config('filesystems.default', 'local');
        $driver = (string) config("filesystems.disks.{$disk}.driver", 'unknown');
        $path = self::PROBE_DIRECTORY.'/'.Str::uuid()->toString().'.txt';
        $token = Str::random(24);

        try {
            $storage = Storage::disk($disk);
            $storage->put($path, $token);
            $roundTripped = $storage->get($path) === $token;
            $storage->delete($path);

            return [
                'disk' => $disk,
                'driver' => $driver,
                'status' => $roundTripped ? 'ok' : 'error',
                ...$this->capacity($disk),
            ];
        } catch (Throwable) {
            return ['disk' => $disk, 'driver' => $driver, 'status' => 'error', 'free_bytes' => null, 'used_bytes' => null, 'total_bytes' => null];
        }
    }

    /**
     * Best-effort capacity reading of the volume holding the disk root; only meaningful for a local disk.
     *
     * @param string $disk Disk name from filesystems.php.
     * @return array{free_bytes: int|null, used_bytes: int|null, total_bytes: int|null} Nulls when the disk
     *  is not a local path (e.g. S3) or the volume cannot be read.
     */
    private function capacity(string $disk): array
    {
        $unknown = ['free_bytes' => null, 'used_bytes' => null, 'total_bytes' => null];
        $root = config("filesystems.disks.{$disk}.root");
        if (! is_string($root) || $root === '' || ! is_dir($root)) {
            return $unknown;
        }

        $free = @disk_free_space($root);
        $total = @disk_total_space($root);
        if ($free === false || $total === false) {
            return $unknown;
        }

        return [
            'free_bytes' => (int) $free,
            'used_bytes' => max(0, (int) $total - (int) $free),
            'total_bytes' => (int) $total,
        ];
    }
}

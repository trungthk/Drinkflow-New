<?php

declare(strict_types=1);

namespace App\Services\System;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Actively verifies the default filesystem disk is writable by round-tripping a tiny probe file,
 * and reports its driver plus how much of its capacity is used.
 *
 * Capacity is the storage quota (filesystems.quota_mb, set in .env or System settings) when one
 * is configured, otherwise the size of the volume the disk lives on.
 */
class StorageHealthService
{
    private const PROBE_DIRECTORY = 'health-check';

    public const LIMIT_QUOTA = 'quota';
    public const LIMIT_VOLUME = 'volume';

    /** Seconds the measured disk usage is cached, since walking the directory tree is not free. */
    private const USAGE_CACHE_SECONDS = 300;

    /**
     * Write, read back and delete a small probe file on the default disk.
     *
     * @return array{disk: string, driver: string, status: 'ok'|'error', free_bytes: int|null, used_bytes: int|null, total_bytes: int|null, limit: 'quota'|'volume'|null}
     *  Byte counts (and limit) are null when the disk is not a local path or cannot be read.
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
            return ['disk' => $disk, 'driver' => $driver, 'status' => 'error', 'free_bytes' => null, 'used_bytes' => null, 'total_bytes' => null, 'limit' => null];
        }
    }

    /**
     * Best-effort capacity reading for a local disk: usage against the quota when one is set,
     * otherwise usage of the whole volume holding the disk root.
     *
     * @param string $disk Disk name from filesystems.php.
     * @return array{free_bytes: int|null, used_bytes: int|null, total_bytes: int|null, limit: 'quota'|'volume'|null}
     *  Nulls when the disk is not a local path (e.g. S3) or cannot be read.
     */
    private function capacity(string $disk): array
    {
        $unknown = ['free_bytes' => null, 'used_bytes' => null, 'total_bytes' => null, 'limit' => null];
        $root = config("filesystems.disks.{$disk}.root");
        if (! is_string($root) || $root === '' || ! is_dir($root)) {
            return $unknown;
        }

        $quotaMb = (int) config('filesystems.quota_mb');
        if ($quotaMb > 0) {
            $total = $quotaMb * 1024 * 1024;
            $used = $this->directorySize($root);

            return [
                'free_bytes' => max(0, $total - $used),
                'used_bytes' => $used,
                'total_bytes' => $total,
                'limit' => self::LIMIT_QUOTA,
            ];
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
            'limit' => self::LIMIT_VOLUME,
        ];
    }

    /**
     * Total size of the files under a directory (symlinks are not followed), cached briefly.
     *
     * @param string $root Absolute directory path.
     * @return int Size in bytes.
     */
    private function directorySize(string $root): int
    {
        return (int) Cache::remember('storage-health.usage.'.md5($root), self::USAGE_CACHE_SECONDS, static function () use ($root): int {
            $size = 0;
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($files as $file) {
                if ($file->isFile() && ! $file->isLink()) {
                    $size += $file->getSize();
                }
            }

            return $size;
        });
    }
}

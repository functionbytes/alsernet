<?php

namespace Modules\Media\Services;

use Illuminate\Support\Facades\Cache;

class MediaLockService
{
    private const LOCK_TTL_SECONDS = 900; // 15 min

    public function acquire(int $fileId, int $userId): bool
    {
        $key = $this->key($fileId);
        $current = Cache::get($key);

        if ($current === null || $current === $userId) {
            Cache::put($key, $userId, self::LOCK_TTL_SECONDS);

            return true;
        }

        return false;
    }

    public function release(int $fileId, int $userId): bool
    {
        $key = $this->key($fileId);

        if (Cache::get($key) === $userId) {
            Cache::forget($key);

            return true;
        }

        return false;
    }

    public function holder(int $fileId): ?int
    {
        return Cache::get($this->key($fileId));
    }

    public function isLocked(int $fileId): bool
    {
        return $this->holder($fileId) !== null;
    }

    private function key(int $fileId): string
    {
        return "media:lock:file:{$fileId}";
    }
}

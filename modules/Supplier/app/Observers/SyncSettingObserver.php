<?php

namespace Modules\Supplier\Observers;

use Illuminate\Support\Facades\Cache;
use Modules\Supplier\Models\Sync\SyncSetting;

/**
 * Invalidate the cached settings map whenever any SyncSetting row changes.
 *
 * The settings map is read in SupplierSyncController::index() with a 30-minute
 * Cache::remember; this observer makes sure that direct writes (tinker,
 * background jobs, alternate controllers) also flush the cache so the UI
 * never displays stale values.
 */
class SyncSettingObserver
{
    private const CACHE_KEY = 'supplier:sync:settings';

    public function saved(SyncSetting $setting): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function deleted(SyncSetting $setting): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}

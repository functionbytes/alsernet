<?php

namespace Modules\Core\Providers;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

/**
 * Register query-level caching macros on the Query Builder.
 *
 * Usage:
 *   DB::table('users')->where('active', true)->cache(300)->get();
 *   User::where('role', 'admin')->cache(600)->first();
 */
class QueryCacheMacroServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Builder::macro('cache', function (int $seconds, ?string $key = null) {
            /** @var Builder $this */
            $cacheKey = $key ?? $this->getCacheKey();

            return Cache::remember($cacheKey, $seconds, function () {
                return $this->get();
            });
        });

        Builder::macro('cacheValue', function (int $seconds, ?string $key = null) {
            /** @var Builder $this */
            $cacheKey = $key ?? $this->getCacheKey().':value';

            return Cache::remember($cacheKey, $seconds, function () {
                return $this->value($this->columns[0] ?? '*');
            });
        });

        Builder::macro('getCacheKey', function (): string {
            /** @var Builder $this */
            return 'query_cache:'.md5(serialize($this->toSql()).serialize($this->getBindings()));
        });
    }
}

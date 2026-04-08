<?php

namespace Modules\Seo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Seo404Log extends Model
{
    protected $table = 'seo_404_logs';

    protected $fillable = [
        'path',
        'referer',
        'user_agent',
        'ip',
        'hit_count',
        'first_seen_at',
        'last_seen_at',
        'has_redirect',
    ];

    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'has_redirect' => 'boolean',
            'hit_count' => 'integer',
        ];
    }

    /**
     * Record a 404 hit for a path (upsert pattern).
     */
    public static function recordHit(string $path, ?string $referer = null, ?string $ip = null, ?string $userAgent = null): void
    {
        static::upsert(
            [[
                'path' => mb_substr($path, 0, 500),
                'referer' => $referer ? mb_substr($referer, 0, 1000) : null,
                'user_agent' => $userAgent ? mb_substr($userAgent, 0, 500) : null,
                'ip' => $ip,
                'hit_count' => 1,
                'first_seen_at' => now(),
                'last_seen_at' => now(),
                'has_redirect' => false,
            ]],
            ['path'],
            ['hit_count' => DB::raw('hit_count + 1'), 'last_seen_at' => now()]
        );
    }

    /**
     * Scope to filter logs without a redirect.
     */
    public function scopeWithoutRedirect($query): mixed
    {
        return $query->where('has_redirect', false);
    }

    /**
     * Scope to order by hit count descending.
     */
    public function scopeOrderByHits($query): mixed
    {
        return $query->orderBy('hit_count', 'desc');
    }
}

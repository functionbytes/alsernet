<?php

namespace Modules\Seo\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SeoPagespeedSnapshot extends Model
{
    protected $table = 'seo_pagespeed_snapshots';

    /**
     * @var array<string>
     */
    protected $fillable = [
        'url', 'url_path', 'strategy',
        'performance', 'accessibility', 'best_practices', 'seo',
        'lcp_ms', 'inp_ms', 'cls', 'fcp_ms', 'ttfb_ms',
        'captured_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'performance' => 'integer',
        'accessibility' => 'integer',
        'best_practices' => 'integer',
        'seo' => 'integer',
        'lcp_ms' => 'decimal:2',
        'inp_ms' => 'decimal:2',
        'cls' => 'decimal:3',
        'fcp_ms' => 'decimal:2',
        'ttfb_ms' => 'decimal:2',
        'captured_at' => 'datetime',
    ];

    public function scopeForPath(Builder $query, string $path): Builder
    {
        return $query->where('url_path', $path);
    }

    public function scopeForStrategy(Builder $query, string $strategy): Builder
    {
        return $query->where('strategy', $strategy);
    }

    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderByDesc('captured_at');
    }
}

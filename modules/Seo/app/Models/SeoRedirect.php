<?php

namespace Modules\Seo\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SeoRedirect extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'seo_redirects';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'source_path',
        'target_path',
        'status_code',
        'hits_count',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status_code' => 'integer',
        'hits_count' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Normalize paths to lowercase before saving for case-insensitive matching
        static::saving(function ($redirect) {
            $redirect->source_path = strtolower(trim($redirect->source_path));

            // Ensure paths start with /
            if (! str_starts_with($redirect->source_path, '/')) {
                $redirect->source_path = '/'.$redirect->source_path;
            }

            if (! str_starts_with($redirect->target_path, '/') && ! filter_var($redirect->target_path, FILTER_VALIDATE_URL)) {
                $redirect->target_path = '/'.$redirect->target_path;
            }
        });
    }

    /**
     * Scope to filter only active redirects.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by hits count.
     */
    public function scopeByHits(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderBy('hits_count', $direction);
    }

    /**
     * Scope to search by source or target path.
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('source_path', 'like', "%{$search}%")
                ->orWhere('target_path', 'like', "%{$search}%");
        });
    }

    /**
     * Scope to filter by status code.
     */
    public function scopeWithStatusCode(Builder $query, int $statusCode): Builder
    {
        return $query->where('status_code', $statusCode);
    }

    /**
     * Find redirect by source path (case-insensitive).
     */
    public static function findBySourcePath(string $path): ?self
    {
        $normalizedPath = strtolower(trim($path));

        // Ensure path starts with /
        if (! str_starts_with($normalizedPath, '/')) {
            $normalizedPath = '/'.$normalizedPath;
        }

        return static::active()
            ->where('source_path', $normalizedPath)
            ->first();
    }

    /**
     * Increment the hits count.
     */
    public function incrementHits(): bool
    {
        return $this->increment('hits_count');
    }

    /**
     * Check if redirect is permanent (301).
     */
    public function isPermanent(): bool
    {
        return $this->status_code === 301;
    }

    /**
     * Check if redirect is temporary (302).
     */
    public function isTemporary(): bool
    {
        return $this->status_code === 302;
    }

    /**
     * Get status code label.
     */
    public function getStatusCodeLabelAttribute(): string
    {
        return match ($this->status_code) {
            301 => '301 - Permanent',
            302 => '302 - Temporary',
            default => (string) $this->status_code,
        };
    }
}

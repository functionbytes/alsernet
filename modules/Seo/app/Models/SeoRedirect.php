<?php

namespace Modules\Seo\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeoRedirect extends Model
{
    use HasFactory;

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
        'is_regex',
        'is_wildcard',
        'active_from',
        'active_until',
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
        'is_regex' => 'boolean',
        'is_wildcard' => 'boolean',
        'active_from' => 'datetime',
        'active_until' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Bootstrap the model.
     */
    protected static function booted(): void
    {
        static::saving(function ($redirect) {
            // Skip path normalization for regex and wildcard patterns — they have their own syntax
            if (! $redirect->is_regex && ! $redirect->is_wildcard) {
                $redirect->source_path = strtolower(trim($redirect->source_path));

                if (! str_starts_with($redirect->source_path, '/')) {
                    $redirect->source_path = '/'.$redirect->source_path;
                }
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
     * Scope to filter redirects that are currently active (respects date range).
     */
    public function scopeCurrentlyActive(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('active_from')->orWhere('active_from', '<=', now()))
            ->where(fn ($q) => $q->whereNull('active_until')->orWhere('active_until', '>=', now()));
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

        return static::currentlyActive()
            ->where('is_regex', false)
            ->where('source_path', $normalizedPath)
            ->first();
    }

    /**
     * All daily hit records for this redirect.
     */
    public function hits(): HasMany
    {
        return $this->hasMany(SeoRedirectHit::class);
    }

    /**
     * Daily hit records for the last 30 days.
     */
    public function hitsLast30Days(): HasMany
    {
        return $this->hasMany(SeoRedirectHit::class)
            ->where('hit_date', '>=', now()->subDays(30)->toDateString());
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
     * Generate a consistent cache key for a redirect path.
     */
    public static function cacheKey(string $path): string
    {
        return 'seo_redirect_'.md5(strtolower($path));
    }

    /**
     * Get status code label.
     */
    public function getStatusCodeLabelAttribute(): string
    {
        return match ($this->status_code) {
            301 => '301 - Permanente',
            302 => '302 - Temporal',
            307 => '307 - Temporal (preserva método)',
            308 => '308 - Permanente (preserva método)',
            default => (string) $this->status_code,
        };
    }
}

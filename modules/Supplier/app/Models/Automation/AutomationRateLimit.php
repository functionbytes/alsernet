<?php

namespace Modules\Supplier\Models\Automation;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Supplier\Database\Factories\Automation\AutomationRateLimitFactory;

class AutomationRateLimit extends Model
{
    use HasFactory;

    protected $table = 'supplier_automation_rate_limits';

    public $timestamps = false;

    protected $fillable = [
        'limitable_type',
        'limitable_id',
        'window_type',
        'max_requests',
        'current_count',
        'window_start',
        'blocked_until',
        'updated_at',
    ];

    protected $casts = [
        'max_requests' => 'integer',
        'current_count' => 'integer',
        'window_start' => 'datetime',
        'blocked_until' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function newFactory(): AutomationRateLimitFactory
    {
        return AutomationRateLimitFactory::new();
    }

    /**
     * Get the limitable model (polymorphic)
     */
    public function limitable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope: Filter by window type
     */
    public function scopeForWindow(Builder $query, string $windowType): Builder
    {
        return $query->where('window_type', $windowType);
    }

    /**
     * Scope: Get currently blocked limits
     */
    public function scopeBlocked(Builder $query): Builder
    {
        return $query->where('blocked_until', '>', now());
    }

    /**
     * Scope: Get active limits (not blocked)
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('blocked_until')
                ->orWhere('blocked_until', '<=', now());
        });
    }

    /**
     * Check if rate limit is currently blocked
     */
    public function isBlocked(): bool
    {
        return $this->blocked_until && $this->blocked_until->isFuture();
    }

    /**
     * Check if rate limit is exceeded
     */
    public function isExceeded(): bool
    {
        return $this->current_count >= $this->max_requests;
    }

    /**
     * Check if window has expired
     */
    public function isWindowExpired(): bool
    {
        $windowDuration = match ($this->window_type) {
            'minute' => 60,
            'hour' => 3600,
            'day' => 86400,
            default => 3600,
        };

        return $this->window_start->addSeconds($windowDuration)->isPast();
    }

    /**
     * Increment the rate limit counter
     */
    public function incrementCounter(): void
    {
        $this->increment('current_count');
        $this->updated_at = now();
        $this->save();

        // Check if limit exceeded and block if needed
        if ($this->isExceeded()) {
            $this->blockUntilNextWindow();
        }
    }

    /**
     * Reset the rate limit window
     */
    public function resetWindow(): void
    {
        $this->update([
            'current_count' => 0,
            'window_start' => now(),
            'blocked_until' => null,
            'updated_at' => now(),
        ]);
    }

    /**
     * Block until the next window
     */
    public function blockUntilNextWindow(): void
    {
        $windowDuration = match ($this->window_type) {
            'minute' => 60,
            'hour' => 3600,
            'day' => 86400,
            default => 3600,
        };

        $this->update([
            'blocked_until' => $this->window_start->addSeconds($windowDuration),
            'updated_at' => now(),
        ]);
    }

    /**
     * Get or create rate limit for a model
     */
    public static function forModel(Model $model, string $windowType, int $maxRequests): self
    {
        return self::firstOrCreate(
            [
                'limitable_type' => get_class($model),
                'limitable_id' => $model->id,
                'window_type' => $windowType,
            ],
            [
                'max_requests' => $maxRequests,
                'current_count' => 0,
                'window_start' => now(),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Check and increment rate limit
     */
    public static function checkAndIncrement(Model $model, string $windowType, int $maxRequests): bool
    {
        $rateLimit = self::forModel($model, $windowType, $maxRequests);

        // Check if blocked
        if ($rateLimit->isBlocked()) {
            return false;
        }

        // Reset window if expired
        if ($rateLimit->isWindowExpired()) {
            $rateLimit->resetWindow();
        }

        // Check if would exceed
        if ($rateLimit->current_count >= $maxRequests) {
            $rateLimit->blockUntilNextWindow();

            return false;
        }

        // Increment and allow
        $rateLimit->incrementCounter();

        return true;
    }
}

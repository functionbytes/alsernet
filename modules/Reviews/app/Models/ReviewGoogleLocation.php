<?php

namespace Modules\Reviews\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ReviewGoogleLocation extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'review_google_locations';

    protected $fillable = [
        'connection_id',
        'google_location_id',
        'google_account_id',
        'name',
        'address',
        'phone',
        'website_url',
        'average_rating',
        'total_reviews',
        'is_verified',
        'is_active',
        'metadata_json',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'average_rating' => 'decimal:2',
            'total_reviews' => 'integer',
            'is_verified' => 'boolean',
            'is_active' => 'boolean',
            'metadata_json' => 'array',
            'synced_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'is_active', 'average_rating', 'total_reviews'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(ReviewGoogleConnection::class, 'connection_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'location_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeNeedingSync($query)
    {
        return $query->active()
            ->where(function ($q) {
                $q->whereNull('synced_at')
                    ->orWhere('synced_at', '<', now()->subMinutes(config('reviews.general.sync_interval_minutes', 15)));
            });
    }

    public function markAsSynced(): void
    {
        $this->update(['synced_at' => now()]);
    }

    public function updateStats(float $averageRating, int $totalReviews): void
    {
        $this->update([
            'average_rating' => $averageRating,
            'total_reviews' => $totalReviews,
        ]);
    }
}

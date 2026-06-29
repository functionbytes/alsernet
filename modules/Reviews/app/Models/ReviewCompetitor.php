<?php

namespace Modules\Reviews\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ReviewCompetitor extends Model
{
    protected $table = 'review_competitors';

    protected $fillable = [
        'location_id',
        'name',
        'google_maps_url',
        'place_id',
        'last_checked_at',
    ];

    protected function casts(): array
    {
        return [
            'last_checked_at' => 'datetime',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(ReviewGoogleLocation::class, 'location_id');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(ReviewCompetitorSnapshot::class, 'competitor_id');
    }

    public function latestSnapshot(): HasOne
    {
        return $this->hasOne(ReviewCompetitorSnapshot::class, 'competitor_id')
            ->latestOfMany('captured_at');
    }
}

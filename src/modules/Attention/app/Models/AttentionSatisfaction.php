<?php

namespace Modules\Attention\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Encuesta de satisfacción para un peticiones resuelto
 */
class AttentionSatisfaction extends Model
{
    protected $table = 'attention_satisfaction';

    protected $fillable = [
        'attention_id',
        'rating',
        'comment',
        'submitted_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'submitted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Attention this satisfaction survey belongs to
     */
    public function attention(): BelongsTo
    {
        return $this->belongsTo(Attention::class, 'attention_id');
    }

    /**
     * Scope submitted surveys
     */
    public function scopeSubmitted($query)
    {
        return $query->whereNotNull('submitted_at');
    }

    /**
     * Scope by rating
     */
    public function scopeByRating($query, int $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Scope high ratings (4-5 stars)
     */
    public function scopeHighRatings($query)
    {
        return $query->whereIn('rating', [4, 5]);
    }

    /**
     * Scope low ratings (1-2 stars)
     */
    public function scopeLowRatings($query)
    {
        return $query->whereIn('rating', [1, 2]);
    }
}

<?php

namespace Modules\Reviews\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewWidgetToken extends Model
{
    protected $table = 'review_widget_tokens';

    protected $fillable = [
        'location_id',
        'token',
        'is_active',
        'allowed_origins',
        'max_reviews',
        'min_rating',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'allowed_origins' => 'array',
            'max_reviews' => 'integer',
            'min_rating' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ReviewWidgetToken $token): void {
            if (empty($token->token)) {
                $token->token = $token->generateToken();
            }
        });
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(ReviewGoogleLocation::class, 'location_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}

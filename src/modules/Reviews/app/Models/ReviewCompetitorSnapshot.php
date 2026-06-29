<?php

namespace Modules\Reviews\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewCompetitorSnapshot extends Model
{
    protected $table = 'review_competitor_snapshots';

    public $timestamps = false;

    protected $fillable = [
        'competitor_id',
        'avg_rating',
        'review_count',
        'captured_at',
    ];

    protected function casts(): array
    {
        return [
            'avg_rating' => 'decimal:2',
            'review_count' => 'integer',
            'captured_at' => 'datetime',
        ];
    }

    public function competitor(): BelongsTo
    {
        return $this->belongsTo(ReviewCompetitor::class, 'competitor_id');
    }
}

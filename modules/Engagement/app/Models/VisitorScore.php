<?php

namespace Modules\Engagement\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Engagement\Database\Factories\VisitorScoreFactory;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Inbox;

class VisitorScore extends Model
{
    use HasFactory;

    public const SEGMENT_COLD = 'cold';

    public const SEGMENT_WARM = 'warm';

    public const SEGMENT_HOT = 'hot';

    protected $connection = 'helpdesk';

    protected $table = 'engagement_visitor_scores';

    protected $fillable = [
        'session_token',
        'customer_id',
        'inbox_id',
        'score',
        'segment',
        'last_event_at',
        'last_recalc_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'last_event_at' => 'datetime',
            'last_recalc_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function inbox(): BelongsTo
    {
        return $this->belongsTo(Inbox::class, 'inbox_id');
    }

    public function scopeForSession(Builder $query, string $sessionToken): Builder
    {
        return $query->where('session_token', $sessionToken);
    }

    public function scopeOfSegment(Builder $query, string $segment): Builder
    {
        return $query->where('segment', $segment);
    }

    public function scopeHot(Builder $query): Builder
    {
        return $query->where('segment', self::SEGMENT_HOT);
    }

    public static function segmentFromScore(int $score): string
    {
        if ($score >= 60) {
            return self::SEGMENT_HOT;
        }

        if ($score >= 25) {
            return self::SEGMENT_WARM;
        }

        return self::SEGMENT_COLD;
    }

    protected static function newFactory(): VisitorScoreFactory
    {
        return VisitorScoreFactory::new();
    }
}

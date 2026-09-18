<?php

namespace Modules\HelpdeskSocial\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\HelpdeskSocial\Database\Factories\SocialMetricFactory;

class SocialMetrics extends Model
{
    use HasFactory;

    protected $table = 'helpdesk_social_metrics';

    protected $fillable = [
        'date',
        'social_account_id',
        'platform',
        'comments_received',
        'messages_received',
        'replies_sent',
        'auto_replies_sent',
        'manual_replies_sent',
        'escalated_count',
        'spam_detected',
        'avg_response_time_seconds',
        'first_response_time_seconds',
        'automation_rate',
        'intents_breakdown',
        'sentiment_breakdown',
        'hourly_distribution',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'comments_received' => 'integer',
            'messages_received' => 'integer',
            'replies_sent' => 'integer',
            'auto_replies_sent' => 'integer',
            'manual_replies_sent' => 'integer',
            'escalated_count' => 'integer',
            'spam_detected' => 'integer',
            'avg_response_time_seconds' => 'integer',
            'first_response_time_seconds' => 'integer',
            'automation_rate' => 'decimal:2',
            'intents_breakdown' => 'array',
            'sentiment_breakdown' => 'array',
            'hourly_distribution' => 'array',
        ];
    }

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class, 'social_account_id');
    }

    protected static function newFactory(): SocialMetricFactory
    {
        return SocialMetricFactory::new();
    }
}

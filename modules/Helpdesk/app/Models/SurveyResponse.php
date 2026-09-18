<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyResponse extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_survey_responses';

    protected $fillable = [
        'survey_id',
        'customer_id',
        'conversation_id',
        'answers',
        'survey_token',
        'sent_at',
        'answered_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'sent_at' => 'datetime',
            'answered_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class, 'survey_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    public function getIsAnsweredAttribute(): bool
    {
        return $this->answered_at !== null;
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}

<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CsatRating extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_csat_ratings';

    protected $fillable = [
        'conversation_id',
        'customer_id',
        'agent_id',
        'rating',
        'comment',
        'survey_token',
        'sent_at',
        'answered_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'answered_at' => 'datetime',
            'expires_at' => 'datetime',
            'rating' => 'integer',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}

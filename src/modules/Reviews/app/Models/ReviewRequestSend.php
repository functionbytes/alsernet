<?php

namespace Modules\Reviews\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewRequestSend extends Model
{
    protected $table = 'review_request_sends';

    protected $fillable = [
        'campaign_id',
        'customer_name',
        'customer_email',
        'sent_at',
        'opened_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'opened_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(ReviewRequestCampaign::class, 'campaign_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', 'sent');
    }
}

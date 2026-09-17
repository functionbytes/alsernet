<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Helpdesk\Models\Concerns\HasCrossDatabaseUserRelation;

class SupervisorReview extends Model
{
    use HasCrossDatabaseUserRelation;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_supervisor_reviews';

    protected $fillable = [
        'conversation_id',
        'requested_by',
        'review_type',
        'comment',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsToUser('requested_by', 'requestedBy');
    }
}

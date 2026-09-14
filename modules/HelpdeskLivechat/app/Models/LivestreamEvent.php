<?php

namespace Modules\HelpdeskLivechat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Helpdesk\Models\Conversation;

class LivestreamEvent extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_livestream_events';

    public const UPDATED_AT = null;

    protected $fillable = [
        'conversation_id',
        'batch_index',
        'events',
    ];

    protected function casts(): array
    {
        return [
            'events' => 'array',
            'batch_index' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}

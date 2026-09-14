<?php

namespace Modules\HelpdeskChatFlow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatFlowVersion extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_chat_flow_versions';

    protected $fillable = [
        'chat_flow_id',
        'name',
        'nodes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'nodes' => 'array',
        ];
    }

    public function chatFlow(): BelongsTo
    {
        return $this->belongsTo(ChatFlow::class, 'chat_flow_id');
    }
}

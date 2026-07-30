<?php

namespace Modules\HelpdeskChatFlow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatFlowTestCase extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_chat_flow_test_cases';

    protected $fillable = [
        'chat_flow_id',
        'name',
        'steps',
        'last_result',
        'last_run_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'steps' => 'array',
            'last_run_at' => 'datetime',
        ];
    }

    public function chatFlow(): BelongsTo
    {
        return $this->belongsTo(ChatFlow::class, 'chat_flow_id');
    }
}

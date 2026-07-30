<?php

namespace Modules\HelpdeskChatFlow\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatFlowExecution extends Model
{
    use HasFactory;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_chat_flow_executions';

    protected $fillable = [
        'session_id',
        'node_id',
        'node_type',
        'input',
        'output',
        'status',
        'duration_ms',
        'error_message',
        'executed_at',
    ];

    protected function casts(): array
    {
        return [
            'input' => 'array',
            'output' => 'array',
            'executed_at' => 'datetime',
        ];
    }

    // ==================== Relationships ====================

    public function session(): BelongsTo
    {
        return $this->belongsTo(ChatFlowSession::class, 'session_id');
    }
}

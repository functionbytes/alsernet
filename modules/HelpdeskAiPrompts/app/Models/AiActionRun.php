<?php

namespace Modules\HelpdeskAiPrompts\Models;

use Illuminate\Database\Eloquent\Model;

class AiActionRun extends Model
{
    public $timestamps = false;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ai_action_runs';

    protected $fillable = [
        'action_key',
        'source',
        'trace_id',
        'conversation_id',
        'user_id',
        'agent_verified',
        'status',
        'error',
        'latency_ms',
        'args_summary',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'conversation_id' => 'integer',
            'user_id' => 'integer',
            'agent_verified' => 'boolean',
            'latency_ms' => 'integer',
            'args_summary' => 'array',
            'created_at' => 'datetime',
        ];
    }
}

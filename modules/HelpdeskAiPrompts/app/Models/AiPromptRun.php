<?php

namespace Modules\HelpdeskAiPrompts\Models;

use Illuminate\Database\Eloquent\Model;

class AiPromptRun extends Model
{
    public $timestamps = false;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ai_prompt_runs';

    protected $fillable = [
        'trace_id',
        'conversation_id',
        'item_id',
        'case_key',
        'routed_by',
        'action',
        'used_tools',
        'latency_ms',
        'feedback',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'conversation_id' => 'integer',
            'item_id' => 'integer',
            'used_tools' => 'array',
            'latency_ms' => 'integer',
            'feedback' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}

<?php

namespace Modules\HelpdeskAgents\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiToolExecution extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ai_tool_executions';

    public $timestamps = false;

    protected $fillable = [
        'tool_id',
        'session_id',
        'arguments',
        'result',
        'success',
        'error_message',
        'duration_ms',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'arguments' => 'array',
            'result' => 'array',
            'success' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    // ==================== Relationships ====================

    public function tool(): BelongsTo
    {
        return $this->belongsTo(AiAgentTool::class, 'tool_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AiAgentSession::class, 'session_id');
    }

    // ==================== Scopes ====================

    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('success', true);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('success', false);
    }
}

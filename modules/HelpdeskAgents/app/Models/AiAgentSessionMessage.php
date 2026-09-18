<?php

namespace Modules\HelpdeskAgents\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAgentSessionMessage extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ai_agent_session_messages';

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected $fillable = [
        'session_id',
        'role',
        'content',
        'node_id',
        'metadata',
    ];

    public $timestamps = false;

    // ==================== Relationships ====================

    public function session(): BelongsTo
    {
        return $this->belongsTo(AiAgentSession::class, 'session_id');
    }

    // ==================== Scopes ====================

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    // ==================== Accessors ====================

    protected function roleLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->role) {
                'user' => 'Usuario',
                'assistant' => 'Asistente',
                'system' => 'Sistema',
                default => $this->role,
            },
        );
    }
}

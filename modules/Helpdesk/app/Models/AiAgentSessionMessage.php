<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAgentSessionMessage extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ai_agent_session_messages';

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    protected $fillable = [
        'session_id',
        'role', // 'user', 'assistant', 'system'
        'content',
        'metadata',
    ];

    public $timestamps = false;

    // ==================== Relationships ====================

    /**
     * The session this message belongs to
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(AiAgentSession::class, 'session_id');
    }

    // ==================== Scopes ====================

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    // ==================== Accessors ====================

    /**
     * Get role label
     */
    public function getRoleLabelAttribute()
    {
        return match ($this->role) {
            'user' => 'Usuario',
            'assistant' => 'Asistente',
            'system' => 'Sistema',
            default => $this->role,
        };
    }
}

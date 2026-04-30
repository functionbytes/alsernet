<?php

namespace Modules\Chat\Models\Ai;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAgentSessionMessage extends Model
{
    protected $table = 'chat_ai_agent_session_messages';

    protected $casts = [
        'metadata' => 'array',
        'tool_calls' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'session_id',
        'role',
        'content',
        'metadata',
        'tool_calls',
        'token_count',
    ];

    // ==================== Relationships ====================

    /**
     * The session this message belongs to
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(AiAgentSession::class, 'session_id');
    }

    // ==================== Scopes ====================

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function scopeUserMessages($query)
    {
        return $query->where('role', 'user');
    }

    public function scopeAssistantMessages($query)
    {
        return $query->where('role', 'assistant');
    }

    public function scopeSystemMessages($query)
    {
        return $query->where('role', 'system');
    }

    public function scopeWithToolCalls($query)
    {
        return $query->whereNotNull('tool_calls');
    }

    // ==================== Accessors ====================

    /**
     * Get role label
     */
    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            'user' => 'Usuario',
            'assistant' => 'Asistente',
            'system' => 'Sistema',
            'tool' => 'Herramienta',
            default => $this->role,
        };
    }

    /**
     * Get role color for UI
     */
    public function getRoleColorAttribute(): string
    {
        return match ($this->role) {
            'user' => 'primary',
            'assistant' => 'success',
            'system' => 'secondary',
            'tool' => 'info',
            default => 'light',
        };
    }

    /**
     * Check if message has tool calls
     */
    public function getHasToolCallsAttribute(): bool
    {
        return ! empty($this->tool_calls);
    }

    // ==================== Methods ====================

    /**
     * Get metadata value
     */
    public function getMetadataValue(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Set metadata value
     */
    public function setMetadataValue(string $key, $value): void
    {
        $metadata = $this->metadata ?? [];
        $metadata[$key] = $value;
        $this->update(['metadata' => $metadata]);
    }
}

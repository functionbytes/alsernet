<?php

namespace Modules\Chat\Models\Ai;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAgentTool extends Model
{
    protected $table = 'chat_ai_agent_tools';

    protected $casts = [
        'parameters' => 'array',
        'auth_config' => 'array',
        'requires_approval' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'ai_agent_id',
        'name',
        'description',
        'type',
        'parameters',
        'implementation',
        'auth_config',
        'requires_approval',
        'is_active',
    ];

    // ==================== Relationships ====================

    /**
     * The AI agent this tool belongs to
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(AiAgent::class, 'ai_agent_id');
    }

    // ==================== Scopes ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRequiringApproval($query)
    {
        return $query->where('requires_approval', true);
    }

    // ==================== Accessors ====================

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'function' => 'Función',
            'api' => 'API',
            'database' => 'Base de datos',
            'custom' => 'Personalizada',
            default => $this->type,
        };
    }

    /**
     * Get icon based on type
     */
    public function getIconAttribute(): string
    {
        return match ($this->type) {
            'function' => 'fa-code',
            'api' => 'fa-cloud',
            'database' => 'fa-database',
            'custom' => 'fa-wrench',
            default => 'fa-tools',
        };
    }
}

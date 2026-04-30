<?php

namespace Modules\Chat\Models\Ai;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAgentTag extends Model
{
    protected $table = 'chat_ai_agent_tags';

    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'ai_agent_id',
        'name',
        'description',
        'color',
        'icon',
        'system_prompt_addition',
        'priority',
        'is_active',
    ];

    // ==================== Relationships ====================

    /**
     * The AI agent this tag belongs to
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

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    // ==================== Accessors ====================

    /**
     * Get color with # prefix if missing
     */
    public function getColorAttribute($value): string
    {
        if (! $value) {
            return '#000000';
        }

        return str_starts_with($value, '#') ? $value : '#'.$value;
    }
}

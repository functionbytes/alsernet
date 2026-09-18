<?php

namespace Modules\HelpdeskAgents\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Helpdesk\Models\Conversation;

class AiAgentTag extends Model
{
    use SoftDeletes;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ai_agent_tags';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'metadata' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    protected $fillable = [
        'name',
        'description',
        'color',
        'icon',
        'system_prompt_addition',
        'priority',
        'is_active',
        'metadata',
    ];

    // ==================== Relationships ====================

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(
            Conversation::class,
            'helpdesk_conversation_tag',
            'tag_id',
            'conversation_id'
        )->withPivot('tagged_at');
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

    protected function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->is_active ? 'Activo' : 'Inactivo',
        );
    }
}

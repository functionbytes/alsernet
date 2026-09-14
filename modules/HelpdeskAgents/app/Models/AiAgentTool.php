<?php

namespace Modules\HelpdeskAgents\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiAgentTool extends Model
{
    use SoftDeletes;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ai_agent_tools';

    protected function casts(): array
    {
        return [
            'parameters' => 'array',
            'auth_config' => 'array',
            'requires_approval' => 'boolean',
            'is_active' => 'boolean',
            'metadata' => 'array',
            'last_used_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    protected $fillable = [
        'ai_agent_id',
        'name',
        'description',
        'type',
        'parameters',
        'implementation',
        'auth_config',
        'requires_approval',
        'usage_count',
        'last_used_at',
        'is_active',
        'metadata',
    ];

    // ==================== Relationships ====================

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

    public function scopeRequiresApproval($query)
    {
        return $query->where('requires_approval', true);
    }

    // ==================== Accessors ====================

    protected function typeLabel(): Attribute
    {
        return Attribute::make(
            get: fn (): string => match ($this->type) {
                'function' => 'Función',
                'api' => 'API Externa',
                'database' => 'Consulta BD',
                'custom' => 'Personalizado',
                default => $this->type,
            },
        );
    }

    // ==================== Methods ====================

    public function activate(): static
    {
        $this->update(['is_active' => true]);

        return $this;
    }

    public function deactivate(): static
    {
        $this->update(['is_active' => false]);

        return $this;
    }

    public function recordUsage(): static
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);

        return $this;
    }

    public function getFunctionDefinition(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'parameters' => $this->parameters ?? ['type' => 'object', 'properties' => []],
        ];
    }
}

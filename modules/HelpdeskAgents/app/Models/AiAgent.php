<?php

namespace Modules\HelpdeskAgents\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\HelpdeskAgents\Database\Factories\AiAgentFactory;

class AiAgent extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory(): AiAgentFactory
    {
        return new AiAgentFactory;
    }

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ai_agents';

    protected function casts(): array
    {
        return [
            'backups' => 'array',
            'parameters' => 'array',
            'metadata' => 'array',
            'is_default' => 'boolean',
            'api_key_encrypted' => 'encrypted',
            'enabled_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    protected $fillable = [
        'name',
        'description',
        'provider',
        'model',
        'personality',
        'system_prompt',
        'status',
        'backups',
        'parameters',
        'is_default',
        'metadata',
        'api_key_encrypted',
        'enabled_at',
    ];

    // ==================== Relationships ====================

    public function flows(): HasMany
    {
        return $this->hasMany(AiAgentFlow::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(AiAgentSession::class);
    }

    public function tools(): HasMany
    {
        return $this->hasMany(AiAgentTool::class);
    }

    public function knowledgeBase(): HasMany
    {
        return $this->hasMany(AiAgentKnowledgeBase::class);
    }

    // ==================== Scopes ====================

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->whereNotNull('enabled_at');
    }

    public function scopeByProvider($query, $provider)
    {
        return $query->where('provider', $provider);
    }

    public function scopeByModel($query, $model)
    {
        return $query->where('model', $model);
    }

    // ==================== Accessors ====================

    protected function isActive(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->status === 'active' && ! is_null($this->enabled_at),
        );
    }

    protected function providerLabel(): Attribute
    {
        return Attribute::make(
            get: fn (): string => match ($this->provider) {
                'openai' => 'OpenAI',
                'anthropic' => 'Anthropic (Claude)',
                'gemini' => 'Google Gemini',
                'local' => 'Local Model',
                default => ucfirst($this->provider),
            },
        );
    }

    protected function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn (): string => match ($this->status) {
                'inactive' => 'Inactivo',
                'active' => 'Activo',
                'paused' => 'Pausado',
                default => $this->status,
            },
        );
    }

    protected function statusColor(): Attribute
    {
        return Attribute::make(
            get: fn (): string => match ($this->status) {
                'inactive' => 'secondary',
                'active' => 'success',
                'paused' => 'warning',
                default => 'light',
            },
        );
    }

    // ==================== Methods ====================

    public function activate(): static
    {
        $this->update([
            'status' => 'active',
            'enabled_at' => now(),
        ]);

        return $this;
    }

    public function deactivate(): static
    {
        $this->update(['status' => 'inactive']);

        return $this;
    }

    public function pause(): static
    {
        if ($this->is_active) {
            $this->update(['status' => 'paused']);
        }

        return $this;
    }

    public function resume(): static
    {
        if ($this->status === 'paused') {
            $this->update(['status' => 'active']);
        }

        return $this;
    }

    /**
     * Get the API key for this agent.
     *
     * Reads from the encrypted column first; falls back to the legacy
     * plaintext backups entry so existing data works before the data
     * migration runs.
     */
    public function getApiKey(): ?string
    {
        return $this->api_key_encrypted
            ?? $this->parameters['api_key']
            ?? $this->backups['api_key']
            ?? null;
    }

    public function setApiKey(string $key): void
    {
        $this->api_key_encrypted = $key;
        $this->save();
    }

    public function getModelConfig(): array
    {
        $params = $this->parameters ?? $this->backups ?? [];

        return [
            'provider' => $this->provider,
            'model' => $this->model,
            'temperature' => $params['temperature'] ?? 0.7,
            'max_tokens' => $params['max_tokens'] ?? 2000,
            'top_p' => $params['top_p'] ?? 1,
        ];
    }

    public function getTotalSessionsAttribute(): int
    {
        return $this->sessions()->count();
    }

    public function getActiveSessionsAttribute(): int
    {
        $counts = $this->sessions()
            ->selectRaw('COUNT(*) as total, SUM(status = ?) as active', ['active'])
            ->first();

        return (int) ($counts->active ?? 0);
    }
}

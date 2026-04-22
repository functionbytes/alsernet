<?php

namespace Modules\HelpdeskAgents\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiAgent extends Model
{
    use SoftDeletes;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ai_agents';

    protected function casts(): array
    {
        return [
            'backups' => 'array',
            'metadata' => 'array',
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
        'provider', // 'openai', 'anthropic', 'gemini', 'local'
        'model', // 'gpt-4o', 'claude-3-opus', 'gemini-pro', etc.
        'personality', // System prompt / personality description
        'status', // 'inactive', 'active', 'paused'
        'backups', // JSON: temperature, max_tokens, etc. (api_key removed after migration)
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
        return $this->api_key_encrypted ?? $this->backups['api_key'] ?? null;
    }

    public function setApiKey(string $key): void
    {
        $this->api_key_encrypted = $key;
        $this->save();
    }

    public function getModelConfig(): array
    {
        return [
            'provider' => $this->provider,
            'model' => $this->model,
            'temperature' => $this->backups['temperature'] ?? 0.7,
            'max_tokens' => $this->backups['max_tokens'] ?? 2000,
            'top_p' => $this->backups['top_p'] ?? 1,
        ];
    }

    public function getTotalSessionsAttribute(): int
    {
        return $this->sessions()->count();
    }

    public function getActiveSessionsAttribute(): int
    {
        return $this->sessions()->where('status', 'active')->count();
    }
}

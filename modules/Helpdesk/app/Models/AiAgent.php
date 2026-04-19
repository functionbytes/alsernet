<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiAgent extends Model
{
    use SoftDeletes;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ai_agents';

    protected $casts = [
        'backups' => 'array',
        'metadata' => 'array',
        'api_key_encrypted' => 'encrypted',
        'enabled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

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

    /**
     * Conversation flows for this agent
     */
    public function flows(): HasMany
    {
        return $this->hasMany(AiAgentFlow::class);
    }

    /**
     * Agent sessions/conversations
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(AiAgentSession::class);
    }

    /**
     * Tools/functions available to this agent
     */
    public function tools(): HasMany
    {
        return $this->hasMany(AiAgentTool::class);
    }

    /**
     * Knowledge base entries for this agent
     */
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

    /**
     * Check if agent is currently active
     */
    public function getIsActiveAttribute()
    {
        return $this->status === 'active' && ! is_null($this->enabled_at);
    }

    /**
     * Get provider label
     */
    public function getProviderLabelAttribute()
    {
        return match ($this->provider) {
            'openai' => 'OpenAI',
            'anthropic' => 'Anthropic (Claude)',
            'gemini' => 'Google Gemini',
            'local' => 'Local Model',
            default => ucfirst($this->provider),
        };
    }

    /**
     * Get status label in Spanish
     */
    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            'inactive' => 'Inactivo',
            'active' => 'Activo',
            'paused' => 'Pausado',
            default => $this->status,
        };
    }

    /**
     * Get status color for Bootstrap badges
     */
    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            'inactive' => 'secondary',
            'active' => 'success',
            'paused' => 'warning',
            default => 'light',
        };
    }

    // ==================== Methods ====================

    /**
     * Activate the agent
     */
    public function activate()
    {
        $this->update([
            'status' => 'active',
            'enabled_at' => now(),
        ]);

        return $this;
    }

    /**
     * Deactivate the agent
     */
    public function deactivate()
    {
        $this->update(['status' => 'inactive']);

        return $this;
    }

    /**
     * Pause the agent
     */
    public function pause()
    {
        if ($this->is_active) {
            $this->update(['status' => 'paused']);
        }

        return $this;
    }

    /**
     * Resume the agent
     */
    public function resume()
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

    /**
     * Persist the API key using the encrypted column.
     *
     * Use this instead of writing to backups['api_key'] directly.
     */
    public function setApiKey(string $key): void
    {
        $this->api_key_encrypted = $key;
        $this->save();
    }

    /**
     * Get model configuration
     */
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

    /**
     * Count total sessions
     */
    public function getTotalSessionsAttribute()
    {
        return $this->sessions()->count();
    }

    /**
     * Count active sessions
     */
    public function getActiveSessionsAttribute()
    {
        return $this->sessions()->where('status', 'active')->count();
    }
}

<?php

namespace Modules\HelpdeskChat\Models\Ai;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiAgent extends Model
{
    use SoftDeletes;

    protected $table = 'helpdesk_ai_agents';

    protected $guarded = [];

    protected $casts = [
        'settings' => 'array',
        'metadata' => 'array',
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
        'settings', // JSON: API keys, temperature, max_tokens, etc.
        'metadata',
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
     * Get API key from settings
     */
    public function getApiKey()
    {
        return $this->settings['api_key'] ?? null;
    }

    /**
     * Get model configuration
     */
    public function getModelConfig()
    {
        return [
            'provider' => $this->provider,
            'model' => $this->model,
            'temperature' => $this->settings['temperature'] ?? 0.7,
            'max_tokens' => $this->settings['max_tokens'] ?? 2000,
            'top_p' => $this->settings['top_p'] ?? 1,
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

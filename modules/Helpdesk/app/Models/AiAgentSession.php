<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiAgentSession extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ai_agent_sessions';

    protected $guarded = [];

    protected $casts = [
        'context' => 'array', // Session context/variables
        'metadata' => 'array',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'ai_agent_id',
        'conversation_id',
        'customer_id',
        'status', // 'active', 'completed', 'failed', 'paused'
        'context',
        'metadata',
        'started_at',
        'ended_at',
    ];

    // ==================== Relationships ====================

    /**
     * The AI agent for this session
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(AiAgent::class, 'ai_agent_id');
    }

    /**
     * The conversation this session belongs to
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * The customer initiating the session
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Messages in this session
     */
    public function messages(): HasMany
    {
        return $this->hasMany(AiAgentSessionMessage::class, 'session_id');
    }

    // ==================== Scopes ====================

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ==================== Accessors ====================

    /**
     * Get status label
     */
    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            'active' => 'Activo',
            'completed' => 'Completado',
            'failed' => 'Falló',
            'paused' => 'Pausado',
            default => $this->status,
        };
    }

    /**
     * Get duration in seconds
     */
    public function getDurationAttribute()
    {
        $start = $this->started_at ?? $this->created_at;
        $end = $this->ended_at ?? now();

        return $end->diffInSeconds($start);
    }

    /**
     * Get message count
     */
    public function getMessageCountAttribute()
    {
        return $this->messages()->count();
    }

    // ==================== Methods ====================

    /**
     * Complete the session
     */
    public function complete()
    {
        $this->update([
            'status' => 'completed',
            'ended_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark session as failed
     */
    public function fail($error = null)
    {
        $this->update([
            'status' => 'failed',
            'ended_at' => now(),
            'metadata' => array_merge($this->metadata ?? [], ['error' => $error]),
        ]);

        return $this;
    }

    /**
     * Get context variable
     */
    public function getContextValue($key, $default = null)
    {
        return $this->context[$key] ?? $default;
    }

    /**
     * Set context variable
     */
    public function setContextValue($key, $value)
    {
        $context = $this->context ?? [];
        $context[$key] = $value;
        $this->update(['context' => $context]);

        return $this;
    }
}
